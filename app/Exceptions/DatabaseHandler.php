<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Support\Arr;
use App\Http\HttpHandler\ResponseHttp;
use Illuminate\Database\QueryException;

trait DatabaseHandler
{
  // Códigos de error MySQL / MariaDB (Comparten los mismos códigos nativos)
  private const MYSQL_DUPLICATE_ENTRY = 1062;

  private const MYSQL_FOREIGN_KEY_CONSTRAINT = 1451;

  private const MYSQL_COMLUMN_NOT_EXIST = 1452;

  private const MYSQL_TABLE_NOT_EXIST = 1146;

  private const MYSQL_UNKNOWN_COLUMN = 1054;

  // Códigos de error PGSQL (SQLSTATE)
  private const PGSQL_DUPLICATE_ENTRY = '23505';

  private const PGSQL_FOREIGN_KEY_CONSTRAINT = '23503';

  private const PGSQL_TABLE_NOT_EXIST = '42P01';

  private const PGSQL_UNKNOWN_COLUMN = '42703';

  private const MYSQL_ERRORS = [
    1062 => 'El registro ya existe. No es posible ingresar registros duplicados',
    1451 => 'El registro no puede ser eliminado. Posee relación con otros registros',
    1452 => 'El valor de la clave foránea no existe en la tabla principal',
    1146 => 'Tabla no existe. Error en la ejecución del modelo',
    1054 => 'Error en la consulta. Campo desconocido',
  ];

  private const PGSQL_ERRORS = [
    '23505' => 'El registro ya existe. No es posible ingresar registros duplicados',
    '23503' => 'El registro no puede ser eliminado. Posee relación con otros registros',
    '42P01' => 'Tabla no existe. Error en la ejecución del modelo',
    '42703' => 'Error en la consulta. Campo desconocido',
  ];

  private const STATUS_CODES = [
    'duplicate' => ResponseHttp::HTTP_CONFLICT,
    'foreign_key' => ResponseHttp::HTTP_CONFLICT,
    'default' => ResponseHttp::HTTP_UNPROCESSABLE_ENTITY,
  ];

  public static function databaseErrorHandler(Throwable $e, int $defaultStatusCode = ResponseHttp::HTTP_UNPROCESSABLE_ENTITY)
  {
    if (! $e instanceof QueryException) {
      return null;
    }

    $driver = config('database.default');

        // errorInfo es un array estandarizado:
        // [0] => SQLSTATE, [1] => Native Error Code, [2] => Native Error Message
    /** @var \PDOException $e */
    $errorInfo = $e->errorInfo;

    // Verificar si el array de información de error es válido
    if (! is_array($errorInfo) || count($errorInfo) < 3) {
      return null;
    }

    // Para MySQL/MariaDB, usamos el código de error nativo (index 1).
    // Para PostgreSQL, usamos el SQLSTATE (index 0).
    if (in_array($driver, ['mysql', 'mariadb'])) {
      $errorCode = $errorInfo[1] ?? null;
    } elseif ($driver === 'pgsql') {
      $errorCode = $errorInfo[0] ?? null;
    } else {
      return null; // Driver no soportado
    }

    if (empty($errorCode)) {
      return null;
    }

    $errorMap = self::getErrorMap($driver);
    $errorMessage = Arr::get($errorMap, $errorCode);

    if ($errorMessage) {
      $statusCode = self::getStatusCodeForError($errorCode, $driver);

      return ResponseHttp::statusError(
        msg: $errorMessage,
        error: $e->getMessage(),
        statusCode: $statusCode,
      );
    }

    // Si no se encuentra el error, devolvemos una respuesta genérica
    return ResponseHttp::statusError(
      msg: 'Error de Base de Datos Desconocido',
      error: $e->getMessage(),
      statusCode: $defaultStatusCode,
    );
  }

  private static function getErrorMap(string $driver): array
  {
    return match ($driver) {
      'mysql', 'mariadb' => self::MYSQL_ERRORS, // MariaDB usa los mismos códigos
      'pgsql' => self::PGSQL_ERRORS,
      default => [],
    };
  }

  private static function getStatusCodeForError(string|int $errorCode, string $driver): int
  {
    // La comparación se hace con el errorCode (que puede ser int o string)
    return match ((string) $errorCode) {
      (string) self::MYSQL_DUPLICATE_ENTRY,
      (string) self::PGSQL_DUPLICATE_ENTRY => self::STATUS_CODES['duplicate'],
      (string) self::MYSQL_FOREIGN_KEY_CONSTRAINT,
      (string) self::PGSQL_FOREIGN_KEY_CONSTRAINT => self::STATUS_CODES['foreign_key'],
      default => self::STATUS_CODES['default'],
    };
  }
}
