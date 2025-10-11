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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarianDashboard.css"/>
</head>
<body class="h-screen w-screen flex">
  <!-- Sidebar -->
  <aside class="w-2/12 text-white">
    <div class="sidebar-top">
      <h2 class="text-lg font-bold p-4">Librarian Name</h2>
    </div>
    <div class="sidebar-main font-bold">
      <ul>
        <li class="links"><a href="#" id="dashboardBtn">Dashboard</a></li>
        <li class="links"><a href="#" id="booksBtn">Books</a></li>
        <li class="links"><a href="#" id="borrowersBtn">Borrowers</a></li>
        <li class="links"><a href="#" id="detailsBtn">Borrowing Details</a></li>
        <li class="links"><a href="#" id="reportsBtn">Reports</a></li>
      </ul>
    </div>
  </aside>

  <!-- Main links -->
  <div class="flex flex-col w-10/12">
    <!-- Navbar -->
    <nav>
      <h1 class="text-xl font-semibold">Dashboard</h1>
    </nav>

    <!-- Main Content -->
    <main>
      <div class="container">
        <div class="section dashboardSection grid grid-cols-2 md:grid-cols-4 gap-4">
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
  
        <div class="booksSection hidden">
          <div class="btn-group">
            <button type="button" class="manage w-1/6" id="manageBooksBtn">Manage Books</button><button type="button" class="manage w-1/6" id="manageCategoriesBtn">Manage Categories</button>
          </div>
          
          <div class="section manage_books grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="info total-books bg-blue-400">
              <h2>Total Books</h2>
            </div>
          </div>

          <div class="section manage_categories grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="info total-books bg-red-400">
              <h2>Total Books</h2>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

    <!-- <form action="../../controllers/logout.php" method="POST">

    
  <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
    Logout
  </button> -->
</body>
<script src="../../../public/assets/js/dashboard.js"></script>
</html>