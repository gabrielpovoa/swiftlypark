<?php
namespace Core;

class Controller {
    protected function setView($view, $data = []) {
        extract($data);
        require __DIR__ . "/../app/views/{$view}.php";
    }
}
