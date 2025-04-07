<?php

// Ruta al directorio Laravel - ajustada según la estructura del servidor
$laravelBasePath = __DIR__ . '/laravel';

try {
    // Define la ruta de la aplicación Laravel
    define('LARAVEL_START', microtime(true));

    // Registra el autoloader
    if (!file_exists($laravelBasePath . '/vendor/autoload.php')) {
        throw new Exception("No se pudo encontrar vendor/autoload.php en la ruta $laravelBasePath");
    }
    require $laravelBasePath . '/vendor/autoload.php';

    // Carga la aplicación
    if (!file_exists($laravelBasePath . '/bootstrap/app.php')) {
        throw new Exception("No se pudo encontrar bootstrap/app.php en la ruta $laravelBasePath");
    }
    $app = require_once $laravelBasePath . '/bootstrap/app.php';

    // Ejecuta la aplicación
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    $response->send();

    $kernel->terminate($request, $response);
} catch (Exception $e) {
    // Mostrar error con más detalles para diagnosticar problemas
    echo "<h1>Error al cargar la aplicación Laravel</h1>";
    echo "<p>Detalles: " . $e->getMessage() . "</p>";
    echo "<p>Archivo: " . $e->getFile() . " (Línea: " . $e->getLine() . ")</p>";
    
    // Para depuración, mostrar información adicional
    echo "<h2>Información de rutas:</h2>";
    echo "<p>Directorio base: " . __DIR__ . "</p>";
    echo "<p>Ruta de Laravel: " . $laravelBasePath . "</p>";
    echo "<p>Ruta de autoload.php: " . $laravelBasePath . '/vendor/autoload.php' . "</p>";
    echo "<p>Existe autoload.php: " . (file_exists($laravelBasePath . '/vendor/autoload.php') ? 'SÍ' : 'NO') . "</p>";
    
    echo "<p><a href='diagnostico.php'>Ejecutar diagnóstico completo</a></p>";
}
?>