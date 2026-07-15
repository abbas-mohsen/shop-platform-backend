<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class NewOrderAdmin extends Mailable
{
    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order->loadMissing(['items.product', 'user']);
    }

    public function build()
    {
        $mail = $this->subject('XTREMEFIT — New Order #' . $this->order->id . ' Received')
                     ->view('emails.new-order-admin')
                     ->text('emails.new-order-admin-text');

        // Reply-To the customer so it reads as a genuine transactional message
        // (a plain-text alternative + a real Reply-To keep it out of spam).
        if ($this->order->user && $this->order->user->email) {
            $mail->replyTo($this->order->user->email, $this->order->user->name ?? null);
        }

        return $mail;
    }
}
