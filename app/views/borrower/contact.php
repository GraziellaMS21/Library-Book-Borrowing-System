<?php
session_start();
// No redirect logic here, users should be able to contact support even if logged in.

// We can pre-fill user data if they are logged in
$name = $_SESSION["fName"] . ' ' . $_SESSION["lName"] ?? ''; // Assuming you store name in session
$email = $_SESSION["email"] ?? ''; // Assuming you store email in session

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <!-- Assuming contact.php is in the same directory as login.php (app/views/shared/) -->
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/login.css">
    <link rel="stylesheet" href="../../../public/assets/css/header_footer2.css">
    <style>
        /* Ensure textarea matches the input field style from login.css */
        .input-field[name="message"] {
            height: auto; /* Allow height to be set by rows */
            padding-top: 10px;
            padding-bottom: 10px;
            resize: none; /* Disable resizing */
        }
    </style>
</head>

<body>
    <div class="color-layer"></div>

  
    <?php if(isset($_SESSION["email"])) {
      require_once(__DIR__ . '/../shared/headerBorrower.php');
    }else {
      require_once(__DIR__ . '/../shared/header.php');
    }
    ?>

    <main class="flex justify-center items-center">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 flex flex-col justify-center items-center">
                <div class="image">
                    <img src="../../../public/assets/images/bg.png" alt="Background Image">
                </div>
            </div>

            <div class="form-section w-1/2 flex flex-col justify-center items-center">
                <h1 class="font-extrabold">CONTACT US</h1>
                <!-- 
                    Update the action to point to your mail handling script.
                    This assumes 'mail.php' is in the same directory.
                    You may need to adjust this path.
                -->
                <form action="../../../app/controllers/emailController.php" method="POST">

                    <div class="input">
                        <label for="name">Name:</label>
                        <input type="text" class="input-field" name="name" placeholder="Your name" value="<?= htmlspecialchars($name) ?>" required autofocus>
                    </div>
                    
                    <div class="input">
                        <label for="email">Email:</label>
                        <input type="email" class="input-field" name="email" placeholder="Your Email Address" value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <div class="input">
                        <label for="subject">Subject:</label>
                        <input type="text" class="input-field" name="subject" placeholder="Type your subject line" required>
                    </div>

                    <div class="input">
                        <label for="message">Message:</label>
                        <textarea class="input-field" name="message" placeholder="Type your Message Details Here..." rows="5" required></textarea>
                    </div>

                    <br>
                    <input type="submit" value="Submit Now" name="send" class="font-bold cursor-pointer mb-8 border-none rounded-lg">

                </form>
            </div>
        </div>
    </main>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>
<script src="../../../public/assets/js/header_footer.js"></script>
<script src="../../../public/assets/js/borrower.js"></script>

</html>