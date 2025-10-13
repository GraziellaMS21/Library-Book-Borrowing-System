<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBook.php");

$bookObj = new Book();

if (isset($_GET['id'])) {
    $bookID = $_GET['id'];
    $book = $bookObj->fetchBook($bookID);

    if (!$book) {
        echo "<p>No book found with that ID.</p>";
        exit;
    }
} else {
    echo "<p>No book ID provided.</p>";
    exit;
}
$category = $bookObj->fetchCategory();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/manage_book.css" />
</head>

<body class="w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="booksSection" id="bookSection">
                <div class="btn-group">
                    <button type="button" class="manage w-1/6" id="manageBooksBtn">Manage Books</button><button
                        type="button" class="manage w-1/6" id="manageCategoriesBtn">Manage Categories</button>
                </div>

                <div class="section manage_books h-full">
                    <div class="addBook rounded-xl p-4 bg-red-800 inline-block text-white my-2">
                        <button><a href="../../../app/views/librarian/booksSection.php"">Return</a></button>
                    </div>
                    <div class=" book-details">
                                <p><strong>Title:</strong> <?= htmlspecialchars($book['book_title']) ?></p>
                                <p><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></p>
                                <p><strong>Category:</strong> <?= htmlspecialchars($book['category_name'] ?? 'N/A') ?>
                                </p>
                                <p><strong>Publication Name:</strong> <?= htmlspecialchars($book['publication_name']) ?>
                                </p>
                                <p><strong>Publication Year:</strong> <?= htmlspecialchars($book['publication_year']) ?>
                                </p>
                                <p><strong>ISBN:</strong> <?= htmlspecialchars($book['ISBN']) ?></p>
                                <p><strong>Copies Available:</strong> <?= htmlspecialchars($book['book_copies']) ?></p>
                                <p><strong>Condition:</strong> <?= htmlspecialchars($book['book_condition']) ?></p>
                                <p><strong>Date Added:</strong> <?= htmlspecialchars($book['date_added']) ?></p>
                    </div>

                    <div class="section manage_categories grid grid-cols-2 md:grid-cols-4 gap-4">

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
<script src="../../../public/assets/js/librarian/dashboard.js"></script>

</html>