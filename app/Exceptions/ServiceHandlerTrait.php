<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use App\Utilities\LoggerHandler;
use App\Http\HttpHandler\ResponseHttp;
use Illuminate\Http\Exceptions\HttpResponseException;

trait ServiceHandlerTrait
{
  use LoggerHandler;

  /**
   * Relanza una excepción para que suba al nivel superior (Controller).
   *
   * Registra un log estructurado con el contexto completo de la excepción
   * antes de relanzarla, para facilitar la depuración.
   *
   * @param  Throwable  $error  Excepción capturada en el Service.
   *
   * @throws Throwable Relanza la misma excepción recibida.
   */
  public function handleServiceThrow(Throwable $error): never
  {
    // logger para capturar el error sin interrumpir el flujo.
    self::logTrace([
      'request_url' => 'ServiceHandlerTrait::handleServiceThrow',
      'exception_class' => get_class($error),
      'exception_message' => $error->getMessage(),
      'exception_code' => $error->getCode(),
      'exception_file' => $error->getFile(),
      'exception_line' => $error->getLine(),
    ]);
    throw $error; // Relanza la misma excepción
  }

  /**
   * Crea y lanza una excepción con mensaje personalizado.
   *
   * Si el código es un código HTTP válido (100-599), lanza HttpResponseException
   * para que Laravel maneje correctamente el código de estado.
   * De lo contrario, lanza una Exception genérica.
   *
   * @param  string  $error  Mensaje descriptivo del error (campo `error` en la respuesta JSON, visible solo en dev).
   * @param  int  $code  Código HTTP (opcional, default: 500)
   * @param  Throwable|null  $previous  Excepción anterior (para traza)
   * @param  string  $msg  Mensaje de respuesta para el usuario (campo `message` en la respuesta JSON).
   *
   * @throws HttpResponseException|Exception
   */
  public function handleServiceThrowMsg(
    string $error,
    int $code = 500,
    ?Throwable $previous = null,
    string $msg = 'Error en la Solicitud'
  ): never {

    self::logTrace([
      'request_url' => 'ServiceHandlerTrait::handleServiceThrowMsg',
      'exception_message' => $error,
      'exception_code' => $code,
    ]);

    // Si el código es un código HTTP válido (100-599), lanzar HttpResponseException
    if ($code >= 100 && $code <= 599) {
      throw new HttpResponseException(
        ResponseHttp::statusError(
          msg: $msg,
          error: $error,
          statusCode: $code
        )
      );
    }

    // Para otros códigos, mantener Exception genérica
    throw new Exception($error, $code, $previous);
  }
}
