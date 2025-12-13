<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMSU Library - Home</title>

    <link rel="stylesheet" href="../../public/assets/fontawesome-free-7.1.0-web/css/all.min.css">
    <script src="../../public/assets/js/tailwind.3.4.17.js"></script>

    <link rel="stylesheet" href="../../public/assets/css/styles.css">
    <link rel="stylesheet" href="../../public/assets/css/header_footer2.css">
</head>

<body>

    <header class="m-0">
        <nav class="navbar flex justify-between items-center bg-white fixed top-0 left-0 w-full z-[9999]">
            <div class="logo-section flex items-center gap-3">
                <img src="../../public/assets/images/logo.png" alt="Logo" class="logo">
                <h2 class="title font-extrabold text-2xl text-red-900">WMSU LIBRARY</h2>
            </div>

            <div class="flex justify-center items-center">
                <ul class="nav-links flex gap-6 list-none items-center">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="borrower/about.php">About</a></li>
                    <li><a href="borrower/catalogue.php">Catalogue</a></li>
                    <li><a href="borrower/contact.php">Contact</a></li>
    
                </ul>
                <div class="flex gap-3 ml-4">
                    <ul class="flex gap-6 list-none items-center">
                        <li><a href="borrower/login.php" class="nav-btn btn-login">Login</a></li>
                        <li><a href="borrower/register.php" class="nav-btn btn-register">Register</a></li>
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


    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">WMSU Library</h1>
            <p class="hero-subtitle">Book Borrowing System</p>

            <form action="borrower/catalogue.php" method="GET" class="search-container">
                <input type="text" name="search" class="search-input"
                    placeholder="Search for books, authors, or ISBN...">
                <button type="submit" class="search-btn">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i> Search
                </button>
            </form>
        </div>
    </section>

    <div class="book-transition"></div>

    <section class="info-section">
        <div class="info-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <h3 class="card-title">Browse & Search Books</h3>
            <p class="card-text">
                Easily browse the library collection or use the search and filter tools to quickly find books by title,
                author, category, or availability.
            </p>
        </div>

        <div class="info-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-clock"></i>
            </div>
            <h3 class="card-title">Real-time Book Availability</h3>
            <p class="card-text">
                Instantly view whether a book is available or currently borrowed. Borrow books directly through the
                system
                without waiting in line.
            </p>
        </div>

        <div class="info-card">
            <div class="icon-wrapper">
                <i class="fa-solid fa-laptop-file"></i>
            </div>
            <h3 class="card-title">Loan Tracking Dashboard</h3>
            <p class="card-text">
                Monitor the number of books you have borrowed and see how many days are left before each item must be
                returned.
            </p>
        </div>
    </section>


    <div class="book-transition2"></div>

    <section class="cta-banner">
        <div class="cta-content">
            <h2 class="font-playfair text-4xl md:text-5xl font-bold mb-4">Start Your Learning Journey Today</h2>
            <p class="text-lg opacity-90 font-light max-w-2xl mx-auto">Browse our vast collection, manage your account,
                or visit us in person to access other resources.</p>

            <div class="mt-8">
                <a href="borrower/catalogue.php" class="cta-btn">Browse Catalogue</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="borrower/login.php" class="cta-btn-outline">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="footer !m-0 text-white">
        <div class="footer-container grid grid-cols-1 md:grid-cols-3 gap-8 py-10">

            <div class="footer-brand flex flex-col items-center md:items-start text-center md:text-left">
                <h3 class="text-2xl font-bold tracking-wide text-white">WMSU Library System</h3>
                <p class="line opacity-90 mt-1 mb-4">Home of a Wealthy Knowledge</p>
                <p class="text-sm opacity-80 leading-relaxed max-w-xs">
                    Empowering the academic community with access to vast educational resources and wealthy knowledge.
                </p>
            </div>

            <div class="footer-links flex flex-col items-center md:items-start">
                <h4 class="text-lg font-semibold mb-4 uppercase tracking-wider border-b-2 border-yellow-400/50 pb-1">
                    Quick Links</h4>
                <div class="flex flex-col gap-3 text-sm font-medium">
                    <a href="index.php" class="hover:text-yellow-300 transition-colors">Home</a>
                    <a href="borrower/about.php" class="hover:text-yellow-300 transition-colors">About Us</a>
                    <a href="borrower/catalogue.php" class="hover:text-yellow-300 transition-colors">Library Catalog</a>
                    <a href="borrower/contact.php" class="hover:text-yellow-300 transition-colors">Contact Support</a>
                </div>
            </div>

            <div class="footer-contact flex flex-col items-center md:items-start">
                <h4 class="text-lg font-semibold mb-4 uppercase tracking-wider border-b-2 border-yellow-400/50 pb-1">
                    Contact Us</h4>
                <div class="text-sm opacity-90 space-y-2 text-center md:text-left">
                    <p>Normal Road, Baliwasan</p>
                    <p>Zamboanga City, Philippines</p>
                    <p class="mt-2">library@wmsu.edu.ph</p>
                    <p>(062) 991-1771</p>
                </div>
            </div>
        </div>

        <div class="border-t border-white/20 w-full"></div>

        <div class="footer-bottom py-6 text-center text-sm opacity-75">
            <p>&copy; 2025 WMSU Library. All Rights Reserved.</p>
        </div>
    </footer>
</body>

</html>