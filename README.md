# srv.base.com

Template base de Laravel 13 (PHP 8.3+) orientado a la construcción de **REST APIs**. Incluye un conjunto de implementaciones personalizadas y reutilizables pensadas para estandarizar la estructura, las respuestas HTTP, el manejo de errores, la autenticación, el acceso a base de datos, la importación/exportación de datos y los comandos Artisan de soporte.

> El proyecto se entrega con `routes/api.php` vacío y sin controladores de dominio: el template provee la "infraestructura" y la base de estilo. Los endpoints de negocio se construyen sobre esta base.

---

## Stack y paquetes principales

- **Framework:** Laravel `^13.8` (PHP `^8.3`).
- **Auth JWT:** `php-open-source-saver/jwt-auth` `^2.9`.
- **Validación de esquema:** `laracraft-tech/laravel-schema-rules` `^1.6` (con resolver personalizado para MariaDB).
- **Import / Export Excel:** `maatwebsite/excel` `^3.1` (stubs listos para modelos, colecciones y queries).
- **Tasa de cambio (BCV):** `laymont/venezuelan-foreign-exchanges` `^1.1`.
- **Data model helpers:** `zero-to-prod/data-model` `^81.18`.
- **Testing:** Pest `^4.7` + `pestphp/pest-plugin-laravel`.
- **Calidad de código:** Laravel Pint `^1.27`.
- **MCP:** `laravel/mcp` (stubs para `tool`, `resource`, `prompt`, `server` y `app-resource`).
- **Migraciones con paths personalizados:** `nscreed/laravel-migration-paths`.

---

## Convenciones de respuesta JSON

Todas las respuestas se construyen a través de `App\Http\HttpHandler\ResponseHttp` y las constantes de estado de `App\Http\HttpHandler\StatusHttp`. Esto garantiza una forma de respuesta única y predecible.

```jsonc
// statusOK (sin payload)
{
  "success": true,
  "message": "Operación exitosa",
  "statusCode": 200
}

// statusData (con payload)
{
  "success": true,
  "message": "Recurso encontrado",
  "statusCode": 200,
  "data": { "id": 1 }
}

// statusError (con detalle sólo en dev)
{
  "success": false,
  "message": "El registro ya existe",
  "statusCode": 409
  // "error": "<detalle>" solo si APP_ENV !== 'production'
}
```

Métodos disponibles en `ResponseHttp`:

- `responseJson($data, $statusCode, $headers)` — JSON crudo.
- `statusOK(string $msg, int $statusCode, array $headers)`.
- `statusData(?string $msg, mixed $data, int $statusCode, array $headers)`.
- `statusError(string $msg, int $statusCode, mixed $data, mixed $error)`.
- `statusErrorServer(string $msg, int $statusCode, mixed $data)`.
- `responseFileStream($file, $statusCode, $headers)` — streams PDF.
- `isDevelopment()` — helper para saber si se debe exponer el detalle de error.

> `StatusHttp` expone constantes para todos los códigos HTTP relevantes (1xx, 2xx, 3xx, 4xx, 5xx). **Usa siempre estas constantes** en lugar de literales numéricos.

---

## Manejo de errores

El template centraliza el renderizado de excepciones en `bootstrap/app.php`, que delega a `App\Exceptions\Handler::renderResponse($request, $e)`.

Tipos cubiertos de forma personalizada:

- `MethodNotAllowedException` / `MethodNotAllowedHttpException` → 405.
- `HttpResponseException` → se devuelve la respuesta que la excepción trae consigo.
- `Illuminate\Validation\ValidationException` → 400 (`Datos Invalidos`).
- `Illuminate\Validation\UnauthorizedException` → 403.
- `Symfony\…\UnauthorizedHttpException` → 401.
- `Symfony\…\NotFoundResourceException` → 404.
- `Illuminate\Routing\Exceptions\UrlGenerationException` → 406.
- `NotFoundHttpException` (rutas inexistentes) → 502.
- Detección de **errores de permisos del sistema de archivos** (palabras clave: `permission denied`, `failed to open stream`, `read-only file system`, `no such file or directory`) → 500 con mensaje de "Error de Permisos en el Servidor".
- Cualquier otro `Throwable` → 500 con `Error en la Solicitud` y el detalle sólo en entorno `local`/`development`.

Adicionalmente, para uso en **servicios** y **controladores**, se proveen:

- `App\Exceptions\ServiceHandlerTrait`:
  - `handleServiceThrow(Throwable $error): never` — registra log estructurado y **relanza** la excepción para que llegue al controlador.
  - `handleServiceThrowMsg(string $error, int $code = 500, ?Throwable $previous, string $msg): never` — lanza una `HttpResponseException` con un JSON ya formateado (si `$code` es un código HTTP válido 100–599) o una `Exception` genérica.
