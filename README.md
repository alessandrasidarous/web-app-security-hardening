# Twittle - Analisi e Mitigazione delle Vulnerabilità in una Web Application

> **Tesi di Laurea Triennale in Ingegneria Gestionale**  
> **Politecnico di Torino** — A.A. 2024/2025  
> **Candidata:** Alessandra Sidarous Farag  
> **Relatore:** Prof. Andrea Atzeni  

---

## 📌 Panoramica del Progetto

**Twittle** è una web application didattica di microblogging ispirata a Twitter (oggi X), sviluppata con stack LAMP (**PHP**, **MySQL**, **Apache**, **HTML5/CSS3/JavaScript**) che consente agli utenti di registrarsi, autenticarsi, pubblicare brevi messaggi testuali (detti *twit*, fino a 140 caratteri), visualizzare la propria bacheca filtrando i tweet per data ed esplorare i messaggi pubblici nella sezione *Scopri*.

L'obiettivo cardine della tesi è stato quello di condurre un processo completo e strutturato di **Vulnerability Assessment**, **Code Review** e **Hardening di Sicurezza**, applicando i principi del **Secure Software Development Lifecycle (SSDLC)** e le linee guida **OWASP** (Open Web Application Security Project).

L'intero ciclo si è articolato in tre fasi:
1. **Analisi Dinamica Iniziale (Pre-Mitigazione)** con **OWASP ZAP** (Zed Attack Proxy v2.16.1, profilo *Developer Full*) e revisione del codice sorgente.
2. **Progettazione e Implementazione delle Tecniche di Mitigazione** sia a livello applicativo (PHP/JS) che architetturale e di web server (.htaccess).
3. **Verifica Finale (Post-Mitigazione)** con una seconda scansione dinamica nelle medesime condizioni per quantificare l'efficacia delle contromisure adottate.

---

## 🏗️ Architettura del Progetto

Per prevenire l'accesso non autorizzato a file sensibili e implementare il principio di *separation of concerns*, l'applicazione è stata riorganizzata in due aree logiche distinte:

```text
PWR_twittle_TESI/
├── .htaccess                      # Protezione globale da directory listing e dotfiles
├── README.md                      # Documentazione del progetto
│
├── app/                           # 🔒 AREA RISERVATA (Accesso da browser bloccato)
│   ├── .htaccess                  # 'Require all denied' (nessun accesso web diretto)
│   ├── check_origin.php           # Validazione intestazioni HTTP Origin e Referer
│   ├── csp.php                    # Generazione nonce e configurazione Content Security Policy
│   ├── Db/
│   │   ├── db_normale.php         # Connessione DB con privilegi standard (least privilege)
│   │   └── db_privilegiato.php    # Connessione DB per operazioni amministrative
│   ├── log/
│   │   ├── errori.log             # Registro centralizzato degli eventi e degli errori
│   │   └── function_log.php       # Funzione personalizzata logErrore() con IP, UA e URI
│   ├── security/
│   │   ├── .env                   # Archiviazione della chiave segreta PEPPER_SECRET
│   │   └── load_env.php           # Parser custom sicuro per variabili d'ambiente
│   └── Session/
│       ├── init.php               # Hardening direttive PHP, header HTTP e CSRF token
│       └── sessionManager.php     # Gestione timeout di sessione (inattività e assoluto)
│
└── public/                        # 🌐 AREA PUBBLICA (Accessibile dal web)
    ├── .htaccess                  # Regole Rewrite HTTPS, Options -Indexes, blocco dotfiles
    ├── home.php                   # Homepage dinamica (stato anonimo/autenticato)
    ├── login.php                  # Autenticazione con password_verify e cookie sicuro
    ├── logout.php                 # Distruzione sicura della sessione e cookie cleanup
    ├── registrazione.php          # Creazione account con hashing Argon2id + Pepper
    ├── bacheca.php                # Bacheca personale con filtri data e rendering sanificato
    ├── scrivi.php                 # Form pubblicazione twit con validazione e anti-CSRF
    ├── scopri.php                 # Feed pubblico di tutti i twit
    ├── ultimo_tweet.php           # Preview asincrona dell'ultimo tweet pubblicato
    ├── Componenti/                # Frammenti modulari protetti da inclusione diretta
    │   ├── .htaccess              # Blocco accesso HTTP diretto ai frammenti
    │   ├── header.php             # Intestazione HTML e inclusione fogli di stile
    │   ├── menu.php               # Barra di navigazione dinamica basata sullo stato sessione
    │   └── footer.php             # Chiusura markup e copyright
    ├── CSS/
    │   └── style.css              # Stili grafici responsive
    ├── JS/
    │   ├── common.js              # Logica client-side unificata (no script inline)
    │   ├── menu_toggle.js         # Gestione responsive del menu mobile
    │   └── purify.min.js          # Libreria DOMPurify per sanificazione client-side
    └── Immagini/
        └── twitter-rounded-border-svgrepo-com.svg
```

---

## 🛡️ Vulnerabilità Identificate e Tecniche di Mitigazione

