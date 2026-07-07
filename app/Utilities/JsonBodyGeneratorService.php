<?php

namespace App\Utilities;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class JsonBodyGeneratorService
{
  /**
   * Genera la estructura del cuerpo JSON a partir de las reglas del FormRequest
   * intentando evitar la ejecución de métodos internos que requieran un ciclo HTTP.
   *
   * @param string $requestClass El FQCN (Fully Qualified Class Name) del FormRequest.
   * @return array
   * @throws \ReflectionException|\InvalidArgumentException
   */
  public function generate(string $requestClass): array
  {
    // === CLAVE: INSTANCIACIÓN BÁSICA ===
    // Usamos la instanciación simple para evitar que Laravel resuelva dependencias
    // de HTTP o llame a métodos complejos como 'authorize()'.
    try {
      $requestInstance = new $requestClass();
    } catch (\Throwable $e) {
      throw new \InvalidArgumentException(
        "No se pudo instanciar la clase '$requestClass'. Error: " . $e->getMessage(),
        0,
        $e
      );
    }

    if (!method_exists($requestInstance, 'rules')) {
      throw new \InvalidArgumentException("La clase '$requestClass' no implementa el método 'rules()'.");
    }

    // 1. Obtener las reglas sin activar el ciclo de validación de Laravel
    try {
      $rules = $requestInstance->rules();
    } catch (\BadMethodCallException $e) {
      // Capturar errores de métodos dinámicos que no existen en el contexto de CLI
      throw new \InvalidArgumentException(
        "Error al obtener las reglas del FormRequest '$requestClass'. " .
          "Posiblemente el método rules() llama a un método que no existe en el contexto de CLI. " .
          "Error original: " . $e->getMessage(),
        0,
        $e
      );
    }

    // 2. Procesar las reglas para generar el cuerpo JSON.
    $jsonBody = [];

    foreach ($rules as $field => $fieldRules) {
      $this->processFieldRules($jsonBody, $field, $fieldRules);
    }

    return $jsonBody;
  }

  /**
   * Procesa las reglas de un campo (incluyendo campos anidados).
   */
  protected function processFieldRules(array &$target, string $field, mixed $rules): void
  {
    // Convertir array de reglas a cadena si es necesario
    $rulesString = is_array($rules) ? implode('|', $this->normalizeRules($rules)) : $rules;
    $defaultValue = $this->mapRuleToValue($rulesString, $field);

    // Caso 1: Array anidado (ej: 'items.*.name')
    if (Str::contains($field, '.*')) {
      $rootField = Str::before($field, '.*');
      $subField = Str::after($field, '.*.');

      if (!isset($target[$rootField])) {
        $target[$rootField] = [];
      }

      // Si es el campo raíz 'items.*', simplemente creamos un array vacío de ejemplo
      if ($subField === '') {
        $target[$rootField] = ['placeholder_item'];
        return;
      }

      // Creamos un objeto de ejemplo dentro del array principal
      if (!isset($target[$rootField][0])) {
        $target[$rootField] = [[]];
      }

      // Asignamos el valor anidado dentro del primer elemento del array
      Arr::set($target[$rootField][0], $subField, $defaultValue);
      return;
    }

    // Caso 2: Objeto anidado simple (ej: 'address.street')
    if (Str::contains($field, '.')) {
      Arr::set($target, $field, $defaultValue);
      return;
    }

    // Caso 3: Campo plano
    $target[$field] = $defaultValue;
  }

  /**
   * Mapea la regla de validación al tipo de valor nativo de JSON.
   */

  protected function mapRuleToValue(string $rulesString, string $field): mixed
  {
    $rules = explode('|', $rulesString);
    $type = 'string'; // Default type

    // Prioridad de tipos: boolean > integer > numeric > array > date > string
    foreach ($rules as $rule) {
      $ruleName = explode(':', $rule)[0];

      if (in_array($ruleName, ['boolean', 'bool'])) {
        return true;
      }

      if (in_array($ruleName, ['integer', 'int'])) {
        $type = 'integer';
      }

      if (in_array($ruleName, ['numeric', 'float', 'decimal']) && $type !== 'integer') {
        $type = 'numeric';
      }

      if ($ruleName === 'array') {
        return [];
      }

      if (in_array($ruleName, ['date', 'date_format'])) {
        $type = 'date';
      }

      if ($ruleName === 'email') {
        $type = 'email';
      }
    }

    return match ($type) {
      'integer' => 100,
      'numeric' => 100.50,
      'date' => '2025-01-01',
      'email' => Str::snake($field) . '@example.com',
      default => Str::snake($field) . '_value',
    };
  }

  /**
   * Normaliza un array de reglas a una cadena de strings para el mapeo.
   */
  protected function normalizeRules(array $rules): array
  {
    return array_map(function ($rule) {
      // Ignoramos objetos y closures, solo procesamos strings
      if (is_object($rule) || is_callable($rule)) {
        return '';
      }
      return (string) $rule;
    }, $rules);
  }
}
