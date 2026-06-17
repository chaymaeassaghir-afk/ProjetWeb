<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ControllerSoutenance.php';
require_once __DIR__ . '/../controllers/EtudiantController.php';
require_once __DIR__ . '/../controllers/juryController.php';
require_once __DIR__ . '/../controllers/profController.php';
require_once __DIR__ . '/../models/Planning.php';
require_once __DIR__ . '/../models/jury.php';


class PlanningController {
    private PDO $pdo;

    private SoutenanceController $soutenanceCtrl;
    private EtudiantController $etudiantCtrl;
    private juryController $juryCtrl;
    private profController $profCtrl;
    private Planning $planningModel;
    private jury $juryModel;

    public function __construct(PDO $pdo) {
        $this->pdo   = $pdo;
        $this->soutenanceCtrl = new SoutenanceController($this->pdo);
        $this->etudiantCtrl   = new EtudiantController($this->pdo);
        $this->juryCtrl       = new juryController($this->pdo);
        $this->profCtrl       = new profController($this->pdo);
        $this->planningModel  = new Planning($this->pdo);
        $this->juryModel      = new jury($this->pdo);
    }

    public function affectationFinale() {
        $this->etudiantCtrl->genererAffectation();
        $date=$this->soutenanceCtrl->planifierDates();
        if (!$date) {
            $warning = "Le nombre de jours choisi est insuffisant pour planifier toutes les soutenances.";
            $this->juryModel->delete();
            require_once 'views/soutenance/plannification.php';
            return;
        }
        $this->juryCtrl->affecterJuryAuto();
        $this->soutenanceCtrl->affecterSalles();

        header('Location: index.php?controller=dashboard');
        exit();
    }


        

    
} 