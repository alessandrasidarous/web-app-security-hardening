<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('id'); 
    session_start();
}

$username = '';
$lastTweet = '';

//controllo che l'utente sia loggato, attraverso la sessione dedicata
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    //recupero lo username dalla sessione utente
    $username = $_SESSION['username'];

    try{
        include __DIR__ . '/../app/Db/db_normale.php';
        mysqli_set_charset($conn, "utf8mb4");

        $query = "SELECT testo FROM tweets WHERE username = ? ORDER BY data DESC LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);

        if( !$stmt){
            throw new Exception("Preparazione query fallita: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "s", $username);
        if(!mysqli_stmt_execute($stmt)){
            throw new Exception("Esecuzione query fallita: " . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_bind_result($stmt, $tweet);
        //prendo la sottostringa dei primi 30 caratteri
        if (mysqli_stmt_fetch($stmt)) {
            $lastTweet = mb_substr($tweet, 0, 30, 'UTF-8');
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

    } catch (Exception $e){
        logErrore("Errore nel recupero ultimo tweet: " . $e->getMessage());
    }
}

if ($lastTweet!="") {
    echo "<div class='ultimo_tweet'>
        <div class='username'>&#64;". htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'). "</div>";
    $encoded = base64_encode($lastTweet);
    echo '<p class="last-tweet" data-raw="' . $encoded . '"></p>';
    echo "</div>";
}

?>