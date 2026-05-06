<?php


class Soutenance {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllSoutenances(array $filters = []): array {
        $sql = "SELECT * FROM soutenance";
        $params = [];
        $clauses = [];

        if (!empty($filters['statut'])) {
            $clauses[] = 'statut = ?';
            $params[] = $filters['statut'];
        }
        if (!empty($filters['id_salle'])) {
            $clauses[] = 'id_salle = ?';
            $params[] = $filters['id_salle'];
        }

        if (!empty($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSoutenanceById(int $id): ?array {
        $sql = "SELECT * FROM soutenance WHERE id_soutenance = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createSoutenance(array $data): array {
        $sql = "INSERT INTO soutenance (date_soutenance, id_salle, id_etudiant, id_jury, statut)
                VALUES (?, ?, ?, ?, 'planifiée')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['date_soutenance'], 
            $data['id_salle'], 
            $data['id_etudiant'], 
            $data['id_jury']
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->getSoutenanceById($id);
    }

    public function updateSoutenance(int $id, array $data): bool {
        $sql = "UPDATE soutenance SET date_soutenance = ?, id_salle = ?, id_etudiant = ?, id_jury = ? WHERE id_soutenance = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['date_soutenance'], 
            $data['id_salle'], 
            $data['id_etudiant'], 
            $data['id_jury'], 
            $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function deleteSoutenance(int $id): bool {
        $sql = "DELETE FROM soutenance WHERE id_soutenance = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // CHAYMAE: debut

    public function soutenancesSansAffectation(): array {
        $stmt = $this->pdo->query(
            "SELECT * FROM soutenance 
            WHERE id_salle IS NULL 
            ORDER BY date, heure_debut"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function affecterSalles(int $id_stc, int $id_salle): void {
        $stmt = $this->pdo->prepare(
            "UPDATE soutenance SET id_salle = ? WHERE id_stnc = ?"
        );
        $stmt->execute([$id_salle, $id_stc]);
    }
    //CHAYMAE: fin 
}