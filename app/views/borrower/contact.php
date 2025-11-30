<?php
session_start();

// FIX: Check if fName exists. If yes, combine them. If no, set to empty string.
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
    <title>Contact Us</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/login.css">
    <link rel="stylesheet" href="../../../public/assets/css/header_footer2.css">
    <link rel="stylesheet" href="../../../public/assets/fontawesome-free-7.1.0-web/css/all.min.css">
    <style>
        .input-field[name="message"] {
            height: auto;
            padding-top: 10px;
            padding-bottom: 10px;
            resize: none;
        }
    </style>
</head>

<body>
    <div class="color-layer"></div>

    <?php
    // Simplified logic: Just check if user_id or email is set to decide header
    if (isset($_SESSION["email"])) {
        require_once(__DIR__ . '/../shared/headerBorrower.php');
    } else {
        require_once(__DIR__ . '/../shared/header.php');
    }
    ?>

    <div class="flex justify-center items-center flex-col text-center px-44">
        <h1 class="text-4xl md:text-5xl font-extrabold mb-6 tracking-wide text-white shadow-lg">Contact Us</h1>
        <p class="text-white mb-12 leading-relaxed shadow-lg text-lg">
            Have questions or need assistance with library resources? We are here to help. Reach out to us
            through any of the channels below or send us a direct message.
        </p>
    </div>
    <main class="flex justify-center items-center">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 flex flex-col p-16">

                <div class="space-y-20">
                    <div class="flex items-start">
                        <div
                            class="bg-white p-4 rounded-full text-red-900 mr-5 flex items-center justify-center w-14 h-14 shadow-lg shrink-0">
                            <i class="fas fa-location-dot text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-xl text-red-400 mb-1">Address</h3>
                            <p class="text-white leading-snug">Normal Road, Baliwasan,<br>Zamboanga City, Philippines
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div
                            class="bg-white p-4 rounded-full text-red-900 mr-5 flex items-center justify-center w-14 h-14 shadow-lg shrink-0">
                            <i class="fas fa-phone text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-xl text-red-400 mb-1">Phone</h3>
                            <p class="text-gray-200">(062) 991-1771</p>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div
                            class="bg-white p-4 rounded-full text-red-900 mr-5 flex items-center justify-center w-14 h-14 shadow-lg shrink-0">
                            <i class="fas fa-envelope text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-xl text-red-400 mb-1">Email</h3>
                            <p class="text-gray-200">library@wmsu.edu.ph</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="form-section w-1/2 flex flex-col justify-center items-center">
                <h1 class="font-extrabold">Send us a Message!</h1>
                <form action="../../../app/controllers/emailController.php" method="POST">

                    <div class="input">
                        <label for="name">Name:</label>
                        <input type="text" class="input-field" name="name" placeholder="Your name"
                            value="<?= htmlspecialchars($name) ?>" required autofocus>
                    </div>

                    <div class="input">
                        <label for="email">Email:</label>
                        <input type="email" class="input-field" name="email" placeholder="Your Email Address"
                            value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <div class="input">
                        <label for="subject">Subject:</label>
                        <input type="text" class="input-field" name="subject" placeholder="Type your subject line"
                            required>
                    </div>

                    <div class="input">
                        <label for="message">Message:</label>
                        <textarea class="input-field" name="message" placeholder="Type your Message Details Here..."
                            rows="5" required></textarea>
                    </div>

                    <br>
                    <input type="submit" value="Submit Now" name="send"
                        class="font-bold cursor-pointer mb-8 border-none rounded-lg">

                </form>
            </div>
        </div>
    </main>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>
<script src="../../../public/assets/js/header_footer.js"></script>
<script src="../../../public/assets/js/borrower.js"></script>

</html>