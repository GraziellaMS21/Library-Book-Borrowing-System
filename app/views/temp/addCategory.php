<?php
session_start();
$errors = $_SESSION["errors"] ?? [];
$book = $_SESSION["old"] ?? [];
unset($_SESSION["errors"], $_SESSION["old"]);

require_once(__DIR__ . "/../../models/manageCategory.php");
$categoryObj = new Category();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
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

                <div class="section manage_categories h-full">
                    <div class="addCat flex items-center gap-3">
                        <form action="../../../app/controllers/addCategoryController.php?action=add" method="POST">
                            <div class="addDiv flex items-center gap-3" id="addDiv">
                                <input type="text" name="category_name" id="category_name"
                                    class="border border-red-800 rounded-lg p-2" placeholder="Enter category name"
                                    value="<?= $category["category_name"] ?? "" ?>">
                                <p class="errors"><?= $errors["category_name"] ?? "" ?></p>
                                <br>
                                <input type="submit" value="Add" class="rounded-xl p-2 bg-red-800 text-white">
                            </div>
                        </form>
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
    </main>
    </div>



</body>
<script src="../../../public/assets/js/librarian/admin2.js"></script>

</html>