<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción personalizada que se lanza cuando falta el encabezado Content-Type en una solicitud HTTP
 *
 * Esta excepción se utiliza para manejar casos donde una solicitud HTTP no incluye
 * el encabezado Content-Type requerido, lo cual es necesario para determinar el tipo
 * de contenido que se está enviando al servidor.
 *
 * @package App\Exceptions
 */
class MissingContentTypeException extends Exception
{
  /**
   * Constructor de la excepción
   *
   * Inicializa la excepción con un mensaje predefinido indicando la ausencia
   * del encabezado Content-Type.
   */
  public function __construct()
  {
    parent::__construct('Encabezado Content-Type ausente. Asegúrese de enviarlo en la solicitud.');
  }
}
