<nav>
    <!-- Menu principale (visibile su schermi più grandi) -->
    <ul class="main-menu">
        <li><a href="home.php">Home</a></li>
        
        <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']){ ?>
            <li class="btn_nav"><a href="bacheca.php">Bacheca</a></li>
            <li class="btn_nav"><a href="scopri.php">Scopri</a></li>
            <li class="btn_nav"><a href="scrivi.php">Scrivi</a></li>
            <li class="disabled"><a href="#">Registrati</a></li>
            <li class="disabled"><a href="login.php">Login</a></li>
            <li class="btn_nav"><a href="logout.php">Logout</a></li>
        <?php }else{ ?>
            <li class="disabled"><a href="#">Bacheca</a></li>
            <li class="btn_nav"><a href="scopri.php">Scopri</a></li>
            <li class="disabled"><a href="#">Scrivi</a></li>
            <li class="btn_nav"><a href="registrazione.php">Registrati</a></li>
            <li class="btn_nav"><a href="login.php">Login</a></li>
            <li class="disabled"><a href="#">Logout</a></li>
        <?php } ?>
    </ul>

    <!-- Bottone per attivare il menu a tendina (visibile su dispositivi mobili) -->
    <button class="menu-toggle">☰</button>

    <!-- Menu a tendina (visibile su dispositivi mobili) -->
    <ul class="dropdown-menu">
        <li><a href="home.php">Home</a></li>
        
        <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']){ ?>
            <li class="btn_nav"><a href="bacheca.php">Bacheca</a></li>
            <li class="btn_nav"><a href="scopri.php">Scopri</a></li>
            <li class="btn_nav"><a href="scrivi.php">Scrivi</a></li>
            <li class="disabled"><a href="#">Registrati</a></li>
            <li class="disabled"><a href="login.php">Login</a></li>
            <li class="btn_nav"><a href="logout.php">Logout</a></li>
        <?php }else{ ?>
            <li class="disabled"><a href="#">Bacheca</a></li>
            <li class="btn_nav"><a href="scopri.php">Scopri</a></li>
            <li class="disabled"><a href="#">Scrivi</a></li>
            <li class="btn_nav"><a href="registrazione.php">Registrati</a></li>
            <li class="btn_nav"><a href="login.php">Login</a></li>
            <li class="disabled"><a href="#">Logout</a></li>
        <?php } ?>
    </ul>
</nav>


<script src="JS/menu_toggle.js" ></script>
