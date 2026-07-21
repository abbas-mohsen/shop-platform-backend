<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends email through Resend's HTTPS API instead of SMTP.
 *
 * Render (our backend host) blocks all outbound SMTP ports, so Gmail SMTP
 * times out in production. Resend delivers over port 443, which is never
 * blocked. This reuses the existing Blade-based Mailables unchanged — it just
 * renders them to HTML and posts them.
 */
class ResendService
{
    private const ENDPOINT = 'https://api.resend.com/emails';

    public function isConfigured(): bool
    {
        return (bool) config('services.resend.key');
    }

    /**
     * Render an existing Mailable and send it via Resend.
     * Returns true on success. Never throws — failures are logged so a mail
     * problem can never break checkout or an admin action.
     */
    public function sendMailable(string $to, Mailable $mailable): bool
    {
        try {
            // render() runs the Mailable's build(), populating its subject,
            // HTML body, any raw attachments (e.g. the invoice PDF) and reply-to.
            $html    = $mailable->render();
            $subject = $mailable->subject ?? 'XTREMEFIT';

            $payload = [
                'from'    => config('services.resend.from'),
                'to'      => [$to],
                'subject' => $subject,
                'html'    => $html,
            ];

            // Carry over any Reply-To the Mailable set (NewOrderAdmin does).
            if (!empty($mailable->replyTo)) {
                $rt = $mailable->replyTo[0]['address'] ?? null;
                if ($rt) {
                    $payload['reply_to'] = $rt;
                }
            }

            // Carry over raw attachments (the invoice PDF on OrderConfirmation).
            $attachments = [];
            foreach ($mailable->rawAttachments ?? [] as $att) {
                if (!empty($att['data']) && !empty($att['name'])) {
                    $attachments[] = [
                        'filename' => $att['name'],
                        'content'  => base64_encode($att['data']),
                    ];
                }
            }
            if ($attachments) {
                $payload['attachments'] = $attachments;
            }

            $response = Http::withToken(config('services.resend.key'))
                ->timeout(20)
                ->post(self::ENDPOINT, $payload);

            if ($response->successful()) {
                Log::info("Resend: email sent to {$to} (\"{$subject}\")");
                return true;
            }

            Log::error("Resend: send to {$to} failed [{$response->status()}]: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("Resend: exception sending to {$to}: " . $e->getMessage());
            return false;
        }
    }
}
