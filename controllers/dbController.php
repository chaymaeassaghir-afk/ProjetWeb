<?php
require_once __DIR__ . '/../config/database.php';

class dbController {
    private PDO $pdo;
    public function __construct(PDO $pdo){
        $this->pdo=$pdo;
    }
    public function deleteAll(){
        $stmt = $this->pdo->prepare("DELETE FROM pv");
        $stmt->execute();
        $stmt = $this->pdo->prepare("DELETE FROM jury");
        $stmt->execute();
        $stmt = $this->pdo->prepare("DELETE FROM soutenance");
        $stmt->execute();   
        $stmt = $this->pdo->prepare("DELETE FROM etudiant");
        $stmt->execute();
        $stmt = $this->pdo->prepare("DELETE FROM professeur");
        $stmt->execute();
        
        header("Location: index.php?controller=dashboard");
        exit;
        

    }
   
}
