<?php
require_once('./config/database.php');
require_once('./models/Etudiant.php');
require_once('./models/prof.php');
require_once('./models/soutenance.php');
require_once('./models/jury.php');
require_once 'libs/fpdf186/fpdf.php';
class EtudiantController{
    private $model;
    private $profModel;
    private $stncModel;
    private $juryModel;

    public function __construct($connexion){
        $this->model=new Etudiant($connexion);
        $this->profModel=new Prof($connexion);
        $this->stncModel=new Soutenance($connexion);
        $this->juryModel=new Jury($connexion);
    }
    public function dashboard() {
        // Ajouter ces lignes avant le require
        $statistiques = $this->juryModel->getStatJury();
        if (!$statistiques) {
            $statistiques = [];
        }

        $noms = [];
        $total = [];
        foreach ($statistiques as $s) {
            $noms[] = $s['nom'] . ' ' . $s['prenom'];
            $total[] = $s['total'];
        }
        $noms_json   = json_encode($noms);
        $totaux_json = json_encode($total);

        require 'views/dashboard/dashboard.php';
    }
    public function afficherImport(){
        require $_SERVER['DOCUMENT_ROOT'] . '/projetweb/views/etudiant/import.php';
    }
    public function afficherListe(){
        if(isset($_GET['filiere']) && $_GET['filiere']!=''){
            $filiere=$_GET['filiere'];
            $etudiants=$this->model->getParFiliere($filiere);
        }else{
           $etudiants=$this->model->getEtudiants();
        }
        require $_SERVER['DOCUMENT_ROOT'] . '/projetweb/views/etudiant/liste.php';
       
    }
    public function afficherFormulaireAjout(){
        require $_SERVER['DOCUMENT_ROOT'] . '/projetweb/views/etudiant/ajouteretudiant.php';
    }
    public function ajoutEtd(){
        
        if($_SERVER['REQUEST_METHOD']=='POST'){
            $CNE=$_POST['CNE'];
            $nom=$_POST['nom'];
            $prenom=$_POST['prenom'];
            $email_perso=$_POST['email_perso'];
            $email_pro=$_POST['email_pro'];
            $filiere=$_POST['filiere'];
            $this->model->insert($CNE,$nom,$prenom,$email_perso,$email_pro,$filiere,null);
        }
        header("location:/projetweb/index.php?controller=etudiant&page=liste_etudiants");
        exit();
    }
    public function AfficherModifier(){
        if($_SERVER['REQUEST_METHOD']=='GET'){
            $id=$_GET['id'];
            $etudiant=$this->model->getById($id);
            require $_SERVER['DOCUMENT_ROOT'] . '/projetweb/views/etudiant/modifier.php';
        }
         
    }
    public function traiterModification(){
        if($_SERVER['REQUEST_METHOD']=='POST'){
            $id=$_POST['id'];
            $CNE=$_POST['CNE'];
            $nom=$_POST['nom'];
            $prenom=$_POST['prenom'];
            $email_perso=$_POST['email_perso'];
            $email_pro=$_POST['email_pro'];
            $filiere=$_POST['filiere'];
            $this->model->update($id,$CNE,$nom,$prenom,$email_perso,$email_pro,$filiere);
            
        }
        header("location:/projetweb/index.php?controller=etudiant&page=liste_etudiants");
        exit();
    }

   public function supprimerEtudiant(){
    if($_SERVER['REQUEST_METHOD']=='GET'){
        $id=$_GET['id'];
        $this->model->delete($id);
    }
    header("location:index.php?controller=etudiant&page=liste_etudiants");
    exit();
   }

    public function remplirSoutenances(string $filiere) {
        $etudiants = $this->model->getParFiliere($filiere);
        foreach ($etudiants as $etudiant) {
            $this->stncModel->insert($etudiant['id_etudiant']);
        }

    }


