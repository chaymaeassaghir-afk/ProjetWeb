<?php
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/salle.php';           
require_once __DIR__ . '/../models/prof.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/configuration.php';
require_once 'libs/fpdf186/fpdf.php';

class ImportController {
    private $etudiantModel;
    private $profModel;
    private $salleModel;
    private $configModel;

    public function __construct($pdo) {
        $this->etudiantModel = new Etudiant($pdo);
        $this->profModel = new Prof($pdo);
        $this->salleModel = new Salle($pdo);
        $this->configModel = new Configuration($pdo);
    }

    public function importerToutLeFichier() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $tmp = $_FILES['fichier']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);

        $compteurs = [
            'etudiants' => 0,
            'profs' => 0,
            'salles' => 0,
            'configs' => 0,
        ];

        // ── 1. FEUILLE ETUDIANTS ── (A=nom, B=prenom, C=filiere, D=email)
        $feuilleEtd = $spreadsheet->getSheetByName('etudiants');
        $compteur = 0;
        foreach ($feuilleEtd->getRowIterator() as $ligne) {
            $compteur++;
            if ($compteur == 1) { continue; }

            $nom     = $feuilleEtd->getCell('A' . $compteur)->getValue();
            $prenom  = $feuilleEtd->getCell('B' . $compteur)->getValue();
            $filiere = $feuilleEtd->getCell('C' . $compteur)->getValue();
            $email   = $feuilleEtd->getCell('D' . $compteur)->getValue();

            if (empty($nom)) { continue; }

            $ok = $this->etudiantModel->insert(null, $nom, $prenom, $email, $email, $filiere, null);
            if ($ok) { $compteurs['etudiants']++; }
        }

        // ── 2. FEUILLE PROFS ── (A=nom, B=prenom, C=specialite, D=modules)
        $feuilleProf = $spreadsheet->getSheetByName('profs');
        $compteur = 0;
        foreach ($feuilleProf->getRowIterator() as $ligne) {
            $compteur++;
            if ($compteur == 1) { continue; }

            $nom        = $feuilleProf->getCell('A' . $compteur)->getValue();
            $prenom     = $feuilleProf->getCell('B' . $compteur)->getValue();
            $specialite = trim($feuilleProf->getCell('C' . $compteur)->getValue());

            if (empty($nom)) { continue; }

            $ok = $this->profModel->insert($nom, $prenom, $specialite);
            if ($ok) { $compteurs['profs']++; }
        }

        // ── 3. FEUILLE SALLES ── (A=nom, B=bloc, C=capacite)
        $feuilleSalle = $spreadsheet->getSheetByName('salles');
        $compteur = 0;
        foreach ($feuilleSalle->getRowIterator() as $ligne) {
            $compteur++;
            if ($compteur == 1) { continue; }

            $numero   = $feuilleSalle->getCell('A' . $compteur)->getValue();
            $batiment = $feuilleSalle->getCell('B' . $compteur)->getValue();
            

            if (empty($numero)) { continue; }

            $ok = $this->salleModel->insert($numero,$batiment);
            if ($ok) { $compteurs['salles']++; }
        }

        
        // ── 4. FEUILLE CONFIGS ──
        $feuilleConfig = $spreadsheet->getSheetByName('config');
        $compteur = 0;
        foreach ($feuilleConfig->getRowIterator() as $ligne) {
            $compteur++;
            if ($compteur == 1) { continue; }

            $cle    = $feuilleConfig->getCell('A' . $compteur)->getValue();
            $valeur = $feuilleConfig->getCell('B' . $compteur)->getValue();

            if (empty($cle)) { continue; }

            // Conversion des dates
            if ($cle == 'date_debut' && is_numeric($valeur)) {
                $valeur = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valeur)->format('Y-m-d');
            }

            // Conversion des heures (toutes les clés qui contiennent "heure")
            if (strpos($cle, 'heure') !== false && is_numeric($valeur)) {
                $valeur = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valeur)->format('H:i');
            }

            $ok = $this->configModel->insertOuUpdate($cle, $valeur);
            if ($ok) { $compteurs['configs']++; }
        }

        

        header('Location: /projetweb/index.php?controller=etudiant&page=liste_etudiants');
        exit();
    }
}

?>