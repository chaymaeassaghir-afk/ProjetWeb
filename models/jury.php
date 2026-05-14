<?php

require_once 'prof.php';

class jury {

    private ?int $id_jury = null;
    private int $id_soutenance;
    private int $id_prof;
    private string $role;

    public function __construct(private PDO $pdo) {

    }

  
    // GETTERS
    

    public function getIdJury(): ?int {
        return $this->id_jury;
    }

    public function getIdSoutenance(): int {
        return $this->id_soutenance;
    }

    public function getIdProf(): int {
        return $this->id_prof;
    }

    public function getRole(): string {
        return $this->role;
    }

  
    // SETTERS
  

    public function setIdJury(?int $id_jury): void {
        $this->id_jury = $id_jury;
    }

    public function setIdSoutenance(int $id_soutenance): void {
        $this->id_soutenance = $id_soutenance;
    }

    public function setIdProf(int $id_prof): void {
        $this->id_prof = $id_prof;
    }

    public function setRole(string $role): void {
        $this->role = $role;
    }

   
    // CRUD
   

    // Afficher tous les jurys
    public function getAll() {

        $sql = "SELECT j.*, 
                       p.nom,
                       p.prenom,
                       s.date
                FROM jury j
                INNER JOIN professeur p 
                    ON j.id_prof = p.id
                INNER JOIN soutenance s
                    ON j.id_soutenance = s.id_stnc
                ORDER BY s.date";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un jury par id
    public function getById(int $id) {

        $sql = "SELECT * FROM jury 
                WHERE id_jury = :id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Ajouter un membre jury
    public function insert(
        int $id_soutenance,
        int $id_prof,
        string $role
    ) {

        $sql = "INSERT INTO jury(
                    id_soutenance,
                    id_prof,
                    role
                )
                VALUES(
                    :id_soutenance,
                    :id_prof,
                    :role
                )";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id_soutenance' => $id_soutenance,
            ':id_prof'       => $id_prof,
            ':role'          => $role
        ]);
    }

    // Modifier jury
    public function update(
        int $id_jury,
        int $id_soutenance,
        int $id_prof,
        string $role
    ) {

        $sql = "UPDATE jury
                SET 
                    id_soutenance = :id_soutenance,
                    id_prof       = :id_prof,
                    role          = :role
                WHERE id_jury = :id_jury";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id_jury'       => $id_jury,
            ':id_soutenance' => $id_soutenance,
            ':id_prof'       => $id_prof,
            ':role'          => $role
        ]);
    }

    // Supprimer jury
    public function delete(int $id) {

        $sql = "DELETE FROM jury
                WHERE id_jury = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    // Membres d'une soutenance
    public function getMembresBySoutenance(int $id_soutenance) {

        $sql = "SELECT j.*, 
                       p.nom,
                       p.prenom
                FROM jury j
                INNER JOIN professeur p
                    ON j.id_prof = p.id
                WHERE j.id_soutenance = :id_soutenance";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_soutenance' => $id_soutenance
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    #debut israe
    public function getSoutenances(){
        $sql="SELECT e.nom as nom_etudiant,
                e.prenom as prenom_etudiant,
                p.nom as nom_encadrant ,
                p.prenom as prenom_encadrant,
                s.id_stnc,
                s.date,
                s.heure_debut,
                s.heure_fin
                FROM etudiant as e JOIN soutenance as s
                ON e.id_etudiant=s.etudiant_id
                JOIN professeur as p
                ON  e.id_prof=p.id";
        $stmt=$this->pdo->query($sql);
        return $stmt->fetchall(PDO::FETCH_ASSOC);

    }
    public function getProfDispo(int $id_soutenance, bool $info_seulement = false) {
        $sql = "SELECT p.id, p.nom as nom_prof,
                    p.prenom as prenom_prof,
                    p.specialite
                FROM professeur as p
                WHERE p.id NOT IN(
                    SELECT e.id_prof FROM etudiant as e 
                    JOIN soutenance as s ON e.id_etudiant = s.etudiant_id 
                    WHERE s.id_stnc = ?
                )
                AND p.id NOT IN(
                    SELECT id_prof FROM jury WHERE id_soutenance = ?
                )
                AND p.id NOT IN(
                    SELECT j.id_prof FROM jury as j
                    JOIN soutenance as s2 ON j.id_soutenance = s2.id_stnc
                    WHERE s2.date = (SELECT date FROM soutenance WHERE id_stnc = ?)
                    AND s2.heure_debut = (SELECT heure_debut FROM soutenance WHERE id_stnc = ?)
                    AND s2.id_stnc != ?
                )";

        // ✅ si on a besoin de profs info → filtrer par spécialité
        if($info_seulement) {
            $sql .= " AND p.specialite = 'informatique'";
        }

        $sql .= " ORDER BY (
                    SELECT COUNT(*) FROM jury j2
                    JOIN soutenance s3 ON j2.id_soutenance = s3.id_stnc
                    WHERE j2.id_prof = p.id
                    AND s3.date = (SELECT date FROM soutenance WHERE id_stnc = ?)
                ) ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $id_soutenance,
            $id_soutenance,
            $id_soutenance,
            $id_soutenance,
            $id_soutenance,
            $id_soutenance
        ]);
        return $stmt->fetchAll();
    }
    public function juryDejaAffecte($id_soutenance){
        $sql="SELECT count(j.id_prof) FROM jury as j
            
            WHERE id_soutenance=?";
             $stmt=$this->pdo->prepare($sql);
             $stmt->execute([$id_soutenance]);
             $nb=$stmt->fetchColumn();
             return $nb;


    }
    public function getNbJuryInfoDispo(int $id_soutenance): int {
        $sql = "SELECT COUNT(*) FROM jury j
                JOIN professeur p ON j.id_prof = p.id
                WHERE j.id_soutenance = ?
                AND p.specialite = 'informatique'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_soutenance]);
        return (int) $stmt->fetchColumn();
    }

    #fin israe
   public function getStatJury(){
        $sql="SELECT p.nom,p.prenom ,count(j.id_soutenance) as total FROM jury as j
            JOIN professeur as p ON j.id_prof=p.id
            GROUP BY p.id,p.nom,p.prenom
            ORDER BY total DESC ";
        $stmt=$this->pdo->query($sql);
        return $stmt->fetchall();

    }

    public function getStatEncadrant(){
        $sql="SELECT p.nom,p.prenom ,count(e.id_etudiant) as total FROM professeur p
              JOIN etudiant e 
              ON e.id_prof=p.id
              GROUP BY p.id,p.nom,p.prenom
              ORDER BY total DESC";
        $stmt=$this->pdo->query($sql);
        return $stmt->fetchall();
    }
    public function getStatParfiliere(){
        $sql="SELECT e.filiere , count(s.id_stnc) as total FROM soutenance s
              JOIN etudiant e 
              ON e.id_etudiant=s.etudiant_id
              GROUP BY e.filiere";
        $stmt=$this->pdo->query($sql);
        return $stmt->fetchall();
    }
}

?>