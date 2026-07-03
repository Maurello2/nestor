<?php
require_once __DIR__ . '/Database.php';

class Logro {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT cod_indicador, descrip_indicador, cod_materia, estado_indicador FROM indicador_de_logro ORDER BY cod_indicador ASC");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        foreach ($results as &$row) {
            $row['estado_indicador'] = ($row['estado_indicador'] === 'Activo' || $row['estado_indicador'] === 'Activado') ? 'Activado' : 'Desactivado';
        }
        return $results;
    }

    public function getByCod($cod) {
        $stmt = $this->db->prepare("SELECT cod_indicador, descrip_indicador, cod_materia, estado_indicador FROM indicador_de_logro WHERE cod_indicador = :cod");
        $stmt->execute([':cod' => $cod]);
        $row = $stmt->fetch();
        if ($row) {
            $row['estado_indicador'] = ($row['estado_indicador'] === 'Activo' || $row['estado_indicador'] === 'Activado') ? 'Activado' : 'Desactivado';
        }
        return $row;
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO indicador_de_logro (cod_indicador, descrip_indicador, cod_materia, estado_indicador)
            VALUES (:cod_indicador, :descrip_indicador, :cod_materia, :estado_indicador)
        ");
        
        $estado = ($data['estado_indicador'] === 'Activado' || $data['estado_indicador'] === 'Activo') ? 'Activo' : 'Inactivo';
        
        return $stmt->execute([
            ':cod_indicador' => $data['cod_indicador'],
            ':descrip_indicador' => $data['descrip_indicador'],
            ':cod_materia' => $data['cod_materia'],
            ':estado_indicador' => $estado
        ]);
    }

    public function update($cod, $data) {
        $stmt = $this->db->prepare("
            UPDATE indicador_de_logro 
            SET descrip_indicador = :descrip_indicador, 
                cod_materia = :cod_materia, 
                estado_indicador = :estado_indicador 
            WHERE cod_indicador = :cod
        ");
        
        $estado = ($data['estado_indicador'] === 'Activado' || $data['estado_indicador'] === 'Activo') ? 'Activo' : 'Inactivo';
        
        return $stmt->execute([
            ':descrip_indicador' => $data['descrip_indicador'],
            ':cod_materia' => $data['cod_materia'],
            ':estado_indicador' => $estado,
            ':cod' => $cod
        ]);
    }

    public function delete($cod) {
        $stmt = $this->db->prepare("DELETE FROM indicador_de_logro WHERE cod_indicador = :cod");
        return $stmt->execute([':cod' => $cod]);
    }
}