### 1. Cross-Site Scripting (XSS) — *Persistente e Riflesso*
- **Criticità iniziale (Alto Rischio):** Il contenuto dei tweet immesso in `scrivi.php` veniva riprodotto direttamente nel DOM di `bacheca.php` e `scopri.php` senza encoding, consentendo l'esecuzione di JavaScript malevolo arbitrario (es. furto cookie di sessione).
- **Mitigazioni implementate:**
  - **Output Encoding Lato Server:** Utilizzo di `htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` per la codifica di tutti i dati dinamici riflessi.
  - **Sanificazione Client-Side con DOMPurify:** I tweet consentono una formattazione controllata (tag `<b>`, `<i>`, `<u>`, `<strong>`, `<em>`, `<a>`). Il testo viene salvato in un attributo `data-raw` codificato in base64, decodificato via JS e filtrato con **DOMPurify** (`purify.min.js`).
  - **Hook Personalizzato Anti Reverse Tabnabbing:** Nei tag `<a>`, l'attributo `href` viene accettato esclusivamente con schema `https://` (altrimenti rimosso) e vengono iniettati automaticamente gli attributi `target="_blank"` e `rel="noopener noreferrer"`.
  - **Eliminazione degli Script Inline:** Tutti i gestori inline (`onclick`, `onsubmit`, ecc.) sono stati rimossi ed esternalizzati in `common.js`.
  - **Content Security Policy (CSP) Dinamica con Nonce:** Header generato in `app/csp.php` con nonce crittografico (`random_bytes(16)`):
    ```http
    Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-<NONCE>'; style-src 'self'; img-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'self';
    ```

---

### 2. Cross-Site Request Forgery (CSRF)
- **Criticità iniziale (Medio Rischio):** I moduli di invio tweet, login, registrazione e filtraggio non implementavano protezioni contro richieste contraffatte provenienti da siti terzi.
- **Mitigazioni implementate:**
  - **Synchronizer Token Pattern:** Generazione in `init.php` di un token pseudo-casuale crittograficamente forte a 32 byte (`bin2hex(random_bytes(32))`), salvato in `$_SESSION['csrf_token']` e validato in tutte le richieste `POST` tramite confronto a tempo costante (`hash_equals()`).
  - **Cookie SameSite:** Configurazione di `session.cookie_samesite = 'Strict'` per impedire l'inoltro dei cookie di sessione durante navigazioni cross-site.
  - **Verifica delle Intestazioni Origin & Referer:** Modulo dedicato `app/check_origin.php` che confronta le intestazioni HTTP `Origin` e `Referer` con la whitelist dei domini autorizzati, restituendo `403 Forbidden` in caso di anomalie.

---

### 3. Protezione Dati e Hashing delle Password (Sensitive Data Exposure)
- **Criticità iniziale:** Password memorizzate in chiaro nel database MySQL.
- **Mitigazioni implementate:**
  - **Argon2id con Pepper crittografico:** Hashing all'avanguardia con `password_hash($peppered, PASSWORD_ARGON2ID)`.
  - **Isolamento del Pepper:** La chiave segreta `PEPPER_SECRET` è memorizzata in `app/security/.env`, caricata dal modulo `load_env.php` e isolata sia dal codice sorgente che dal database. Prima dell'hashing, la password viene pre-elaborata tramite `hash_hmac('sha256', $password, $pepper)`.
  - **Verifica Sicura:** Autenticazione effettuata tramite `password_verify($peppered, $hash_from_db)`.

---

### 4. Gestione della Sessione (Session Management & Fixation)
- **Criticità iniziale:** Mancata rigenerazione dell'ID sessione, cookie `PHPSESSID` non protetto, assenza di timeout per inattività.
- **Mitigazioni implementate (`app/Session/init.php` & `sessionManager.php`):**
  - **Configurazione Direttive PHP:**
    ```php
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_name('id'); // Nome personalizzato e non standard
    ```
  - **Prevenzione Session Fixation:** Rigenerazione obbligatoria dell'identificativo via `session_regenerate_id(true)` ad ogni autenticazione o cambio di privilegio.
  - **Doppio Meccanismo di Timeout:**
    - *Timeout di inattività:* **15 minuti** (`$idleTimeout = 900s`).
    - *Timeout assoluto:* **4 ore** (`$absoluteTimeout = 14400s`).
  - **Disabilitazione Cache per Pagine Protette:** Header `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` e `Pragma: no-cache`.

---

### 5. SQL Injection & Database Hardening
- **Mitigazioni implementate:**
  - Adozione sistematica di **Prepared Statements** con l'estensione MySQLi (`mysqli_prepare`, `mysqli_stmt_bind_param`, `mysqli_stmt_execute`) per tutte le query dinamiche su parametri forniti dall'utente (`login.php`, `registrazione.php`, `bacheca.php`, `scrivi.php`, `ultimo_tweet.php`).
  - Separazione degli account del database per rispettare il **principio dei privilegi minimi** (`db_normale.php` vs `db_privilegiato.php`).

---

