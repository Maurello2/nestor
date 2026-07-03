<?php
require_once __DIR__ . '/Database.php';

class Area {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT cod_area, nom_area, estado_area FROM area ORDER BY cod_area ASC");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        // Normaliza el estado para mantener consistencia en la interfaz
        foreach ($results as &$row) {
            $row['estado_area'] = ($row['estado_area'] === 'Activa' || $row['estado_area'] === 'Activado') ? 'Activado' : 'Desactivado';
        }
        return $results;
    }

    public function getByCod($cod) {
        $stmt = $this->db->prepare("SELECT * FROM area WHERE cod_area = :cod");
        $stmt->execute([':cod' => $cod]);
        $row = $stmt->fetch();
        if ($row) {
            $row['estado_area'] = ($row['estado_area'] === 'Activa' || $row['estado_area'] === 'Activado') ? 'Activado' : 'Desactivado';
        }
        return $row;
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO area (cod_area, nom_area, estado_area)
            VALUES (:cod_area, :nom_area, :estado_area)
        ");
        
        // Guarda como 'Activa' o 'Inactiva' en la base de datos
        $estado = ($data['estado_area'] === 'Activado' || $data['estado_area'] === 'Activa') ? 'Activa' : 'Inactiva';
        
        return $stmt->execute([
            ':cod_area' => $data['cod_area'],
            ':nom_area' => $data['nom_area'],
            ':estado_area' => $estado
        ]);
    }

    public function update($cod, $data) {
        $stmt = $this->db->prepare("
            UPDATE area 
            SET nom_area = :nom_area, estado_area = :estado_area 
            WHERE cod_area = :cod
        ");
        
        $estado = ($data['estado_area'] === 'Activado' || $data['estado_area'] === 'Activa') ? 'Activa' : 'Inactiva';
        
        return $stmt->execute([
            ':nom_area' => $data['nom_area'],
            ':estado_area' => $estado,
            ':cod' => $cod
        ]);
    }

    public function delete($cod) {
        $stmt = $this->db->prepare("DELETE FROM area WHERE cod_area = :cod");
        return $stmt->execute([':cod' => $cod]);
    }
}
