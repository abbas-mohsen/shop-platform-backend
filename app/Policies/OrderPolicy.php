<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        return ($user->id === $order->user_id || $user->isAdmin())
            && $order->status === 'pending';
    }

    /**
     * Determine if the user can update the order status (admin only).
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
