<?php
include __DIR__ . '/../app/Session/init.php';

// includo il content security policy alla pagina
include __DIR__ . '/../app/csp.php';

// Include il file di configurazione per la connessione al database
include __DIR__ . '/../app/Db/db_normale.php';


// Recupera tutti i tweet dal db (prende i tweet dalla tab tweet e il nome-cognome da tab utenti )
$query = "SELECT u.nome, u.cognome,u.username, t.data, t.testo FROM tweets t INNER JOIN utenti u ON t.username = u.username ORDER BY t.data DESC";
$result = mysqli_query($conn, $query);

//log di erroer nel caso di fallimento della query
if(!$result){
    logErrore("Errore prepare scopri.php: " . mysqli_error($conn));
    $_SESSION['error_message'] = "Errore del server. Riprova più tardi.";
    header("Location: scopri.php"); 
    exit();
}

$tweets = [];
if (!$result) {
    $_SESSION['error_message'] = "Impossibile caricare i twit.";
} else {
    $tweets = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
mysqli_close($conn);

// Funzione helper per output sicuro
function esc_html($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?> 

<!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="author" content="Alessandra Sidarous Farag">
        <meta name="keywords" lang="it" content="html">
        <meta name="description" content="Pagina esplora di Twittle">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/style.css">
        <link rel="icon" href="Immagini/twitter-rounded-border-svgrepo-com.svg" type="image/png">
        <title>Scopri - Twittle</title>
    </head>

    <body>
        <?php 
        include 'Componenti/header.php'; // Header con il nome del sito + ultimo tweet
        include 'Componenti/menu.php'; //contiene il menu di navigazione
        ?>
        <?php
            // Mostra il messaggio di errore se esiste
            if (isset($_SESSION['error_message'])) {
                echo '<div class="err">' . esc_html($_SESSION['error_message']) . '</div>';
                // Rimuovo il messaggio di errore dalla sessione dopo averlo visualizzato
                unset($_SESSION['error_message']);
            }
        ?>
        <main> 
            
            <div class="tweet-list">
                <h2>Scopri gli ultimi twit</h2>
                <p>Benvenuto su Twittle! Qui puoi leggere tutti i twit condivisi dagli utenti.</p>
                <!--Visualizzazione di tutti i tweet contenuti nel database in un elenco-->
                <?php
                if (!empty($tweets)) {
                    foreach($tweets as $tweet){
                        echo '<div class="tweet">';
                            echo '<div class="header">';
                                echo '<span class="author">'. esc_html($tweet['nome']) ." ". esc_html($tweet['cognome']) ."</span>";
                                echo '<span class="username">&#64;' . esc_html($tweet['username']) .'</span>';
                                echo '<span class="date">'. esc_html($tweet['data']) ."</span>";
                            echo "</div>";
                            $encoded = base64_encode($tweet['testo']);
                            echo '<p class="content" data-raw="' . $encoded . '"></p>';
                        echo "</div>";
                    }
                }else{
                    echo '<p class="no-tweets">Non ci sono tweet al momento.</p>';

                }
                ?>
            </div>
           

        </main>

        <?php include "Componenti/footer.php"; ?> <!--footer-->

         <!-- logica javascrip inserita in file outline -->
         <script src="JS/purify.min.js"></script>
        <script src="JS/common.js" nonce="<?php echo $nonce; ?>"></script>
    </body>
</html>