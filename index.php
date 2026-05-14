<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

require_once __DIR__ . '/vendor/autoload.php';

// ── Chargement des controllers ──
require_once 'controllers/EtudiantController.php';
require_once 'controllers/sallecontroller.php';
require_once 'controllers/ControllerSoutenance.php';
require_once 'controllers/PvController.php';
require_once 'controllers/ConfigurationController.php';
require_once 'controllers/juryController.php';
require_once 'controllers/profController.php';
require_once 'controllers/PlanningController.php';
require_once 'models/Planning.php';



// ── Récupérer controller et page depuis l'URL ──
$controller = $_GET['controller'] ?? 'etudiant';
$page       = $_GET['page']       ?? 'dashboard';

// ── Instancier le bon controller ──
switch($controller) {
    case 'salle':
        $ctrl = new SalleController($pdo);
        break;
    case 'soutenance':
        $ctrl = new SoutenanceController($pdo);
        break;
    case 'prof':
        $ctrl= new profController($pdo);
        break;   
    case 'etudiant':
        $ctrl = new EtudiantController($pdo);
        break;
    case 'pv':
        $ctrl = new PvController($pdo);
        break;
    case 'configuration':
        $ctrl = new ConfigurationController($pdo);
        break;    
    case 'jury':
        $ctrl = new JuryController($pdo);
        break;  
    case 'finale':   
        $planningCtrl = new PlanningController($pdo);
        break; 
    case 'genererPDF':
        $planninModel = new Planning($pdo);
        break;  
    case 'dashboard':
    default:    
        $ctrl = new JuryController($pdo);
        break;
}

// ── Début du HTML (layout principal) ──
?>


<?php

// ── Router : appeler la bonne méthode selon controller + page ──
switch($controller) {

    // ── ÉTUDIANT ──
    case 'etudiant':
        switch($page) {
            case 'dashboard':
                $ctrl->dashboard();
                break;
            case 'liste_etudiants':
                $ctrl->afficherListe();
                break;
            case 'ajouter_etudiant':
                $ctrl->afficherFormulaireAjout(); // remplace par afficherFormulaireAjout() quand prêt
                break;
            case 'importer_etudiants':
                if($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $ctrl->importerEtudiants();
                } else {
                    $ctrl->afficherImport();
                }
                break;
            case 'traiter_ajout':
                $ctrl->ajoutEtd();
                break;
            case 'afficher_modifier':
                $ctrl->AfficherModifier();//va afficher le formulaire pre-remplie
                break;
            case 'traiter_modifier':
                $ctrl->traiterModification();
                break;
            case 'supprimer_etudiant':
                $ctrl->supprimerEtudiant();
                break;
            case 'genererAffectation':
                $ctrl->genererAffectation();
                break; 
            case 'affecter_pdf':
                $ctrl->genererPDF();
                break;  

            default:
                $ctrl->dashboard();
                break;       
            
        }
        break;

    // ── SALLE ──
    case 'salle':
        $action = $_GET['action'] ?? $page;
        switch($action) {
            case 'liste':
            case 'supprimer':    
                $ctrl->index();
                break;
            case 'ajouter':
                
                $ctrl->ajouter();
                
                break;
            case 'disponible':
                $ctrl->disponible();
                break;    
            case 'modifier':
                if($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $ctrl->modifier();
                } else {
                    $ctrl->afficherFormulaireModifier((int)$_GET['id']);
                }
                break;
            case 'supprimer':
                $ctrl->supprimer((int)$_GET['id']);
                break;
            default:
                $ctrl->index();
                break;
        }
        break;

    // ── SOUTENANCE ──
    case 'soutenance':
        $action = $_GET['page'] ?? $page;
        switch($page) {
            case 'affecterSalles':
                $ctrl->affecterSalles();
                break;
            case 'planifier':
                $ctrl->planifierDates();
                break;
            case 'afficherFormulairePlanification':
                $ctrl->afficherFormulairePlanification();
                break;
            default:
                echo "<h3>Soutenances</h3>";
                break;
        }
        break;

    //PV
    case 'pv':
        switch ($page) {
            case 'index':
                // Vue principale
                $ctrl->index();
                break;
            case 'telecharger':
                $scope       = $_GET['scope'] ?? 'tous';
                $encadrantId = (int)($_GET['encadrant_id'] ?? 0);
                $etudiantId  = (int)($_GET['etudiant_id'] ?? 0);
                match ($scope) {
                    'encadrant' => $encadrantId > 0
                        ? $ctrl->_telechargerParEncadrant($encadrantId)
                        : die("encadrant_id requis"),
                    'etudiant' => $etudiantId > 0
                        ? $ctrl->_telechargerUnPV($etudiantId)
                        : die("etudiant_id requis"),
                    default => $ctrl->_telechargerTous(),
                };
                break;
            case 'generer':
                $etudiantId = (int)($_GET['etudiant_id'] ?? 0);
                if ($etudiantId > 0) {
                    $ctrl->_genererPV($etudiantId);
                } else {
                    echo "ID invalide";
                }
                break;
            default:
                $ctrl->index();
                break;
        }
        break;
    //configuration 
    case 'configuration':
        switch ($page) {
            case 'index':
                $ctrl->index();
                break;
            case 'modifier':
                $ctrl->modifier();
                break;
            default:
                $ctrl->index();
                break;
        }
        break;    
    //jury
    case 'jury':
        switch ($page) {
            case 'index':
                $ctrl->index();
                break;
            case 'ajouter':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $ctrl->store();
                } else {
                    $ctrl->create();
                }
                break;
            case 'modifier':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $ctrl->updateJury();
                } else {
                    $ctrl->edit();
                }
                break;
            case 'supprimer':
                $ctrl->delete((int)($_GET['id'] ?? 0));
                break;
            case 'affecter':
                $ctrl->affecterJuryAuto();
                break;
            case 'dashboard':
            default:    
                $ctrl->afficherDashboard();
                break;
        } 
        break;   

    //prof
    case 'prof':
        require_once 'controllers/import_profs.php';

        break; 
    case 'liste_prof':
        require_once 'views/prof/liste_profs.php';  
        break; 
    //planning
    case 'finale':
        $planningCtrl->affectationFinale();
        break; 
    case 'genererPDF':
        
        $planninModel->genererPlanningPDF();
        break;    

    // ── DASHBOARD ──
    case 'dashboard':
    default:
        $ctrl->afficherDashboard();
        break;
}

?>