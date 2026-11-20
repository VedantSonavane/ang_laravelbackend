<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (AuthenticationException $e, $request) {
            return ApiResponse::error('Unauthenticated', 401);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            return ApiResponse::error($e->getMessage() ?: 'Forbidden', 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return ApiResponse::error('Resource not found', 404);
        });
    })->create();
