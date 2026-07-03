<?php
// Script de configuración de la base de datos
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'dbsiga2022';

try {
    // 1. Conectar al servidor MySQL
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected to MySQL successfully.\n";

    // 2. Verificar si la base de datos existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '$db'");
    $dbExists = $stmt->fetch();

    if (!$dbExists) {
        echo "Database $db does not exist. Creating and importing...\n";
        $pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8 COLLATE utf8_general_ci");
        
        // Conectar a la nueva base de datos
        $pdo->exec("USE `$db`");

        // Leer y ejecutar DBsiga.sql
        $sqlFile = __DIR__ . '/DBsiga.sql';
        if (file_exists($sqlFile)) {
            echo "Importing DBsiga.sql... This might take a few seconds.\n";
            $sql = file_get_contents($sqlFile);
            
            // Eliminar comentarios y dividir en consultas
            // Como el archivo es grande, podemos ejecutarlo cargándolo directamente.
            // PDO exec de MariaDB/MySQL puede ejecutar varias consultas si están separadas.
            // Pero deberíamos limpiar los delimitadores si HeidiSQL los incluye.
            // Ejecutémoslo usando el CLI de mysql o analizándolo.
            // Un exec simple podría funcionar, o podemos usar el CLI si mysql está en el PATH.
            // Probemos primero con PDO exec. Si falla, intentaremos con el CLI de mysql.
            try {
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                $pdo->exec($sql);
                echo "Database imported successfully.\n";
            } catch (Exception $ex) {
                echo "PDO exec failed: " . $ex->getMessage() . "\nTrying via mysql CLI...\n";
                $cmd = "mysql -h $host -u $user " . (!empty($pass) ? "-p$pass " : "") . "$db < \"" . $sqlFile . "\"";
                system($cmd, $retval);
                if ($retval === 0) {
                    echo "Database imported successfully via CLI.\n";
                } else {
                    echo "CLI import also failed with code $retval.\n";
                }
            }
        } else {
            echo "Error: DBsiga.sql file not found at $sqlFile\n";
        }
    } else {
        echo "Database $db already exists.\n";
    }

    // 3. Modificar la tabla usuario para añadir las columnas requeridas si no existen
    $pdo->exec("USE `$db`");
    
    $columnsToAdd = [
        'nombre_usuario' => "VARCHAR(255) NULL DEFAULT NULL AFTER `Tipo_usuario`",
        'foto_usuario' => "VARCHAR(255) NULL DEFAULT NULL AFTER `nombre_usuario`",
        'estado_usuario' => "VARCHAR(20) NOT NULL DEFAULT 'Activado' AFTER `foto_usuario`",
        'ultimo_login' => "DATETIME NULL DEFAULT NULL AFTER `estado_usuario`"
    ];

    foreach ($columnsToAdd as $col => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `usuario` LIKE '$col'");
        if (!$stmt->fetch()) {
            echo "Adding column `$col` to `usuario` table...\n";
            $pdo->exec("ALTER TABLE `usuario` ADD `$col` $definition");
        } else {
            echo "Column `$col` already exists in `usuario` table.\n";
        }
    }

    // 4. Actualizar los usuarios existentes con nombres desde funcionario si están vacíos
    $pdo->exec("
        UPDATE `usuario` u
        LEFT JOIN `funcionario` f ON u.doc_usuario = f.doc_funcionario
        SET u.nombre_usuario = COALESCE(f.nom_funcionario, u.doc_usuario)
        WHERE u.nombre_usuario IS NULL
    ");
    echo "User names synchronized.\n";

    echo "Database setup finished successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
