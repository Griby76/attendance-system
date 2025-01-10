<?php
session_start();
session_destroy(); // Détruire la session
header("Location: login.php"); // Rediriger vers la page de connexion
exit();
?>
