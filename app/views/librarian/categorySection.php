<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageCategory.php");
$categoryObj = new Category();

// 1. Retrieve temporary session data for PRG pattern
$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];

// Clear the session variables for old data and errors after retrieval
unset($_SESSION["old"], $_SESSION["errors"]);

// 2. Get state from query parameters. This is the primary way to open modals.
$query_modal = $_GET['modal'] ?? '';
$query_id = (int) ($_GET['id'] ?? 0);

// 3. Determine which modal to open
$open_modal_cat = '';
$edit_category_id = null;
$delete_category_id = null;

if ($query_modal === 'add') {
    $open_modal_cat = 'addCategoryModal';
} elseif ($query_modal === 'edit' && $query_id) {
    $open_modal_cat = 'editCategoryModal';
    $edit_category_id = $query_id;
} elseif ($query_modal === 'delete' && $query_id) {
    $open_modal_cat = 'deleteConfirmModal';
    $delete_category_id = $query_id;
}


// 4. Load Category Data for Modals (Separated for clarity)
$modal_category_data = []; // For Edit/Delete
$add_category_data = [];   // For Add

if ($open_modal_cat == 'editCategoryModal' && $edit_category_id) {
    // Check if there are old inputs (failed submission)
    if (empty($old)) {
        // Load fresh data for an initial edit click
        $modal_category_data = $categoryObj->fetchCategory($edit_category_id);
    } else {
        // Use failed submission data if validation failed (data is in $old)
        $modal_category_data = $old;
        $modal_category_data['categoryID'] = $edit_category_id; // Keep the category ID
    }
} elseif ($open_modal_cat == 'deleteConfirmModal' && $delete_category_id) {
    $modal_category_data = $categoryObj->fetchCategory($delete_category_id);
    if (!$modal_category_data) {
        $modal_category_data = ['category_name' => 'Category not found.'];
    }
    $modal_category_data['categoryID'] = $delete_category_id;
} elseif ($open_modal_cat == 'addCategoryModal' && !empty($old)) {
    // If adding category has invalid inputs, retrieve the previous input (data is in $old)
    $add_category_data = $old;
}


$all_categories = $categoryObj->viewCategory();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/admin.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="section h-full">
                <div class="title flex w-full items-center justify-between">
                    <h1 class="text-red-800 font-bold text-4xl">MANAGE CATEGORIES</h1>
                    <a id="openAddCategoryModalBtn" class="addBtn" href="categorySection.php?modal=add">+ Add
                        Category</a>
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
                        foreach ($all_categories as $cat) {
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

    <div id="addCategoryModal" class="modal <?= $open_modal_cat == 'addCategoryModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="addCategoryModal">&times;</span>
            <h2>Add New Category</h2>
            <form action="../../../app/controllers/categoryController.php?action=add" method="POST" autocomplete="off">
                <div class="input">
                    <label for="category_name">Category Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="category_name" id="add_category_name"
                        value="<?= $add_category_data["category_name"] ?? "" ?>">
                    <p class="errors"><?= $errors["category_name"] ?? "" ?></p>
                </div>
                <br>
                <input type="submit" value="Add Category" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
            </form>
        </div>
    </div>

    <div id="editCategoryModal" class="modal <?= $open_modal_cat == 'editCategoryModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="editCategoryModal">&times;</span>
            <h2>Edit Category</h2>
            <form id="editCategoryForm"
                action="../../../app/controllers/categoryController.php?action=edit&id=<?= $edit_category_id ?>"
                method="POST" autocomplete="off">
                <input type="hidden" name="categoryID" value="<?= $edit_category_id ?>">
                <div class="input">
                    <label for="category_name">Category Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="category_name" id="edit_category_name"
                        value="<?= $modal_category_data["category_name"] ?? "" ?>">
                    <p class="errors">
                        <?= $errors["category_name"] ?? "" ?>
                    </p>
                </div>
                <br>
                <input type="submit" value="Save Changes" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
            </form>
        </div>
    </div>

    <div id="deleteConfirmModal"
        class="modal delete-modal <?= $open_modal_cat == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close" data-modal="deleteConfirmModal">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to delete the category:
                <span
                    class="font-semibold italic"><?= htmlspecialchars($modal_category_data['category_name'] ?? 'this category') ?></span>?
                This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    onclick="document.getElementById('deleteConfirmModal').style.display='none';">
                    Cancel
                </button>
                <a href="../../../app/controllers/categoryController.php?action=delete&id=<?= htmlspecialchars($modal_category_data['categoryID'] ?? $delete_category_id) ?>"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 cursor-pointer">
                    Confirm Delete
                </a>
            </div>
        </div>
    </div>


</body>
<script src="../../../public/assets/js/librarian/admin.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addCategoryModal = document.getElementById('addCategoryModal');
        const openAddCategoryBtn = document.getElementById('openAddCategoryModalBtn');
        const closeBtns = document.querySelectorAll('.close');

        // Function to open a modal
        const openModal = (modal) => {
            modal.style.display = 'flex';
            modal.classList.add('open');
        };

        // Function to close a modal
        const closeModal = (modal) => {
            modal.style.display = 'none';
            modal.classList.remove('open');

            // Immediately reset form when manually closed (important for add modal)
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                // clear error
                modal.querySelectorAll('.errors').forEach(p => p.textContent = '');
            }
        };

        // Open Modal using addBtn
        openAddCategoryBtn.addEventListener('click', (e) => {
            e.preventDefault();
            // Clear any old data/errors when opening manually
            const form = document.querySelector('#addCategoryModal form');
            if (form) {
                form.reset();
            }
            document.querySelectorAll('#addCategoryModal .errors').forEach(p => p.textContent = '');

            openModal(addCategoryModal);
        });

        // Close Modal using Buttons
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-modal');
                closeModal(document.getElementById(modalId));
            });
        });

        // Close Modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
            }
        });

        // If the page was loaded with a modal parameter (from controller failure or direct link), open it
        const currentModal = document.querySelector('.modal.open');
        if (currentModal) {
            openModal(currentModal);
        }
    });
</script>

</html>