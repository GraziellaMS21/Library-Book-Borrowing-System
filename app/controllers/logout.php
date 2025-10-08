<?php
    session_start();
    session_unset();
    session_destroy();
    header("Location: ../../app/views/borrower/login.php");
    exit;
?>
