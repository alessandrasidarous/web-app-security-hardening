<?php
// Genero un nonce sicuro per la sessione corrente
$nonce = base64_encode(random_bytes(16));

// Imposto l'header CSP con il nonce incluso
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; style-src 'self'; img-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'self';");

?>