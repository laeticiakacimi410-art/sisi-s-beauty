<?php


function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}


function redirect($url) {
    header("Location: $url");
    exit();
}


function isPost() {
    return $_SERVER["REQUEST_METHOD"] === "POST";
}


function isGet() {
    return $_SERVER["REQUEST_METHOD"] === "GET";
}


function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}


function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}


function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}


function requireLogin() {
    if (!isLoggedIn()) {
        redirect("/connexion.php");
    }
}