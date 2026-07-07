<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Gescom Application Settings
    |--------------------------------------------------------------------------
    */
  'allowed_application_id' => env('ALLOWED_APPLICATION_ID', 'com.gescom.app'),
  'version_release' => env('VERSION_RELEASE', '1.0.0'),
  'front_url' => env('FRONTEND_URL', 'http://localhost:5080'),
  'builder' => env('APP_BUILDER', 'GESCOM'),
  'socket_url' => env('SOCKET_URL', 'http://localhost:3000'),
  'storage_user_images' => env('STORAGE_USER_IMAGES', 'images/users'),
  'storage_item_images' => env('STORAGE_ITEM_IMAGES', 'images/items'),
  'storage_envio_images' => env('STORAGE_ENVIO_IMAGES', 'images/envios'),
];
