<?php

declare(strict_types=1);

namespace App\Entity;

class Order
{
    private ?int $id = null;
    private string $orderNumber = '';
    private string $customerName = '';
    private string $customerEmail = '';
    private float $totalAmount = 0.0;
    private string $status = 'pending'; // pending, processing, completed, cancelled
    private array $items = [];
    private ?array $shippingAddress = null;
    private ?string $notes = null;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $order = new self();
        $order->id = isset($data['id']) ? (int)$data['id'] : null;
        $order->orderNumber = $data['order_number'] ?? ('ORD-' . strtoupper(bin2hex(random_bytes(4))));
        $order->customerName = $data['customer_name'] ?? '';
        $order->customerEmail = $data['customer_email'] ?? '';
        $order->totalAmount = (float)($data['total_amount'] ?? 0.0);
        $order->status = $data['status'] ?? 'pending';
        $order->items = is_string($data['items'] ?? null) ? (json_decode($data['items'], true) ?: []) : ($data['items'] ?? []);
        $order->shippingAddress = is_string($data['shipping_address'] ?? null) ? (json_decode($data['shipping_address'], true) ?: null) : ($data['shipping_address'] ?? null);
        $order->notes = $data['notes'] ?? null;
        $order->createdAt = $data['created_at'] ?? null;
        $order->updatedAt = $data['updated_at'] ?? null;
        return $order;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'items' => $this->items,
            'shipping_address' => $this->shippingAddress,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function getOrderNumber(): string { return $this->orderNumber; }
    public function setOrderNumber(string $val): self { $this->orderNumber = $val; return $this; }
    public function getCustomerName(): string { return $this->customerName; }
    public function setCustomerName(string $val): self { $this->customerName = $val; return $this; }
    public function getCustomerEmail(): string { return $this->customerEmail; }
    public function setCustomerEmail(string $val): self { $this->customerEmail = $val; return $this; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function setTotalAmount(float $val): self { $this->totalAmount = $val; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $val): self { $this->status = $val; return $this; }
    public function getItems(): array { return $this->items; }
    public function setItems(array $val): self { $this->items = $val; return $this; }
    public function getShippingAddress(): ?array { return $this->shippingAddress; }
    public function setShippingAddress(?array $val): self { $this->shippingAddress = $val; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $val): self { $this->notes = $val; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
}
