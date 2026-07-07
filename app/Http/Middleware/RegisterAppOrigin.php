<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Http\HttpHandler\{ResponseHttp,StatusHttp};

class RegisterAppOrigin
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    $resp = $next($request);
    $applicationId = $request->header('ApplicationId');

    
    if (!in_array($applicationId, self::AllowedApplication())) {

      Log::info($applicationId ?? 'Origen No Autorizado');
      
      return ResponseHttp::statusError(
        msg: 'Acceso no Permitido. Origen No Autorizado',
        error: $applicationId ?? 'Origen No Autorizado',
        statusCode: StatusHttp::HTTP_UNAUTHORIZED
      );
    }
    $resp->headers->set('ApplicationId', $applicationId);
    return $resp;
  }

  /**
   * Allowed Authorized Application Id
   *
   * @return array
   */
  private static function AllowedApplication(): array
  {
    return [
      'com.gescom.app',
      'com.monarcait.app_express',
      'com.monarcait.gescom_abm',
      'com.gescom.app2',
      'com.gescom.app3',
    ];
  }
}
