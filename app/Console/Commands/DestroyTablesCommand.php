<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DestroyTablesCommand extends Command
{
  /**
   * El nombre y la firma del comando de consola.
   * Añadimos el flag opcional {--W|with-migrations}.
   *
   * @var string
   */
  protected $signature = 'db:destroy-tb {--with-migrations : Incluye la tabla de migraciones en la eliminación.}';

  /**
   * La descripción del comando de consola.
   *
   * @var string
   */
  protected $description = 'Elimina todas las tablas de la base de datos (DROP). Usa el flag --with-migrations para incluir la tabla de migraciones.';

  /**
   * Ejecuta el comando de consola.
   *
   * @return int
   */
  public function handle()
  {
    // 1. Verificar si el usuario incluyó la opción para purgar migraciones.
    $purgeMigrations = $this->option('with-migrations');
    $this->info('Iniciando la Eliminación de Tablas (DROP)...');

    // Detectar el motor de base de datos
    $driver = DB::getDriverName();
    $isPostgres = $driver === 'pgsql';
    $isMySQL = in_array($driver, ['mysql', 'mariadb']);

    $this->info("Motor de base de datos detectado: " . strtoupper($driver));

    // Mensaje específico dependiendo de la opción.
    if ($purgeMigrations) {
      $this->warn('¡ADVERTENCIA! Se eliminará la tabla "migrations" y todas las demás.');
      sleep(2);
    } else {
      $this->info('La tabla "migrations" será omitida. Usa --with-migrations para incluirla.');
      sleep(1);
    }

    // 2. Desactivar las revisiones de claves foráneas (solo para MySQL/MariaDB).
    if ($isMySQL) {
      DB::statement("SET foreign_key_checks=0");
    }

    // 3. Obtener TODAS las tablas de la base de datos.
    if ($isPostgres) {
      // En PostgreSQL, usamos 'public' como schema por defecto
      $tables = DB::select("SELECT table_name
              FROM information_schema.tables
              WHERE table_schema = 'public'
              AND table_type = 'BASE TABLE'");
    } else {
      // En MySQL/MariaDB, usamos el nombre de la base de datos
      $databaseName = DB::getDatabaseName();
      $tables = DB::select("SELECT table_name
              FROM information_schema.tables
              WHERE table_schema = ?", [$databaseName]);
    }

    if (empty($tables)) {
      $this->info("No Existen tablas que eliminar");

      // Reactivar foreign_key_checks si es MySQL
      if ($isMySQL) {
        DB::statement("SET foreign_key_checks=1");
      }
      return Command::FAILURE;
    }

    $destroyCount = 0;

    foreach ($tables as $table) {
      $name = $table->table_name;

      // 4. Implementar la lógica condicional de eliminación.
      if ($name === 'migrations' && !$purgeMigrations) {
        // Si es la tabla de migraciones Y el flag NO está presente, la saltamos.
        $this->info("Saltando: $name");
        continue;
      }

      // 5. Eliminar la tabla según el motor de base de datos.
      $this->warn("Eliminando tabla: $name");

      if ($isPostgres) {
        // En PostgreSQL, usamos CASCADE para manejar dependencias automáticamente
        DB::statement("DROP TABLE IF EXISTS \"$name\" CASCADE");
      } else {
        // En MySQL/MariaDB, usamos Schema::dropIfExists (foreign_key_checks ya está en 0)
        Schema::dropIfExists($name);
      }
      $destroyCount++;
    }

    // 6. Reactivar las revisiones de claves foráneas (solo para MySQL/MariaDB).
    if ($isMySQL) {
      DB::statement("SET foreign_key_checks=1");
    }

    $this->info("¡Éxito! Base de datos vaciada. ($destroyCount tablas eliminadas)");
    return Command::SUCCESS;
  }
}
