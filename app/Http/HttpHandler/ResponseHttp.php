<?php

namespace App\Http\HttpHandler;

use Illuminate\Http\JsonResponse;

class ResponseHttp extends StatusHttp
{
  /**
   *  Response Json
   *  @param mixed $arrData
   *  @param int $statusCode
   *  @param array $headers
   *  @return JsonResponse
   */
  public static function responseJson(
    $arrData,
    int $statusCode = self::HTTP_OK,
    array $headers = []
  ): JsonResponse {
    $response = response()->json(
      $arrData,
      $statusCode
    )
      ->header('Accept', 'application/json')
      ->header('Content-Type', 'application/json')
      ->withHeaders($headers);
    return $response;
  }

  /**
   *  Status Ok
   *  @param string $msg
   *  @param int $statusCode
   *  @param array $headers
   *  @return JsonResponse
   */
  public static function statusOK(
    string $msg,
    int $statusCode = self::HTTP_OK,
    array $headers = []
  ): JsonResponse {

    $response = response()->json([
      'success' => true,
      'message' => $msg,
      'statusCode' => $statusCode,
    ], $statusCode)
      ->header('Accept', 'application/json')
      ->header('Content-Type', 'application/json')
      ->withHeaders($headers);
    return $response;
  }

  /**
   *  Status Data
   *  @param string ?$msg
   *  @param mixed $data
   *  @param int $statusCode
   *  @param array $headers
   *  @return JsonResponse
   */
  public static function statusData(
    ?string $msg = null,
    mixed $data = null,
    int $statusCode = self::HTTP_OK,
    array $headers = []
  ): JsonResponse {

    $responseArr = [
      'success' => true,
      'statusCode' => $statusCode,
    ];

    if ($msg !== null) {
      $responseArr['message'] = $msg;
    }
    if ($data !== null) {
      $responseArr['data'] = $data;
    }

    $response = response()->json($responseArr, $statusCode)
      ->header('Accept', 'application/json')
      ->header('Content-Type', 'application/json')
      ->withHeaders($headers);
    return $response;
  }

  /**
   *  Response FileStream PDF
   *  @param mixed $file
   *  @param int $statusCode
   *  @param array $headers
   */
  public static function responseFileStream(
    $file,
    int $statusCode = self::HTTP_OK,
    array $headers = []
  ) {
    return response($file, $statusCode)
      ->header('Accept', 'application/pdf')
      ->header('Content-Type', 'application/pdf')
      ->withHeaders($headers);
  }

  /**
   *  Status Error
   *  @param string $msg
   *  @param int $statusCode
   *  @param mixed $data
   *  @param mixed $error
   *  @return JsonResponse
   */
  public static function statusError(
    string $msg = 'solicitud enviada incompleta o en formato incorrecto',
    int $statusCode = self::HTTP_BAD_REQUEST,
    mixed $data = null,
    mixed $error = null,
  ): JsonResponse {

    $response = [
      'success' => false,
      'message' => $msg,
      'statusCode' => $statusCode,
    ];

    if ($data !== null) {
      $response['data'] = $data;
    }

    if ($error !== null && self::isDevelopment()) {
      $response['error'] = $error;
    }

    return response()->json($response, $statusCode)
      ->header('Accept', 'application/json')
      ->header('Content-Type', 'application/json');
  }

  /**
   * Status Error Server
   *  @param string $msg
   *  @param int $statusCode
   *  @param mixed $data
   *  @return JsonResponse
   */
  public static function statusErrorServer(
    string $msg = 'Error interno del servidor',
    int $statusCode = self::HTTP_INTERNAL_SERVER_ERROR,
    mixed $data = null
  ): JsonResponse {
    $response = [
      'success' => false,
      'message' => $msg,
      'statusCode' => $statusCode,
    ];

    if ($data !== null && self::isDevelopment()) {
      $response['data'] = $data;
    }

    return response()->json($response, $statusCode)
      ->header('Accept', 'application/json')
      ->header('Content-Type', 'application/json');
  }


  public static function isDevelopment(): bool
  {
    return config('app.env') === 'local' || config('app.env') === 'development';
  }
}
