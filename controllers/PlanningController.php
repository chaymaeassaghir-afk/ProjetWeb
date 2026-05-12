<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ControllerSoutenance.php';
require_once __DIR__ . '/../controllers/EtudiantController.php';
require_once __DIR__ . '/../controllers/juryController.php';
require_once __DIR__ . '/../controllers/profController.php';


class PlanningController {
    private PDO $pdo;

    private SoutenanceController $soutenanceCtrl;
    private EtudiantController $etudiantCtrl;
    private juryController $juryCtrl;
    private profController $profCtrl;

    public function __construct(PDO $pdo) {
        $this->pdo   = $pdo;
        $this->soutenanceCtrl = new SoutenanceController($this->pdo);
        $this->etudiantCtrl   = new EtudiantController($this->pdo);
        $this->juryCtrl       = new juryController($this->pdo);
        $this->profCtrl       = new profController($this->pdo);
    }

    public function affectationFinale() {
        
        $this->etudiantCtrl->genererAffectation();
        $this->soutenanceCtrl->planifierDates();
        $this->juryCtrl->affecterJuryAuto();
        $this->soutenanceCtrl->affecterSalles();
        
    }
        

    
} 