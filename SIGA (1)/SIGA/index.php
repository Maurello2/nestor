<?php
// Inicia la gestión de la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configura la zona horaria
date_default_timezone_set('America/Bogota');

// Manejo centralizado de excepciones y errores
set_exception_handler(function($e) {
    error_log('Excepción no controlada: ' . $e->getMessage());
    http_response_code(500);

    $isApi = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isApi || $isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudo completar la solicitud. Intente más tarde.'
        ]);
    } else {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error - SIGA</title>'
            . '<style>body{font-family:Arial,sans-serif;background:#1a1a2e;color:#eee;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center}'
            . '.box{max-width:480px;padding:30px}h2{color:#e74c3c}a{color:#0D8ABC}</style></head>'
            . '<body><div class="box"><h2>No se pudo cargar la página</h2>'
            . '<p>Ocurrió un problema de conexión con el servidor. Verifique que el servicio de base de datos esté activo e intente de nuevo.</p>'
            . '<a href="./login">Volver al Inicio de Sesión</a></div></body></html>';
    }
    exit;
});

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Importa el Router
require_once __DIR__ . '/Router.php';

$router = new Router();

// Rutas de páginas (Vistas)
$router->add('GET', '/', 'AuthController@showHome');
$router->add('GET', '/login', 'AuthController@showLogin');
$router->add('GET', '/recovery', 'AuthController@showRecovery');
$router->add('GET', '/reset-password', 'AuthController@showResetPassword');
$router->add('GET', '/dashboard', 'AuthController@showDashboard');
$router->add('GET', '/logout', 'AuthController@logout');

// Fragmentos de páginas (carga por AJAX)
$router->add('GET', '/{page}.html', 'AuthController@servePage');

// Rutas de la API de autenticación
$router->add('POST', '/api/login', 'AuthController@login');
$router->add('POST', '/api/recovery', 'AuthController@recovery');
$router->add('POST', '/api/reset-password', 'AuthController@resetPassword');

// Rutas CRUD de la API de Usuarios
$router->add('GET', '/api/usuarios', 'UsuarioController@index');
$router->add('POST', '/api/usuarios', 'UsuarioController@create');
$router->add('POST', '/api/usuarios/{doc}', 'UsuarioController@update');
$router->add('DELETE', '/api/usuarios/{doc}', 'UsuarioController@delete');
$router->add('POST', '/api/usuarios/{doc}/status', 'UsuarioController@toggleStatus');

// Rutas CRUD de la API de Áreas
$router->add('GET', '/api/areas', 'AreaController@index');
$router->add('GET', '/api/areas/{id}', 'AreaController@show');
$router->add('POST', '/api/areas', 'AreaController@create');
$router->add('PUT', '/api/areas/{id}', 'AreaController@update');
$router->add('DELETE', '/api/areas/{id}', 'AreaController@delete');

// Rutas CRUD de la API de Materias
$router->add('GET', '/api/materias', 'MateriaController@index');
$router->add('GET', '/api/materias/{id}', 'MateriaController@show');
$router->add('POST', '/api/materias', 'MateriaController@create');
$router->add('PUT', '/api/materias/{id}', 'MateriaController@update');
$router->add('DELETE', '/api/materias/{id}', 'MateriaController@delete');

// Rutas CRUD de la API de Logros
$router->add('GET', '/api/logros', 'LogroController@index');
$router->add('GET', '/api/logros/{id}', 'LogroController@show');
$router->add('POST', '/api/logros', 'LogroController@create');
$router->add('PUT', '/api/logros/{id}', 'LogroController@update');
$router->add('DELETE', '/api/logros/{id}', 'LogroController@delete');

// Rutas CRUD de la API de Matrículas
$router->add('GET', '/api/matriculas', 'MatriculaController@index');
$router->add('GET', '/api/matriculas/{id}', 'MatriculaController@show');
$router->add('POST', '/api/matriculas', 'MatriculaController@create');
$router->add('PUT', '/api/matriculas/{id}', 'MatriculaController@update');
$router->add('DELETE', '/api/matriculas/{id}', 'MatriculaController@delete');

// Despacha la solicitud
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

if (!$router->dispatch($requestMethod, $requestUri)) {
    // Ruta no encontrada (404)
    http_response_code(404);
    echo "<h3>Error 404: Página no encontrada</h3><p>La ruta especificada no existe.</p><a href='./login'>Volver al Inicio</a>";
}
