<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds an ETag to successful JSON GET responses and answers 304 when the client already has
 * that body, so an unchanged board refresh costs a few bytes instead of the whole page.
 */
class ConditionalJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || ! $response instanceof JsonResponse || $response->getStatusCode() !== 200) {
            return $response;
        }

        $response->setEtag(md5($response->getContent()));
        // Private: bodies depend on the bearer token. no-cache: always revalidate, never show stale tasks.
        $response->headers->set('Cache-Control', 'private, no-cache');
        $response->isNotModified($request);

        return $response;
    }
}
