<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use LaracraftTech\LaravelSchemaRules\Resolvers\SchemaRulesResolverMySql;
use LaracraftTech\LaravelSchemaRules\Resolvers\SchemaRulesResolverPgSql;
use LaracraftTech\LaravelSchemaRules\Resolvers\SchemaRulesResolverSqlite;
use LaracraftTech\LaravelSchemaRules\Contracts\SchemaRulesResolverInterface;
use LaracraftTech\LaravelSchemaRules\Exceptions\UnsupportedDbDriverException;


/**
 * Service provider para la configuración de la base de datos.
 *
 * Este servicio provider se encarga de realizar diversas configuraciones relacionadas con la base de datos en la aplicación.
 * Algunas de las funcionalidades incluyen:
 *
 * - Habilitar las reglas de esquema para las bases de datos MySQL y MariaDB.
 * - Registrar una macro personalizada llamada 'uuidCompat' para la clase Blueprint, que permite la creación de campos UUID en la base de datos compatibles con MySQL y PostgreSQL.
 * - Configurar los comandos de la base de datos para prohibir comandos destructivos en modo de producción.
 * - Evitar la carga predeterminada de modelos en modo de producción.
 *
 * Este servicio provider es necesario para que la aplicación funcione correctamente y debe ser registrado en el archivo de configuración de servicios.
 * 
 * 
 * @property \Illuminate\Foundation\Application $app
 */
class DatabaseServiceProvider extends ServiceProvider
{
  /**
   * Enables schema rules for MySQL and MariaDB databases.
   * This method is called during the register method of the service provider.
   * @return void
   */
  public function register(): void
  {
    $this->enableSchemaRules();
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    $this->registerMacros();
    $this->configureCommands();
    $this->preventLazyLoadingModels();
  }


  /**
   * Registre macros personalizados para la aplicación. 
   * 
   * Este método registra la macro 'uuidcompat' para el 
   * Clase de planos, que permite la creación de 
   * Campos UUID en la base de datos que son compatibles con 
   * Tanto MySQL como PostgreSQL. 
   * 
   * Esta macro solo está registrada cuando la aplicación es 
   * Correr en la consola, ya que no es necesario en el 
   * Entorno web. 
   */
  private function registerMacros(): void
  {
    if ($this->app->runningInConsole()) {
      Blueprint::macro('uuidCompat', function ($column) {
        /** @var \Illuminate\Database\Schema\Blueprint $this */
        return $this->addColumn('char', $column, ['length' => 36]);
      });
    }
  }

  private function configureCommands(): void
  {
    DB::prohibitDestructiveCommands(
      $this->app->isProduction()
    );
  }
  private function preventLazyLoadingModels(): void
  {
    Model::preventLazyLoading(! $this->app->isProduction());
  }

  private function enableSchemaRules()
  {
    $this->app->bind(
      SchemaRulesResolverInterface::class,
      function ($app, $parameters) {
        $driver = config("database.connections." . config('database.default') . ".driver");
        $class = match ($driver) {
          'sqlite' => SchemaRulesResolverSqlite::class,
          'mysql', 'mariadb' => SchemaRulesResolverMySql::class,
          'pgsql' => SchemaRulesResolverPgSql::class,
          default => throw new UnsupportedDbDriverException('This db driver is not supported: ' . $driver),
        };
        return new $class(...array_values($parameters));
      }
    );
  }
}
