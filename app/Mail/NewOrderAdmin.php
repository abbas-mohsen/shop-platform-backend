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
        return $this->subject('XTREMEFIT — New Order #' . $this->order->id . ' Received')
                    ->view('emails.new-order-admin');
    }
}
