<?php

use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
    api: __DIR__ . '/../routes/api.php',
    apiPrefix: 'api',
  )
  ->withMiddleware(function (Middleware $middleware): void {
    //
    $middleware->use([
      App\Http\Middleware\ImplementCors::class, // Debe ser Global
      Illuminate\Foundation\Http\Middleware\TrimStrings::class,
      Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
      Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ]);

    $middleware->group('api', [
      App\Http\Middleware\RegisterAppOrigin::class,
      Illuminate\Routing\Middleware\SubstituteBindings::class,
    ]);

    $middleware->group('web', [
      Illuminate\Session\Middleware\StartSession::class,
    ]);

    $middleware->alias([
      'append.content' => App\Http\Middleware\AppendContentType::class,
      'auth.headers' => App\Http\Middleware\AuthJwtHeaders::class,
      'artisan.execute' => App\Http\Middleware\CanExecuteArtisanCommands::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions): void {
    // A. Excepciones específicas (NotFound, etc.)
    $exceptions->render(function (NotFoundHttpException $e) {
      return App\Http\HttpHandler\ResponseHttp::statusError(
        msg: 'No existe la Ruta Solicitada',
        error: $e->getMessage(),
        statusCode: App\Http\HttpHandler\StatusHttp::HTTP_BAD_GATEWAY
      );
    });
    // B. Redirección genérica a `Handler`
    $exceptions->render(function (Throwable $e, Request $request) {
      return Handler::renderResponse($request, $e);
    });
  })->create();
