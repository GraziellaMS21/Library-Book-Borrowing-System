<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBook.php");
$bookObj = new Book();
$category = $bookObj->fetchCategory();

$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["old"], $_SESSION["errors"]);

require_once(__DIR__ . "/../../models/manageCategory.php");
$categoryObj = new Category();

$bookObj = new Book();

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$categoryID = isset($_GET['category']) ? trim($_GET['category']) : "";

$books = $bookObj->viewBook($search, $categoryID);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/admin1.css" />
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
                    <div class="addBook rounded-xl p-4 bg-red-800 inline-block text-white my-2">
                        <a href="../../../app/views/librarian/addBook.php">Add Book</a>
                    </div>
                    <form method="GET" class="searchBook">
                        <input type="text" name="search" placeholder="Search book title..."
                            class="border border-red-800 rounded-lg p-2 w-1/3">
                        <select name="category" id="category" class="border border-gray-400 rounded-lg p-2">
                            <option value="">All Categories</option>
                            <?php foreach ($category as $cat): ?>
                                <option value="<?= $cat["categoryID"] ?>"><?= $cat["category_name"] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-red-800 text-white rounded-lg px-4 py-2">Search</button>
                    </form>


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
                            foreach ($books as $book) {
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $book["book_title"] ?></td>
                                    <td><?= $book["author"] ?></td>
                                    <td><?= $book["category_name"] ?></td>
                                    <td><?= $book["book_copies"] ?></td>
                                    <td><?= $book["book_condition"] ?></td>
                                    <td><?= $book["status"] ?></td>
                                    <td class="action text-center">
                                        <a class="editBtn"
                                            href="../../../app/views/librarian/editBook.php?id=<?= $book['bookID'] ?>">Edit</a>
                                        <a class="deleteBtn"
                                            href="../../../app/controllers/deleteBookController.php?id=<?= $book['bookID'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this book?');">
                                            Delete
                                        </a>
                                        <a class="viewBtn"
                                            href="../../../app/views/librarian/fullDetails.php?id=<?= $book['bookID'] ?>">View
                                            Full Details</a>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </table>
                    </div>
                </div>

                <div class="section manage_categories h-full">
                    <div class="addCat flex items-center gap-3">
                        <a id="addCatBtn" class="rounded-xl p-4 bg-red-800 text-white"
                            href="../../../app/views/librarian/addCategory.php">
                            Add Category
                        </a>
                    </div>
                    <div class="viewBooks">
                        <table>
                            <tr>
                                <th>No</th>
                                <th>Category Name</th>
                                <th>Actions</th>
                            </tr>

                            <?php
                            $no = 1;
                            foreach ($categoryObj->viewCategory() as $cat) {
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $cat["category_name"] ?></td>
                                    <td class="action text-center">
                                        <a class="editBtn"
                                            href="../../../app/views/librarian/editCategory.php?id=<?= $cat['categoryID'] ?>">Edit</a>
                                        <a class="deleteBtn"
                                            href="../../../app/controllers/deleteCategoryController.php?id=<?= $cat['categoryID'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this Category?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </table>
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
<script src="../../../public/assets/js/librarian/admin.js"></script>

</html>