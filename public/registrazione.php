<?php
include __DIR__ . '/../app/Session/init.php'; //inizalizzazione della sessione 

// includo il content security policy alla pagina
include __DIR__ . '/../app/csp.php';

//se l'utente è già loggato, reindirizza alla pagina home.php, dove mostra poi il messaggio di errore
if( isset($_SESSION['logged_in']) && $_SESSION['logged_in']){
    $_SESSION['url_access_denied'] = "Sei già loggato."; 
    header("Location: home.php");
    exit();
}


//inizializzo le variabili di gestione dell'errore
$nameError = $surnameError = $birthdateError = $addressError = $usernameError = $passwordError = "";

//controllo che il form sia stato inviato
if ($_SERVER['REQUEST_METHOD'] == 'POST') { 
    include __DIR__ . '/../app/check_origin.php';

    // controllo che il token del form combaci con quello della sessione,  altrimenti interrompo e ricarico la pagina
    if(!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
        $_SESSION['error_message'] = "Richiesta non valida. Riprova.";
        logErrore("CSRF token mismatch");
        header("Location: registrazione.php");
        exit();
    }

    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $birthdate = trim($_POST['birthdate']);
    $address = trim($_POST['address']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $isCorrect = true;

    //inizio il controllo dei vari parametri con l'uso delle regex
    if (!preg_match('/^[A-Z][a-zA-Z ]{1,11}$/', $name)) {
        $nameError = "Deve iniziare con lettera maiuscola e contere solo lettere o spazi, da 2 a 12 caratteri.";
        $isCorrect = false;
    }
    if (!preg_match('/^[A-Z][a-zA-Z ]{1,15}$/', $surname)) {
        $surnameError = "Deve iniziare con lettera maiuscola e contere solo lettere o spazi, da 2 a 16 caratteri.";
        $isCorrect = false;
    }
    if (!preg_match('/^(?:\d{4})-(?:0?[1-9]|1[0-2])-(?:0?[1-9]|[12][0-9]|3[01])$/', $birthdate)) {
        $birthdateError = "Deve essere nel formato 'aaaa-mm-gg'.";
        $isCorrect  = false;
    }else{
        //il formato è corretto, quindi controllo che sia effettivamente una data valida
        $dateParts = explode('-', $birthdate);
        if (!checkdate($dateParts[1], $dateParts[2], $dateParts[0]) || ($dateParts[0] <1900)) {
            $birthdateError = "Data non valida.";
            $isCorrect = false;
        }
    }
    if (!preg_match('/^(Via|Corso|Largo|Piazza|Vicolo) [a-zA-Z ]+ \d{1,4}$/', $address)) {
        $addressError = "Deve essere nel formato 'Via/Corso/Largo/Piazza/Vicolo nome numeroCivico'.";
        $isCorrect = false;
    }
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{3,9}$/', $username)) {
        $usernameError = "Deve iniziare con una lettera e contenere solo lettere, numeri, '-' o '_', da 4 a 10 caratteri.";
        $isCorrect = false;
    }
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=(.*\d){2})(?=(.*[#!?@%^&*+=]){2}).{8,16}$/', $password)){
        $passwordError = "Deve avere 8-16 caratteri, con almeno 1 lettera maiuscola, 1 minuscola, 2 numeri e 2 caratteri speciali tra #!?@%^&*+=";
        $isCorrect = false;
    }

    if( $isCorrect){
        $isUnique = true;

        if (checkUsernameExists($username)) {
            $usernameError = "Inserisci un altro username: " . htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')  . " è già in uso";
            $isUnique = false;
        }  

        if($isUnique){
            if (registerUser($name, $surname, $birthdate, $address, $username, $password)) { 
                //registro l'utente, salvo i dati di sessione e proseguo con l'utente autenticato
                session_regenerate_id(true);
                $_SESSION['username'] = $username; 
                $_SESSION['name'] = $name;
                $_SESSION['surname'] = $surname;
                $_SESSION['logged_in'] = true;
                $_SESSION['created'] = time();
                $_SESSION['last_activity'] = time();
                header("Location: bacheca.php");
                exit();
            }else{
                $_SESSION['error_message'] = "Errore generico. Riprova più tardi.";
            }
        }
    }
}


//funzione che controlla se esiste già un utente con lo stesso username ( ritorna true se esiste  una corrispondenza)
// loggo anche i possibili errori di connessione al db
function checkUsernameExists($username) { 
    include __DIR__ . "/../app/Db/db_normale.php";

    $stmt = mysqli_prepare($conn, "SELECT * FROM utenti WHERE username = ?");
    if(!$stmt){
        logErrore("Errore prepare registrazione.php: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Errore generico. Riprova più tardi.";
        header("Location: registrazione.php"); 
        exit();
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    if(!mysqli_stmt_execute($stmt)){
        logErrore("Errore execute registrazione.php: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Errore generico. Riprova più tardi.";
        header("Location: registrazione.php"); 
        exit();
    }

    $result = mysqli_stmt_get_result($stmt);
    // verifico se esiste almeno un risultato
    $exists = mysqli_num_rows($result) > 0; 
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $exists;
}


// funzione che aggiunge al db un nuovo utente, ritorna true se è stato aggiunto correttamente
function registerUser($name, $surname, $birthdate, $address, $username, $password) { 
    include __DIR__ . "/../app/Db/db_privilegiato.php";
    include_once __DIR__ . "/../app/security/load_env.php";

    $peppered = hash_hmac('sha256', $password, $pepper);

    //hash della password tramite Argon2id
    $hashedPassword = password_hash($peppered, PASSWORD_ARGON2ID);

    $stmt = mysqli_prepare($conn, "INSERT INTO utenti (nome, cognome, data, indirizzo, username, pwd) VALUES (?, ?, ?, ?, ?, ?)");
    if(!$stmt){
        logErrore("Errore prapare registrazione.php: " .mysqli_error($conn));
        //il messsaggio di errore da mostrare al client viene già inplementato prima quando viene chiamata la funzione
        return false;
    }
    mysqli_stmt_bind_param($stmt, "ssssss", $name, $surname, $birthdate, $address, $username, $hashedPassword);
    if(!mysqli_stmt_execute($stmt)){
        logErrore("Errore execute registrazione.php: " . mysqli_stmt_error($stmt));
        return false;
    }
    //assicuro che sia effettivamente stato aggiunto al db
    $result = mysqli_affected_rows($conn) > 0; 
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $result;
}


// Funzione helper per output sicuro, si occupa di fare l'escaping delle variabili nel client
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
        <meta name="description" content="Twittle pagina di registrazione">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/style.css">
        <link rel="icon" href="Immagini/twitter-rounded-border-svgrepo-com.svg" type="image/png">
        <title>Registrazione - Twittle</title>
    </head>
    
    <body  data-page="registrazione" >
        <?php 
            include 'Componenti/header.php';  //Header con il nome del sito e il logo
            include 'Componenti/menu.php'; // contiene il menu di navigazione
            
            if (isset($_SESSION['error_message'])) {
                echo '<div class="err">' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
                // Rimuovo il messaggio di errore dalla sessione dopo averlo visualizzato
                unset($_SESSION['error_message']);
            }
         ?>

        <main> 
            <div class="container">
                <h2>Crea un nuovo account</h2>
                <!-- Form di registrazione, nel caso di errore, questo viene mostrato sotto ogni casella di testo ed i valori inseriti rimangono invariati, non si resettano -->
                <form name="form-registrazione" method="post" action="registrazione.php">
                    <input type="hidden" name="csrf_token" value="<?php echo esc_html($_SESSION['csrf_token']); ?>">
                    <div class="form-component">
                        <label for="name">Nome:</label>
                        <input type="text" id="name" name="name" required value="<?php echo isset($name) ? esc_html($name) : ''; ?>" >
                        <span class="error-message" id="nameError"><?php echo esc_html($nameError) ?></span>
                    </div>
                    <div class="form-component">
                        <label for="surname">Cognome:</label>
                        <input type="text" id="surname" name="surname" required value="<?php echo isset($surname) ? esc_html($surname) : ''; ?>" >
                        <span class="error-message" id="surnameError"><?php echo esc_html($surnameError) ?></span>
                    </div>
                    <div class="form-component">
                        <label for="birthdate">Data di nascita:</label>
                        <input type="text" id="birthdate" name="birthdate" required value="<?php echo isset($birthdate) ? esc_html($birthdate) : ''; ?>">
                        <span class="error-message" id="birthdateError"><?php echo esc_html($birthdateError) ?></span>
                    </div>
                    <div class="form-component">
                        <label for="address">Indirizzo:</label>
                        <input type="text" id="address" name="address" required value="<?php echo isset($address) ? esc_html($address) : ''; ?>" >
                        <span class="error-message" id="addressError"><?php echo esc_html($addressError) ?></span>
                    </div>
                    <div class="form-component">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required value="<?php echo isset($username) ? esc_html($username) : ''; ?>" >
                        <span class="error-message" id="usernameError"><?php echo esc_html($usernameError) ?></span>
                    </div>
                    <div class="form-component">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                        <span class="error-message" id="passwordError"><?php echo esc_html($passwordError)?></span>
                    </div>
                    <button type="submit" class="button">Registrati</button>
                </form>
            </div> 

        </main>
        <?php include "Componenti/footer.php"; ?> <!--footer-->

        <!--  logica di controllo lato client in js outline   -->
        <script src="JS/purify.min.js"></script>
        <script src="JS/common.js" nonce="<?php echo $nonce; ?>"></script>
        
    </body>

</html>