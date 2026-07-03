<?php
require_once __DIR__ . '/../Models/Area.php';
require_once __DIR__ . '/AuthController.php';

class AreaController {
    private $areaModel;

    public function __construct() {
        $this->areaModel = new Area();
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
            $areas = $this->areaModel->getAll();
            echo json_encode($areas);
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
            $area = $this->areaModel->getByCod($cod);
            if ($area) {
                echo json_encode($area);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Área no encontrada']);
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
        
        $cod = $data['nuevoCodArea'] ?? '';
        $nombre = $data['nuevoNomArea'] ?? '';
        $estado = $data['nuevoEstadoArea'] ?? 'Activado';

        if (empty($cod) || empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'Código y Nombre son obligatorios']);
            return;
        }

        if ($this->areaModel->getByCod($cod)) {
            echo json_encode(['status' => 'error', 'message' => 'El código de área ya existe']);
            return;
        }

        try {
            $insertData = [
                'cod_area' => $cod,
                'nom_area' => $nombre,
                'estado_area' => $estado
            ];
            if ($this->areaModel->create($insertData)) {
                echo json_encode(['status' => 'success', 'message' => 'Área creada correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo crear el área']);
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

        $nombre = $data['editarNomArea'] ?? '';
        $estado = $data['editarEstadoArea'] ?? 'Activado';

        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del área es obligatorio']);
            return;
        }

        try {
            $updateData = [
                'nom_area' => $nombre,
                'estado_area' => $estado
            ];
            if ($this->areaModel->update($cod, $updateData)) {
                echo json_encode(['status' => 'success', 'message' => 'Área actualizada correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el área']);
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
            if ($this->areaModel->delete($cod)) {
                echo json_encode(['status' => 'success', 'message' => 'Área eliminada correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el área']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
