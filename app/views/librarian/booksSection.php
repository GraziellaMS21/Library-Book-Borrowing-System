<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

$errors = $_SESSION["errors"] ?? [];
$book = $_SESSION["old"] ?? [];

// Clear after loading so values don’t persist forever
unset($_SESSION["errors"], $_SESSION["old"]);

require_once(__DIR__ . "/../../models/manageBook.php");
$bookObj = new Book();
$category = $bookObj->fetchCategory();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/dashboard.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="booksSection" id="bookSection">
                <div class="btn-group">
                    <button type="button" class="manage w-1/6" id="manageBooksBtn">Manage Books</button><button
                        type="button" class="manage w-1/6" id="manageCategoriesBtn">Manage Categories</button>
                </div>

                <div class="section manage_books h-full">
                    <div class="addBook">
                        <button><a href="../../../app/views/librarian/addBook.php"">Add Book</a></button>
                    </div>
                    <div class=" searchBook">
                                <input type="text">
                                <select name="category" id="category">
                                    <?php
                                    foreach ($category as $cat) {
                                        ?>
                                        <option value="<?= $cat["categoryID"] ?>"><?= $cat["category_name"] ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <button type="button">Search</button>
                    </div>
                    <div class="viewBooks">
                        <table>
                            <tr>
                                <th>No</th>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>No. of Copies</th>
                                <th>Condition</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                            <?php
                            $no = 1;
                            foreach ($bookObj->viewBook() as $book) {
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $book["book_title"] ?></td>
                                    <td><?= $book["author"] ?></td>
                                    <td><?= $book["category_name"] ?></td>
                                    <td><?= $book["book_copies"] ?></td>
                                    <td><?= $book["book_condition"] ?></td>
                                    <td><?= $book["status"] ?></td>
                                    <td>
                                        <button>Edit</button>
                                        <button>Delete</button>
                                        <button>View Full Details</button>
                                    </td>

                                </tr>
                                <?php
                            }
                            ?>
                        </table>
                    </div>
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