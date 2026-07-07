<?php

namespace App\Providers;

use NsCreed\MigrationPath\CustomMigrationPaths;
use PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider;

class MigrationProvider extends LaravelServiceProvider
{
  public function boot()
  {
    if ($this->app->runningInConsole()) {
      $this->publishes([config_path('migration-paths.php')]);
    }
  }

  public function register()
  {
    if ($this->app->runningInConsole()) {
      $this->mergeConfigFrom(config_path('migration-paths.php'), 'migration-paths');
      $customMigrationPaths = new CustomMigrationPaths(config('migration-paths'));
      $this->loadMigrationsFrom($customMigrationPaths->getRegisteredPaths());
    }
  }
}
