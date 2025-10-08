<?php
    require_once "../config/database.php";
    require_once "../classes/userLogin.php";
    $loginObj = new Login();

    $login = [];
    $errors = [];
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $login["email"] = trim(htmlspecialchars($_POST["email"]));
        $login["password"] = trim(htmlspecialchars($_POST["password"]));

        if(empty($login["email"])){
            $errors["email"] = "Please Input Your Email Address";
        }

        if(empty($login["password"])){
            $errors["password"] = "Please Input Your Password";
        }

        if(empty(array_filter($errors))){
            $result = $loginObj->logIn($login["email"], $login["password"]);
            
            if($result === true){
                header("location: view.php");
                exit;
            } else $errors["invalid"] = $result;
        }
        
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/css/login.css"/>
    <link rel="stylesheet" href="../public/css/components/header_footer.css"/>
    
    <link href="https://fonts.googleapis.com/css2?family=Licorice&display=swap" rel="stylesheet">
</head>
<body>
    <div class="color-layer"></div>
    <?php require_once "../includes/header.php"?>

     <main class="flex justify-center items-center">
        <div class="form-container flex justify-center">
            <div class="info-section w-1/2 flex flex-col justify-center items-center">
                <div class="image">
                    <img src="../public/images/bg.png" alt="">
                </div>
            </div>
            
            <div class="form-section w-1/2 flex flex-col justify-center items-center">
                <h1 class="font-extrabold">LOG IN</h1>
                <form action="" method = "POST">
                    
                    <p class="errors" name="invalid"><?= $errors["invalid"] ?? ""?></p>
            
                    <div class="input">
                        <label for="email">Email:</label>
                        <input type="text" class="input-field" name="email">
                        <p class="errors"><?= $errors["email"] ?? ""?></p>
                    </div>
                    <div class="input">
                        <label for="password">Password: </label>
                        <input type="text" class="input-field" name="password">
                        <p class="errors"><?= $errors["password"] ?? ""?></p>
                    </div>
                    <br>
                    <input type="submit" value="Log In" class="font-bold cursor-pointer mb-8 border-none rounded-lg">

                    <div class="register py-1 flex justify-center font-bold">
                        <p>Don't Have an Account Yet? <span><a href="../views/register.php">Log In</a></span></p>
                    </div>
                </form>
            </div>
    </main>
    
    <?php require_once "../includes/footer.php"?>
</body>
<script src="../public/js/components/header_footer.js"></script>
</html>