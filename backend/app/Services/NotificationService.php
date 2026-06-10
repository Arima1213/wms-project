<?php

namespace App\Services;

use App\Models\AlertRule;
use App\Models\Inbound;
use App\Models\Notification;
use App\Models\Outbound;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Events\LowStockDetected;
use App\Events\ExpiringProductDetected;
use App\Events\InboundOverdueDetected;
use App\Events\OutboundOverdueDetected;

class NotificationService
{
    /**
     * Get users who should receive alerts
     */
    protected function getUsersToNotify()
    {
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Super Admin', 'Admin', 'Warehouse Manager']);
        })->where('is_active', true)->get();

        if ($users->isEmpty()) {
            $users = User::where('is_active', true)->get();
        }

        return $users;
    }

    /**
     * Notify users
     */
    protected function notifyUsers(string $type, string $title, string $message, array $data = []): bool
    {
        $users = $this->getUsersToNotify();
        $notifications = [];
        $now = now();
        $hasNew = false;

        foreach ($users as $user) {
            // Check for duplicate in last 24 hours
            $duplicate = Notification::where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class)
                ->where('type', $type)
                ->where('title', $title)
                ->where('created_at', '>=', $now->copy()->subHours(24))
                ->exists();

            if (!$duplicate) {
                $notifications[] = [
                    'type' => $type,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'title' => $title,
                    'message' => $message,
                    'data' => json_encode($data),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $hasNew = true;
            }
        }

        if (!empty($notifications)) {
            Notification::insert($notifications);
        }

        return $hasNew;
    }

    public function checkLowStock(): void
    {
        $rule = AlertRule::where('type', 'low_stock')->where('is_active', true)->first();
        if (!$rule) return;

        $products = Product::lowStock()->get();

        foreach ($products as $product) {
            $notified = $this->notifyUsers(
                type: 'low_stock',
                title: 'Low Stock Alert',
                message: "Product {$product->name} (SKU: {$product->sku}) is running low.",
                data: ['product_id' => $product->id, 'sku' => $product->sku]
            );

            if ($notified) {
                event(new LowStockDetected($product));
            }
        }
    }

    public function checkExpiringProducts(int $days = 30): void
    {
        $rule = AlertRule::where('type', 'expiring_products')->where('is_active', true)->first();
        if (!$rule) return;

        $daysConfig = $rule->config['days'] ?? $days;
        $targetDate = now()->addDays($daysConfig);

        $batches = ProductBatch::with('product')
            ->where('expiry_date', '<=', $targetDate)
            ->where('expiry_date', '>=', now())
            ->where('is_active', true)
            ->get();

        foreach ($batches as $batch) {
            $productName = $batch->product ? $batch->product->name : 'Unknown';
            $notified = $this->notifyUsers(
                type: 'expiring_products',
                title: 'Expiring Product Alert',
                message: "Batch {$batch->batch_number} of product {$productName} will expire on {$batch->expiry_date->format('Y-m-d')}.",
                data: ['batch_id' => $batch->id, 'product_id' => $batch->product_id]
            );

            if ($notified) {
                event(new ExpiringProductDetected($batch));
            }
        }
    }

    public function checkOverdueInbounds(int $hours = 24): void
    {
        $rule = AlertRule::where('type', 'inbound_overdue')->where('is_active', true)->first();
        if (!$rule) return;

        $hoursConfig = $rule->config['hours'] ?? $hours;
        $targetDate = now()->subHours($hoursConfig);

        $inbounds = Inbound::whereIn('status', ['pending', 'partial'])
            ->where('created_at', '<=', $targetDate)
            ->get();

        foreach ($inbounds as $inbound) {
            $notified = $this->notifyUsers(
                type: 'inbound_overdue',
                title: 'Overdue Inbound Alert',
                message: "Inbound {$inbound->inbound_number} has been pending for over {$hoursConfig} hours.",
                data: ['inbound_id' => $inbound->id, 'inbound_number' => $inbound->inbound_number]
            );

            if ($notified) {
                event(new InboundOverdueDetected($inbound));
            }
        }
    }

    public function checkOverdueOutbounds(int $hours = 24): void
    {
        $rule = AlertRule::where('type', 'outbound_overdue')->where('is_active', true)->first();
        if (!$rule) return;

        $hoursConfig = $rule->config['hours'] ?? $hours;
        $targetDate = now()->subHours($hoursConfig);

        $outbounds = Outbound::where('status', 'pending')
            ->where('created_at', '<=', $targetDate)
            ->get();

        foreach ($outbounds as $outbound) {
            $notified = $this->notifyUsers(
                type: 'outbound_overdue',
                title: 'Overdue Outbound Alert',
                message: "Outbound {$outbound->outbound_number} has been pending for over {$hoursConfig} hours.",
                data: ['outbound_id' => $outbound->id, 'outbound_number' => $outbound->outbound_number]
            );

            if ($notified) {
                event(new OutboundOverdueDetected($outbound));
            }
        }
    }

    public function runAllChecks(): void
    {
        $this->checkLowStock();
        $this->checkExpiringProducts();
        $this->checkOverdueInbounds();
        $this->checkOverdueOutbounds();
    }

    public function getUnreadNotifications(int $userId)
    {
        return Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function markAsRead(int $userId, ?int $notificationId = null): bool
    {
        $query = Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at');

        if ($notificationId) {
            $query->where('id', $notificationId);
        }

        return $query->update(['read_at' => now()]) > 0;
    }
}
