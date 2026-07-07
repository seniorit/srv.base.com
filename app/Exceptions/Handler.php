<?php

namespace App\Exceptions;

use Throwable;
use App\Utilities\LoggerHandler;
use Illuminate\Http\JsonResponse;
use App\Http\HttpHandler\StatusHttp;
use App\Http\HttpHandler\ResponseHttp;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Validation\ValidationException as FormValidation;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler
{
  use LoggerHandler;

  public static function renderResponse($request, Throwable $e)
  {
    return self::render($request, $e);
  }

  private static function render($request, Throwable $e): JsonResponse
  {

    // logger para capturar el error sin interrumpir el flujo.
    self::logTrace([
      'type' => class_basename($e),
      'message' => $e->getMessage(),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
      'url' => $request->fullUrl(),
    ]);

    // Nuevo bloque para capturar errores de permisos
    // La clave es buscar en el mensaje de la excepción por palabras clave.
    // Esto es muy útil cuando Laravel no lanza una excepción de tipo específico.
    $errorMessage = strtolower($e->getMessage());
    if (
      str_contains($errorMessage, 'permission denied') ||
      str_contains($errorMessage, 'failed to open stream') ||
      str_contains($errorMessage, 'read-only file system') ||
      str_contains($errorMessage, 'no such file or directory')
    ) {
      return ResponseHttp::statusError(
        msg: 'Error de Permisos en el Servidor',
        error: 'No se pudo escribir en el directorio. Verifique los permisos de la carpeta.',
        statusCode: StatusHttp::HTTP_INTERNAL_SERVER_ERROR
      );
    }

    if ($e instanceof MethodNotAllowedException || $e instanceof MethodNotAllowedHttpException) {
      return ResponseHttp::statusError(
        msg: 'Operacion no Permitida',
        statusCode: StatusHttp::HTTP_METHOD_NOT_ALLOWED,
        error: 'Método ' . $request->method() . ' no permitido para esta ruta'
      );
    }

    if ($e instanceof HttpResponseException) {
      return $e->getResponse();
    }

    if ($e instanceof FormValidation) {
      return ResponseHttp::statusError(
        msg: 'Datos Invalidos',
        statusCode: StatusHttp::HTTP_BAD_REQUEST
      );
    }

    if ($e instanceof UnauthorizedException) {
      return ResponseHttp::statusError(
        msg: 'Modelo no Autorizado',
        statusCode: StatusHttp::HTTP_FORBIDDEN
      );
    }

    if ($e instanceof UnauthorizedHttpException) {
      return ResponseHttp::statusError(
        msg: 'Acceso no Autorizado',
        statusCode: StatusHttp::HTTP_UNAUTHORIZED
      );
    }

    if ($e instanceof NotFoundResourceException) {
      return ResponseHttp::statusError(
        msg: 'El Recurso solicitado no Existe',
        error: $e->getMessage(),
        statusCode: StatusHttp::HTTP_NOT_FOUND
      );
    }

    if ($e instanceof UrlGenerationException) {
      return ResponseHttp::statusError(
        msg: 'Parámetro no encontrado en la URL',
        error: $e->getMessage(),
        statusCode: StatusHttp::HTTP_NOT_ACCEPTABLE
      );
    }

    return ResponseHttp::statusError(
      msg: 'Error en la Solicitud',
      error: $e->getMessage(),
      statusCode: StatusHttp::HTTP_INTERNAL_SERVER_ERROR
    );
  }
}
