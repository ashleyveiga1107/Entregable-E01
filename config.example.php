<?php
$host = "127.0.0.1";
$puerto = "3306";
$base = "restaurante_api";
$usuario = "root";
$password = "CAMBIAR_POR_TU_PASSWORD";

try {
    $conexion = new PDO(
        "mysql:host=$host;port=$puerto;dbname=$base;charset=utf8mb4",
        $usuario,
        $password
    );

    $conexion->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("No se pudo conectar a la base de datos.");
}
