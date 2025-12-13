<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About the System | WMSU Library</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Licorice&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/about.css">
    <link rel="stylesheet" href="../../../public/assets/css/header_footer2.css">
    <link rel="stylesheet" href="../../../public/assets/fontawesome-free-7.1.0-web/css/all.min.css">
</head>

<body class="bg-gray-50">
    <?php
    // Dynamic Header loading
    if (isset($_SESSION["email"])) {
        require_once(__DIR__ . '/../shared/headerBorrower.php');
    } else {
        require_once(__DIR__ . '/../shared/header.php');
    }
    ?>

    <div class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content relative z-10">
            <h1 class="hero-title drop-shadow-lg">WMSU Book Borrowing System</h1>
            <p class="hero-subtitle">About Us</p>
        </div>
    </div>

    <section class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-20 section-text">
            <div class="lg:w-1/2 history-img-container">
                <img src="../../../public/assets/images/1university_library_bg.png" alt="Library System" class="history-img w-full h-[400px] object-cover">
            </div>
            
            <div class="lg:w-1/2">
                <h2 class="font-playfair text-4xl font-bold text-red-900 mb-6 section-title">About the Project</h2>
                <p class="text-gray-600 text-lg leading-relaxed mb-6">
                    The <strong>WMSU Library Book Borrowing System</strong> is a digital initiative designed to modernize how students and faculty interact with the university's learning resources.
                </p>
                <p class="text-gray-600 text-lg leading-relaxed">
                    By transitioning from manual logging to a computerized database, this system eliminates long queues, prevents record-keeping errors, and ensures that knowledge is just a click away. It bridges the gap between traditional library values and modern technological convenience.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-red-900 py-20 px-4 sm:px-6 lg:px-8 relative">
        <div class="absolute inset-0 opacity-5 bg-[radial-gradient(#931c19_1px,transparent_1px)] [background-size:16px_16px]"></div>
        
        <div class="max-w-7xl mx-auto relative z-10 bg-white p-8 rounded-lg shadow-lg">
            <div class="text-center mb-16">
                <h2 class="font-playfair text-4xl font-bold text-red-900">System Core Values</h2>
                <div class="w-20 h-1 bg-yellow-400 mx-auto mt-4"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="mv-card group">
                    <div class="mv-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3 class="font-playfair text-2xl font-bold text-gray-800 mb-4 group-hover:text-red-900 transition-colors">Mission</h3>
                    <p class="text-gray-600 leading-relaxed">
                        To provide a secure, efficient, and user-friendly platform that automates the borrowing and returning processes, ensuring seamless access to information for the entire WMSU community.
                    </p>
                </div>

                <div class="mv-card group">
                    <div class="mv-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="font-playfair text-2xl font-bold text-gray-800 mb-4 group-hover:text-red-900 transition-colors">Vision</h3>
                    <p class="text-gray-600 leading-relaxed">
                        To be the standard for digital library management in the region, creating a paperless environment where data integrity and user convenience drive academic success.
                    </p>
                </div>

                <div class="mv-card group">
                    <div class="mv-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <h3 class="font-playfair text-2xl font-bold text-gray-800 mb-4 group-hover:text-red-900 transition-colors">Objectives</h3>
                    <p class="text-gray-600 leading-relaxed">
                        To eliminate manual redundancy, provide real-time availability tracking of resources, and generate accurate reports to assist administration in decision-making.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
    
    <script src="../../../public/assets/js/header_footer.js"></script>
</body>
</html>