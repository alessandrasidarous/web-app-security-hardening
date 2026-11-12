<?php
// includo inizializzazione della sessesione con i header specifici
include __DIR__ . '/../app/Session/init.php';

// includo il content security policy alla pagina
include __DIR__ . '/../app/csp.php';

// Verifica che l'utente sia autenticato
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $_SESSION['error_message'] = "Identità non verificata. Pagina non disponibile senza autenticazione.";
    header("Location: scopri.php");
    exit();
}

//includo il timeout di inattività e assoluto per la sessione
include __DIR__ . '/../app/Session/sessionManager.php';


//verifico se è stato inviato un tweet tramite il form
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    include __DIR__ . '/../app/check_origin.php';

    // controllo che il token del form combaci con quello della sessione,  altrimenti interrompo e ricarico la pagina
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        $_SESSION['error_message'] = "Richiesta non valida. Riprova.";
        logErrore("CSRF token mismatch");
        header("Location: registrazione.php");
        exit();
    }

    include __DIR__ . '/../app/Db/db_privilegiato.php';
    //forzo la codifica dei dati del database
    mysqli_set_charset($conn, "utf8mb4");

    if (isset($_POST['tweet']) && !empty(trim($_POST['tweet'])) && strlen($_POST['tweet'])<141 ) {
        $content = $_POST['tweet'];
        //recupero lo username dell'utente dalla sessione
        $username = $_SESSION['username'];

        // Inserimento del tweet nel database e reindirizzo verso la bacheca se avviene con successo
        $stmt = mysqli_prepare($conn, "INSERT INTO tweets (username, data, testo) VALUES (?, NOW(), ?)");
        if(!$stmt){
            logErrore("Errore prepare scrivi.php: " . mysqli_error($conn));
            $_SESSION['error_message'] = "Errore del server. Riprova più tardi.";
            header("Location: scrivi.php"); 
            exit();
        }

        mysqli_stmt_bind_param($stmt, "ss", $username, $content);
        if (!mysqli_stmt_execute($stmt)) {
            $_SESSION['error_message'] = "Errore nell'invio del twit. Riprova più tardi.";
            logErrore("Errore execute scrivi.php: " . mysqli_error($conn));
            header("Location: scrivi.php"); 
            exit();
        }

        // twit inserito con successo, reindirizzo l'utente nell'area bacheca
        mysqli_stmt_close( $stmt);
        mysqli_close( $conn );
        header("Location: bacheca.php");
        exit();

    }else{
        //caso in cui il tweet è vuoto>ricarico la pagina
        $_SESSION['error_message'] = "Il twit non può essere vuoto.";
        // Caso in cui supera i caratteri max> ricarico la pagina
        if (strlen($_POST['tweet']) > 140){
            $_SESSION['error_message'] = "Il twit non può superare i 140 caratteri.";
        }

        mysqli_close($conn);
        header("Location: scrivi.php");
        exit();
    }
}
?> 

<!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="author" content="Alessandra Sidarous Farag">
        <meta name="keywords" lang="it" content="html">
        <meta name="description" content="Pagina scrittura twit">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/style.css">
        <link rel="icon" href="Immagini/twitter-rounded-border-svgrepo-com.svg" type="image/png">
        <title>Scrivi - Twittle</title>
    </head>

    <body  data-page="scrivi" >
        <?php 
        include 'Componenti/header.php'; // Header con il nome del sito +  ultimo tweet
        include 'Componenti/menu.php'; //contiene il menu di navigazione
        ?>
        <?php
            // Mostra il messaggio di errore se esiste
            if (isset($_SESSION['error_message'])) {
                echo '<div class="err">' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
                // Rimuove il messaggio di errore dalla sessione dopo averlo visualizzato
                unset($_SESSION['error_message']);
            } 
        ?>
        
        <main> 
            <div class="tweet-box">
                <h2>Scrivi un nuovo Twit</h2>
                <h3>&#64;<?php echo htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); ?></h3>

                <!-- Form per scrivere il tweet -->
                <form id="tweetForm" method="post" action="scrivi.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    <label for="tweet">Testo (max 140 caratteri):</label>
                    <textarea id="tweet" name="tweet" maxlength="140" rows="5" placeholder="Cosa vuoi condividere oggi?" required></textarea>
                    <button id="submitTweet" type="submit" class="button">Invia</button>
                    <button id="cancelTweet" type="button" class="button">Cancella</button>
                </form>
            </div>
        </main>

        <?php include "Componenti/footer.php"; ?> <!--footer-->
        <!-- logica javascrip inserita in file outline -->
        <script src="JS/purify.min.js"></script>
        <script src="JS/common.js" nonce="<?php echo $nonce; ?>"></script>
        
    </body>

</html>