<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageCategory.php");
$categoryObj = new Category();

$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];

unset($_SESSION["old"], $_SESSION["errors"]);

$current_modal = $_GET['modal'] ?? '';
$category_id = (int) ($_GET['id'] ?? 0);
$open_modal = '';

if ($current_modal === 'add') {
    $open_modal = 'addCategoryModal';
} elseif ($current_modal === 'edit') {
    $open_modal = 'editCategoryModal';
} elseif ($current_modal === 'delete') {
    $open_modal = 'deleteConfirmModal';
}

$modal = [];

if ($open_modal == 'editCategoryModal' || $open_modal == 'deleteConfirmModal') {
    if ($open_modal == 'editCategoryModal' && !empty($old)) {
        $modal = $old;
    } else {
        $modal = $categoryObj->fetchCategory($category_id) ?: [];
    }
    if ($open_modal == 'deleteConfirmModal') {
        $modal['categoryID'] = $category_id;
    }
} elseif ($open_modal == 'addCategoryModal') {
    $modal = $old;
}


$categories = $categoryObj->viewCategory();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/adminFinal.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>


    <div class="flex flex-col w-10/12">
    <nav>
        <h1 class="text-xl font-semibold">Categories</h1>
    </nav>
    <main>
        <div class="container">
            <div class="section h-full">
                <div class="title flex w-full items-center justify-between">
                    <h1 class="text-red-800 font-bold text-4xl">MANAGE CATEGORIES</h1>
                    <a id="openAddCategoryModalBtn" class="addBtn" href="categorySection.php?modal=add">+ Add
                        Category</a>
                </div>


                <div class="view">
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Category Name</th>
                            <th>Actions</th>
                        </tr>

                        <?php
                        $no = 1;
                        foreach ($categories as $cat) {
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $cat["category_name"] ?></td>
                                <td class="action text-center">
                                    <a class="editBtn"
                                        href="categorySection.php?modal=edit&id=<?= $cat['categoryID'] ?>">Edit</a>
                                    <a class="deleteBtn"
                                        href="categorySection.php?modal=delete&id=<?= $cat['categoryID'] ?>">
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

    <div id="addCategoryModal" class="modal <?= $open_modal == 'addCategoryModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close close-times" data-modal="addCategoryModal">&times;</span>
            <h2>Add New Category</h2>
            <form action="../../../app/controllers/categoryController.php?action=add" method="POST" autocomplete="off">
                <div class="input">
                    <label for="category_name">Category Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="category_name" id="add_category_name"
                        value="<?= $modal["category_name"] ?? "" ?>">
                    <p class="errors"><?= $errors["category_name"] ?? "" ?></p>
                </div>
                <br>
                <input type="submit" value="Add Category" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
            </form>
        </div>
    </div>

    <div id="editCategoryModal" class="modal <?= $open_modal == 'editCategoryModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close close-times" data-modal="editCategoryModal">&times;</span>
            <h2>Edit Category</h2>
            <form id="editCategoryForm"
                action="../../../app/controllers/categoryController.php?action=edit&id=<?= $category_id ?>"
                method="POST" autocomplete="off">
                <input type="hidden" name="categoryID" value="<?= $category_id ?>">
                <div class="input">
                    <label for="category_name">Category Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="category_name" id="edit_category_name"
                        value="<?= $modal["category_name"] ?? "" ?>">
                    <p class="errors">
                        <?= $errors["category_name"] ?? "" ?>
                    </p>
                </div>
                <br>
                <input type="submit" value="Save Changes" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
            </form>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal delete-modal <?= $open_modal == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to delete the category:
                <span
                    class="font-semibold italic"><?= htmlspecialchars($modal['category_name'] ?? 'this category') ?></span>?
                This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button" data-modal="deleteConfirmModal"
                    class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">
                    Cancel
                </button>
                <a href="../../../app/controllers/categoryController.php?action=delete&id=<?= htmlspecialchars($modal['categoryID'] ?? $category_id) ?>"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 cursor-pointer">
                    Confirm Delete
                </a>
            </div>
        </div>
    </div>


</body>
<script src="../../../public/assets/js/admin.js"></script>

</html>