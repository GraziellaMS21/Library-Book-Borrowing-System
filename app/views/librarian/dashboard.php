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
  <title>Librarian Dashboard</title>
  <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
  <link rel="stylesheet" href="../../../public/assets/css/librarian/adminFinal.css" />
</head>

<body class="w-screen flex">

  <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

  <main>
    <div class="container">
      <div id="dashboardSection" class="section dashboardSection grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="info total-books bg-blue-400">
          <h2>Total Books</h2>
        </div>
        <div class="info total-borrowers bg-green-400">
          <h2>Total Borrowers</h2>
        </div>
        <div class="info overdue-book-count bg-yellow-400">
          <h2>Overdue Books</h2>
        </div>
        <div class="info pending-request bg-red-400">
          <h2>Pending Requests</h2>
        </div>
      </div>
    </div>
  </main>
  </div>
  </div>

  <!-- <form action="../../controllers/logout.php" method="POST">
    <button type="submit" class="bg-red-600 text-white px-4 py-24 rounded-md hover:bg-red-700">
      Logout
    </button>
  </form> -->
</body>

</html>