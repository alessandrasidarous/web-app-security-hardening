<?php
include __DIR__ . '/../app/Session/init.php';

// includo il content security policy alla pagina
include __DIR__ . '/../app/csp.php';

//se l'utente è già loggato, reindirizza alla pagina home.php, dove mostra poi il messaggio di errore
if( isset($_SESSION['logged_in']) && $_SESSION['logged_in']){
    $_SESSION['url_access_denied'] = "Sei già loggato."; 
    header("Location: home.php");
    exit();
}


$messageError = "";
include_once __DIR__ . '/../app/log/function_log.php';

//controllo che sia stato inviato il form
if( $_SERVER['REQUEST_METHOD'] == 'POST'){
    include __DIR__ . '/../app/check_origin.php';

     //recupero il token CSFR sia form che dalla sessione e controllo che combacino
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Richiesta non valida. Ricarica la pagina e riprova.";
        logErrore("CSRF token mismatch");
        header("Location: login.php");
        exit();
    }

    if(isset($_POST['user']) && isset($_POST['pwd']) && $_POST['user']!="" && $_POST['pwd']!= ""){
        $username = trim($_POST['user']);
        $password = $_POST['pwd'];

        // validazione input utente prima di inserirli nel bd
        if( !preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{3,9}$/', $username) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=(.*\d){2})(?=(.*[#!?@%^&*+=]){2}).{8,16}$/', $password)){
            $messageError = "Credenziali errate. Nome utente o password non validi.";
        
        }else{
            //mi connetto al db, tramite l'utente normale e controllo se esiste una corrispondenza con l'utente inserito (utilizzo i prepared statement)
            include __DIR__ . '/../app/Db/db_normale.php';
            
            $stmt = mysqli_prepare($conn, "SELECT * FROM utenti WHERE username = ?");
            // implemento i log di errore nel caso di fallimento della query
            if( !$stmt){
                logErrore("Errore prepare login.php: " . mysqli_error($conn));
                $_SESSION['error_message'] = "Errore del server. Riprova più tardi.";
                header("Location: login.php"); 
                exit();
            }

            mysqli_stmt_bind_param($stmt, "s", $username);
            if(!mysqli_stmt_execute($stmt)){
                logErrore("Errore execute login.php: " . mysqli_stmt_error($stmt));
                $_SESSION['error_message'] = "Errore del server. Riprova più tardi.";
                header("Location: login.php"); 
                exit();
            }
            
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) { 
                // lo username esiste, quindi controllo che la password combaci
                include_once __DIR__ . "/../app/security/load_env.php";

                $row = mysqli_fetch_assoc($result);  

                $pepperedPassword = hash_hmac('sha256', $password, $pepper);
                if (password_verify($pepperedPassword,$row['pwd'])){ 
                    //rigenero l'id dopo il cambio di privilegio
                    session_regenerate_id(true);
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['name'] = $row['nome'];
                    $_SESSION['surname'] = $row['cognome'];

                    //imposto i tempi per il timeout
                    $_SESSION['created'] = time();   // absolute timeout
                    $_SESSION['last_activity'] = time(); //idle timeout

                    // Memorizzo lo username in un cookie per 16 ore
                    setcookie('last_username', urlencode($username),[
                        'path' => '/login/',    //ristretto solo all'area login
                        'secure' => true,      // invio dei cookie solo tramite HTTPS
                        'httponly' => true,   // inaccessibile via javascript
                        'samesite' => 'Lax', //prevenzione contro CSRF
                    ]);

                    //chiudo lo statement e la connessione
                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);
                    header('Location: bacheca.php');
                    exit();
                }else{
                    //caso in cui lo username esiste ma la pwd è sbagliata
                    $messageError = "Credenziali errate. Nome utente o password non validi.";
                }
            }else{
                //caso in cui lo username è errato, non esiste
                $messageError = "Credenziali errate. Nome utente o password non validi.";
            }
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
        } 
    }else{
        $_SESSION['error_message'] = "Username e password sono richiesti.";
    }
}

?> 

<!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="author" content="Alessandra Sidarous Farag">
        <meta name="keywords" lang="it" content="html">
        <meta name="description" content="Pagina di login twittle">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/style.css">
        <link rel="icon" href="Immagini/twitter-rounded-border-svgrepo-com.svg" type="image/png">
        <title>Login - Twittle</title>
    </head>

    <body data-page="login">
        <?php 
        include 'Componenti/header.php'; // header pagina ( titolo + logo)
        include 'Componenti/menu.php';  // menu di navigazione


        // Mostra il messaggio di errore se esiste
        if (isset($_SESSION['error_message'])) {
            echo '<div class="err">' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
            // Rimuove il messaggio di errore dalla sessione dopo averlo visualizzato
            unset($_SESSION['error_message']);
        }
        ?>

        <main> 

           <!--form per autenticarsi, con text fields, bottone di invio, cancella e continua senza autenticarti, in caso di errore, questo viene vistualizzato sopra il form -->
           <div class="container">
            <h2>Accedi a Twittle</h2>
                <div class="error-message">
                    <span class="error-message" id="messageError"><?php echo htmlspecialchars($messageError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                </div>
                <form id="loginForm" action="login.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                    <div class="form-component">
                        <label for="user">Username:</label>
                        <input type="text" id="user" name="user" value="<?php echo isset($_COOKIE['last_username']) ? htmlspecialchars($_COOKIE['last_username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : ''; ?>" required>
                    </div>
                    <div class="form-component">
                        <label for="pwd">Password:</label>
                        <input type="password" id="pwd" name="pwd" required>
                    </div>

                    <button id="submit" type="submit" class="button">Invia</button>
                    <button id="cancel" type="button" class="button">Cancella</button>
                    <button id="senzaAutenticazione" type="button" class="button">Continua senza autenticarsi</button>
                </form>
            </div>

        </main>

        <!--footer-->
        <?php include "Componenti/footer.php"; ?> 

        <!-- logica javascrip inserita in file outline -->
        <script src="JS/purify.min.js"></script>
        <script src="JS/common.js" nonce="<?php echo $nonce; ?>"></script>

    </body>
</html>