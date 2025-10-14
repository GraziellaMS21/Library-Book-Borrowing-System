<?php
session_start();
$errors = $_SESSION["errors"] ?? [];
$category = $_SESSION["old"] ?? [];
unset($_SESSION["errors"], $_SESSION["old"]);

require_once(__DIR__ . "/../../models/manageCategory.php");
$categoryObj = new Category();

if (isset($_GET['id'])) {
    $categoryID = $_GET['id'];
    $category = $categoryObj->fetchCategory($categoryID);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/manage_category.css" />
</head>

<body class="w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="booksSection" id="bookSection">
                <div class="btn-group">
                    <button type="button" class="manage w-1/6" id="manageBooksBtn"><a
                            href="../../../app/views/librarian/booksSection.php">Manage Books</a></button><button
                        type="button" class="manage w-1/6" id="manageCategoriesBtn"><a
                            href="../../../app/views/librarian/booksSection.php">Manage Categories</a></button>
                </div>

                <div class=" section manage_categories h-full">
                    <div class="addBook rounded-xl p-4 bg-red-800 inline-block text-white my-2">
                    <button><a href="../../../app/views/librarian/booksSection.php"">Return</a></button>
                    </div>
                            <div class="addCat flex items-center gap-3">
                                <form
                                    action=" ../../../app/controllers/editCategoryController.php?id=<?= $category['categoryID'] ?>"
                                    method="POST">
                                    <div class="addDiv flex items-center gap-3" id="addDiv">
                                        <input type="text" name="category_name" id="category_name"
                                            class="border border-red-800 rounded-lg p-2"
                                            placeholder="Enter category name"
                                            value="<?= $category["category_name"] ?? "" ?>">
                                        <p class="errors"><?= $errors["category_name"] ?? "" ?></p>
                                        <br>
                                        <input type="submit" value="Add" class="rounded-xl p-2 bg-red-800 text-white">
                                    </div>
                                </form>
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
<script src="../../../public/assets/js/librarian/admin2.js"></script>

</html>