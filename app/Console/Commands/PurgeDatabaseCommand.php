<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeDatabaseCommand extends Command
{
  /**
   * El nombre y la firma del comando de consola.
   * Añadimos el flag opcional {--W|with-migrations}.
   *
   * @var string
   */
  protected $signature = 'db:purge {--W|with-migrations : Incluye la tabla de migraciones en la purga.}';
  /**
   * La descripción del comando de consola.
   *
   * @var string
   */
  protected $description = 'Trunca todas las tablas. Usa el flag --with-migrations para incluir la tabla de migraciones.';

  /**
   * Ejecuta el comando de consola.
   *
   * @return int
   */
  public function handle()
  {
    // 1. Verificar si el usuario incluyó la opción para purgar migraciones.
    $purgeMigrations = $this->option('with-migrations');

    $this->info('Iniciando purga de la base de datos...');

    // Mensaje específico dependiendo de la opción.
    if ($purgeMigrations) {
      $this->warn('¡Advertencia! Se purgará la tabla "migrations" y todas las demás.');
      sleep(2);
    } else {
      $this->info('La tabla "migrations" será omitida. Usa --with-migrations para incluirla.');
      sleep(1);
    }

    // 2. Desactivar las revisiones de claves foráneas.
    DB::statement("SET foreign_key_checks=0");
    $databaseName = DB::getDatabaseName();

    // 3. Obtener TODAS las tablas de la base de datos.
    // Ya no filtramos 'migrations' aquí, la lógica de exclusión va en el bucle.
    $tables = DB::select("SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = ?", [$databaseName]);

    $truncatedCount = 0;

    foreach ($tables as $table) {
      $name = $table->table_name;

      // 4. Implementar la lógica condicional de truncado.
      if ($name === 'migrations' && !$purgeMigrations) {
        // Si es la tabla de migraciones Y el flag NO está presente, la saltamos.
        $this->info("Saltando: $name");
        continue;
      }

      // Truncar la tabla (incluyendo migraciones si $purgeMigrations es true).
      $this->warn("Truncando tabla: $name");
      DB::table($name)->truncate();
      $truncatedCount++;
    }

    // 5. Reactivar las revisiones de claves foráneas.
    DB::statement("SET foreign_key_checks=1");

    $this->info("Base de datos purgada exitosamente. ($truncatedCount tablas afectadas)");
    return Command::SUCCESS;
  }
}



// namespace App\Console\Commands;

// use Illuminate\Console\Command;
// use Illuminate\Support\Facades\DB;


// class PurgeDatabaseCommand extends Command
// {
//   protected $signature = 'db:purge';
//   protected $description = 'Trunca todas las tablas excepto las migraciones.';
//   public function handle()
//   {
//     $this->info('Iniciando purga de la base de datos...');

//     sleep(3);

//     DB::statement("SET foreign_key_checks=0");
//     $databaseName = DB::getDatabaseName();

//     $tables = DB::select("SELECT table_name 
//         FROM information_schema.tables 
//         WHERE table_schema = ? 
//         AND table_name != 'migrations'", [$databaseName]);

//     foreach ($tables as $table) {
//       $name = $table->table_name;
//       $this->warn("Truncando tabla: $name");
//       DB::table($name)->truncate();

//       // Truncar la tabla de migraciones mediante un parametro adicional: add-mig
//       if ($name == 'migrations') {
//         $this->warn("Truncando tabla: $name");
//         DB::table($name)->truncate();
//       }
//     }

//     DB::statement("SET foreign_key_checks=1");

//     $this->info('Base de datos purgada exitosamente.');
//     return Command::SUCCESS;
//   }
// }
