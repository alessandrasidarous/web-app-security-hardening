<?php
include __DIR__ . '/../app/Session/init.php';

// includo il content security policy alla pagina
include __DIR__ .  '/../app/csp.php';

//nel caso l'utente sia autenticato, aggiungo le sessioni di timeout
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    include __DIR__ . '/../app/Session/sessionManager.php';
}
?>


<!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="author" content="Alessandra Sidarous Farag">
        <meta name="keywords" lang="it" content="html">
        <meta name="description" content="Homepage di Twittle">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/style.css">
        <link rel="icon" href="Immagini/twitter-rounded-border-svgrepo-com.svg" type="image/png">
        <title>Home - Twittle</title>
    </head>

    <body data-page="home">
        <?php 
        include 'Componenti/header.php'; // Header con il nome del sito, logo e ultimo tweet
        include 'Componenti/menu.php'; //contiene il menu di navigazione
        ?>
        <?php
            // Mostro il messaggio di errore, se presente
            if (isset($_SESSION['url_access_denied']) && $_SESSION['url_access_denied'] != "") {
                echo "<div class='err'>". htmlentities($_SESSION['url_access_denied'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ."</div>";
                // Resetto il messaggio di errore dopo averlo mostrato
                $_SESSION['url_access_denied'] = "";  
            }
        ?>
        <main> 
            <div class="columns">
                <div class="left-column">
                    <div class="subtitle">  
                        <h2>Benvenuto su Twittle</h2> 
                    </div>
                    <img src="Immagini/twitter-rounded-border-svgrepo-com.svg" class="logo" alt="Logo Twittle" > 
                    <p>Twittle ti aiuta a connetterti e rimanere in contatto con le persone della tua vita.</p>     
                </div> 

                <div class="right-column">
                    <!--se un utente risulta loggato, reindirizza alla bacheca/scrivi, se no, consiglia di accedere/registrarsi-->
                    <div class="button-container">
                        <?php     
                        if( !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']){  ?>
                            <p> Sei nuovo qui? Iscriviti adesso!</p>
                            <button id="btn-registrati" type="button" class="button">Registrati</button>
                            <p> Hai già un account?</p>
                            <button id="btn-login" type="button" class="button">Accedi</button>
                        <?php }else{ ?>
                            <p>Vuoi rimanere aggiornato sugli ultimi twit?</p>
                            <button id="btn-scopri" type="button" class="button">Scopri</button>
                            <p>Scrivi un nuovo twit!</p>
                            <button id="btn-scrivi" type="button" class="button">Scrivi</button>
                        <?php } ?>
                    </div>  
                </div>
            </div>

            <!--descrizione delle funzionalità di twittle -->
            <div class="dropdown-menu">
                <h3>Con Twittle puoi</h3>
                <ul>
                    <li>Creare il tuo profilo ed iniziare a condividere i tuoi pensieri</li>
                    <li>Esprimere i tuoi pensieri nella sezione 'Scrivi'</li>
                    <li>Connetterti con le persone e le community attraverso la sezione 'Scopri'</li>
                </ul>
            </div>
        </main>

        <!--footer-->
        <?php include 'Componenti/footer.php'; ?> 

        <!-- logica javascrip inserita in file outline -->
        <script src="JS/purify.min.js"></script>
        <script src="JS/common.js" nonce="<?php echo $nonce; ?>"></script>
    </body>
</html>