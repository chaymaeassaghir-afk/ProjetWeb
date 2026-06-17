<?php

class Etudiant{

    private PDO $connexion;
    public function __construct(PDO $connexion){
        
        $this->connexion=$connexion;
     }
     public function getEtudiantsAvecEncadrants() {

        $sql = "SELECT 
                    e.nom AS nom_etudiant,
                    e.prenom AS prenom_etudiant,
                    e.filiere,
                    p.nom AS nom_prof,
                    p.prenom AS prenom_prof
                FROM etudiant e
                LEFT JOIN professeur p 
                ON e.id_prof = p.id";

        $stmt = $this->connexion->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
     public function getEtudiants(){
        $stmt=$this->connexion->query("
        SELECT * FROM etudiant");
        return $stmt->fetchAll();
     }
     public function getById($id){
        $stmt=$this->connexion->prepare("SELECT * FROM etudiant where id_etudiant=:id");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch();
     }
     public function insert($CNE, $nom, $prenom, $email_perso, $email_pro, $filiere, $id_prof)
      {
         try {

            // Insertion de l'étudiant
            $stmt = $this->connexion->prepare("
                  INSERT INTO etudiant
                  (CNE, nom, prenom, email_perso, email_pro, filiere, id_prof)
                  VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                  $CNE,
                  $nom,
                  $prenom,
                  $email_perso,
                  $email_pro,
                  $filiere,
                  $id_prof
            ]);

            // Récupérer l'id de l'étudiant ajouté
            $idEtudiant = $this->connexion->lastInsertId();

            // Créer automatiquement une soutenance
            $stmt = $this->connexion->prepare("
                  INSERT INTO soutenance (etudiant_id)
                  VALUES (?)
            ");

            $stmt->execute([$idEtudiant]);

            return true;

         } catch (PDOException $e) {

            die("Erreur : " . $e->getMessage());
         }
      }
     public function getByProf($id_prof){
      $stmt=$this->connexion->prepare("SELECT *FROM etudiant where id_prof=:id_prof");
      $stmt->execute([':id_prof'=>$id_prof]);
      return $stmt->fetchAll();
     }
     public function updateProf($id_etudiant,$id_prof){
      $stmt=$this->connexion->prepare("UPDATE etudiant SET id_prof=:id_prof WHERE id_etudiant=:id_etudiant");
      $stmt->execute([
         ':id_prof'=>$id_prof,
         ':id_etudiant'=>$id_etudiant,
      ]);
     }
     public function getParFiliere($filiere){
      $stmt=$this->connexion->prepare("SELECT * FROM etudiant WHERE filiere=?");
      $stmt->execute([$filiere]);
      return $stmt->fetchAll();
     }
     public function delete($id){
      $stmt=$this->connexion->prepare("DELETE  FROM etudiant WHERE id_etudiant=?");
      $stmt->execute([$id]);

     }
     public function update($id, $CNE, $nom, $prenom, $email_perso, $email_pro, $filiere) {
      $stmt = $this->connexion->prepare("UPDATE etudiant SET CNE=?, nom=?, prenom=?, email_perso=?, email_pro=?, filiere=? WHERE id_etudiant=?");
      $stmt->execute([$CNE, $nom, $prenom, $email_perso, $email_pro, $filiere, $id]);
      }

      //douaa:debut
      public function affecterProf($id, $id_prof){

         $sql1="INSERT INTO jury (id_soutenance, id_prof, role) 
                SELECT s.id_stnc, :id_prof, 'Encadrant' 
                FROM soutenance s
                JOIN etudiant e ON s.etudiant_id = e.id_etudiant
                WHERE e.id_etudiant = :id_etudiant";
         $stmt1 = $this->connexion->prepare($sql1);
         $stmt1->execute([
            ':id_prof' => $id_prof,
            ':id_etudiant' => $id
         ]);       

         $sql = "UPDATE etudiant
                  SET id_prof = :id_prof
                  WHERE id_etudiant = :id_etudiant";

         $stmt = $this->connexion->prepare($sql);

         return $stmt->execute([
            ':id_prof' => $id_prof,
            ':id_etudiant' => $id
         ]);

      }
      //douaa:fin
      public function getFilieresDistinctes() {
         $stmt = $this->connexion->query("SELECT DISTINCT filiere FROM etudiant WHERE filiere IS NOT NULL");
         return $stmt->fetchAll(PDO::FETCH_COLUMN);
      }
}
?>