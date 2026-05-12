<?php
class Prof {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Récupérer tous les profs
    public function getProfs() {
        $stmt = $this->pdo->query("SELECT * FROM professeur ORDER BY nom, prenom");
        return $stmt->fetchAll();
    }

    // Récupérer un prof par son id
    public function getById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM professeur WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Insérer un prof
    public function insert(string $nom, string $prenom, string $specialite) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO professeur(nom, prenom, specialite) VALUES (?, ?, ?)"
            );
            $stmt->execute([$nom, $prenom, $specialite]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return false; // doublon, on ignore
            }
            throw $e; // autre erreur, on relance
        }
    }

    // Modifier un prof
    public function update(int $id, string $nom, string $prenom, string $specialite) {
        $stmt = $this->pdo->prepare(
            "UPDATE professeur SET nom=?, prenom=?, specialite=? WHERE id=?"
        );
        $stmt->execute([$nom, $prenom, $specialite, $id]);
    }

    // Supprimer un prof
    public function delete(int $id) {
        $stmt = $this->pdo->prepare("DELETE FROM professeur WHERE id = ?");
        $stmt->execute([$id]);
    }

   

    // Compter le nombre d'étudiants encadrés par un prof
    public function getNbEtudiants(int $id) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM etudiant WHERE id_prof = ?"
        );
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

 

    
    
}
?>