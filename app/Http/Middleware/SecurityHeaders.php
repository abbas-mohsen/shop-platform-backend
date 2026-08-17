<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Attach baseline security headers to every API response.
     *
     * This API serves JSON and media files only — it never returns an HTML
     * document a browser would execute — so the policy can be maximally strict
     * without breaking anything. The frontend is a separate origin on Vercel
     * and carries its own headers (see shop-frontend/vercel.json).
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Stop browsers guessing a content type. Without this, a file uploaded
        // as an image but containing markup can be re-interpreted as HTML.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Nothing here is meant to be framed.
        $response->headers->set('X-Frame-Options', 'DENY');

        // Do not leak API paths (which contain order and product ids) in the
        // Referer header when a response links off-site.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // The API itself needs none of these device capabilities.
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), interest-cohort=()');

        // Nothing in a JSON/media response should ever load a subresource or be
        // embedded in a frame.
        $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'");

        // HSTS is only meaningful — and only safe — over a real HTTPS request.
        // Sending it in local HTTP development would pin localhost to HTTPS in
        // the developer's browser and be a pain to undo.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
