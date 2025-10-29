<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DelayResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //sleep(5);

        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::info(
            "HTTP Request Log:\n" , [
                'method' => $request->method(),
                'uri' => $request->getPathInfo(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'body' => $this->filterBody($request),
                'headers' => $request->headers->all(),
                'user_id' => optional($request->user())->id,
            ]
        );

        return $response;
    }

    private function filterBody(Request $request)
    {
        // Không log password hoặc file upload để bảo mật
        $data = $request->except(['password', 'file', 'image']);
        return $data;
    }
}
