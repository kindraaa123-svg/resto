<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRouteActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log mutating requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            try {
                // Prevent logging sensitive data
                $payload = $request->except([
                    'password',
                    'password_confirmation',
                    'current_password',
                    '_token',
                    'snap_token',
                ]);

                // Determine a readable action name
                $routeName = $request->route() ? $request->route()->getName() : null;
                $action = $routeName ? "route '{$routeName}'" : "endpoint '{$request->path()}'";

                activity('system_route')
                    ->causedBy($request->user())
                    ->withProperties([
                        'payload' => ! empty($payload) ? $payload : null,
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                    ])
                    ->log("Submitted {$request->method()} request to {$action}");

            } catch (\Exception $e) {
                // Failsafe: never break the app if logging fails
            }
        }

        return $response;
    }
}
