<?php
include __DIR__ . '/../app/Session/init.php';

//non genera contenuto, ma fa solo redirect, quindi non ho messo il header con la content security policy, ma c'è già nella pagina dove reindirizzo

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $_SESSION['error_message'] = "Identità non verificata! Non hai permesso di usare questa funzionalità senza autenticazione.";
    header("Location: login.php");
    exit();
}

//svuoto le variabili di sessione in memoria
$_SESSION = [];

//controllo se la sessione usa i cookie per mantenere l'id di sess, se si, lo cancello
if(ini_get("session.use_cookies")){
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// cancello la sessione da disco
session_destroy();

// Reindirizzo alla pagina di  home.php dopo il logout
header("Location: login.php");
exit();
?>