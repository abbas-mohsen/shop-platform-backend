<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $oldStatus;

    public function __construct(Order $order, string $oldStatus)
    {
        $this->order     = $order;
        $this->oldStatus = $oldStatus;
    }

    public function build()
    {
        return $this->subject('XTREMEFIT — Order #' . $this->order->id . ' Status Update')
                    ->view('emails.order-status-updated');
    }
}
