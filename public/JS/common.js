"use strict";

window.addEventListener("DOMContentLoaded", () => {
    // Recupera il valore di data-page dal <body data-page="...">
    
    const currentPage = document.body.dataset.page;
    



    // ---- implementazione della logica DOMPurify (per area bacheca e scopri) -----
    // nel php stampo il testo non pulito ma sicuro tramite htmlspecialchars dentro data-raw
    //in js prendo il testo, lo sanifico e lo inserisco poi nel contenuto HTML
    //anche se sto usando innerHTML, API non sicura poiché interpreta HTML a differenza di .textContent ( utile per testo semplice)
    // lo rendo sicuro poiché sanifico il contenuto con DOMPurify, permettendo così di far interpretare alcuni tag html sicuri

    const allowedTags = ['b', 'i', 'u', 'strong', 'em', 'a'];
    const allowedAttrs = ['href', 'title', 'target', 'rel'];

    // Hook per filtrare link pericolosi
    DOMPurify.addHook('afterSanitizeAttributes', function (node) {
        if (node.tagName === 'A' && node.hasAttribute('href')) {
            const href = node.getAttribute('href').trim();
    
            // Accetta solo http o https, altrimenti rimuove href
            if (!/^https:\/\//i.test(href)) {
                node.removeAttribute('href');
            } else {
                // Aggiunge attributi per prevenire reverse tabnabbing
                node.setAttribute('rel', 'noopener noreferrer');
                node.setAttribute('target', '_blank');
            }
        }
    });
    

    //sanifico ogni tweet in uscita
    document.querySelectorAll('[data-raw]').forEach(el => {
        // Prendo il testo originale da data-raw
        const encoded = el.getAttribute('data-raw');

        // Decodifica da base64
        let dirty = '';
        try {
            dirty = atob(encoded);
        } catch (e) {
            dirty = '';
        }

        // Pulisco con DOMPurify lasciando solo i tag e attributi consentiti
        const clean = DOMPurify.sanitize(dirty, {
          ALLOWED_TAGS: allowedTags,
          ALLOWED_ATTR: allowedAttrs
        });

        // Inserisco il contenuto sanificato in pagina come HTML
        el.innerHTML = clean;
      });




    // -------- logica della pagina HOME!! --------
    if (currentPage === "home") {
        const registratiBtn = document.getElementById("btn-registrati");
        const loginBtn = document.getElementById("btn-login");
        const scopriBtn = document.getElementById("btn-scopri");
        const scriviBtn = document.getElementById("btn-scrivi");

        //implementazione dei 4 bottoni che reindirizzano verso registrazione, login o scrivi e bacheca
        if (registratiBtn) {
            registratiBtn.addEventListener("click", () => {
            window.location.href = "registrazione.php";
            });
        }

        if (loginBtn) {
            loginBtn.addEventListener("click", () => {
            window.location.href = "login.php";
            });
        }

        if (scopriBtn) {
            scopriBtn.addEventListener("click", () => {
            window.location.href = "scopri.php";
            });
        }

        if (scriviBtn) {
            scriviBtn.addEventListener("click", () => {
            window.location.href = "scrivi.php";
            });
        }

    }


    // -- logica della pagina REGISTRAZIONE!! --
    if (currentPage === "registrazione") {

        const form = document.forms["form-registrazione"];
        if (form) {
            form.addEventListener("submit", function (event) {
                if (!validateForm()) {
                    event.preventDefault(); // blocca l'invio se ci sono errori
                }
            });
        }

        // logica di validazione del form di registrazione
        function validateForm() {
            let isCorrect = true;

            const name = document.getElementById("name").value.trim();
            const surname = document.getElementById("surname").value.trim();
            const birthdate = document.getElementById("birthdate").value.trim();
            const address = document.getElementById("address").value.trim();
            const username = document.getElementById("username").value.trim();
            const password = document.getElementById("password").value;

            clearErrorMessages(); // reset messaggi di errore

            // Validazioni con espressioni regolari
            if (!/^[A-Z][a-zA-Z ]{1,11}$/.test(name)) {
                document.getElementById("nameError").textContent = "Deve iniziare con lettera maiuscola e contenere solo lettere o spazi, da 2 a 12 caratteri.";
                isCorrect = false;
            }

            if (!/^[A-Z][a-zA-Z ]{1,15}$/.test(surname)) {
                document.getElementById("surnameError").textContent = "Deve iniziare con lettera maiuscola e contenere solo lettere o spazi, da 2 a 16 caratteri.";
                isCorrect = false;
            }

            if (!/^(?:\d{4})-(?:0?[1-9]|1[0-2])-(?:0?[1-9]|[12][0-9]|3[01])$/.test(birthdate)) {
                document.getElementById("birthdateError").textContent = "Deve essere nel formato 'aaaa-mm-gg'.";
                isCorrect = false;
            }

            if (!/^(Via|Corso|Largo|Piazza|Vicolo) [a-zA-Z ]+ \d{1,4}$/.test(address)) {
                document.getElementById("addressError").textContent = "Formato corretto: 'Via/Corso/Largo/Piazza/Vicolo nome numeroCivico'.";
                isCorrect = false;
            }

            if (!/^[A-Za-z][A-Za-z0-9_-]{3,9}$/.test(username)) {
                document.getElementById("usernameError").textContent = "Deve iniziare con una lettera e contenere 4-10 caratteri (lettere, numeri, '-' o '_').";
                isCorrect = false;
            }

            if (!/^(?=.*[a-z])(?=.*[A-Z])(?=(.*\d){2})(?=(.*[#!?@%^&*+=]){2}).{8,16}$/.test(password)) {
                document.getElementById("passwordError").textContent = "8-16 caratteri con almeno 1 maiuscola, 1 minuscola, 2 numeri e 2 simboli (#!?@%^&*+=).";
                isCorrect = false;
            }

            return isCorrect;
        }

        function clearErrorMessages() {
            // funzione per il reset dei messaggi di errore
            const errorIds = [
                "nameError",
                "surnameError",
                "birthdateError",
                "addressError",
                "usernameError",
                "passwordError"
            ];

            for (let id of errorIds) {
                document.getElementById(id).textContent = "";
            }
        }

    }
    
    

    // --------- Logica della pagina di LOGIN!! ----------
    if (currentPage === "login") {
        const loginForm = document.getElementById("loginForm");
        const usernameInput = document.getElementById("user");
        const passwordInput = document.getElementById("pwd");
        const errorMsg = document.getElementById("messageError");
        const cancelButton = document.getElementById("cancel");
        const senzaAutenticazioneBtn = document.getElementById("senzaAutenticazione");

        // Cancella i campi di input e il messaggio di errore
        function clearLoginFormFields() {
            if (usernameInput) usernameInput.value = "";
            if (passwordInput) passwordInput.value = "";
            if (errorMsg) errorMsg.textContent = "";
        }

        // Aggiunge evento per il pulsante "Cancella" 
        if (cancelButton) {
            cancelButton.addEventListener("click", clearLoginFormFields);
        }


        // validazione dei campi al submit del form
        if (loginForm) {
            loginForm.addEventListener("submit", (event) => {
                const username = usernameInput?.value.trim();
                const password = passwordInput?.value;

                const usernamePattern = /^[a-zA-Z][a-zA-Z0-9_-]{3,9}$/;
                const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=(.*\d){2})(?=(.*[#!?@%^&*+=]){2}).{8,16}$/;

                // nel caso uno dei due campi input sia vuoto
                if (!username || !password) {
                    event.preventDefault(); // Blocca invio del form
                    if (errorMsg) {
                        errorMsg.textContent = "Username e password sono richiesti.";
                        return;
                    }
                }

                if(!usernamePattern.test(username) || !passwordPattern.test(password)){
                    event.preventDefault(); // Blocca invio del form
                    if (errorMsg) {
                        errorMsg.textContent = "Credenziali errate. Nome utente o password non validi.";
                        return;
                    }
                }

            });
        }

        // indirizza verso scopri.php per l'accesso senza autenticazione
        if (senzaAutenticazioneBtn) {
            senzaAutenticazioneBtn.addEventListener("click", () => {
            window.location.href = "scopri.php";
            });
        }
    }


    


    // -- logica della pagina SCRIVI!! --
    if( currentPage === "scrivi"){
        const cancelButton = document.getElementById("cancelTweet");
        const tweetInput = document.getElementById("tweet");

        // evento per cancellare il testo del tweet
        cancelButton.addEventListener("click", function () {
            tweetInput.value = "";
            tweetInput.focus();
        });

    }





    

});