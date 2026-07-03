<?php
require_once __DIR__ . '/Database.php';

class Usuario {
    private $db;

    private function db() {
        if ($this->db === null) {
            $this->db = Database::connect();
        }
        return $this->db;
    }

    public function getAll() {
        $stmt = $this->db()->prepare("SELECT doc_usuario, mail_usuario, Tipo_usuario, nombre_usuario, foto_usuario, estado_usuario, ultimo_login FROM usuario ORDER BY nombre_usuario ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByDoc($doc) {
        $stmt = $this->db()->prepare("SELECT * FROM usuario WHERE doc_usuario = :doc");
        $stmt->execute([':doc' => $doc]);
        return $stmt->fetch();
    }

    public function getByEmail($email) {
        $stmt = $this->db()->prepare("SELECT * FROM usuario WHERE mail_usuario = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db()->prepare("
            INSERT INTO usuario (doc_usuario, clave_usuario, mail_usuario, Tipo_usuario, nombre_usuario, foto_usuario, estado_usuario)
            VALUES (:doc_usuario, :clave_usuario, :mail_usuario, :Tipo_usuario, :nombre_usuario, :foto_usuario, :estado_usuario)
        ");
        
        $clave = md5($data['clave_usuario']);
        
        return $stmt->execute([
            ':doc_usuario' => $data['doc_usuario'],
            ':clave_usuario' => $clave,
            ':mail_usuario' => $data['mail_usuario'],
            ':Tipo_usuario' => $data['Tipo_usuario'],
            ':nombre_usuario' => $data['nombre_usuario'],
            ':foto_usuario' => $data['foto_usuario'] ?? null,
            ':estado_usuario' => $data['estado_usuario'] ?? 'Activado'
        ]);
    }

    public function update($doc, $data) {
        $fields = [
            'mail_usuario = :mail_usuario',
            'Tipo_usuario = :Tipo_usuario',
            'nombre_usuario = :nombre_usuario',
            'estado_usuario = :estado_usuario'
        ];
        
        $params = [
            ':doc' => $doc,
            ':mail_usuario' => $data['mail_usuario'],
            ':Tipo_usuario' => $data['Tipo_usuario'],
            ':nombre_usuario' => $data['nombre_usuario'],
            ':estado_usuario' => $data['estado_usuario']
        ];

        if (!empty($data['clave_usuario'])) {
            $fields[] = 'clave_usuario = :clave_usuario';
            $params[':clave_usuario'] = md5($data['clave_usuario']);
        }

        if (isset($data['foto_usuario'])) {
            $fields[] = 'foto_usuario = :foto_usuario';
            $params[':foto_usuario'] = $data['foto_usuario'];
        }

        $sql = "UPDATE usuario SET " . implode(', ', $fields) . " WHERE doc_usuario = :doc";
        $stmt = $this->db()->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($doc) {
        $stmt = $this->db()->prepare("DELETE FROM usuario WHERE doc_usuario = :doc");
        return $stmt->execute([':doc' => $doc]);
    }

    public function updateLogin($doc) {
        $stmt = $this->db()->prepare("UPDATE usuario SET ultimo_login = NOW() WHERE doc_usuario = :doc");
        return $stmt->execute([':doc' => $doc]);
    }

    public function updateStatus($doc, $status) {
        $stmt = $this->db()->prepare("UPDATE usuario SET estado_usuario = :status WHERE doc_usuario = :doc");
        return $stmt->execute([
            ':status' => $status,
            ':doc' => $doc
        ]);
    }

    public function updatePassword($doc, $newPassword) {
        $stmt = $this->db()->prepare("UPDATE usuario SET clave_usuario = :password WHERE doc_usuario = :doc");
        return $stmt->execute([
            ':password' => md5($newPassword),
            ':doc' => $doc
        ]);
    }

    // =========================================================================
    // MÉTODOS DE GESTIÓN DE TOKENS DE RECUPERACIÓN DE CONTRASEÑA
    // =========================================================================
    // Estos métodos interactúan con la tabla `token_recuperacion`, que tiene
    // una relación de clave foránea con la tabla `usuario` (campo doc_usuario).
    // Esto garantiza integridad referencial: si un usuario es eliminado, todos
    // sus tokens de recuperación también se eliminan automáticamente (ON DELETE CASCADE).
    // =========================================================================

    /**
     * Guarda un nuevo token de recuperación de contraseña en la base de datos.
     *
     * Antes de insertar, elimina cualquier token anterior del mismo usuario
     * para evitar acumulación de registros obsoletos en la tabla.
     *
     * @param string $doc       Documento del usuario propietario del token.
     * @param string $token     Cadena JWT firmada que actúa como enlace de recuperación.
     * @param string $expiracion Fecha y hora de expiración en formato 'Y-m-d H:i:s'.
     * @return bool             true si se insertó correctamente, false en caso contrario.
     */
    public function saveRecoveryToken($doc, $token, $expiracion) {
        // Elimina tokens previos del usuario para mantener la tabla limpia
        $stmtDelete = $this->db()->prepare(
            "DELETE FROM token_recuperacion WHERE doc_usuario = :doc"
        );
        $stmtDelete->execute([':doc' => $doc]);

        // Inserta el nuevo token con estado 'no usado' (usado = 0)
        $stmtInsert = $this->db()->prepare(
            "INSERT INTO token_recuperacion (doc_usuario, token, expiracion, usado)
             VALUES (:doc, :token, :expiracion, 0)"
        );
        return $stmtInsert->execute([
            ':doc'        => $doc,
            ':token'      => $token,
            ':expiracion' => $expiracion
        ]);
    }

    /**
     * Busca un token de recuperación que sea válido:
     * - Que exista en la base de datos.
     * - Que no haya sido usado anteriormente (usado = 0).
     * - Que su fecha de expiración sea mayor a la hora actual.
     *
     * @param string $token  Cadena JWT a verificar.
     * @return array|false   Fila del registro del token si es válido, false si no existe o ya expiró/fue usado.
     */
    public function findValidToken($token) {
        $stmt = $this->db()->prepare(
            "SELECT * FROM token_recuperacion
             WHERE token = :token
               AND usado = 0
               AND expiracion > NOW()"
        );
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(); // Retorna el registro o false si no se encontró
    }

    /**
     * Marca un token de recuperación como 'usado' (usado = 1).
     *
     * Este método se llama inmediatamente después de que el usuario
     * actualiza su contraseña, para que el mismo enlace no pueda
     * reutilizarse aunque el JWT todavía no haya expirado.
     *
     * @param string $token  Cadena JWT que se desea invalidar.
     * @return bool          true si se actualizó al menos un registro.
     */
    public function invalidateToken($token) {
        $stmt = $this->db()->prepare(
            "UPDATE token_recuperacion SET usado = 1 WHERE token = :token"
        );
        return $stmt->execute([':token' => $token]);
    }
}
