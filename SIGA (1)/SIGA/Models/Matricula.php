<?php
require_once __DIR__ . '/Database.php';

class Matricula {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAll() {
        $stmt = $this->db->prepare("
            SELECT m.*, a.nom_alumno, a.mail_alumno, a.direcc_alumno, a.tele_alumno, a.estado_alumno, a.sexo_alumno, a.fechanac_alumno 
            FROM matricula m
            LEFT JOIN alumno a ON m.doc_alumno = a.doc_alumno
            ORDER BY m.num_matricula DESC
            LIMIT 500
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByNum($num) {
        $stmt = $this->db->prepare("
            SELECT m.*, a.nom_alumno, a.mail_alumno, a.direcc_alumno, a.tele_alumno, a.estado_alumno, a.sexo_alumno, a.fechanac_alumno 
            FROM matricula m
            LEFT JOIN alumno a ON m.doc_alumno = a.doc_alumno
            WHERE m.num_matricula = :num
        ");
        $stmt->execute([':num' => $num]);
        return $stmt->fetch();
    }

    public function create($data) {
        $this->db->beginTransaction();
        try {
            // 1. Crear o actualizar el Alumno primero
            $stmtCheck = $this->db->prepare("SELECT doc_alumno FROM alumno WHERE doc_alumno = :doc");
            $stmtCheck->execute([':doc' => $data['doc_alumno']]);
            $exists = $stmtCheck->fetch();

            if (!$exists) {
                $stmtAlumno = $this->db->prepare("
                    INSERT INTO alumno (doc_alumno, nom_alumno, mail_alumno, direcc_alumno, tele_alumno, estado_alumno, sexo_alumno)
                    VALUES (:doc, :nom, :mail, :direcc, :tele, :estado, :sexo)
                ");
                $stmtAlumno->execute([
                    ':doc' => $data['doc_alumno'],
                    ':nom' => $data['nom_alumno'] ?? 'Estudiante Nuevo',
                    ':mail' => $data['mail_alumno'] ?? null,
                    ':direcc' => $data['direcc_alumno'] ?? null,
                    ':tele' => $data['tele_alumno'] ?? null,
                    ':estado' => $data['estado_alumno'] ?? 'Activo',
                    ':sexo' => $data['sexo_alumno'] ?? 'Masculino'
                ]);
            } else {
                $stmtAlumno = $this->db->prepare("
                    UPDATE alumno 
                    SET nom_alumno = :nom, mail_alumno = :mail, direcc_alumno = :direcc, tele_alumno = :tele, estado_alumno = :estado, sexo_alumno = :sexo
                    WHERE doc_alumno = :doc
                ");
                $stmtAlumno->execute([
                    ':nom' => $data['nom_alumno'] ?? 'Estudiante Nuevo',
                    ':mail' => $data['mail_alumno'] ?? null,
                    ':direcc' => $data['direcc_alumno'] ?? null,
                    ':tele' => $data['tele_alumno'] ?? null,
                    ':estado' => $data['estado_alumno'] ?? 'Activo',
                    ':sexo' => $data['sexo_alumno'] ?? 'Masculino',
                    ':doc' => $data['doc_alumno']
                ]);
            }

            // 2. Insertar la Matrícula
            $stmtMat = $this->db->prepare("
                INSERT INTO matricula (num_matricula, doc_alumno, ciclo_matricula, fech_matricula, jornada_matricula, sed_matricula, concepto_matricula, estado_matricula, Folio_matricula, ano, semestre)
                VALUES (:num, :doc, :ciclo, :fech, :jornada, :sede, :concepto, :estado, :folio, :ano, :semestre)
            ");
            
            $stmtMat->execute([
                ':num' => $data['num_matricula'],
                ':doc' => $data['doc_alumno'],
                ':ciclo' => $data['ciclo_matricula'] ?? null,
                ':fech' => $data['fech_matricula'] ?? null,
                ':jornada' => $data['jornada_matricula'] ?? null,
                ':sede' => $data['sed_matricula'] ?? null,
                ':concepto' => $data['concepto_matricula'] ?? null,
                ':estado' => $data['estado_matricula'] ?? 'Activa',
                ':folio' => $data['Folio_matricula'] ?? null,
                ':ano' => $data['ano'] ?? null,
                ':semestre' => $data['semestre'] ?? null
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($num, $data) {
        $this->db->beginTransaction();
        try {
            // 1. Actualizar la Matrícula
            $stmtMat = $this->db->prepare("
                UPDATE matricula 
                SET doc_alumno = :doc,
                    ciclo_matricula = :ciclo,
                    fech_matricula = :fech,
                    jornada_matricula = :jornada,
                    sed_matricula = :sede,
                    concepto_matricula = :concepto,
                    estado_matricula = :estado,
                    Folio_matricula = :folio,
                    ano = :ano,
                    semestre = :semestre
                WHERE num_matricula = :num
            ");
            
            $stmtMat->execute([
                ':doc' => $data['doc_alumno'],
                ':ciclo' => $data['ciclo_matricula'],
                ':fech' => $data['fech_matricula'],
                ':jornada' => $data['jornada_matricula'],
                ':sede' => $data['sed_matricula'],
                ':concepto' => $data['concepto_matricula'],
                ':estado' => $data['estado_matricula'],
                ':folio' => $data['Folio_matricula'],
                ':ano' => $data['ano'],
                ':semestre' => $data['semestre'],
                ':num' => $num
            ]);

            // 2. Actualizar el Alumno
            $stmtAlumno = $this->db->prepare("
                UPDATE alumno 
                SET nom_alumno = :nom, mail_alumno = :mail, direcc_alumno = :direcc, tele_alumno = :tele, estado_alumno = :estado, sexo_alumno = :sexo
                WHERE doc_alumno = :doc
            ");
            $stmtAlumno->execute([
                ':nom' => $data['nom_alumno'],
                ':mail' => $data['mail_alumno'],
                ':direcc' => $data['direcc_alumno'],
                ':tele' => $data['tele_alumno'],
                ':estado' => $data['estado_alumno'],
                ':sexo' => $data['sexo_alumno'],
                ':doc' => $data['doc_alumno']
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete($num) {
        $stmt = $this->db->prepare("DELETE FROM matricula WHERE num_matricula = :num");
        return $stmt->execute([':num' => $num]);
    }
}
