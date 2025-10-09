<?php
  session_start();
  if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
  }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian-Dashboard</title>
    <link rel="stylesheet" href="librarianDashboard.css"/>
</head>
<body>
    <form action="../../controllers/logout.php" method="POST">
  <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
    Logout
  </button>
</body>
</html>