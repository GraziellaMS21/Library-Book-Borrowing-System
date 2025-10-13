<?php
session_start();
$errors = $_SESSION["errors"] ?? [];
$book = $_SESSION["old"] ?? [];
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
                    <form action=" ../../../app/controllers/addCategoryController.php" method="POST">
                                <div class="input">
                                    <label for="book_title">Book Title<span>*</span> : </label>
                                    <input type="text" class="input-field" name="book_title" id="book_title"
                                        value="<?= $book["book_title"] ?? "" ?>">
                                    <p class="errors"><?= $errors["book_title"] ?? "" ?></p>
                                </div>

                                <div class="input">
                                    <label for="author">Author<span>*</span> : </label>
                                    <input type="text" class="input-field" name="author" id="author"
                                        value="<?= $book["author"] ?? "" ?>">
                                    <p class="errors"><?= $errors["author"] ?? "" ?></p>
                                </div>

                                <div class="categoryID">
                                    <label for="categoryID">Category<span>*</span> : </label>
                                    <select name="categoryID" id="categoryID">
                                        <option value="">---Select Option---</option>
                                        <?php foreach ($category as $cat) {
                                            ?>
                                            <option value="<?= $cat['categoryID'] ?>" <?= isset($book['categoryID']) && $cat['categoryID'] == $book['categoryID'] ? 'selected' : '' ?>>
                                                <?= $cat['category_name'] ?>
                                            </option>

                                            <?php
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="input">
                                    <label for="publication_name">Publication Name<span>*</span> : </label>
                                    <input type="text" class="input-field" name="publication_name" id="publication_name"
                                        value="<?= $book["publication_name"] ?? "" ?>">
                                    <p class="errors"><?= $errors["publication_name"] ?? "" ?></p>
                                </div>

                                <div class="input">
                                    <label for="publication_year">Publication Year<span>*</span> : </label>
                                    <input type="text" class="input-field" name="publication_year" id="publication_year"
                                        value="<?= $book["publication_year"] ?? "" ?>">
                                    <p class="errors"><?= $errors["publication_year"] ?? "" ?></p>
                                </div>

                                <div class="input">
                                    <label for="ISBN">ISBN<span>*</span> : </label>
                                    <input type="text" class="input-field" name="ISBN" id="ISBN"
                                        value="<?= $book["ISBN"] ?? "" ?>">
                                    <p class="errors"><?= $errors["ISBN"] ?? "" ?></p>
                                </div>

                                <div class="input">
                                    <label for="book_copies">No. of Copies<span>*</span> : </label>
                                    <input type="text" class="input-field" name="book_copies" id="book_copies"
                                        value="<?= $book["book_copies"] ?? "" ?>">
                                    <p class="errors"><?= $errors["book_copies"] ?? "" ?></p>
                                </div>

                                <div class="book_condition">
                                    <label for="book_condition">Condition<span>*</span> : </label>
                                    <select name="book_condition" id="book_condition">
                                        <option value="">---Select Option---</option>
                                        <option value="Good" <?= isset($book["book_condition"]) && $book["book_condition"] == "Good" ? "selected" : "" ?>>Good</option>

                                        <option value="Damaged" <?= isset($book["book_condition"]) && $book["book_condition"] == "Damaged" ? "selected" : "" ?>>Damaged</option>

                                        <option value="Lost" <?= isset($book["book_condition"]) && $book["book_condition"] == "Lost" ? "selected" : "" ?>>Lost</option>
                                    </select>
                                </div>
                                <br>
                                <input type="submit" value="Add Book"
                                    class="font-bold cursor-pointer mb-8 border-none rounded-lg">
                                </form>
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