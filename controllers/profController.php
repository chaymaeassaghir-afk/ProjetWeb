<?php

require_once './models/prof.php';

class profController {

    private prof $prof;

    public function __construct(private PDO $pdo) {
        $this->prof = new prof($pdo);
    }

    // Afficher tous les profs
    public function index() {
        $profs = $this->prof->getAll();
        include '../views/prof/index.php';
    }

    // Afficher le formulaire d'ajout
    public function create() {
        include '../views/prof/create.php';
    }

    // Insérer un prof
    public function store() {
        $nom        = $_POST['nom']        ?? '';
        $prenom     = $_POST['prenom']     ?? '';
        $specialite = $_POST['specialite'] ?? '';

        if (empty($nom) || empty($prenom) || empty($specialite)) {
            $erreur = "Tous les champs sont obligatoires.";
            include '../views/prof/create.php';
            return;
        }

        $result = $this->prof->insert($nom, $prenom, $specialite);

        if ($result) {
            header('Location: index.php?page=prof&action=index');
        } else {
            $erreur = "Ce prof existe déjà.";
            include '../views/prof/create.php';
        }
    }

    // Afficher le formulaire de modification
    public function edit() {
        $id   = $_GET['id'] ?? null;
        $prof = $this->prof->getById((int)$id);

        if (!$prof) {
            $erreur = "Prof introuvable.";
            include '../views/prof/index.php';
            return;
        }

        include '../views/prof/edit.php';
    }

    // Modifier un prof
    public function updateProf() {
        $id         = $_POST['id']         ?? null;
        $nom        = $_POST['nom']        ?? '';
        $prenom     = $_POST['prenom']     ?? '';
        $specialite = $_POST['specialite'] ?? '';

        if (empty($nom) || empty($prenom) || empty($specialite)) {
            $erreur = "Tous les champs sont obligatoires.";
            $prof   = $this->prof->getById((int)$id);
            include '../views/prof/edit.php';
            return;
        }

        $this->prof->update((int)$id, $nom, $prenom, $specialite);
        header('Location: index.php?page=prof&action=index');
    }

    // Supprimer un prof
    public function delete() {
        $id = $_GET['id'] ?? null;
        $this->prof->delete((int)$id);
        header('Location: index.php?page=prof&action=index');
    }

    

    // Afficher le nombre d'étudiants d'un prof
    public function nbEtudiants() {
        $id  = $_GET['id'] ?? null;
        $nb  = $this->prof->getNbEtudiants((int)$id);
        $prof = $this->prof->getById((int)$id);
        include '../views/prof/nb_etudiants.php';
    }


    
}
?>