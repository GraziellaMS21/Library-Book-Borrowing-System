<!-- includes/header.php -->
<header>
    <nav class="navbar flex justify-between items-center bg-white fixed top-0 left-0 w-full z-[9999]">
        <div class="logo-section flex items-center gap-3">
            <img src="../../../public/assets/images/logo.png" alt="Logo" class="logo">
            <h2 class="title font-extrabold text-2xl text-red-900">WMSU LIBRARY</h2>
        </div>

        <div class="flex justify-center items-center">
            <ul class="nav-links flex gap-6 list-none items-center">
                <li><a href="../index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="catalogue.php">Catalogue</a></li>
                <li><a href="contact.php">Contact</a></li>

            </ul>
            <div class="flex gap-3 ml-4">
                <ul class="flex gap-6 list-none items-center">
                    <li><a href="login.php" class="nav-btn btn-login">Login</a></li>
                    <li><a href="register.php" class="nav-btn btn-register">Register</a></li>
                </ul>
            </div>
        </div>

        <div class="burger flex flex-col justify-between cursor-pointer">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>
</header>