<?php
session_start();
if (!isset($_SESSION['teacher_email'])) {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
$conn = new mysqli('localhost', 'root', '', 'attendance_system');
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupérer les paramètres
$date = $_GET['date'] ?? date('Y-m-d'); // Par défaut, la date actuelle
$room_id = $_GET['room_id'] ?? 1; // Par défaut, la salle 1

// Rechercher la première heure d'enregistrement dans la salle
$sql = "SELECT MIN(timestamp) AS start_time FROM attendance WHERE room_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $room_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$start_time = $row['start_time'] ?? date('Y-m-d H:i:s'); // Si pas d'enregistrements, utilise l'heure actuelle

// Durée limite (1 heure après le début de la session)
$time_limit = strtotime($start_time) + 60 * 60; // 1 heure
$current_time = time();
$remaining_time = max(0, $time_limit - $current_time);

// Récupérer tous les étudiants avec leur statut
$sql = "SELECT 
            students.email, 
            students.name, 
            rooms.name AS room_name, 
            IFNULL(attendance.status, 'absent') AS status, 
            IFNULL(attendance.date, ?) AS date 
        FROM students
        LEFT JOIN attendance ON students.email = attendance.email AND attendance.date = ? AND attendance.room_id = ?
        LEFT JOIN rooms ON attendance.room_id = rooms.id
        ORDER BY students.name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $date, $date, $room_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance View</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%; /* Assure que le corps prend toute la hauteur */
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
        }
        .container {
            min-height: 100%; /* S'assure que la partie blanche occupe toute la hauteur */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 100%;
            padding: 15px;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            padding: 12px 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #007bff;
            color: #fff;
        }
        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .button {
            display: inline-block;
            margin: 10px 5px;
            padding: 10px 15px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .status-present {
            color: #155724;
            font-weight: bold;
        }
        .status-absent {
            color: #721c24;
            font-weight: bold;
        }
        .timer {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
            color: #555;
        }
        @media screen and (max-width: 600px) {
            .container {
                padding: 10px;
                box-shadow: none;
            }
            table th, table td {
                padding: 8px 5px;
                font-size: 12px;
            }
            .button {
                padding: 8px 12px;
                font-size: 12px;
            }
            h1 {
                font-size: 20px;
            }
            .timer {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Présences pour la salle <?= htmlspecialchars($room_id) ?> le <?= htmlspecialchars($date) ?></h1>
        <div class="timer" id="timer">Chargement du timer...</div>
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Nom</th>
                    <th>Salle</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['room_name'] ?? 'Salle non attribuée') ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td class="<?= $row['status'] === 'present' ? 'status-present' : 'status-absent' ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="footer">
            <a href="generate_pdf.php?room_id=<?= htmlspecialchars($room_id) ?>&date=<?= htmlspecialchars($date) ?>" 
               class="button">
                Télécharger la liste (PDF)
            </a>
        </div>
        <div class="footer">
            <a href="attendance.php?room_id=<?= htmlspecialchars($room_id) ?>" class="button">Retour à l'Appel</a>
        </div>
        <div class="footer">
            <a href="logout.php" class="button">Déconnexion</a>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
