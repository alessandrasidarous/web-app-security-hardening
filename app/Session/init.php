<?php
// Imposto le direttive di sicurezza della sessione PRIMA di session_start()
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1); 
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); 
ini_set('session.cookie_samesite', 'Strict');

ini_set('display_errors', '0'); //non mostra gli errori lato brower (runtime error), evitando di rilevare info sensibili
ini_set('log_errors', '1'); // registra gli errori in file di log
ini_set('error_log', '/config/workspace/PWR_twittle_TESI/app/log/errori.log'); //non funziona, mancano i permessi di scrittura
ini_set('expose_php', 'Off');
ini_set('file_uploads', '0');

// Cambio il nome del cookie di sessione
session_name('id'); 

//avvio della sessione, se non è già attiva
if( session_status() !== PHP_SESSION_ACTIVE ){
    session_start();
}  

// generazione token CSRF se non esiste
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Intestazioni per impedire caching lato browser
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false); // Compatibilità IE
header("Pragma: no-cache");

// Intestazioni di sicurezza
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");  // o DENY
header("Referrer-Policy: no-referrer");

//intestazione HSTS solo se la pagina è su https
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}


?>