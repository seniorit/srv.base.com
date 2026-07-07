<?php

namespace App\Http\Middleware;

use Closure;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Http\HttpHandler\{ResponseHttp,StatusHttp};
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class AuthJwtHeaders
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    try {
      // Verificar si el token existe en el header
      if (!$token = $request->header('Authorization')) {
        return $this->unauthorizedResponse('Token no proporcionado');
      }

      // Limpiar el token (remover 'Bearer ' si existe)
      $token = str_replace('Bearer ', '', $token);

      // Verificar si el token es válido
      if (!$this->isValidToken($token)) {
        return $this->unauthorizedResponse('Token inválido');
      }

      // Verificar si el token ha expirado
      if ($this->isTokenExpired($token)) {
        return $this->unauthorizedResponse('Token expirado');
      }



      // TODO Verificar si el usuario existe y si el token coincide con el token de sesión del usuario
      $user = JWTAuth::parseToken()->authenticate();

      if (!$user) {
        return $this->unauthorizedResponse('Usuario no encontrado');
      }

      // Verificar si el token coincide con el token de sesión del usuario
      if ($user->session_token !== $token) {
        return $this->unauthorizedResponse('Token de sesión inválido');
      }

      // Agregar el usuario autenticado a la request
      $request->merge(['auth_user' => $user]);

      return $next($request);
    } catch (TokenExpiredException $e) {
      return $this->unauthorizedResponse('Token expirado');
    } catch (TokenInvalidException $e) {
      return $this->unauthorizedResponse('Token inválido');
    } catch (Throwable $e) {
      return $this->unauthorizedResponse('Error de autenticación: ' . $e->getMessage());
    }
  }

  /**
   * Verifica si el token es válido
   */
  private function isValidToken(string $token): bool
  {
    try {
      JWTAuth::setToken($token)->getPayload();
      return true;
    } catch (Throwable $e) {
      return false;
    }
  }

  /**
   * Verifica si el token ha expirado
   */
  private function isTokenExpired(string $token): bool
  {
    try {
      JWTAuth::setToken($token)->checkOrFail();
      return false;
    } catch (TokenExpiredException $e) {
      return true;
    } catch (Throwable $e) {
      return true;
    }
  }

  /**
   * Retorna una respuesta de error de autenticación
   */
  private function unauthorizedResponse(string $message): JsonResponse
  {
    return ResponseHttp::statusError(
      msg: $message,
      statusCode: StatusHttp::HTTP_UNAUTHORIZED
    );
  }
}
