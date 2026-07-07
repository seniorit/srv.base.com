<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\HttpHandler\StatusHttp;
use App\Http\HttpHandler\ResponseHttp;
use Symfony\Component\HttpFoundation\Response;

class CanExecuteArtisanCommands
{
  /**
   * Handle an incoming request.
   *
   * @return mixed
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Get authenticated user
    $user = $request->input('auth_user');

    if (! $user) {
      return ResponseHttp::statusError(
        msg: 'Usuario no autenticado',
        statusCode: StatusHttp::HTTP_UNAUTHORIZED,
        error: 'Authentication required'
      );
    }

    // Get command from request
    $command = $request->input('command');

    if (! $command) {
      return ResponseHttp::statusError(
        msg: 'Comando no especificado',
        statusCode: StatusHttp::HTTP_BAD_REQUEST,
        error: 'Command is required'
      );
    }

    // Check if command is allowed
    $allowedCommands = config('artisan-commands.allowed_commands', []);

    if (! in_array($command, $allowedCommands)) {
      return ResponseHttp::statusError(
        msg: 'Comando no permitido',
        statusCode: StatusHttp::HTTP_FORBIDDEN,
        error: "Command '{$command}' is not allowed"
      );
    }

    // Check command-specific permissions
    $commandPermissions = config('artisan-commands.command_permissions', []);
    $requiredPermission = $commandPermissions[$command] ?? $commandPermissions['default'] ?? 'user';

    // For now, we'll check if user has admin role/permission
    // You can customize this based on your permission system
    if ($requiredPermission === 'admin') {
      // Check if user is admin
      // Adjust this based on your user model structure
      $isAdmin = $this->isUserAdmin($user);

      if (! $isAdmin) {
        return ResponseHttp::statusError(
          msg: 'No tienes permisos para ejecutar este comando',
          statusCode: StatusHttp::HTTP_FORBIDDEN,
          error: 'Insufficient permissions'
        );
      }
    }

    return $next($request);
  }

  /**
   * Check if user is an administrator.
   * Customize this method based on your user model and permission system.
   *
   * You can implement this by:
   * 1. Checking a specific access_key permission (recommended)
   * 2. Checking if user's AccessProfile has a specific department/name
   * 3. Checking a custom field on the User or Profile model
   *
   * Example using access_key:
   * ```php
   * $sessionService = app(\App\Http\Services\Auth\SessionService::class);
   * return $sessionService->checkAccessPermission('artisan:execute');
   * ```
   *
   * Example using AccessProfile:
   * ```php
   * return $user->profile?->accessProfile?->department === 'admin';
   * ```
   *
   * @param  mixed  $user
   */
  private function isUserAdmin($user): bool
  {
    // TODO: Implement admin check based on your permission system
    // For now, return false as a safe default
    // This prevents any command execution until properly configured

    // Example implementation using access_key (uncomment and adjust):
    // try {
    //     $sessionService = app(\App\Http\Services\Auth\SessionService::class);
    //     return $sessionService->checkAccessPermission('artisan:execute');
    // } catch (\Throwable $e) {
    //     return false;
    // }

    // Example implementation using AccessProfile department:
    // return $user->profile?->accessProfile?->department === 'admin';

    return false;
  }
}
