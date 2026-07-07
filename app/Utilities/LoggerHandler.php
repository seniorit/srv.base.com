<?php

namespace App\Utilities;

use Illuminate\Support\Facades\Log;
use Throwable;

trait LoggerHandler
{
    /**
     * Guarda datos de depuración con la ubicación del código y sin interrumpir el flujo.
     * Funciona solo en entornos que no sean 'production'.
     *
     * @param  mixed  $data  Los datos a registrar (puede ser una variable, array, objeto, etc.).
     */
    public static function logTrace(mixed $data): void
    {
        if (config('app.env') === 'production') {
            return;
        }

        try {
            // 1. Obtener el rastro de la pila de llamadas
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $trace[1] ?? null;

            // 2. Extraer información del origen de la llamada
            $file = $caller['file'] ?? 'Desconocido';
            $line = $caller['line'] ?? 'Desconocido';
            $class = $caller['class'] ?? 'Desconocida';
            $function = $caller['function'] ?? 'Desconocida';

            // 3. Formatear la información para el log
            $context = [
                'ubicacion' => "{$class}::{$function} en {$file}:{$line}",
                'datos' => $data,
            ];

            // 4. Registrar en el log
            Log::info('[Debug Trace]', $context);
        } catch (Throwable $e) {
            // No hacemos nada para no interrumpir el flujo del proceso.
            // Opcionalmente, podrías registrar el error de logging en un canal separado.
        }
    }
}
