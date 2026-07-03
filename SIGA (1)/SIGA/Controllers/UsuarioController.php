<?php
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/AuthController.php';

class UsuarioController {
    private $userModel;

    public function __construct() {
        $this->userModel = new Usuario();
    }

    private function authenticate() {
        return AuthController::checkApiAuth();
    }

    /**
     * Valida y guarda la foto de un usuario.
     * Verifica tamaño (máx. 2MB) y tipo real de imagen (JPG/PNG únicamente,
     * comprobado con getimagesize y no solo por la extensión del archivo),
     * ya que confiar solo en la extensión permite subir archivos disfrazados.
     *
     * @return array ['success' => bool, 'path' => string, 'error' => string]
     */
    private function handleFotoUpload($file, $doc, $suffix = '') {
        if ($file['size'] > 2 * 1024 * 1024) {
            return ['success' => false, 'path' => '', 'error' => 'La foto no debe superar los 2MB'];
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if ($imageInfo === false || !isset($allowedMimes[$imageInfo['mime']])) {
            return ['success' => false, 'path' => '', 'error' => 'La foto debe ser una imagen válida en formato JPG o PNG'];
        }

        $extension = $allowedMimes[$imageInfo['mime']];
        $uploadDir = __DIR__ . '/../Views/images/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = $doc . $suffix . '.' . $extension;
        $destPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'path' => '', 'error' => 'No se pudo guardar la foto'];
        }

        return ['success' => true, 'path' => 'images/uploads/' . $newFileName, 'error' => ''];
    }

    public function index() {
        $this->authenticate();
        header('Content-Type: application/json');
        
        try {
            $users = $this->userModel->getAll();
            echo json_encode($users);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function create() {
        $this->authenticate();
        header('Content-Type: application/json');

        $doc = $_POST['nuevoUsuario'] ?? '';
        $nombre = $_POST['nuevoNombre'] ?? '';
        $password = $_POST['nuevoPassword'] ?? '';
        $perfil = $_POST['nuevoPerfil'] ?? '';
        
        if (empty($doc) || empty($nombre) || empty($password) || empty($perfil)) {
            echo json_encode(['status' => 'error', 'message' => 'Campos obligatorios incompletos']);
            return;
        }

        // Verifica si el usuario ya existe
        if ($this->userModel->getByDoc($doc)) {
            echo json_encode(['status' => 'error', 'message' => 'El número de usuario/documento ya existe']);
            return;
        }

        // Maneja la subida de archivos
        $fotoPath = '';
        if (isset($_FILES['nuevaFoto']) && $_FILES['nuevaFoto']['error'] === UPLOAD_ERR_OK) {
            $resultado = $this->handleFotoUpload($_FILES['nuevaFoto'], $doc);
            if (!$resultado['success']) {
                echo json_encode(['status' => 'error', 'message' => $resultado['error']]);
                return;
            }
            $fotoPath = $resultado['path'];
        }

        $data = [
            'doc_usuario' => $doc,
            'clave_usuario' => $password,
            'mail_usuario' => $_POST['nuevoEmail'] ?? ($doc . '@ceis.edu.co'), // Correo de respaldo predeterminado
            'Tipo_usuario' => $perfil,
            'nombre_usuario' => $nombre,
            'foto_usuario' => $fotoPath,
            'estado_usuario' => 'Activado'
        ];

        try {
            if ($this->userModel->create($data)) {
                echo json_encode(['status' => 'success', 'message' => 'Usuario creado correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo crear el usuario']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function update($params) {
        $this->authenticate();
        header('Content-Type: application/json');
        
        $doc = $params['doc'];
        $user = $this->userModel->getByDoc($doc);
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
            return;
        }

        $nombre = $_POST['editarNombre'] ?? '';
        $perfil = $_POST['editarPerfil'] ?? '';
        $password = $_POST['editarPassword'] ?? '';
        
        if (empty($nombre) || empty($perfil)) {
            echo json_encode(['status' => 'error', 'message' => 'Nombre y perfil son campos obligatorios']);
            return;
        }

        // Maneja la subida de archivos
        $fotoPath = $user['foto_usuario'];
        if (isset($_FILES['editarFoto']) && $_FILES['editarFoto']['error'] === UPLOAD_ERR_OK) {
            $resultado = $this->handleFotoUpload($_FILES['editarFoto'], $doc, '_' . time());
            if (!$resultado['success']) {
                echo json_encode(['status' => 'error', 'message' => $resultado['error']]);
                return;
            }

            // Elimina el archivo anterior si existe
            if (!empty($user['foto_usuario']) && file_exists(__DIR__ . '/../Views/' . $user['foto_usuario'])) {
                @unlink(__DIR__ . '/../Views/' . $user['foto_usuario']);
            }

            $fotoPath = $resultado['path'];
        }

        $data = [
            'mail_usuario' => $_POST['editarEmail'] ?? $user['mail_usuario'],
            'Tipo_usuario' => $perfil,
            'nombre_usuario' => $nombre,
            'estado_usuario' => $_POST['editarEstado'] ?? $user['estado_usuario'],
            'clave_usuario' => $password, // Solo se actualizará si no está vacío en el Modelo
            'foto_usuario' => $fotoPath
        ];

        try {
            if ($this->userModel->update($doc, $data)) {
                echo json_encode(['status' => 'success', 'message' => 'Usuario actualizado correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el usuario']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function delete($params) {
        $this->authenticate();
        header('Content-Type: application/json');
        
        $doc = $params['doc'];
        $user = $this->userModel->getByDoc($doc);
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
            return;
        }

        try {
            // Elimina la foto si existe
            if (!empty($user['foto_usuario']) && file_exists(__DIR__ . '/../Views/' . $user['foto_usuario'])) {
                @unlink(__DIR__ . '/../Views/' . $user['foto_usuario']);
            }

            if ($this->userModel->delete($doc)) {
                echo json_encode(['status' => 'success', 'message' => 'Usuario eliminado correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el usuario']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function toggleStatus($params) {
        $this->authenticate();
        header('Content-Type: application/json');
        
        $doc = $params['doc'];
        $estado = $_POST['estado'] ?? '';
        
        if (empty($estado)) {
            echo json_encode(['status' => 'error', 'message' => 'Estado no proporcionado']);
            return;
        }

        try {
            if ($this->userModel->updateStatus($doc, $estado)) {
                echo json_encode(['status' => 'success', 'message' => 'Estado de usuario actualizado']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el estado']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
