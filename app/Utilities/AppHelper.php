<?php

use Carbon\Carbon;
use Illuminate\Support\Str;

if (!function_exists('titleCase')) {
  /**
   * Return Title Case `Str::title`
   * @param string $stringValue
   * @return string
   */
  function titleCase(string $stringValue): string
  {
    return Str::title($stringValue);
  }
}

if (!function_exists('setString')) {
  /**
   * UTF8 Encoding Tipe
   *
   * @param mixed $keyData
   * @param string $encodingType
   * @return void
   */
  function setString(mixed $keyData, string $encodingType = 'UTF-8')
  {
    $encoding = mb_convert_encoding($keyData, 'ISO-8859-1', $encodingType);
    return $encoding;
  }
}

if (!function_exists('strActive')) {
  /**
   * Transform Value Boolean
   *
   * @param [string|number] $value
   * @return string Activo | Inactivo
   */
  function strActive(mixed $value)
  {
    if ($value == true || $value == 1 || $value == '1') {
      return setString('Activo');
    } else {
      return setString('Inactivo');
    }
  }
}

if (!function_exists('strYesNo')) {
  /**
   * Transform Value Boolean
   *
   * @param type[string|number] $value
   * @return string Activo | Inactivo
   */
  function strYesNo(mixed $value)
  {
    if ($value == true || $value == 1 || $value == '1') {
      return setString('SI');
    } else {
      return setString('NO');
    }
  }
}

if (!function_exists('dateParse')) {
  /**
   * Parse Date Format d/m/Y With Carbon
   * @param string $date
   * @return string
   */
  function dateParse($date, $formatDate = 'd/m/Y'): string
  {
    return Carbon::parse($date)->format($formatDate);
  }
}

if (!function_exists('floatParse')) {
  /**
   * Parse float value
   * @param string $value
   * @return float
   */
  function floatParse($value): float
  {
    return floatval(str_replace(',', '', $value));
  }
}

if (!function_exists('dateReport')) {

  /**
   * Retur Date Report
   * @return string
   */
  function dateReport($formatDate = 'd/m/Y'): string
  {
    return Carbon::parse(now())->format($formatDate);
  }
}

if (!function_exists('timeReport')) {
  function timeReport()
  {
    return Carbon::parse(now())->format('h:i a');
  }
}

if (!function_exists('formatNumber')) {
  /**
   * Funcion para Formatear valores Numericos
   * @param cantidad: valor numerico
   * @param places: cantidad de decimales
   */
  function formatNumber($cantidad = 0, mixed $places = 2, mixed $decimals = '.', mixed $thousands = ',')
  {
    $cantidad = number_format($cantidad, $places, $decimals, $thousands);
    return $cantidad;
  }
}

if (!function_exists('addLimit')) {
  /**
   * Agregar ellipsis a un string
   *
   * @param string $value
   * @param integer $limit
   * @param string $end
   * @return string
   */
  function addLimit($value, $limit, $end = '...')
  {
    return Str::limit(value: $value, limit: $limit, end: $end);
  }
}
