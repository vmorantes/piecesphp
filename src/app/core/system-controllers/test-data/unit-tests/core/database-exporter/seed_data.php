<?php

use PiecesPHP\Core\Database\Export\Exporter;

/**
 * seed_data.php
 * 
 * Lógica para inicializar la tabla de prueba y sus datos Pro para la suite de pruebas unitarias.
 */

return function (\PDO $db, string $testTable) {

    // 1. Eliminar y crear tabla de prueba
    $db->exec("DROP TABLE IF EXISTS {$testTable}");
    $db->exec("CREATE TABLE {$testTable} (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50),
        email VARCHAR(100),
        bio TEXT,
        avatar_blob BLOB,
        secret_key VARCHAR(50)
    )");

    // 2. Insertar datos pro (incluyendo binarios y sensibles)
    $binaryData = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01"; // PNG 1x1
    $stmt = $db->prepare("INSERT INTO {$testTable} (username, email, bio, avatar_blob, secret_key) VALUES (?, ?, ?, ?, ?)");
    
    // Fila 1: Pedro (Datos Pro para transformaciones y blobs)
    $stmt->execute(['pedro', 'pedro@real.com', 'Hola mundo pro', $binaryData, 'S3CR3T_K3Y_1']);
    
    // Fila 2: Juan (Fila para ser filtrada vía WHERE)
    $stmt->execute(['juan', 'juan@hidden.com', 'Fila para filtrar', null, 'S3CR3T_K3Y_2']);

    return true;
};