### 6. Security Misconfiguration & Web Server (.htaccess)
- **Mitigazioni implementate:**
  - **Disabilitazione Directory Listing:** Direttiva `Options -Indexes` sia a livello root che in `public/`.
  - **Protezione File Nascosti e Sensibili:** Regola `<FilesMatch "^\."> Require all denied </FilesMatch>` per impedire il download diretto di `.env`, `.htaccess` o file git.
  - **Isolamento dell'Area Backend:** Blocco totale dell'accesso web alla cartella `/app/` tramite direttive Apache 2.4 (`Require all denied`).
  - **Forzatura HTTPS:** Regole di riscrittura Apache per il reindirizzamento permanente (301) di tutto il traffico HTTP verso HTTPS, con supporto a reverse proxy (`X-Forwarded-Proto`).
  - **Header di Sicurezza:**
    - `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` (HSTS)
    - `X-Content-Type-Options: nosniff` (prevenzione MIME-sniffing)
    - `X-Frame-Options: SAMEORIGIN` e CSP `frame-ancestors 'none'` (anti-Clickjacking)
    - `Referrer-Policy: no-referrer`
  - **Disattivazione Errori a Video e Logging Centralizzato:** `display_errors = 0`, `file_uploads = 0`, e tracciamento nel file riservato `app/log/errori.log`.

---

### 7. IDOR & Unvalidated Redirects/Forwards
- **IDOR Prevention:** Tutte le operazioni e le letture dei tweet in bacheca filtrano i record vincolandoli esclusivamente all'identità salvata nella sessione server-side (`$_SESSION['username']`), senza mai fidarsi di parametri manipolabili via URL o POST.
- **Redirects/Forwards Sicuri:** Nessun reindirizzamento o inclusione file (`include`/`require`) accetta percorsi arbitrari dal client; gli indirizzamenti sono determinati rigidamente lato server.

---

## 📊 Risultati e Confronto delle Scansioni OWASP ZAP

L'analisi di sicurezza dinamica è stata condotta con **OWASP ZAP 2.16.1** applicando la policy di scansione attiva **Developer Full**, affiancata da esplorazione manuale (*Manual Explore*) con certificato root importato per la decifratura del traffico HTTPS.

| Categoria di Rischio | Pre-Mitigazione | Post-Mitigazione | Variazione |
|:---|:---:|:---:|:---:|
| 🔴 **Alto Rischio (High)** | **2** | **0** | **-100%** |
| 🟠 **Medio Rischio (Medium)** | **4** | **0** | **-100%** |
| 🟡 **Basso Rischio (Low)** | **7** | **4\*** | **-43%** |
| 🔵 **Informativo (Info)** | **6** | **6** | Stabile |
| **Totale** | **19** | **10** | **-47% (Criticità gravi eliminate)** |

*\* Nota sulle vulnerabilità residue (Basso Rischio):*  
I 4 alert a basso rischio residui riguardano l'esposizione informativa di versioni server negli header HTTP (`X-Powered-By: PHP/7.4.3`, `Server: nginx/1.15.12`) e l'assenza degli header HSTS/X-Content-Type-Options sui soli **file statici** (CSS/JS serviti direttamente dal web server senza transitare dall'interprete PHP). Tali elementi dipendono dalla configurazione del reverse-proxy/hosting e non dal codice applicativo.

---

## ⚙️ Requisiti e Configurazione

### Requisiti
- **PHP** >= 7.4 (consigliato PHP 8.x) con estensioni `mysqli`, `sodium`/`hash`, `session`, `openssl`.
- **MySQL / MariaDB**.
- **Web Server Apache** con moduli `mod_rewrite` e `mod_authz_core` abilitati (oppure Nginx configurato adeguatamente).
- Supporto a **HTTPS / TLS** (certificato valido o autofirmato per ambienti di staging).

### Configurazione
1. Configurare le credenziali del database all'interno di:
   - `app/Db/db_normale.php`
   - `app/Db/db_privilegiato.php`
2. Configurare la chiave di cifratura delle password in `app/security/.env`:
   ```env
   PEPPER_SECRET=<stringa_casuale_ad_alta_entropia>
   ```
3. Impostare il dominio autorizzato in `app/check_origin.php`:
   ```php
   $allowedDomain = ['https://tuo-dominio.it'];
   ```
4. Assicurarsi che la directory `app/log/` disponga dei permessi di scrittura per il processo PHP.
5. Configurare la DocumentRoot del web server in modo che punti alla cartella `public/` (oppure navigare tramite il percorso completo con il file `.htaccess` di root attivo).

---

## 🎓 Conclusioni Accademiche

Il progetto dimostra in maniera pragmatica come un'applicazione web complessa ma vulnerabile possa essere trasformata in un sistema resiliente attraverso un approccio ingegneristico e iterativo ispirato al modello **SSDLC** (*Secure Software Development Lifecycle*). L'integrazione di tecniche DAST con OWASP ZAP, abbinata a una rigorosa code review e a una difesa a livelli (*defense in depth*), ha permesso di azzerare i vettori di attacco più critici (OWASP Top 10), validando un percorso metodologico replicabile sia in ambito accademico che professionale.

