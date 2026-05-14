<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ControllerSoutenance.php';
require_once __DIR__ . '/../controllers/EtudiantController.php';
require_once __DIR__ . '/../controllers/juryController.php';
require_once __DIR__ . '/../controllers/profController.php';
require_once __DIR__ . '/../models/Planning.php';
require_once __DIR__ . '/../models/soutenance.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/jury.php';
require_once __DIR__ . '/../models/prof.php';
require_once __DIR__ . '/../models/salle.php';
require_once __DIR__ . '/../models/Configuration.php';

class Planning {
    private PDO $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    public function genererPlanningPDF() {

        require_once 'libs/fpdf186/fpdf.php';

        $stmt = $this->pdo->query("

        SELECT 
            s.date ,
            s.heure_debut ,
            sa.numero_salle,
            sa.batiment,

            -- Encadrant
            p.nom AS nom_enc,
            p.prenom AS prenom_enc,

            -- Jury Président
            prof_pres.nom AS jury1_nom,
            prof_pres.prenom AS jury1_prenom,

            -- Jury Rapporteur
            prof_rap.nom AS jury2_nom,
            prof_rap.prenom AS jury2_prenom,

            -- Étudiant
            e.nom AS nom_et,
            e.prenom AS prenom_et,
            e.filiere

        FROM soutenance s

        JOIN etudiant e
            ON s.etudiant_id = e.id_etudiant

        JOIN professeur p
            ON e.id_prof = p.id

        -- Président
        LEFT JOIN jury j_pres
            ON s.id_stnc = j_pres.id_soutenance
            AND j_pres.role = 'Président'

        LEFT JOIN professeur prof_pres
            ON j_pres.id_prof = prof_pres.id

        -- Rapporteur
        LEFT JOIN jury j_rap
            ON s.id_stnc = j_rap.id_soutenance
            AND j_rap.role = 'Rapporteur'

        LEFT JOIN professeur prof_rap
            ON j_rap.id_prof = prof_rap.id

        -- Salle
        LEFT JOIN salle sa
            ON s.id_salle = sa.id_salle

        ORDER BY s.date, s.heure_debut

    ");

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new FPDF('L','mm','A4');
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',15);

        $pdf->Cell(
            0,
            10,
            iconv('UTF-8','windows-1252//TRANSLIT',
            'Planning des Soutenances'),
            0,
            1,
            'C'
        );

        $pdf->Ln(5);

        // couleurs filières
        $filieres = [
            'GI'   => [220,235,255],
            'TDIA' => [255,235,220],
            'DATA' => [225,255,225]
        ];

        // couleurs profs
        $profColors = [];

        foreach($data as $row) {

            $prof = $row['nom_enc'].' '.$row['prenom_enc'];

            if(!isset($profColors[$prof])) {

                $profColors[$prof] = [
                    rand(160,230),
                    rand(160,230),
                    rand(160,230)
                ];
            }
        }

        // GROUPER PAR JOUR
        $jours = [];

        foreach($data as $row) {

            $jours[$row['date']][] = $row;
        }

        foreach($jours as $date => $soutenances) {

            // titre du jour
            $pdf->SetFont('Arial','B',12);

            $pdf->Cell(
                0,
                10,
                iconv('UTF-8','windows-1252//TRANSLIT',
                'Jour : '.$date),
                0,
                1
            );

            // header
            $pdf->SetFont('Arial','B',9);

            $pdf->Cell(50,7,'Encadrant',1,0,'C');
            $pdf->Cell(50,7,'Jury 1',1,0,'C');
            $pdf->Cell(50,7,'Jury 2',1,0,'C');
            $pdf->Cell(25,7,'Heure',1,0,'C');
            $pdf->Cell(25,7,'Salle',1,0,'C');
            $pdf->Cell(50,7,'Etudiant',1,0,'C');
            $pdf->Cell(30,7,'Filiere',1,1,'C');

            $pdf->SetFont('Arial','',8);

            foreach($soutenances as $s) {

                $prof = $s['nom_enc'].' '.$s['prenom_enc'];

                $pc = $profColors[$prof];

                $pdf->SetFillColor($pc[0],$pc[1],$pc[2]);

                $pdf->Cell(
                    50,
                    7,
                    iconv('UTF-8','windows-1252//TRANSLIT',$prof),
                    1,
                    0,
                    'C',
                    true
                );

                $jury1 = $s['jury1_nom'].' '.$s['jury1_prenom'];
                $c1=$profColors[$jury1] ?? [255,255,255];
                $pdf->SetFillColor($c1[0],$c1[1],$c1[2]);
                $pdf->Cell(
                    50,
                    7,
                    iconv('UTF-8','windows-1252//TRANSLIT',$jury1),
                    1,
                    0,
                    'C',
                    true
                );
                $jury2 = $s['jury2_nom'].' '.$s['jury2_prenom'];
                $c2=$profColors[$jury2] ?? [255,255,255];
                $pdf->SetFillColor($c2[0],$c2[1],$c2[2]);    
                $pdf->Cell(
                    50,
                    7,
                    iconv('UTF-8','windows-1252//TRANSLIT',$jury2),
                    1,
                    0,
                    'C',
                    true
                );

                $pdf->Cell(25,7,$s['heure_debut'],1,0,'C');
                $salle= $s['batiment'].''.$s['numero_salle'];
                $pdf->Cell(
                    25,
                    7,
                    iconv('UTF-8','windows-1252//TRANSLIT',$salle),
                    1,
                    0,
                    'C'
                );

                $etu = $s['nom_et'].' '.$s['prenom_et'];

                $pdf->Cell(
                    50,
                    7,
                    iconv('UTF-8','windows-1252//TRANSLIT',$etu),
                    1
                );

                $f = $s['filiere'];
                if(isset($filieres[$f])) {

                    $c = $filieres[$f];

                    $pdf->SetFillColor($c[0],$c[1],$c[2]);

                    $fill = true;

                } else {

                    $fill = false;
                }

                $pdf->Cell(
                    30,
                    7,
                    $f,
                    1,
                    1,
                    'C',
                    $fill
                );
            }

            $pdf->Ln(8);
        }

        $pdf->Output('D','planning_soutenances.pdf');
        exit;
    }
    



}