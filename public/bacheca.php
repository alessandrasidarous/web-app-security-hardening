<?php
include __DIR__ . '/../app/Session/init.php';

// includo il content security policy alla pagina
include __DIR__ . '/../app/csp.php';

//se l'utente non risulta loggato, tramitte la sessione dedicata, viene reindirizzato alla pg di login
if(!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']){
    $_SESSION['error_message'] = "Identità non verificata. Pagina non disponibile senza autenticazione.";
    header("Location: login.php");
    exit();
}

//includo il timeout di inattività e assoluto per la sessione
include __DIR__ . '/../app/Session/sessionManager.php';


// Recupero i dati dell'utente autenticato dalla sessione
$username = $_SESSION['username'];
$name = $_SESSION['name'];
$surname = $_SESSION['surname'];

// Funzione helper per output sicuro
function esc_html($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

//file di configurazione per la connessione al db
include __DIR__ . '/../app/Db/db_normale.php'; 
$filterQuery = "";
$star_date = $end_date = "";

//filtra i tweet in base all'intervallo temporale, assicurando che end_date sia dopo start_date, altrimenti ricarica la pagina
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include __DIR__ . '/../app/check_origin.php';

    //recupero il token CSFR sia form che dalla sessione e controllo che combacino
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        $_SESSION['error_message'] = "Richiesta non valida. Riprova.";
        logErrore("CSRF token mismatch");
        header("Location: bacheca.php");
        exit();
    }

    if(isset($_POST['start_date']) && isset($_POST['end_date'])){
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        if ($start_date > $end_date) {
            $_SESSION['error_message'] = "La data di fine deve essere successiva a quella di inizio.";
            header("Location: bacheca.php");
            exit();
        }

        //query che viene aggiunta a quella di default, nel caso si richiedesse uno specifico lasso temporale
        $filterQuery = "AND data BETWEEN ? AND ?";
    }
}

//query per recuperare i tweet dell'utente
$query = "SELECT * FROM tweets WHERE username = ? $filterQuery ORDER BY tweets.data DESC";
$stmt = mysqli_prepare($conn, $query);

if(!$stmt){
    logErrore("Errore prepare bacheca.php: " . mysqli_error($conn));
    $_SESSION['error_message'] = "Errore del server. Riprova più tardi.";
    header("Location: bacheca.php"); 
    exit();
}

// Associa i parametri alla query, se è attivo un filtro temporale
if ($filterQuery) {
    mysqli_stmt_bind_param($stmt, "sss", $username, $start_date, $end_date);
}else{
    mysqli_stmt_bind_param($stmt, "s", $username);
}

//forzo la codifica dei dati del database
mysqli_set_charset($conn, "utf8mb4");

//eseguo la query ed estraggo il risultato
if(!mysqli_stmt_execute($stmt)){
    logErrore("Errore execute bacheca.php: " . mysqli_error($conn));
    $_SESSION['error_message'] = "Errore del server. Riprova più tardi.";
    header("Location: bacheca.php"); 
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$tweets = [];
while ($row = mysqli_fetch_assoc($result)){
    $tweets[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close( $conn );

?>

<!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="author" content="Alessandra Sidarous Farag">
        <meta name="keywords" lang="it" content="html">
        <meta name="description" content="Bacheca di Twittle">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/style.css">
        <link rel="icon" href="Immagini/twitter-rounded-border-svgrepo-com.svg" type="image/png">
        <title>Bacheca - Twittle</title>
    </head>

    <body>
        <?php 
        include 'Componenti/header.php'; // Header con il nome del sito, logo e ultimo tweet
        include 'Componenti/menu.php'; //contiene il menu di navigazione
        ?>

        <div>
        <?php  //mostro il messaggio di errore se esiste
            if (isset($_SESSION['error_message'])) {
                echo '<div class="err">' . esc_html($_SESSION['error_message']) . '</div>';
                // Rimuove il messaggio di errore dalla sessione dopo averlo visualizzato
                unset($_SESSION['error_message']);
            }
        ?>
        </div>

        <main>
            <div class="tweet-list">
                <h2><?php echo esc_html($name) . " " . esc_html($surname); ?></h2>
                <h3>&#64;<?php echo esc_html($username); ?></h3>

                <!--filtro temporale fatto da un form, con data di inizio, fine e un bottone submit -->
                <div class="filter-form">
                    <form id="filter" method="post" action="bacheca.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                        <label for="start_date">Data inizio:</label>
                        <input type="datetime-local" id="start_date" name="start_date" value="" required>
                        
                        <label for="end_date">Data fine:</label>
                        <input type="datetime-local" id="end_date" name="end_date" value="" required>
                        
                        <button id="filterBtn" type="submit">Filtra</button>
                    </form>
                </div>

                <!--Visualizzazione dei tweet salvati nell'array tweets se presenti-->
                <?php if(count($tweets)==0 && $filterQuery==""){
                    echo '<p class="no-tweets">È un po\' vuoto qui! <a href="scrivi.php">Scrivi un nuovo twit!</a></p>';
                    }elseif(count($tweets)>0){
                        foreach($tweets as $tweet){
                            echo '<div class="tweet">';
                                echo '<div class="header">';
                                    echo '<span class="author">'. esc_html($name) ." ". esc_html($surname) ."</span>";
                                    echo '<span class="username">&#64;' . esc_html($tweet['username']) .'</span>';
                                    echo '<span class="date">'. esc_html($tweet['data']) ."</span>";
                                echo "</div>";
                                $encoded = base64_encode($tweet['testo']);
                                echo '<p class="content" data-raw="' . $encoded . '"></p>';

                            echo "</div>";
                        }
                    }else{
                        echo '<p class="no-tweets">Non ci sono twit nelle date selezionate!</p>';
                    }
                ?>
                
            </div>
        </main>

        <?php include "Componenti/footer.php" ?> <!--footer-->
        
        <!-- logica javascrip inserita in file outline -->
        <script src="JS/purify.min.js"></script>
        <script src="JS/common.js" nonce="<?php echo $nonce; ?>"></script>
    </body>
</html>