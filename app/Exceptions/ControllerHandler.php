<?php

namespace App\Exceptions;

use Throwable;
use InvalidArgumentException;
use App\Http\HttpHandler\StatusHttp;
use App\Http\HttpHandler\ResponseHttp;
use Illuminate\Http\Exceptions\HttpResponseException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException as FormValidation;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Maatwebsite\Excel\Validators\ValidationException as ExcelException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ControllerHandler
{
  use DatabaseHandler;

  public static function handler(
    Throwable $e,
    string $defaulError = 'Error en la Solicitud',
    int $defaultStatusCode = StatusHttp::HTTP_UNPROCESSABLE_ENTITY
  ) {
    if ($e instanceof HttpResponseException) {
      return $e->getResponse();
    }

    // Primero se maneja la excepción de base de datos
    $databaseErrorResponse = self::databaseErrorHandler($e, $defaultStatusCode);
    if ($databaseErrorResponse) {
      return $databaseErrorResponse;
    }

    if ($e instanceof MethodNotAllowedException || $e instanceof MethodNotAllowedHttpException) {
      return ResponseHttp::statusError(
        msg: 'No Existe el Metodo Solicitado',
        statusCode: StatusHttp::HTTP_NOT_FOUND
      );
    }

    if ($e instanceof InvalidArgumentException) {
      return ResponseHttp::statusError(
        msg: 'Invalid Argument Exception',
        statusCode: StatusHttp::HTTP_BAD_REQUEST
      );
    }

    if ($e instanceof NotFoundResourceException) {
      return ResponseHttp::statusError(
        msg: 'Error en el Metodo Solicitado',
        error: $e->getMessage(),
        statusCode: StatusHttp::HTTP_NOT_FOUND
      );
    }

    if ($e instanceof ModelNotFoundException) {
      return ResponseHttp::statusError(
        msg: 'No Existe el Registro Solicitado',
        statusCode: StatusHttp::HTTP_NOT_FOUND,
        error: $e->getMessage()
      );
    }

    if ($e instanceof ExcelException) {
      return ResponseHttp::statusError(
        msg: 'Error Procesando el Archivo Excel',
        statusCode: StatusHttp::HTTP_BAD_REQUEST,
        error: $e->getMessage()
      );
    }

    if ($e instanceof FormValidation) {
      return ResponseHttp::statusError(
        msg: 'Datos Invalidos',
        statusCode: StatusHttp::HTTP_BAD_REQUEST
      );
    }

    if ($e instanceof JWTException) {
      return ResponseHttp::statusError(
        msg: 'Autenticacion Invalida',
        error: $e->getMessage(),
        statusCode: StatusHttp::HTTP_UNAUTHORIZED,
      );
    }

    return ResponseHttp::statusError(
      msg: $defaulError,
      error: $e->getMessage(),
      statusCode: $defaultStatusCode
    );
  }
}
