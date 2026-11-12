<?php
$serverName = $_SERVER['SERVER_ADDR'];
$usernamedb = "privilegiato";
$passwordDb = "SuperPippo!!!";
$dbname = "social_network";

// Creo la connessione per l'utente privilegiato
$conn = mysqli_connect($serverName, $usernamedb, $passwordDb, $dbname);

// Imposto il set di caratteri a utf8mb4 per la connessione
mysqli_set_charset($conn, "utf8mb4");

include_once __DIR__ . '/../log/function_log.php';

// Controllo la connessione
if (mysqli_connect_errno()) {
    logErrore("Connessione fallita: " . mysqli_connect_error());
    die("<p>Errore del server. Riprova più tardi.</p>\n");
}
?>
