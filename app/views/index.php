<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMSU Library - Home</title>

    <script src="../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../public/assets/css/styles.css">

    <link rel="stylesheet" href="../../public/assets/css/header_footer2.css">
</head>

<body>
    <!-- includes/header.php -->
    <header class="m-0">
        <nav class="navbar flex justify-between items-center bg-white fixed top-0 left-0 w-full z-10">
            <div class="logo-section flex items-center gap-3">
                <img src="../../public/assets/images/logo.png" alt="Logo" class="logo">
                <h2 class="title font-extrabold text-2xl text-red-900">WMSU LIBRARY</h2>
            </div>

            <ul class="nav-links flex gap-8 list-none">
                <li><a href="index.php">Home</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Services</a></li>
                <li><a href="borrower/contact.php">Contact</a></li>
            </ul>

            <div class="burger flex flex-col justify-between cursor-pointer">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>


    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title font-extrabold">WMSU Library</h1>
            <p class="hero-subtitle">Home of a Wealthy Knowledge</p>

            <form action="catalogue.php" method="GET" class="search-container">
                <input type="text" name="q" class="search-input" placeholder="Search for books, authors, or ISBN...">
                <button type="submit" class="search-btn">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
            </form>
        </div>
    </section>

    <section class="info-section">
        <div class="info-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <h3 class="card-title">Extensive Catalogue</h3>
            <p class="card-text">
                Explore thousands of resources. From academic textbooks to rare historical records, find exactly what
                you need for your studies.
            </p>
        </div>

        <div class="info-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-clock"></i>
            </div>
            <h3 class="card-title">Real-time Availability</h3>
            <p class="card-text">
                Check book status instantly. Reserve items online and pick them up at the counter to save time.
            </p>
        </div>

        <div class="info-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-laptop-file"></i>
            </div>
            <h3 class="card-title">Manage Your Loans</h3>
            <p class="card-text">
                Track your borrowed items, avoid fines with due date reminders, and view your reading history through
                your personal dashboard.
            </p>
        </div>
    </section>

    <section class="cta-banner">
        <h2 style="font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">Start Your Learning Journey Today</h2>
        <p style="font-size: 1.1rem; opacity: 0.9;">Browse our collection or manage your account online.</p>

        <div style="margin-top: 2rem;">
            <a href="catalogue.php" class="cta-btn">Browse Catalogue</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="cta-btn"
                    style="background: transparent; border: 2px solid white; color: white; margin-left: 10px;">Login</a>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer !m-0 text-white">
        <div class="footer-container flex flex-col md:flex-row justify-between items-center gap-4 px-8 py-6">
            <div class="footer-left text-center md:text-left">
                <h3 class="text-xl font-bold tracking-wide text-white">WMSU Library System</h3>
                <p class="line opacity-90">Home of a Wealthy Knowledge</p>
            </div>

            <div class="footer-center flex gap-6 text-sm font-semibold">
                <a href="#">Home</a>
                <a href="#">About</a>
                <a href="contact.php">Contact</a>
            </div>

            <div class="footer-right text-sm text-center md:text-right opacity-90">
                <p>©2025 WMSU Library. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>

</html>