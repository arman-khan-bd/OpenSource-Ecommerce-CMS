<?php

declare(strict_types=1);

namespace App\Entity;

class Option
{
    private ?int $id = null;
    private string $optionName = '';
    private mixed $optionValue = null;
    private bool $autoload = true;

    public static function fromArray(array $data): self
    {
        $opt = new self();
        $opt->id = isset($data['id']) ? (int)$data['id'] : null;
        $opt->optionName = $data['option_name'] ?? '';
        $val = $data['option_value'] ?? null;
        // Auto decode json if array/object format
        if (is_string($val) && (str_starts_with($val, '{') || str_starts_with($val, '['))) {
            $decoded = json_decode($val, true);
            $opt->optionValue = $decoded !== null ? $decoded : $val;
        } else {
            $opt->optionValue = $val;
        }
        $opt->autoload = (bool)($data['autoload'] ?? true);
        return $opt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'option_name' => $this->optionName,
            'option_value' => $this->optionValue,
            'autoload' => $this->autoload,
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function getOptionName(): string { return $this->optionName; }
    public function setOptionName(string $val): self { $this->optionName = $val; return $this; }
    public function getOptionValue(): mixed { return $this->optionValue; }
    public function setOptionValue(mixed $val): self { $this->optionValue = $val; return $this; }
    public function isAutoload(): bool { return $this->autoload; }
    public function setAutoload(bool $val): self { $this->autoload = $val; return $this; }
}
