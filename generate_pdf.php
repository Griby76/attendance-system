<?php
ob_start();
require('fpdf.php');

// Récupérer les variables nécessaires
$room_id_ = $_GET['room_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');
if (empty($room_id_)) {
    die("Erreur : L'identifiant de la salle est manquant.");
}

// Connexion à la base de données
$conn = new mysqli('localhost', 'root', '', 'attendance_system');
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer les données de présence
$sql = "
    SELECT a.date, a.status, s.name 
    FROM attendance a
    LEFT JOIN students s ON a.email = s.email
    WHERE a.room_id = ? AND a.date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $room_id_, $date);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();
$conn->close();

// Création du PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10); // Ajout de marges
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode("Liste de présence - Salle $room_id_"), 0, 1, 'C');
$pdf->Ln(10);

// En-têtes du tableau
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 10, 'Date', 1, 0, 'C'); 
$pdf->Cell(80, 10, 'Nom', 1, 0, 'C');
$pdf->Cell(40, 10, 'Statut', 1, 1, 'C');

// Fonction pour gérer les cellules alignées avec texte multi-lignes
function Row($pdf, $widths, $data, $aligns)
{
    $nb = 0;
    foreach ($data as $i => $cell) {
        $nb = max($nb, $pdf->GetStringWidth($cell) / ($widths[$i] - 2)); // Calcul des lignes nécessaires
    }
    $height = ceil($nb) * 6; // Hauteur de ligne calculée en fonction du contenu

    // Dessiner les cellules
    foreach ($data as $i => $cell) {
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Rect($x, $y, $widths[$i], $height); // Dessine les bordures des cellules
        $pdf->MultiCell($widths[$i], 6, utf8_decode($cell), 0, $aligns[$i]);
        $pdf->SetXY($x + $widths[$i], $y); // Déplace le curseur au début de la cellule suivante
    }
    $pdf->Ln($height); // Aller à la ligne suivante après avoir rempli toutes les colonnes
}

// Ajout des données au tableau
$pdf->SetFont('Arial', '', 12);
foreach ($data as $row) {
    Row(
        $pdf,
        [60, 80, 40], // Largeurs des colonnes : date, nom, statut
        [$row['date'], $row['name'] ?? 'Inconnu', $row['status']], // Contenu
        ['C', 'L', 'C'] // Alignements : centre, gauche, centre
    );
}

// Sortie du PDF
ob_end_clean();
$pdf->Output('I', "liste_presence_$room_id_.pdf");
?>
