<?php

namespace App\Console\Commands;

use App\Mail\NewOrderAdmin;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderEmails extends Command
{
    protected $signature   = 'order:send-emails {orderId}';
    protected $description = 'Send order confirmation to client and notification to admins';

    public function handle(): int
    {
        $orderId = (int) $this->argument('orderId');
        $order   = Order::with(['items.product', 'user'])->find($orderId);

        if (! $order) {
            Log::error("SendOrderEmails: order #{$orderId} not found.");
            return 1;
        }

        // ── Customer confirmation ─────────────────────────────────
        try {
            if ($order->user && $order->user->email) {
                Mail::to($order->user->email)->send(new OrderConfirmation($order));
                Log::info("Order #{$orderId}: confirmation sent to {$order->user->email}");
            }
        } catch (\Throwable $e) {
            Log::error("Order #{$orderId}: confirmation failed: " . $e->getMessage());
        }

        // ── Admin notification ────────────────────────────────────
        try {
            $adminEmails = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                ->whereNotNull('email')
                ->pluck('email')
                ->all();

            foreach ($adminEmails as $email) {
                Mail::to($email)->send(new NewOrderAdmin($order));
            }

            if ($adminEmails) {
                Log::info("Order #{$orderId}: admin emails sent to " . implode(', ', $adminEmails));
            }
        } catch (\Throwable $e) {
            Log::error("Order #{$orderId}: admin email failed: " . $e->getMessage());
        }

        return 0;
    }
}
