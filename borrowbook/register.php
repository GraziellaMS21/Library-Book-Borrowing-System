<?php 
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account</title>
    <style>
        label {
            display: block;
        }
        span{
            color: red;
        }
    </style>
</head>
<body>
    <h1>Register Account</h1>

    <form action="" method="POST">
        <label for="lName">Last Name <span>*</span> : </label>
        <input type="text" name="lName" id="lName">

        <label for="fName">First Name<span>*</span> : </label>
        <input type="text" name="fName" id="fName">

        <label for="middleIn">Middle Initial<span>*</span> : </label>
        <input type="text" name="middleIn" id="middleIn">

        <label for="contactNo">Contact Number<span>*</span> : </label>
        <input type="text" name="contactNo" id="contactNo">

        <label for="email">Email<span>*</span> : </label>
        <input type="text" name="email" id="email">

        <label for="password">Password<span>*</span> : </label>
        <input type="text" name="password" id="password">

        <label for="conPass">Confirm Password<span>*</span> : </label>
        <input type="text" name="conPass" id="conPass">
        
        <br>
        <input type="submit" value="Register Account">
    </form>
</body>
</html>