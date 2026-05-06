<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/salle.php';

class SalleController {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ──────────────────────────────────────────
    // Afficher la liste des salles
    // ──────────────────────────────────────────
    public function index(): void {
        $model  = new salle($this->pdo);
        $salles = $model->listersalles();

        // envoie la variable $salles à la vue
        require_once __DIR__ . '/../index1.php';
    }

    // ──────────────────────────────────────────
    // Afficher le formulaire d'ajout
    // ──────────────────────────────────────────
    public function afficherFormulaireAjout(): void {
        require_once __DIR__ . '/../views/salles/form.php';
    }

    // ──────────────────────────────────────────
    // Traiter l'ajout d'une salle (reçoit $_POST)
    // ──────────────────────────────────────────
    public function ajouter(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->rediriger('index');
            return;
        }

        // récupérer les données du formulaire
        $numero_salle = trim($_POST['numero_salle'] ?? '');
        $batiment     = trim($_POST['batiment']     ?? '');
        $id_salle     = (int) ($_POST['id_salle']   ?? 0);

        // validation
        if (empty($numero_salle)) {
            $erreur = "Le numéro de salle est obligatoire.";
            require_once __DIR__ . '/../views/salles/form.php';
            return;
        }

        // appeler le model pour insérer
        $model = new salle($this->pdo);
        $model->setNumero_salle($numero_salle);
        $model->setBatiment($batiment);
        $model->setId_salle($id_salle);
        $model->ajouterSalle();

        // rediriger vers la liste
        $this->rediriger('index');
    }

    // ──────────────────────────────────────────
    // Afficher le formulaire de modification
    // ──────────────────────────────────────────
    public function afficherFormulaireModifier(int $id): void {
        $model            = new salle($this->pdo);
        $salle_a_modifier = $model->trouversalleParId($id);

        if (!$salle_a_modifier) {
            $erreur = "Salle introuvable.";
            require_once __DIR__ . '/../views/salles/index.php';
            return;
        }

        // envoie $salle_a_modifier à la vue
        require_once __DIR__ . '/../views/salles/form.php';
    }

    // ──────────────────────────────────────────
    // Traiter la modification (reçoit $_POST)
    // ──────────────────────────────────────────
    public function modifier(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->rediriger('index');
            return;
        }

        $id           = (int)   ($_POST['id']           ?? 0);
        $numero_salle = trim($_POST['numero_salle'] ?? '');
        $batiment     = trim($_POST['batiment']     ?? '');

        // validation
        if (empty($numero_salle) || $id === 0) {
            $erreur = "Données invalides.";
            require_once __DIR__ . '/../views/salles/form.php';
            return;
        }

        $model = new salle($this->pdo);
        $s     = $model->trouversalleParId($id);

        if ($s) {
            $s->setNumero_salle($numero_salle);
            $s->setBatiment($batiment);
            $s->modifiersalle();
        }

        $this->rediriger('index');
    }

    // ──────────────────────────────────────────
    // Supprimer une salle
    // ──────────────────────────────────────────
    public function supprimer(int $id): void {
        $model = new salle($this->pdo);
        $s     = $model->trouversalleParId($id);

        if (!$s) {
            $this->rediriger('index');
            return;
        }

        try {
            $s->supprimersalle();
        } catch (RuntimeException $e) {
            $erreur = $e->getMessage();
            $salles = $model->listersalles();
            require_once __DIR__ . '/../views/salles/index.php';
            return;
        }

        $this->rediriger('index');
    }

    
    // ──────────────────────────────────────────
    // Méthode privée : rediriger vers une action
    // ──────────────────────────────────────────
    private function rediriger(string $action): void {
        header("Location: index.php?controller=salle&action=$action");
        exit;
    }
}
