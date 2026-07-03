<?php
class Router {
    private $routes = [];

    public function add($method, $path, $callback) {
        // Sustituye temporalmente los parámetros {id} o {doc} por marcadores seguros (texto plano, sin metacaracteres de regex)
        $paramNames = [];
        $marked = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '@@PARAM' . (count($paramNames) - 1) . '@@';
        }, $path);

        // Escapa el resto de la ruta como texto literal (incluyendo puntos como en ".html")
        $escaped = preg_quote($marked, '#');

        // Reinserta los marcadores como capturas nombradas de regex
        $pattern = preg_replace_callback('/@@PARAM(\d+)@@/', function($m) use ($paramNames) {
            return '(?P<' . $paramNames[(int)$m[1]] . '>[a-zA-Z0-9_\-\.]+)';
        }, $escaped);

        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'callback' => $callback
        ];
    }

    public function dispatch($requestMethod, $requestUri) {
        $path = parse_url($requestUri, PHP_URL_PATH);
        
        // Elimina la barra final excepto en la raíz
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }

        // Maneja el subdirectorio SIGA en XAMPP (comparación insensible a mayúsculas,
        // ya que Windows/Apache sirve la carpeta sin importar el uso de mayúsculas)
        $basePath = '/SIGA';
        if (stripos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        if (empty($path)) {
            $path = '/';
        }

        $requestMethod = strtoupper($requestMethod);

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && preg_match($route['pattern'], $path, $matches)) {
                // Filtra los parámetros de las coincidencias (solo claves de tipo string nombradas)
                $params = array_filter($matches, function($key) {
                    return is_string($key);
                }, ARRAY_FILTER_USE_KEY);

                list($controllerClass, $methodName) = explode('@', $route['callback']);
                
                $controllerFile = __DIR__ . '/Controllers/' . $controllerClass . '.php';
                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        if (method_exists($controller, $methodName)) {
                            // Ejecuta la acción
                            $controller->$methodName($params);
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}
