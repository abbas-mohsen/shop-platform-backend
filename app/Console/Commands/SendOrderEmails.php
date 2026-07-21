<?php

namespace App\Console\Commands;

use App\Mail\NewOrderAdmin;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\User;
use App\Services\ResendService;
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

        $resend = app(ResendService::class);

        // ── Customer confirmation ─────────────────────────────────
        try {
            if ($order->user && $order->user->email) {
                if ($resend->isConfigured()) {
                    $resend->sendMailable($order->user->email, new OrderConfirmation($order));
                } else {
                    Mail::to($order->user->email)->send(new OrderConfirmation($order));
                    Log::info("Order #{$orderId}: confirmation sent to {$order->user->email}");
                }
            }
        } catch (\Throwable $e) {
            Log::error("Order #{$orderId}: confirmation failed: " . $e->getMessage());
        }

        // ── Admin notification ────────────────────────────────────
        try {
            // Prefer a single configured inbox (a real, monitored mailbox).
            // Falling back to every admin row can include placeholder addresses
            // whose bounces hurt the sender's reputation and cause spam-foldering.
            $configured = config('mail.admin_address');
            if ($configured) {
                $adminEmails = [$configured];
            } else {
                $adminEmails = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
                    ->whereNotNull('email')
                    ->pluck('email')
                    ->all();
            }

            // De-duplicate and never send the notification to the store's own
            // sending address (a self-send is a common spam signal).
            $fromAddress = config('mail.from.address');
            $adminEmails = array_values(array_unique(array_filter(
                $adminEmails,
                fn ($email) => $email && strcasecmp($email, $fromAddress) !== 0
            )));

            foreach ($adminEmails as $email) {
                if ($resend->isConfigured()) {
                    $resend->sendMailable($email, new NewOrderAdmin($order));
                } else {
                    Mail::to($email)->send(new NewOrderAdmin($order));
                }
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
