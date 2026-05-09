<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

require_once __DIR__ . '/vendor/autoload.php';

// ── Chargement des controllers ──
require_once 'controllers/EtudiantController.php';
require_once 'controllers/sallecontroller.php';
require_once 'controllers/ControllerSoutenance.php';



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
    /*case 'prof':
        $ctrl= new profController($pdo);
        break;   */ 
    case 'etudiant':
    default:
        $ctrl = new EtudiantController($pdo);
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
        $action = $_GET['action'] ?? $page;
        switch($action) {
            case 'affecterSalles':
                $resultat = $ctrl->affecterSalles();
                echo "<div class='alert alert-" . ($resultat['affectees'] > 0 ? 'success' : 'warning') . "'>";
                echo "✅ " . $resultat['affectees'] . " soutenance(s) affectée(s)";
                if(!empty($resultat['conflits'])) {
                    echo " — ⚠️ " . count($resultat['conflits']) . " conflit(s)";
                }
                echo "</div>";
                break;
            default:
                echo "<h3>Soutenances</h3>";
                break;
        }
        break;
    case 'prof':
        require_once 'controllers/import_profs.php';

        break; 
    case 'liste_prof':
        require_once 'views/prof/liste_profs.php';   

    // ── DASHBOARD ──
    default:
        echo "<h3>Bienvenue sur GestPFE 👋</h3>";
        echo "<p>Utilisez le menu à gauche pour naviguer.</p>";
        break;
}

?>
