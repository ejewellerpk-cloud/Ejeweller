<?php

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Middleware\Installed;
use App\Http\Middleware\localization;
use Illuminate\Foundation\Application;
use Illuminate\Database\QueryException;
use App\Http\Middleware\ApiKeyMiddleware;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/analytics.php'));
            if (file_exists(storage_path('installed'))) {
                try {
                    $path = app_path('Http/PaymentGateways/Routes');

                    if (is_dir($path)) {
                        foreach (scandir($path) as $file) {
                            if (!in_array($file, ['.', '..'])) {
                                Route::middleware('web')
                                    ->group($path . '/' . $file);
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                }
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'payment/swich/callback',
            'payin/callback',
        ]);
        $middleware->append(\App\Http\Middleware\ActiveUserTracker::class);
        $middleware->alias([
            'auth'             => Authenticate::class,
            'auth.basic'       => AuthenticateWithBasicAuth::class,
            'auth.session'     => AuthenticateSession::class,
            'cache.headers'    => SetCacheHeaders::class,
            'can'              => Authorize::class,
            'guest'            => RedirectIfAuthenticated::class,
            'password.confirm' => RequirePassword::class,
            'precognitive'     => HandlePrecognitiveRequests::class,
            'permission'       => PermissionMiddleware::class,
            'signed'           => ValidateSignature::class,
            'throttle'         => ThrottleRequests::class,
            'verified'         => EnsureEmailIsVerified::class,
            'apiKey'           => ApiKeyMiddleware::class,
            'localization'     => localization::class,
            'installed'        => Installed::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        $exceptions->renderable(
            function (Throwable $e, Request $request) {
                if ($e instanceof UnauthorizedException) {
                    return new JsonResponse(
                        [
                            'success' => false,
                            'message' => 'User does not have the right permissions.'
                        ],
                        403
                    );
                }

                if ($e instanceof ModelNotFoundException) {
                    return new JsonResponse(
                        [
                            'success' => false,
                            'message' => 'No query results for model.'
                        ],
                        404
                    );
                }

                if ($e instanceof MethodNotAllowedHttpException) {
                    return new JsonResponse(
                        [
                            'success' => false,
                            'message' => 'Method not support for the route.'
                        ],
                        405
                    );
                }

                if ($e instanceof NotFoundHttpException) {
                    return new JsonResponse(
                        [
                            'success' => false,
                            'message' => 'The specified URL cannot be found.'
                        ],
                        404
                    );
                }

                if ($e instanceof HttpException) {
                    return new JsonResponse(
                        [
                            'success' => false,
                            'message' => $e->getMessage()
                        ],
                        422
                    );
                }

                if ($e instanceof QueryException) {
                    return new JsonResponse(
                        [
                            'success' => false,
                            'message' => $e->getMessage()
                        ],
                        422
                    );
                }
            }
        );
    })->create();
