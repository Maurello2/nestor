<?php
require_once __DIR__ . '/Database.php';

class Materia {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT cod_materia, nom_materia, cod_area, ciclo_materia, estado_materia, orden_materia, H_semanales_materia as horas_semanales FROM materia ORDER BY orden_materia ASC");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        foreach ($results as &$row) {
            $row['estado_materia'] = ($row['estado_materia'] === 'Activa' || $row['estado_materia'] === 'Activado') ? 'Activado' : 'Desactivado';
        }
        return $results;
    }

    public function getByCod($cod) {
        $stmt = $this->db->prepare("SELECT cod_materia, nom_materia, cod_area, ciclo_materia, estado_materia, orden_materia, H_semanales_materia as horas_semanales FROM materia WHERE cod_materia = :cod");
        $stmt->execute([':cod' => $cod]);
        $row = $stmt->fetch();
        if ($row) {
            $row['estado_materia'] = ($row['estado_materia'] === 'Activa' || $row['estado_materia'] === 'Activado') ? 'Activado' : 'Desactivado';
        }
        return $row;
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO materia (cod_materia, nom_materia, cod_area, ciclo_materia, estado_materia, orden_materia, H_semanales_materia)
            VALUES (:cod_materia, :nom_materia, :cod_area, :ciclo_materia, :estado_materia, :orden_materia, :H_semanales_materia)
        ");
        
        $estado = ($data['estado_materia'] === 'Activado' || $data['estado_materia'] === 'Activa') ? 'Activa' : 'Inactiva';
        
        return $stmt->execute([
            ':cod_materia' => $data['cod_materia'],
            ':nom_materia' => $data['nom_materia'],
            ':cod_area' => $data['cod_area'],
            ':ciclo_materia' => $data['ciclo_materia'],
            ':estado_materia' => $estado,
            ':orden_materia' => $data['orden_materia'],
            ':H_semanales_materia' => $data['horas_semanales']
        ]);
    }

    public function update($cod, $data) {
        $stmt = $this->db->prepare("
            UPDATE materia 
            SET nom_materia = :nom_materia, 
                cod_area = :cod_area, 
                ciclo_materia = :ciclo_materia, 
                estado_materia = :estado_materia, 
                orden_materia = :orden_materia, 
                H_semanales_materia = :H_semanales_materia 
            WHERE cod_materia = :cod
        ");
        
        $estado = ($data['estado_materia'] === 'Activado' || $data['estado_materia'] === 'Activa') ? 'Activa' : 'Inactiva';
        
        return $stmt->execute([
            ':nom_materia' => $data['nom_materia'],
            ':cod_area' => $data['cod_area'],
            ':ciclo_materia' => $data['ciclo_materia'],
            ':estado_materia' => $estado,
            ':orden_materia' => $data['orden_materia'],
            ':H_semanales_materia' => $data['horas_semanales'],
            ':cod' => $cod
        ]);
    }

    public function delete($cod) {
        $stmt = $this->db->prepare("DELETE FROM materia WHERE cod_materia = :cod");
        return $stmt->execute([':cod' => $cod]);
    }
}
