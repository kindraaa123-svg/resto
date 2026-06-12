<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(function () {
            return '/login';
        });
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/remote-camera/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if (in_array($e->getStatusCode(), [403, 404, 500, 503])) {
                // If it's a 403, we return 200 to avoid console errors,
                // but still show the Error page with the 403 message.
                $statusCode = $e->getStatusCode() === 403 ? 200 : $e->getStatusCode();

                return Inertia::render('Error', [
                    'status' => $e->getStatusCode(),
                    'message' => $e->getMessage() ?: 'Something went wrong.',
                ])
                    ->toResponse($request)
                    ->setStatusCode($statusCode);
            }

            return null;
        });
    })->create();
