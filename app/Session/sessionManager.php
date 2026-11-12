<?php

// sessionManager.php

//setto i TIMEOUT 
$idleTimeout = 900;   // timeout di inattività di 15 minuti
$absoluteTimeout = 14400; // timeout assoluto di 4 ore

//timeout di inattività
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $idleTimeout)){
    session_unset();
    session_destroy();
    header("Location: login.php");
    $_SESSION['error_message'] = "La sessione è scaduta per inattività. Effettua nuovamente il login.";
    exit();
}

$_SESSION['last_activity'] = time();

//timeout assoluto
if(isset($_SESSION['created']) && (time() - $_SESSION['created'] > $absoluteTimeout)){
    session_unset();
    session_destroy();
    header("Location: login.php");
    $_SESSION['error_message'] = "La sessione è scaduta. Effettua nuovamente il login.";
    exit();
}


?>