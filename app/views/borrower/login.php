<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: ../../views/borrower/catalogue.php");
    exit;
}

$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["errors"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../public/assets/css/login.css">
    <link rel="stylesheet" href="../../../public/assets/css/components/header_footer.css">
    <link href="https://fonts.googleapis.com/css2?family=Licorice&display=swap" rel="stylesheet">
</head>
<body>
    <div class="color-layer"></div>

    <?php require_once(__DIR__ . '/../shared/header.php'); ?>
    
    <main class="flex justify-center items-center">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 flex flex-col justify-center items-center">
                <div class="image">
                    <img src="../../../public/assets/images/bg.png" alt="Background Image">
                </div>
            </div>
            
            <div class="form-section w-1/2 flex flex-col justify-center items-center">
                <h1 class="font-extrabold">LOG IN</h1>
                <form action="../../controllers/loginController.php" method="POST">
                    <p class="errors" name="invalid"><?= $errors["invalid"] ?? "" ?></p>
            
                    <div class="input">
                        <label for="email">Email:</label>
                        <input type="text" class="input-field" name="email">
                        <p class="errors"><?= $errors["email"] ?? "" ?></p>
                    </div>
                    <div class="input">
                        <label for="password">Password:</label>
                        <input type="password" class="input-field" name="password">
                        <p class="errors"><?= $errors["password"] ?? "" ?></p>
                    </div>

                    <br>
                    <input type="submit" value="Log In" class="font-bold cursor-pointer mb-8 border-none rounded-lg">

                    <div class="register py-5 flex justify-center font-bold">
                        <p>Don't Have an Account Yet? 
                            <span><a href="../../../app/views/borrower/register.php">Register Account</a></span>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>
<script src="../../../public/assets/js/components/header_footer.js"></script>
</html>
