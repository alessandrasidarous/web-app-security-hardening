<?php

// funzione che mi stampa i log in errori.log 
function logErrore($msg) {
    $timestamp = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP_UNKNOWN';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UA_UNKNOWN';
    $url = $_SERVER['REQUEST_URI'] ?? 'URL_UNKNOWN';
    $percorso = __DIR__ . "/errori.log";  
    error_log("[$timestamp] IP=$ip | URL=$url | UA=$userAgent | MSG=$msg\n", 3, $percorso);
}


?>