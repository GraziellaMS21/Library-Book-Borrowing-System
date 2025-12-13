<?php
session_start();

if (isset($_SESSION["fName"]) && isset($_SESSION["lName"])) {
    $name = $_SESSION["fName"] . ' ' . $_SESSION["lName"];
} else {
    $name = '';
}

$email = $_SESSION["email"] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact WMSU Library</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Licorice&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Nunito:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/contact.css">
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css">
    <link rel="stylesheet" href="../../../public/assets/fontawesome-free-7.1.0-web/css/all.min.css">
</head>

<body class="bg-gray-50">
    <?php
    if (isset($_SESSION["email"])) {
        require_once(__DIR__ . '/../shared/headerBorrower.php');
    } else {
        require_once(__DIR__ . '/../shared/header.php');
    }
    ?>

    <div class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content relative z-1">
            <h1 class="hero-title drop-shadow-lg">WMSU Library</h1>
            <p class="hero-subtitle">Contact Us</p>
        </div>
    </div>

    <main class="relative z-20 px-4 sm:px-6 lg:px-8 py-10 pb-20">
        <div class="max-w-7xl mx-auto">

            <div class="contact-card shadow-2xl flex flex-col md:flex-row overflow-hidden rounded-xl">

                <div
                    class="info-section md:w-5/12 lg:w-4/12 relative text-white p-10 lg:p-12 flex flex-col justify-between">
                    <div class="pattern-overlay"></div>

                    <div class="relative z-10">
                        <h2 class="font-playfair text-3xl mb-2 text-gold">Contact Information</h2>
                        <p class="text-red-100 text-sm mb-10 opacity-80">We'd love to hear from you. Reach out to us
                            using any of the methods below.</p>

                        <div class="space-y-8">
                            <div class="flex items-start group">
                                <div class="icon-box">
                                    <i class="fas fa-location-dot"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gold mb-1">Visit Us</h3>
                                    <p class="text-gray-100 text-sm leading-relaxed">Normal Road,
                                        Baliwasan,<br>Zamboanga City, Philippines</p>
                                </div>
                            </div>

                            <div class="flex items-center group">
                                <div class="icon-box">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gold mb-1">Call Us</h3>
                                    <p class="text-gray-100 text-sm">(062) 991-1771</p>
                                </div>
                            </div>

                            <div class="flex items-center group">
                                <div class="icon-box">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gold mb-1">Email Us</h3>
                                    <p class="text-gray-100 text-sm">library@wmsu.edu.ph</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
                </div>

                <div class="form-section md:w-7/12 lg:w-8/12 bg-white p-10 lg:p-12 relative">
                    <div class="max-w-lg mx-auto">
                        <h1 class="font-playfair font-bold text-3xl mb-2 text-red-900">Send a Message</h1>
                        <p class="text-gray-500 mb-8 text-sm">Have a question about borrowing? Fill out the form below.
                        </p>

                        <form action="../../../app/controllers/emailController.php" method="POST" class="space-y-6">

                            <div class="input-group relative">
                                <input type="text" id="name" name="name" class="input-field peer" placeholder=" "
                                    value="<?= htmlspecialchars($name) ?>" required>
                                <label for="name" class="floating-label">Full Name</label>
                            </div>

                            <div class="input-group relative">
                                <input type="email" id="email" name="email" class="input-field peer" placeholder=" "
                                    value="<?= htmlspecialchars($email) ?>" required>
                                <label for="email" class="floating-label">Email Address</label>
                            </div>

                            <div class="input-group relative">
                                <input type="text" id="subject" name="subject" class="input-field peer" placeholder=" "
                                    required>
                                <label for="subject" class="floating-label">Subject</label>
                            </div>

                            <div class="input-group relative">
                                <textarea id="message" name="message" class="input-field peer h-32 pt-3 resize-none"
                                    placeholder=" " required></textarea>
                                <label for="message" class="floating-label">Message Details</label>
                            </div>

                            <button type="submit" name="send" class="submit-btn group">
                                <span>Send Message</span>
                                <i class="fas fa-paper-plane ml-2 transition-transform group-hover:translate-x-1"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>

    <script src="../../../public/assets/js/header_footer.js"></script>
    <script src="../../../public/assets/js/borrower.js"></script>
</body>

</html>