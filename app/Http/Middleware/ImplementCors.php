<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\HttpHandler\{ResponseHttp, StatusHttp};


/**
 * Dependencias de ImplementCors:
 * * config/cors.php
 * * config/settings.php
 */
class ImplementCors
{

  /**
   * Agrega cabeceras CORS a la respuesta, validando el origen contra una lista de orígenes permitidos.
   * Si el origen no está permitido, bloquea en producción con un error 401.
   * En desarrollo o si falta el header Origin, se usa el valor de la configuración de front_url como fallback.
   *
   * @param Request $request
   * @param Closure $next
   * @return Response
   */
  public function handle(Request $request, Closure $next): Response
  {
    $allowedOrigins = config('cors.allowed_origins', []);
    $origin = $request->header('Origin');

    $validation = $this->validateOrigin($origin, $allowedOrigins);

    // Si el origen no es válido y estamos en producción, bloqueamos la petición real.
    // Las peticiones OPTIONS no se bloquean aquí; el navegador lo hará si la respuesta preflight no es correcta.
    if (!$validation['valid'] && app()->isProduction() && !$request->isMethod('OPTIONS')) {
      if ($validation['reason'] === 'origin_not_allowed') {
        return ResponseHttp::statusError(
          msg: 'Origen no autorizado',
          error: $origin,
          statusCode: StatusHttp::HTTP_UNAUTHORIZED
        );
      }
    }

    // Determinar el origen a permitir en la cabecera de respuesta.
    // Usamos el origen validado, o un fallback.
    $allowOrigin = $validation['origin'] ?: config('settings.front_url');

    // Si es una petición preflight, devolvemos una respuesta 204 con las cabeceras.
    if ($request->isMethod('OPTIONS')) {
      $response = new Response('', Response::HTTP_NO_CONTENT);
      $this->addCorsHeaders($response, $allowOrigin, $request);
      return $response;
    }

    // Para peticiones reales, procesamos la petición y luego añadimos las cabeceras.
    $response = $next($request);
    //$this->addCorsHeaders($response, $allowOrigin, $request);

    return $response;
  }


  /**
   * Valida el origen (Origin) de la petición contra los allowed_origins en configuración.
   * Devuelve un array con las siguientes claves:
   * - valid: boolean que indica si el origen es válido
   * - origin: string con el valor del origen (si es válido) o nulo si no lo es
   * - reason: string que describe el motivo de la validación:
   *   - missing_origin: no se ha proporcionado el origen en la petición
   *   - wildcard_allowed: el wildcard (*) está permitido en la configuración y no estamos en producción
   *   - origin_allowed: el origen se encuentra entre los permitidos
   *   - origin_not_allowed: el origen no se encuentra entre los permitidos
   *
   * @param string|null $origin el valor de la cabecera Origin
   * @param array $allowedOrigins los allowed_origins configurados
   * @return array
   */
  private function validateOrigin(?string $origin, array $allowedOrigins): array
  {
    // ✅ Aquí va TODO el control
    if (!$origin) {
      return ['valid' => false, 'origin' => null, 'reason' => 'missing_origin'];
    }

    if (in_array('*', $allowedOrigins) && !app()->isProduction()) {
      return ['valid' => true, 'origin' => '*', 'reason' => 'wildcard_allowed'];
    }

    if (in_array($origin, $allowedOrigins)) {
      return ['valid' => true, 'origin' => $origin, 'reason' => 'origin_allowed'];
    }

    return ['valid' => false, 'origin' => null, 'reason' => 'origin_not_allowed'];
  }

  /**
   * Agrega las cabeceras CORS necesarias a la respuesta.
   *
   * @param  \Symfony\Component\HttpFoundation\Response  $response
   * @param  string  $origin
   * @param  \Illuminate\Http\Request  $request
   * @return void
   */
  private function addCorsHeaders(Response $response, string $origin, Request $request): void
  {
    //$response->headers->set('Access-Control-Allow-Origin', $origin);
    $response->headers->set('Access-Control-Allow-Methods', config('cors.allowed_methods'));
    $response->headers->set('Access-Control-Allow-Headers', config('cors.allowed_headers'));
    $response->headers->set('Access-Control-Expose-Headers', config('cors.exposed_headers'));
    $response->headers->set('Access-Control-Allow-Credentials', 'true');
    $response->headers->set('Access-Control-Max-Age', config('cors.max_age', 86400));

    // Reenviar ApplicationId si viene en la petición
    $appId = $request->header('ApplicationId');
    if ($appId) {
      $response->headers->set('ApplicationId', $appId);

      // HSTS solo en HTTPS
      if ($request->secure()) {
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
      }
    }
  }
}
