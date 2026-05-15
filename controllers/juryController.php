<?php

require_once './models/jury.php';
require_once './models/prof.php';

class juryController {

    private jury $jury;

    public function __construct(private PDO $pdo) {

        $this->jury = new jury($pdo);
    }

   

    public function index() {

        $jurys = $this->jury->getAll();

        include '../views/jury/index.php';
    }

   

    public function create() {

        include '../views/jury/create.php';
    }

    

    public function store() {

        $id_soutenance = $_POST['id_soutenance'] ?? '';
        $id_prof       = $_POST['id_prof'] ?? '';
        $role          = $_POST['role'] ?? '';

        if (
            empty($id_soutenance) ||
            empty($id_prof) ||
            empty($role)
        ) {

            $erreur = "Tous les champs sont obligatoires.";

            include '../views/jury/create.php';

            return;
        }

        $result = $this->jury->insert(
            (int)$id_soutenance,
            (int)$id_prof,
            $role
        );

        if ($result) {

            header('Location: index.php?controller=jury&page=index');

        } else {

            $erreur = "Erreur lors de l'ajout.";

            include '../views/jury/create.php';
        }
    }

  

    public function edit() {

        $id = $_GET['id'] ?? null;

        $jury = $this->jury->getById((int)$id);

        if (!$jury) {

            $erreur = "Jury introuvable.";

            include '../views/jury/index.php';

            return;
        }

        include '../views/jury/edit.php';
    }

    

    public function updateJury() {

        $id_jury       = $_POST['id_jury'] ?? '';
        $id_soutenance = $_POST['id_soutenance'] ?? '';
        $id_prof       = $_POST['id_prof'] ?? '';
        $role          = $_POST['role'] ?? '';

        if (
            empty($id_jury) ||
            empty($id_soutenance) ||
            empty($id_prof) ||
            empty($role)
        ) {

            $erreur = "Tous les champs sont obligatoires.";

            $jury = $this->jury->getById((int)$id_jury);

            include '../views/jury/edit.php';

            return;
        }

        $this->jury->update(
            (int)$id_jury,
            (int)$id_soutenance,
            (int)$id_prof,
            $role
        );

        header('Location: index.php?controller=jury&page=index');
    }

    

    public function delete() {

        $id = $_GET['id'] ?? null;

        $this->jury->delete((int)$id);

        header('Location: index.php?controller=jury&page=index');
    }

   

    public function membresSoutenance() {

        $id_soutenance = $_GET['id_soutenance'] ?? null;

        $membres = $this->jury->getMembresBySoutenance(
            (int)$id_soutenance
        );

        include '../views/jury/membres.php';
    }
    #debut israe
    public function afficherProfsDisponibles(int $id_soutenance): void {
        

        // compter combien de jurys info déjà affectés
        $nb_info_actuel = $this->jury->getNbJuryInfoDispo($id_soutenance);

        if($nb_info_actuel < 2) {
            $profs_info   = $this->jury->getProfDispo($id_soutenance, true);   // info priorité
            $profs_autres = $this->jury->getProfDispo($id_soutenance, false);  // tous les autres
            
        } else {
            $profs_info   = [];
            $profs_autres = $this->jury->getProfDispo($id_soutenance, false);
           
        }

        
    }

    public function affecterJuryAuto(){
        $soutenances = $this->jury->getSoutenances();

        foreach ($soutenances as $soutenance){
            $id = $soutenance['id_stnc'];
            $nbJury = $this->jury->juryDejaAffecte($id);

            if($nbJury >= 3){
                continue;
            }

            // Compter combien de profs info sont déjà dans ce jury
            $nbInfoActuel = $this->jury->getNbJuryInfoDispo($id);

            if($nbJury == 1){
                if($nbInfoActuel ==0 ){
                    // Besoin de 2 profs -> on prend 2 profs info en priorité
                    $profs = $this->jury->getProfDispo($id, true); // info seulement
                    if(count($profs) >= 2){
                        $this->jury->insert($id, $profs[0]['id'], "Président");
                        $this->jury->insert($id, $profs[1]['id'], "Rapporteur");
                    } elseif(count($profs) == 1){
                        
                        $profInfo = $profs[0];
                        $autresProfs = $this->jury->getProfDispo($id, false);
                        $autresProfs = array_filter(
                            $autresProfs,
                            fn($p) => $p['id'] !== $profInfo['id']
                        );
                        $autresProfs = array_values($autresProfs);
                        if(count($autresProfs) >= 1){
                            $this->jury->insert($id, $profInfo['id'],      "Président");
                            $this->jury->insert($id, $autresProfs[0]['id'], "Rapporteur");
                        } else {
                            echo "Soutenance $id : impossible d'affecter un jury complet.\n";
                        }

                    } else {
                        
                        echo "Soutenance $id : aucun prof informatique disponible.\n";
                    }
                }elseif($nbInfoActuel == 1){
                    // Besoin de 1 prof info + 1 autre prof
                    $profsInfo = $this->jury->getProfDispo($id, true);
                    $profsAutres = $this->jury->getProfDispo($id, false);
                    if(count($profsInfo) >= 1 && count($profsAutres) >= 1){
                        $this->jury->insert($id, $profsInfo[0]['id'], "Président");
                        $this->jury->insert($id, $profsAutres[0]['id'], "Rapporteur");
                    } else {
                        echo "Soutenance $id : impossible d'affecter un jury complet.\n";
                    }
                }
            } elseif($nbJury == 2){
                // Besoin d'1 seul prof supplémentaire
                $besoinInfo = ($nbInfoActuel < 2);
                $profs = $this->jury->getProfDispo($id, $besoinInfo);

                if(count($profs) >= 1){
                    $this->jury->insert($id, $profs[0]['id'], "Rapporteur");
                } else {
                    //si aucun prof info est dispo on prend un autre prof avec une specialite quelconque
                    if($besoinInfo){
                        $profs = $this->jury->getProfDispo($id, false);
                    }
                    if(count($profs) >= 1){
                        $this->jury->insert($id, $profs[0]['id'], "Rapporteur");
                    } else {
                        echo "Soutenance $id : aucun prof disponible pour compléter le jury.\n";
                    }
                }
            }
        }
    }
    #fin israe
    public function afficherDashboard() {
    // Stats jury
        $statistiques = $this->jury->getStatJury();
        $noms = [];
        $totaux = [];
        foreach ($statistiques as $s) {
            $noms[]   = $s['nom'] . ' ' . $s['prenom'];
            $totaux[] = $s['total'];
        }
        $noms_json   = json_encode($noms);
        $totaux_json = json_encode($totaux);

        // Stats encadrant
        $stats_enc = $this->jury->getStatEncadrant();
        $noms_enc   = [];
        $totaux_enc = [];
        foreach ($stats_enc as $s) {
            $noms_enc[]   = $s['nom'] . ' ' . $s['prenom'];
            $totaux_enc[] = $s['total'];
        }
        $noms_enc_json   = json_encode($noms_enc);
        $totaux_enc_json = json_encode($totaux_enc);

        // Stats filière
        $stats_fil   = $this->jury->getStatParfiliere();
        $filieres    = [];
        $totaux_fil  = [];
        foreach ($stats_fil as $s) {
            $filieres[]   = $s['filiere'];
            $totaux_fil[] = $s['total'];
        }
        $filieres_json   = json_encode($filieres);
        $totaux_fil_json = json_encode($totaux_fil);

        require $_SERVER['DOCUMENT_ROOT'] . '/projetweb/views/dashboard/dashboard.php';
    }
}

?>