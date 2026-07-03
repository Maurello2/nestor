<?php
require_once __DIR__ . '/../Models/Matricula.php';
require_once __DIR__ . '/AuthController.php';

class MatriculaController {
    private $matriculaModel;

    public function __construct() {
        $this->matriculaModel = new Matricula();
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
            $matriculas = $this->matriculaModel->getAll();
            echo json_encode($matriculas);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function show($params) {
        $this->authenticate();
        header('Content-Type: application/json');
        
        $num = $params['id'];
        try {
            $matricula = $this->matriculaModel->getByNum($num);
            if ($matricula) {
                echo json_encode($matricula);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Matrícula no encontrada']);
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
        
        $num = $data['num_matricula'] ?? '';
        $doc = $data['doc_alumno'] ?? '';
        
        if (empty($num) || empty($doc)) {
            echo json_encode(['status' => 'error', 'message' => 'Número de matrícula y documento del alumno son obligatorios']);
            return;
        }

        if ($this->matriculaModel->getByNum($num)) {
            echo json_encode(['status' => 'error', 'message' => 'El número de matrícula ya existe']);
            return;
        }

        try {
            if ($this->matriculaModel->create($data)) {
                echo json_encode(['status' => 'success', 'message' => 'Matrícula completada correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo registrar la matrícula']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function update($params) {
        $this->authenticate();
        header('Content-Type: application/json');

        $num = $params['id'];
        $data = $this->getRequestData();

        if (empty($data['doc_alumno'])) {
            echo json_encode(['status' => 'error', 'message' => 'El documento del alumno es obligatorio']);
            return;
        }

        try {
            if ($this->matriculaModel->update($num, $data)) {
                echo json_encode(['status' => 'success', 'message' => 'Registro de matrícula actualizado']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar la matrícula']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function delete($params) {
        $this->authenticate();
        header('Content-Type: application/json');

        $num = $params['id'];
        
        try {
            if ($this->matriculaModel->delete($num)) {
                echo json_encode(['status' => 'success', 'message' => 'Matrícula eliminada correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar la matrícula']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
