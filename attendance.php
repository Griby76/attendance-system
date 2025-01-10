<?php
session_start(); // Démarrer la session

// Connexion à la base de données
$conn = new mysqli('localhost', 'root', '', 'attendance_system');
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Initialiser les messages
$message = null;
$room_id = $_GET['room_id'] ?? 1; // Récupère le room_id depuis l'URL (par défaut : 1)
$user_ip = $_SERVER['REMOTE_ADDR']; // Récupérer l'adresse IP de l'utilisateur

// Vérifier si l'utilisateur est déjà inscrit (par session ou IP)
if (isset($_SESSION['user_email'])) {
    $message = "Vous êtes déjà inscrit avec l'email " . htmlspecialchars($_SESSION['user_email']) . ". Vous ne pouvez pas inscrire une autre personne tant que vous êtes présent.";
} else {
    // Vérifier si l'adresse IP a déjà été utilisée pour cette salle et cette date
    $date = date('Y-m-d');
    $sql = "SELECT * FROM attendance WHERE ip_address = ? AND date = ? AND room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $user_ip, $date, $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $message = "Erreur : Une inscription a déjà été effectuée depuis cette adresse IP.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'];

        // Vérifier si l'email existe dans la table students
        $sql = "SELECT * FROM students WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message = "Erreur : Cet email n'est pas enregistré.";
        } else {
            // Vérifier si la présence a déjà été enregistrée
            $sql = "SELECT * FROM attendance WHERE email = ? AND date = ? AND room_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $email, $date, $room_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $message = "Votre présence a déjà été enregistrée.";
            } else {
                // Insérer une nouvelle présence avec l'adresse IP
                $sql = "INSERT INTO attendance (email, date, room_id, status, ip_address) VALUES (?, ?, ?, 'present', ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssis", $email, $date, $room_id, $user_ip);

                if ($stmt->execute()) {
                    // Marquer la session comme connectée pour l'utilisateur
                    $_SESSION['user_email'] = $email;
                    $message = "Présence enregistrée avec succès.";
                } else {
                    $message = "Erreur lors de l'enregistrement : " . $stmt->error;
                }
            }
        }
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enregistrement de Présence</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 8px;
            color: #555;
        }
        input[type="email"], button {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .message.success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .button-secondary {
            background-color: #6c757d;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            padding: 10px;
            display: block;
            margin-top: 15px;
        }
        .button-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Enregistrement de Présence</h1>
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'succès') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if (!isset($_SESSION['user_email'])): ?>
        <form method="POST">
            <label for="email">Email :</label>
            <input type="email" name="email" id="email" required>
            <input type="hidden" name="room_id" value="<?= htmlspecialchars($room_id) ?>">
            <button type="submit">Enregistrer</button>
        </form>
        <?php else: ?>
            <div class="message success">
                Vous êtes déjà inscrit avec l'email <?= htmlspecialchars($_SESSION['user_email']) ?>.<br>
                Vous ne pouvez pas inscrire une autre personne tant que vous êtes présent.
            </div>
        <?php endif; ?>
        <!-- Lien vers la page de connexion des professeurs -->
        <a href="login.php?redirect=view_attendance.php&room_id=<?= htmlspecialchars($room_id) ?>" class="button-secondary">Connexion Professeur</a>
    </div>
</body>
</html>
<?php $conn->close(); ?>