- `App\Exceptions\ControllerHandler::handler(Throwable $e, string $defaultError, int $defaultStatusCode)` — handler recomendado para usar en el `catch` de los controladores; mapea excepciones de base de datos, validación, JWT, Excel, modelo no encontrado, método no permitido, etc., hacia respuestas JSON estandarizadas.
- `App\Exceptions\MissingContentTypeException` — excepción específica para cuando llega una solicitud sin `Content-Type`.

### Errores de base de datos

`App\Exceptions\DatabaseHandler` (usado por `ControllerHandler`) traduce los códigos nativos de error a mensajes legibles en español y a un código HTTP coherente:

| Motor       | Duplicado | Llave foránea | Tabla inexistente | Columna desconocida | Otros               |
|-------------|-----------|---------------|-------------------|---------------------|---------------------|
| MySQL/MariaDB | 1062     | 1451 / 1452   | 1146              | 1054                | 422 (default)       |
| PostgreSQL  | 23505     | 23503         | 42P01             | 42703               | 422 (default)       |

Los duplicados y las llaves foráneas se devuelven como **409 Conflict**; el resto como **422 Unprocessable Entity**.

### Logging de depuración

`App\Utilities\LoggerHandler::logTrace(mixed $data)` registra en `Log::info('[Debug Trace]', …)` la ubicación exacta (clase::método en archivo:línea) y los datos recibidos. **No escribe nada en `production`**, por lo que puede dejarse en el código sin riesgo de fuga.

---

## Middleware incluidos

Registrados en `bootstrap/app.php`:

- **Globales** (se aplican a toda la app):
  - `App\Http\Middleware\ImplementCors` — valida el `Origin` contra `config('cors.allowed_origins')`. En producción bloquea orígenes no permitidos (excepto preflight `OPTIONS`). Reenvía la cabecera `ApplicationId` y aplica HSTS sobre HTTPS.
  - `Illuminate\Foundation\Http\Middleware\TrimStrings`.
  - `ValidatePostSize`.
  - `ConvertEmptyStringsToNull`.
- **Grupo `api`**:
  - `App\Http\Middleware\RegisterAppOrigin` — valida la cabecera `ApplicationId` contra una lista blanca de aplicaciones permitidas (`com.gescom.app`, `com.monarcait.app_express`, `com.monarcait.gescom_abm`, `com.gescom.app2`, `com.gescom.app3`). Si no coincide, responde 401.
  - `Illuminate\Routing\Middleware\SubstituteBindings`.
- **Aliases disponibles** (para aplicar con `->middleware('alias')` en rutas):
  - `append.content` → `AppendContentType` — si la petición es `POST`, `PUT` o `PATCH` y no trae `Content-Type`, lo setea a `application/json`.
  - `auth.headers` → `AuthJwtHeaders` — autentica la request contra el header `Authorization: Bearer …`, valida expiración, usuario y que `user.session_token` coincida con el token enviado. Inyecta `auth_user` en la request.
  - `artisan.execute` → `CanExecuteArtisanCommands` — valida que el comando a ejecutar esté en la lista blanca de `config('artisan-commands.allowed_commands')` y que el usuario sea `admin` cuando el comando lo requiera.

> Las aplicaciones cliente deben enviar la cabecera `ApplicationId` y un `Authorization: Bearer <jwt>` válido para los endpoints protegidos.

---

## Service Providers personalizados

Registrados desde `bootstrap/providers.php` (o `config/app.php`):

- `App\Providers\AppServiceProvider` — define el singleton de `ResponseHttp` y `Schema::defaultStringLength(255)`.
- `App\Providers\DatabaseServiceProvider`:
  - Habilita las reglas de esquema para SQLite, MySQL/MariaDB y PostgreSQL (`laracraft-tech/laravel-schema-rules`).
  - Registra la **macro** `Blueprint::uuidCompat($column)` para columnas UUID compatibles con MySQL/PostgreSQL.
  - `DB::prohibitDestructiveCommands($app->isProduction())` en producción.
  - `Model::preventLazyLoading(! $app->isProduction())` fuera de producción.
- `App\Providers\RateLimitServiceProvider` — rate limit `api`: 60 req/min por usuario autenticado o IP.
- `App\Providers\ExchangesServiceProvider` — binding de `Laymont\VenezuelanForeignExchanges\Services\BcvService` y publicación de `config/bcv.php`.
- `App\Providers\MigrationProvider` — extiende `JWTAuth\Providers\LaravelServiceProvider` y carga migraciones desde rutas personalizadas definidas en `config/migration-paths.php`.
- `App\Providers\HelperServiceProvider` — autocarga `app/Utilities/AppHelper.php` con helpers globales.

Resolver de esquema para MariaDB: `App\Resolvers\SchemaRulesResolverMariaDb` (extiende `SchemaRulesResolverMySql` ya que comparten códigos de error nativos).

---

## Helpers globales

Cargados automáticamente por `HelperServiceProvider`. Disponibles en cualquier parte:

