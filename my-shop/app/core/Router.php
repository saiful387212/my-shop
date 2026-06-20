<?php
// ============================================================
// FILE: my-shop/app/core/Router.php
// PURPOSE: Simple routing system
// ============================================================

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not allowed.');
}

class Router {
    private $routes = [];
    
    /**
     * Add a route
     */
    public function add($uri, $controller, $method = 'GET') {
        $this->routes[] = [
            'uri' => $uri,
            'controller' => $controller,
            'method' => $method
        ];
    }
    
    /**
     * Dispatch the request
     */
    public function dispatch($uri) {
        // Remove query string
        $uri = strtok($uri, '?');
        
        foreach ($this->routes as $route) {
            if ($route['uri'] === $uri && $_SERVER['REQUEST_METHOD'] === $route['method']) {
                // Load the controller
                $controllerFile = ABSPATH . 'app/controllers/' . $route['controller'] . '.php';
                
                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                    $controllerClass = str_replace('Controller', '', $route['controller']);
                    $controller = new $controllerClass();
                    $controller->index();
                    return;
                }
            }
        }
        
        // 404 Not Found
        http_response_code(404);
        echo '404 - Page not found';
    }
}
?>