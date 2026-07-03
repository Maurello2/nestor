<?php
require_once __DIR__ . '/../Models/Materia.php';
require_once __DIR__ . '/AuthController.php';

class MateriaController {
    private $materiaModel;

    public function __construct() {
        $this->materiaModel = new Materia();
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
            $materias = $this->materiaModel->getAll();
            echo json_encode($materias);
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
            $materia = $this->materiaModel->getByCod($cod);
            if ($materia) {
                echo json_encode($materia);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Materia no encontrada']);
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
        
        $cod = $data['nuevoCodMateria'] ?? '';
        $nombre = $data['nuevoNomMateria'] ?? '';
        $area = $data['nuevoCodArea'] ?? '';
        $ciclo = $data['nuevoCicloMateria'] ?? '';
        $estado = $data['nuevoEstadoMateria'] ?? 'Activado';
        $orden = $data['nuevoOrdenMateria'] ?? 0;
        $horas = $data['nuevoHorasSemanales'] ?? '1';

        if (empty($cod) || empty($nombre) || empty($area) || empty($ciclo)) {
            echo json_encode(['status' => 'error', 'message' => 'Campos obligatorios incompletos']);
            return;
        }

        if ($this->materiaModel->getByCod($cod)) {
            echo json_encode(['status' => 'error', 'message' => 'El código de materia ya existe']);
            return;
        }

        try {
            $insertData = [
                'cod_materia' => $cod,
                'nom_materia' => $nombre,
                'cod_area' => $area,
                'ciclo_materia' => $ciclo,
                'estado_materia' => $estado,
                'orden_materia' => $orden,
                'horas_semanales' => $horas
            ];
            if ($this->materiaModel->create($insertData)) {
                echo json_encode(['status' => 'success', 'message' => 'Materia creada correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo crear la materia']);
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

        $nombre = $data['editarNomMateria'] ?? '';
        $area = $data['editarCodArea'] ?? '';
        $ciclo = $data['editarCicloMateria'] ?? '';
        $estado = $data['editarEstadoMateria'] ?? 'Activado';
        $orden = $data['editarOrdenMateria'] ?? 0;
        $horas = $data['editarHorasSemanales'] ?? '1';

        if (empty($nombre) || empty($area) || empty($ciclo)) {
            echo json_encode(['status' => 'error', 'message' => 'Campos obligatorios incompletos']);
            return;
        }

        try {
            $updateData = [
                'nom_materia' => $nombre,
                'cod_area' => $area,
                'ciclo_materia' => $ciclo,
                'estado_materia' => $estado,
                'orden_materia' => $orden,
                'horas_semanales' => $horas
            ];
            if ($this->materiaModel->update($cod, $updateData)) {
                echo json_encode(['status' => 'success', 'message' => 'Materia actualizada correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar la materia']);
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
            if ($this->materiaModel->delete($cod)) {
                echo json_encode(['status' => 'success', 'message' => 'Materia eliminada correctamente']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar la materia']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
