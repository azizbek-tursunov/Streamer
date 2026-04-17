<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            // AddLinkHeadersForPreloadedAssets removed — large Link headers
            // overflow OpenResty's proxy_buffer_size causing 502
        ]);

        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if ($status === HttpResponse::HTTP_PAGE_EXPIRED) {
                if ($request->header('X-Inertia')) {
                    return back()->with('error', 'Sessiya muddati tugadi. Sahifani yangilab, qayta urinib koring.');
                }

                return $response;
            }

            if (! in_array($status, [
                HttpResponse::HTTP_FORBIDDEN,
                HttpResponse::HTTP_NOT_FOUND,
                HttpResponse::HTTP_TOO_MANY_REQUESTS,
                HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                HttpResponse::HTTP_SERVICE_UNAVAILABLE,
            ], true)) {
                return $response;
            }

            if (app()->environment(['local', 'testing']) && $status >= HttpResponse::HTTP_INTERNAL_SERVER_ERROR) {
                return $response;
            }

            if ($request->header('X-Inertia')) {
                return Inertia::render('ErrorPage', [
                    'status' => $status,
                ])->toResponse($request)->setStatusCode($status);
            }

            return $response;
        });
    })->create();
