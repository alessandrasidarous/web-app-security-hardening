<?php

include_once __DIR__ . '/../log/function_log.php';
/**
 * check_origin_referer.php
 * Funzione per controllare Origin/Referer e difendere da attacchi CSRF.
 */
$allowedDomain = ['https://195.231.2.153.sslip.io'];

 function checkOriginReferer($allowedDomains){
    // Recupera le intestazioni
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    $isValid = false;

    foreach($allowedDomains as $domain){
        //controllo se origin combacia
        if(!empty($origin) && $origin === $domain ){
            $isValid = true;
            break;
        }

        //se origin non c'è o non va bene, controllo referer con strpos, controlla solo che inizi col dominio
        if (!empty($referer) && strpos($referer, $domain) === 0) {
            $isValid = true;
            break;
        }
    }

    if(!$isValid){
        //log dell'evento
        LogErrore('Richiesta con Origin/Referer non valido da: ' . ($origin ?: $referer));
        header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
        echo "Richiesta non autorizzata. Origine non valida.";
        exit();
    }
    
 }


?>