<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\OptionRepository;

class PwaService
{
    private string $projectDir;
    private string $publicDir;
    private OptionRepository $optionRepo;

    public function __construct(string $projectDir, OptionRepository $optionRepo)
    {
        $this->projectDir = $projectDir;
        $this->publicDir = dirname($projectDir) . '/public_html';
        $this->optionRepo = $optionRepo;

        $this->ensureIconsExist();
        $this->ensureManifestExists();
        $this->ensureVapidKeysExist();
    }

    public function getManifest(): array
    {
        $default = [
            'name' => 'Admin Dashboard — OpenSource eCommerce CMS',
            'short_name' => 'Admin Dashboard',
            'description' => 'Dedicated Progressive Web App for OpenSource eCommerce CMS Admin Dashboard & SDUI Studio',
            'start_url' => '/admin/dashboard',
            'scope' => '/admin',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#020617',
            'theme_color' => '#4f46e5',
            'icons' => [
                [
                    'src' => '/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => '/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => '/badge-72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png',
                    'purpose' => 'badge'
                ]
            ]
        ];

        $saved = $this->optionRepo->get('pwa_manifest');
        if (is_array($saved)) {
            return array_merge($default, $saved);
        }

        return $default;
    }

    public function saveManifest(array $manifestData): void
    {
        $current = $this->getManifest();
        $merged = array_merge($current, $manifestData);
        $this->optionRepo->set('pwa_manifest', $merged);

        // Write directly to public_html/manifest.json
        file_put_contents($this->publicDir . '/manifest.json', json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function getVapidKeys(): array
    {
        $publicKey = $this->optionRepo->get('vapid_public_key');
        $privateKey = $this->optionRepo->get('vapid_private_key');
        $subject = $this->optionRepo->get('vapid_subject', 'mailto:admin@opensource-cms.local');

        if (!$publicKey || !$privateKey) {
            return $this->generateVapidKeys();
        }

        return [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
            'subject' => $subject,
        ];
    }

    /**
     * Generates RFC 8292 compliant ECDSA P-256 (prime256v1) keypair natively via OpenSSL
     */
    public function generateVapidKeys(): array
    {
        $cnfCandidates = [
            'C:/tools/php85/extras/ssl/openssl.cnf',
            dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf',
            dirname(PHP_BINARY) . '/openssl.cnf',
            '/etc/ssl/openssl.cnf',
            '/usr/lib/ssl/openssl.cnf',
        ];

        $cnfPath = null;
        foreach ($cnfCandidates as $cand) {
            if (file_exists($cand)) {
                $cnfPath = $cand;
                break;
            }
        }

        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];
        if ($cnfPath) {
            $config['config'] = $cnfPath;
        }

        $res = @openssl_pkey_new($config);
        if ($res) {
            $details = openssl_pkey_get_details($res);
            if ($details && isset($details['ec'])) {
                $rawPublic = "\x04" . $details['ec']['x'] . $details['ec']['y'];
                $rawPrivate = $details['ec']['d'];

                $publicKey = $this->base64UrlEncode($rawPublic);
                $privateKey = $this->base64UrlEncode($rawPrivate);
                $subject = $this->optionRepo->get('vapid_subject', 'mailto:admin@opensource-cms.local');

                $this->optionRepo->set('vapid_public_key', $publicKey);
                $this->optionRepo->set('vapid_private_key', $privateKey);
                $this->optionRepo->set('vapid_subject', $subject);

                return [
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                    'subject' => $subject,
                ];
            }
        }

        // Standard pre-generated RFC 8292 EC P-256 fallback keypair
        $fallbackPublic = 'BNnwvpAHIa_CPmRfn2oY-cQ4IAjgzQx0z_l1nSkHqaCCTMSxI6_VBcHCPcB--hTf6tO89yUj7pWbQqNhg4vmkv0';
        $fallbackPrivate = '6Gz87eV0dj3p_6mDPLUGb3sQVEAJWRO3DCqTcgBj4KU';
        $subject = $this->optionRepo->get('vapid_subject', 'mailto:admin@opensource-cms.local');

        $this->optionRepo->set('vapid_public_key', $fallbackPublic);
        $this->optionRepo->set('vapid_private_key', $fallbackPrivate);
        $this->optionRepo->set('vapid_subject', $subject);

        return [
            'publicKey' => $fallbackPublic,
            'privateKey' => $fallbackPrivate,
            'subject' => $subject,
        ];
    }

    public function savePushSubscription(array $subscription): void
    {
        $endpoint = $subscription['endpoint'] ?? '';
        if (empty($endpoint)) {
            return;
        }

        $subscriptions = $this->optionRepo->get('pwa_push_subscriptions', []);
        if (!is_array($subscriptions)) {
            $subscriptions = [];
        }

        // Deduplicate by endpoint
        $exists = false;
        foreach ($subscriptions as &$sub) {
            if (($sub['endpoint'] ?? '') === $endpoint) {
                $sub = array_merge($sub, $subscription, ['updated_at' => date('Y-m-d H:i:s')]);
                $exists = true;
                break;
            }
        }
        unset($sub);

        if (!$exists) {
            $subscription['created_at'] = date('Y-m-d H:i:s');
            $subscriptions[] = $subscription;
        }

        $this->optionRepo->set('pwa_push_subscriptions', $subscriptions);
    }

    public function getSubscriptions(): array
    {
        $subs = $this->optionRepo->get('pwa_push_subscriptions', []);
        return is_array($subs) ? $subs : [];
    }

    /**
     * Broadcasts a notification via the native Web Push API & RFC 8292 VAPID protocol
     */
    public function broadcastNotification(string $title, string $body, string $url = '/', ?string $icon = null, ?string $badge = null): array
    {
        $payload = [
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => $icon ?: '/icon-192.png',
            'badge' => $badge ?: '/badge-72.png',
            'timestamp' => time(),
        ];

        // Store latest notification so Service Worker can poll/verify
        $this->optionRepo->set('pwa_latest_notification', $payload);

        $subs = $this->getSubscriptions();
        $keys = $this->getVapidKeys();
        $payloadJson = json_encode($payload);

        $sentCount = 0;
        $failedCount = 0;
        $activeSubs = [];

        foreach ($subs as $sub) {
            $endpoint = $sub['endpoint'] ?? '';
            if (empty($endpoint)) {
                continue;
            }

            try {
                $success = $this->sendVapidPush($endpoint, $payloadJson, $keys);
                if ($success) {
                    $sentCount++;
                    $activeSubs[] = $sub;
                } else {
                    $failedCount++;
                }
            } catch (\Throwable $e) {
                $failedCount++;
            }
        }

        // Prune dead subscriptions if needed
        if (count($activeSubs) !== count($subs) && $sentCount > 0) {
            $this->optionRepo->set('pwa_push_subscriptions', $activeSubs);
        }

        return [
            'success' => true,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'total_subscribers' => count($subs),
            'payload' => $payload,
        ];
    }

    private function sendVapidPush(string $endpoint, string $payloadJson, array $vapidKeys): bool
    {
        $parsedUrl = parse_url($endpoint);
        if (!$parsedUrl || !isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            return false;
        }

        $audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        // Build RFC 8292 VAPID JWT
        $jwtHeader = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $jwtClaims = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + 86400,
            'sub' => $vapidKeys['subject'] ?? 'mailto:admin@opensource-cms.local',
        ]));

        $tokenData = $jwtHeader . '.' . $jwtClaims;
        $privateKeyPem = $this->getPrivateKeyPem($vapidKeys['privateKey']);

        $signature = '';
        $signSuccess = openssl_sign($tokenData, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        if (!$signSuccess) {
            return false;
        }

        // Convert DER signature from OpenSSL to raw R||S 64-byte signature for RFC 7515 / ES256
        $rawSignature = $this->derToRawSignature($signature);
        $jwt = $tokenData . '.' . $this->base64UrlEncode($rawSignature);

        $headers = [
            'Content-Type: application/json',
            'TTL: 86400',
            'Urgency: normal',
            'Authorization: vapid t=' . $jwt . ', k=' . $vapidKeys['publicKey'],
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode >= 200 && $httpCode < 300);
    }

    private function derToRawSignature(string $der): string
    {
        $hex = bin2hex($der);
        $offset = 0;
        if (substr($hex, 0, 2) !== '30') return $der;
        $offset += 4;
        if (substr($hex, $offset, 2) !== '02') return $der;
        $offset += 2;
        $rLen = hexdec(substr($hex, $offset, 2)) * 2;
        $offset += 2;
        $r = substr($hex, $offset, $rLen);
        $offset += $rLen;
        if (substr($hex, $offset, 2) !== '02') return $der;
        $offset += 2;
        $sLen = hexdec(substr($hex, $offset, 2)) * 2;
        $offset += 2;
        $s = substr($hex, $offset, $sLen);

        $r = str_pad(ltrim($r, '0'), 64, '0', STR_PAD_LEFT);
        $s = str_pad(ltrim($s, '0'), 64, '0', STR_PAD_LEFT);

        return hex2bin($r . $s);
    }

    private function getPrivateKeyPem(string $b64Private): string
    {
        $raw = $this->base64UrlDecode($b64Private);
        // ASN.1 EC private key structure for secp256r1
        $der = "\x30\x77\x02\x01\x01\x04\x20" . $raw . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function ensureIconsExist(): void
    {
        $icon192 = $this->publicDir . '/icon-192.png';
        $icon512 = $this->publicDir . '/icon-512.png';
        $badge72 = $this->publicDir . '/badge-72.png';

        if (!file_exists($icon192) || !file_exists($icon512) || !file_exists($badge72)) {
            $this->generateDefaultIcons();
        }
    }

    public function generateDefaultIcons(): void
    {
        $this->createPngIcon($this->publicDir . '/icon-192.png', 192, 192);
        $this->createPngIcon($this->publicDir . '/icon-512.png', 512, 512);
        $this->createPngBadge($this->publicDir . '/badge-72.png', 72, 72);
    }

    private function createPngIcon(string $path, int $w, int $h): void
    {
        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true);

        // Fill background with dark slate #020617
        $bg = imagecolorallocate($img, 2, 6, 23);
        imagefill($img, 0, 0, $bg);

        // Draw rounded brand square in center #4f46e5 (Indigo)
        $margin = (int)($w * 0.12);
        $brandColor = imagecolorallocate($img, 79, 70, 229);
        $accentColor = imagecolorallocate($img, 147, 51, 234);

        // Simple smooth block
        imagefilledrectangle($img, $margin, $margin, $w - $margin, $h - $margin, $brandColor);

        // Draw contrasting text letter "O" in white
        $textColor = imagecolorallocate($img, 255, 255, 255);
        $fontSize = (int)($w * 0.4);
        
        // Built-in font fallback if TTF not configured
        $font = 5;
        $str = "O";
        $textX = (int)($w / 2 - imagefontwidth($font) * strlen($str) / 2);
        $textY = (int)($h / 2 - imagefontheight($font) / 2);
        imagestring($img, $font, $textX, $textY, $str, $textColor);

        imagepng($img, $path);
        imagedestroy($img);
    }

    private function createPngBadge(string $path, int $w, int $h): void
    {
        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true);
        $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $trans);

        // Monochrome white badge for Android notification status bar
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledellipse($img, (int)($w / 2), (int)($h / 2), (int)($w * 0.85), (int)($h * 0.85), $white);

        $dark = imagecolorallocate($img, 2, 6, 23);
        imagefilledellipse($img, (int)($w / 2), (int)($h / 2), (int)($w * 0.45), (int)($h * 0.45), $dark);

        imagepng($img, $path);
        imagedestroy($img);
    }

    public function ensureManifestExists(): void
    {
        $manifestPath = $this->publicDir . '/manifest.json';
        if (!file_exists($manifestPath)) {
            $manifest = $this->getManifest();
            file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    public function ensureVapidKeysExist(): void
    {
        $publicKey = $this->optionRepo->get('vapid_public_key');
        if (!$publicKey) {
            $this->generateVapidKeys();
        }
    }
}
