<?php
require_once __DIR__ . '/configuracion.php';

/** Devuelve una única conexión PDO con consultas preparadas reales. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('<h2>No se pudo conectar con la base de datos.</h2><p>Revisa que MySQL esté encendido en XAMPP, que hayas importado <code>base_datos/impulsa_bolivia.sql</code> y los datos de <code>config/configuracion.php</code>.</p>');
        }
    }
    return $pdo;
}
