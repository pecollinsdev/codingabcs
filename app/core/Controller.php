<?php
namespace App\Core;

/**
 * Controller class is the parent class for all controllers.
 *
 * This class provides common methods and properties that are shared
 * across all controllers. It is responsible for loading views and models,
 * and redirecting to other pages.
 */
class Controller {
    // Load a view
    protected function view($view, $data = []) {
        $viewPath = "../app/views/{$view}.php";
    
        if (file_exists($viewPath)) {
            extract($data);
            require_once $viewPath;
        } else {
            die("❌ View file not found: " . htmlspecialchars($viewPath));
        }
    }    
  

    // Load a model
    protected function model($model) {
        $modelPath = "../app/models/{$model}.php";

        if (file_exists($modelPath)) {
            require_once $modelPath;
            $fullClassName = "App\\Models\\{$model}";
            return new $fullClassName();
        } else {
            die("Model '{$model}' not found.");
        }
    }

    // Redirect to another page
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
}
