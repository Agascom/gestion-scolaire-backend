<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité posés sur chaque réponse HTTP.
 *
 * Note sur CSP : l'API étant principalement consommée par l'application
 * Electron compilée "localement" (pas d'origine web classique), on couple le
 * CSP à un faux mode "iframe" désactivé ici pour rester compatible avec les
 * embarquements d'images/files/iframe de l'app. La vraie protection
 * anti-CSRF/clickjacking demeure : X-Frame-Options, X-Content-Type-Options,
 * Referrer-Policy, HSTS (si HTTPS).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-DNS-Prefetch-Control', 'off');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}