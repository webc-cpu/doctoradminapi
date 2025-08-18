<?php
loadEnv(ROOT_PATH . 'functions.php');
loadEnv(ROOT_PATH . '.env');

require_once ROOT_PATH . 'libs/php-jwt/src/JWTExceptionWithPayloadInterface.php';
require_once ROOT_PATH . 'libs/php-jwt/src/ExpiredException.php';
require_once ROOT_PATH . 'libs/php-jwt/src/JWT.php';
require_once ROOT_PATH . 'libs/php-jwt/src/Key.php';
require_once ROOT_PATH . 'libs/php-jwt/src/SignatureInvalidException.php';
require_once ROOT_PATH . 'libs/php-jwt/src/BeforeValidException.php';


use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwt_secret = $_ENV['JWT_SECRET'] ?? 'fallback_secret_key';

function generateJWT($data, $expiry = 3600) {
    global $jwt_secret;
    $payload = [
        "iss" => "doctoradmin_api",
        "iat" => time(),
        "exp" => time() + $expiry,
        "data" => $data
    ];
    return JWT::encode($payload, $jwt_secret, 'HS256');
}

function validateJWT($token) {
    global $jwt_secret;
    return JWT::decode($token, new Key($jwt_secret, 'HS256'));
}