    public function importerEtudiants(){
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $filiere=$_POST['filiere'];
            $tmp=$_FILES['fichier']['tmp_name'];
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
            $feuille=$spreadsheet->getActiveSheet();
            $compteur=0;
            foreach($feuille->getRowIterator() as $ligne){
                $compteur++;
                if($compteur==1){continue ;} //sauter l'entete
                $CNE=$feuille->getCell('A'.$compteur)->getValue();
                $nom=$feuille->getCell('B'.$compteur)->getValue();
                $prenom=$feuille->getCell('C'.$compteur)->getValue();
                $email_perso=$feuille->getCell('D'.$compteur)->getValue();
                $email_pro=$feuille->getCell('E'.$compteur)->getValue();
                if(empty($nom)){continue ;}
                $this->model->insert($CNE,$nom,$prenom,$email_perso,$email_pro,$filiere,null);
            }
            $this->remplirSoutenances($filiere);
            header("location:index.php?controller=etudiant&page=liste_etudiants");
            exit();
        }
    }

    //douaa : debut
    public function genererAffectation() {
        $etudiants = $this->model->getEtudiants();
        $profs = $this->profModel->getProfs();
        foreach ($etudiants as $etudiant) {

            // profs disponibles
            $profsDisponibles = [];

            // vérifier nombre d'affectations
            foreach($profs as $prof) {

                $nb = $this->profModel->getNbEtudiants($prof['id']);
                if($nb < 4) {
                    $profsDisponibles[] = $prof;
                }
            }
            if(empty($profsDisponibles)) {
                continue; // aucun prof dispo, on skip
            }
            $profAleatoire = $profsDisponibles[array_rand($profsDisponibles)];

            $this->model->affecterProf(

                $etudiant['id_etudiant'],   
                $profAleatoire['id']
            );
        }

        
    }     
    
    public function genererPDF() {

        require_once 'libs/fpdf186/fpdf.php';

        $data = $this->model->getEtudiantsAvecEncadrants();
        $groupes = [];

        foreach ($data as $row) {

            $key = $row['nom_prof'] . ' ' . $row['prenom_prof'];

            $groupes[$key][] = $row;
        }

        $pdf = new FPDF('L', 'mm', 'A4'); 
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(
            0,
            10,
            iconv('UTF-8', 'windows-1252//TRANSLIT', 'Encadrement des Étudiants'),
            0,
            1,
            'C'
        );

        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 10);

        $colors = [
            ':Genie Informatique '   => [200, 230, 255],':Transformation Digitale & AI ' => [255, 230, 200],  ':Ingenieurie des donnees' => [220, 255, 220],
        ];

        foreach ($colors as $fil => $rgb) {

            $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);

            // petit carré couleur
            $pdf->Cell(10, 6, '', 1, 0, '', true);

            // texte filière
            $pdf->Cell(
                25,
                6,
                iconv('UTF-8', 'windows-1252//TRANSLIT', $fil),
                0,
                0
            );
            $pdf->Cell(70, 6, '', 0, 0);
        }

        $pdf->Ln(8);


        $pdf->SetFont('Arial', 'B', 11);

        $pdf->Cell(60, 10, 'Encadrants', 1, 0, 'C');
        $pdf->Cell(220, 10, 'Etudiants encadres', 1, 1, 'C');
        $pdf->Cell(60, 10, 'Nom Prenom', 1, 0, 'C');
        $pdf->Cell(55, 10, 'Etudiant 1', 1, 0, 'C');
        $pdf->Cell(55, 10, 'Etudiant 2', 1, 0, 'C');
        $pdf->Cell(55, 10, 'Etudiant 3', 1, 0, 'C');
        $pdf->Cell(55, 10, 'Etudiant 4', 1, 1, 'C');
        $colors = [
        'GI'   => [200, 230, 255], 
        'TDIA' => [255, 230, 200], 
        'DATA'   => [220, 255, 220], ];
        $pdf->SetFont('Arial', '', 10);

        foreach ($groupes as $prof => $etudiants) {

            // PROF
            $pdf->Cell(
                60,
                10,
                iconv('UTF-8', 'windows-1252//TRANSLIT', $prof),
                1
            );

            for ($i = 0; $i < 4; $i++) {

                if (isset($etudiants[$i])) {

                    $e = $etudiants[$i];

                    $nom = $e['nom_etudiant'] . ' ' . $e['prenom_etudiant'];
                    $fil = $e['filiere'];

                    // couleur selon filière
                    if (isset($colors[$fil])) {

                        $pdf->SetFillColor(
                            $colors[$fil][0],
                            $colors[$fil][1],
                            $colors[$fil][2]
                        );

                        $fill = true;

                    } else {

                        // IMPORTANT : reset couleur par défaut
                        $pdf->SetFillColor(245, 245, 245); // gris clair

                        $fill = true;
                    }

                    $pdf->Cell(
                        55,
                        10,
                        iconv('UTF-8', 'windows-1252//TRANSLIT', $nom),
                        1,
                        0,
                        'C',
                        $fill
                    );

                } else {
                    $pdf->Cell(55, 10, '-', 1, 0, 'C');
                }
            }

            $pdf->Ln();
        }

        // IMPORTANT : rien après Output()
        $pdf->Output('D', 'encadrement.pdf');
        
    }
    public function affecterEtGenererPDF() {
        $this->genererAffectation();
        $this->genererPDF();       
    }
    //douaa : fin
}

?>