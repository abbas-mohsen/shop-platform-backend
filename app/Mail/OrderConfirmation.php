<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;

class OrderConfirmation extends Mailable
{

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order->loadMissing(['items.product', 'user']);
    }

    public function build()
    {
        $mail = $this->subject('XTREMEFIT — Order #' . $this->order->id . ' Confirmed')
                     ->view('emails.order-confirmation');

        try {
            ini_set('memory_limit', '256M');
            $pdf = \Barryvdh\DomPDF\Facade::loadView('emails.invoice-pdf', ['order' => $this->order])
                       ->setPaper('a4', 'portrait');

            $mail->attachData($pdf->output(), 'invoice-' . $this->order->id . '.pdf', [
                'mime' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Invoice PDF generation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'order_id'  => $this->order->id,
            ]);
        }

        return $mail;
    }
}