| Helper              | Descripción                                                  |
|---------------------|--------------------------------------------------------------|
| `titleCase($s)`     | `Str::title`.                                                |
| `setString($v)`     | Convierte entre `UTF-8` e `ISO-8859-1`.                      |
| `strActive($v)`     | `1/true` → `Activo`, en otro caso `Inactivo` (en ISO-8859-1).|
| `strYesNo($v)`      | `1/true` → `SI`, en otro caso `NO`.                         |
| `dateParse($d,$f)`  | `Carbon::parse($d)->format($f)` (default `d/m/Y`).           |
| `floatParse($v)`    | `floatval(str_replace(',', '', $v))`.                        |
| `dateReport($f)`    | Fecha actual con formato (default `d/m/Y`).                  |
| `timeReport()`      | Hora actual `h:i a`.                                         |
| `formatNumber($n, $p, $d, $t)` | `number_format` con defaults.                       |
| `addLimit($v, $l, $e)` | `Str::limit` con elipsis.                                  |

---

## Comandos Artisan personalizados

Definidos en `app/Console/Commands/`:

- `php artisan make:json-body {request?}` — genera en consola la estructura JSON esperada a partir de un `FormRequest`. Acepta FQCN o ruta relativa (resuelve `App\Http\Requests\…`).
- `php artisan db:purge {--W|with-migrations}` — trunca todas las tablas de la base de datos. Con `--W`/`--with-migrations` incluye la tabla `migrations`.
- `php artisan db:destroy-tb {--with-migrations}` — equivalente a `db:purge` pero con `DROP TABLE` (soporta MySQL/MariaDB y PostgreSQL con `CASCADE`).

---

## Stubs disponibles

El template incluye stubs listos para acelerar la creación de clases (`stubs/`):

- **Import/Export Excel**: `export.model.stub`, `export.plain.stub`, `export.query-model.stub`, `export.query.stub`, `import.model.stub`, `import.collection.stub`.
- **MCP**: `mcp-tool.stub`, `mcp-resource.stub`, `mcp-prompt.stub`, `mcp-server.stub`, `mcp-app-resource.stub`, `mcp-app-resource.view.stub`.

Publica los stubs de Maatwebsite/Excel con `php artisan vendor:publish --tag=excel-config` y luego cópialos a `stubs/` para personalizar la generación de clases (configurable en `config/excel.php` → `exports.csv.writer` y similares).

---

## Estructura de carpetas relevante

```
app/
├── Console/Commands/        # Comandos Artisan personalizados
├── Exceptions/              # Handler, ControllerHandler, ServiceHandlerTrait, DatabaseHandler, …
├── Http/
│   ├── Controllers/         # Controller base (vacío, extender aquí)
│   ├── HttpHandler/         # ResponseHttp, StatusHttp
│   └── Middleware/          # CORS, Auth, Content-Type, Origin, Artisan
├── Providers/               # ServiceProviders personalizados
├── Resolvers/               # Resolver de schema rules para MariaDB
└── Utilities/               # AppHelper, LoggerHandler, JsonBodyGeneratorService

config/                      # app, auth, jwt, excel, schema-rules, migration-paths, bcv, …
routes/                      # api.php (vacío), web.php, console.php, ai.php (MCP)
stubs/                       # Stubs de export/import Excel y de MCP
database/                    # migrations, factories, seeders
tests/                       # Feature, Unit (Pest)
```

---

## Cómo empezar

1. Clonar / copiar el template y ajustar el bloque `name` y la configuración en `composer.json`.
2. Duplicar `.env.example` a `.env`, definir `APP_KEY`, `APP_URL` y `JWT_SECRET` (`php artisan key:generate` y `php artisan jwt:secret`).
3. Configurar la conexión a base de datos en `.env` y publicar la config de JWT si es necesario.
4. Levantar el servidor:

   ```bash
   composer install
   php artisan migrate
   php artisan serve
   ```

5. Definir las rutas de la API en `routes/api.php` (todas bajo el prefijo `api/`) usando los helpers `ResponseHttp`/`StatusHttp` y los middlewares disponibles.
6. Implementar los `FormRequest`, servicios y controladores apoyándose en:
   - `ServiceHandlerTrait` (servicios).
   - `ControllerHandler::handler(...)` (catch en controladores).
   - `ResponseHttp` (respuestas).
   - Stubs de `stubs/` para Excel / MCP.

---

## Convenciones de desarrollo

- **PHP 8.3+**, propiedades promovidas en constructores, tipos y retornos explícitos.
- **Sin comentarios inline** salvo lógica realmente compleja; preferir PHPDoc.
- **Pint** como formateador: `vendor/bin/pint --dirty --format agent`.
- **Pest 4** como framework de testing: `php artisan make:test --pest NombreTest` y `php artisan test --compact`.
- **No eliminar tests** sin aprobación explícita.
- **Reglas de estilo**:
  - Llaves en control de flujo siempre (`if ($x) { … }`).
  - `Schema::defaultStringLength(255)` ya aplicado.
  - Enums con keys en `TitleCase`.

---

## Licencia

MIT (heredada del skeleton oficial de Laravel).
