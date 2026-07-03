<?php
require_once __DIR__ . '/../Models/Logro.php';
require_once __DIR__ . '/AuthController.php';

class LogroController {
    private $logroModel;

    public function __construct() {
        $this->logroModel = new Logro();
    }

    private function authenticate() {
        return AuthController::checkApiAuth();
    }

    private function getRequestData() {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'POST') {
            return $_POST;
        }
        if ($method === 'PUT') {
            parse_str(file_get_contents('php://input'), $data);
            return $data;
        }
        return [];
    }

    public function index() {
        $this->authenticate();
        header('Content-Type: application/json');
        
        try {
            $logros = $this->logroModel->getAll();
            echo json_encode($logros);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function show($params) {
        $this->authenticate();
        header('Content-Type: application/json');
        
        $cod = $params['id'];
        try {
            $logro = $this->logroModel->getByCod($cod);
            if ($logro) {
                echo json_encode($logro);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Logro no encontrado']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function create() {
        $this->authenticate();
        header('Content-Type: application/json');

        $data = $this->getRequestData();
        
        $cod = $data['nuevoCodIndicador'] ?? '';
        $descrip = $data['nuevoDescripIndicador'] ?? '';
        $materia = $data['nuevoCodMateria'] ?? '';
        $estado = $data['nuevoEstadoIndicador'] ?? 'Activado';

        if (empty($cod) || empty($descrip) || empty($materia)) {
            echo json_encode(['status' => 'error', 'message' => 'Código, Descripción y Materia son obligatorios']);
            return;
        }

        if ($this->logroModel->getByCod($cod)) {
            echo json_encode(['status' => 'error', 'message' => 'El código de logro ya existe']);
            return;
        }

        try {
            $insertData = [
                'cod_indicador' => $cod,
                'descrip_indicador' => $descrip,
                'cod_materia' => $materia,
                'estado_indicador' => $estado
            ];
            if ($this->logroModel->create($insertData)) {
                echo json_encode(['status' => 'success', 'message' => 'Logro creado correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo crear el logro']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function update($params) {
        $this->authenticate();
        header('Content-Type: application/json');

        $cod = $params['id'];
        $data = $this->getRequestData();

        $descrip = $data['editarDescripIndicador'] ?? '';
        $materia = $data['editarCodMateria'] ?? '';
        $estado = $data['editarEstadoIndicador'] ?? 'Activado';

        if (empty($descrip) || empty($materia)) {
            echo json_encode(['status' => 'error', 'message' => 'Descripción y Materia son obligatorios']);
            return;
        }

        try {
            $updateData = [
                'descrip_indicador' => $descrip,
                'cod_materia' => $materia,
                'estado_indicador' => $estado
            ];
            if ($this->logroModel->update($cod, $updateData)) {
                echo json_encode(['status' => 'success', 'message' => 'Logro actualizado correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el logro']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function delete($params) {
        $this->authenticate();
        header('Content-Type: application/json');

        $cod = $params['id'];
        
        try {
            if ($this->logroModel->delete($cod)) {
                echo json_encode(['status' => 'success', 'message' => 'Logro eliminado correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el logro']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
