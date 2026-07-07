<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Utilities\JsonBodyGeneratorService;

// Asegúrate de que este namespace coincida con la ubicación real de tu clase.
class GenerateJsonBodyCommand extends Command
{
  /**
   * Nombre y firma del comando Artisan.
   *
   * @var string
   */
  protected $signature = 'make:json-body {request? : El nombre completo de la clase FormRequest}';

  /**
   * Descripción del comando de consola.
   *
   * @var string
   */
  protected $description = 'Genera la estructura del cuerpo JSON a partir de un FormRequest de Laravel.';

  /**
   * Ejecuta el comando de consola.
   *
   * @param JsonBodyGeneratorService $generatorService Servicio para generar el JSON.
   */
  public function handle(JsonBodyGeneratorService $generatorService)
  {
    $requestClass = $this->argument('request');

    if (!$requestClass) {
      $requestClass = $this->ask('Por favor, ingresa el nombre de la clase FormRequest (ej: Inventory\\Category\\CreateRequest)');
    }

    // 1. Normalizar separadores y agregar prefijo si es necesario (lógica revisada y más flexible)
    $requestClass = str_replace('/', '\\', $requestClass);

    if (!Str::startsWith($requestClass, ['\\', 'App\\']) && !class_exists($requestClass)) {
      $prefixedClass = 'App\\Http\\Requests\\' . $requestClass;
      if (class_exists($prefixedClass)) {
        $requestClass = $prefixedClass;
      }
    }

    if (!class_exists($requestClass)) {
      $this->error("La clase FormRequest [$requestClass] no existe. ¡Verifica la ruta y el namespace!");
      return Command::FAILURE;
    }

    $this->info("Procesando FormRequest: $requestClass...");

    try {
      // Llamar al servicio
      $jsonBodyArray = $generatorService->generate($requestClass);

      // Imprimir el JSON formateado
      $jsonOutput = json_encode($jsonBodyArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

      $this->line("\n" . $jsonOutput);
      $this->info("\n✅ ¡JSON Body generado con éxito!");

      return Command::SUCCESS;
    } catch (\Throwable $e) {
      // --- BLOQUE CATCH MEJORADO PARA DEPURACIÓN ---
      $this->error("--------------------------------------------------");
      $this->error("⚠️ Ocurrió un error crítico al generar el JSON.");
      $this->error("Clase de Request Intentada: [$requestClass]");
      $this->error("--------------------------------------------------");
      $this->error("Tipo de Excepción: " . get_class($e));
      $this->error("Mensaje: " . $e->getMessage());
      $this->line("Archivo: " . $e->getFile() . " en línea " . $e->getLine());
      $this->line("\n--- Trazabilidad (Stack Trace) para Depuración ---");
      $this->line($e->getTraceAsString());
      $this->error("--------------------------------------------------");

      return Command::FAILURE;
    }
  }
}
