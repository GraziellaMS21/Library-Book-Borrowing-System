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

$current_modal = $_GET['modal'] ?? '';
$current_id = (int) ($_GET['id'] ?? 0);
$open_modal = '';

if ($current_modal === 'add') {
    $open_modal = 'addBookModal';
} elseif ($current_modal === 'edit') {
    $open_modal = 'editBookModal';
} elseif ($current_modal === 'view') {
    $open_modal = 'viewDetailsModal';
} elseif ($current_modal === 'delete') {
    $open_modal = 'deleteConfirmModal';
}

$modal_book = [];

if ($open_modal == 'editBookModal' || $open_modal == 'viewDetailsModal' || $open_modal == 'deleteConfirmModal') {
    if ($open_modal == 'editBookModal' && !empty($old)) {
        $modal_book = $old;
    } else {
        $modal_book = $bookObj->fetchBook(bookID: $current_id) ?: [];
    }
    if ($open_modal != 'viewDetailsModal') {
        $modal_book['bookID'] = $current_id;
    }
} elseif ($open_modal == 'addBookModal') {
    $modal_book = $old;
}

require_once(__DIR__ . "/../../models/manageCategory.php");
$categoryObj = new Category();

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
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/admin.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="section h-full">
                <div class="title flex w-full items-center justify-between">
                    <h1 class="text-red-800 font-bold text-4xl">MANAGE BOOKS</h1>
                    <a id="openAddBookModalBtn" class="addBtn" href="booksSection.php?modal=add" ?>+ Add Book</a>
                </div>

                <form method="GET" class="search">
                    <input type="text" name="search" placeholder="Search book title..." value="<?= ($search) ?>">
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach ($category as $cat): ?>
                            <option value="<?= $cat["categoryID"] ?>" <?= $categoryID == $cat["categoryID"] ? 'selected' : '' ?>> <?= $cat["category_name"] ?> </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="bg-red-800 text-white rounded-lg px-4 py-2">Search</button>
                </form>


                <div class="view">
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
                                    <a class="editBtn" href="booksSection.php?modal=edit&id=<?= $book['bookID'] ?>">Edit</a>
                                    <a class="viewBtn" href="booksSection.php?modal=view&id=<?= $book['bookID'] ?>">View
                                        Details</a>
                                    <a class="deleteBtn" href="booksSection.php?modal=delete&id=<?= $book['bookID'] ?>">
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

    <div id="addBookModal" class="modal <?= $open_modal == 'addBookModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="addBookModal">&times;</span>
            <h2>Add New Book</h2>
            <form action="../../../app/controllers/bookController.php?action=add" method="POST">
                <div class="input">
                    <label for="book_title">Book Title<span>*</span> : </label>
                    <input type="text" class="input-field" name="book_title" id="book_title"
                        value="<?= ($modal_book["book_title"] ?? "") ?>">
                    <p class="errors"><?= $errors["book_title"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="author">Author<span>*</span> : </label>
                    <input type="text" class="input-field" name="author" id="author"
                        value="<?= ($modal_book["author"] ?? "") ?>">
                    <p class="errors"><?= $errors["author"] ?? "" ?></p>
                </div>

                <div class="categoryID">
                    <label for="categoryID">Category<span>*</span> : </label>
                    <select name="categoryID" id="add_categoryID">
                        <option value="">---Select Option---</option>
                        <?php foreach ($category as $cat) {
                            $selected = (($modal_book['categoryID'] ?? '') == $cat['categoryID']) ? 'selected' : '';
                            ?>
                            <option value="<?= $cat['categoryID'] ?>" <?= $selected ?>>
                                <?= ($cat['category_name']) ?>
                            </option>
                        <?php } ?>
                    </select>
                    <p class="errors"><?= $errors["categoryID"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="publication_name">Publication Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="publication_name" id="publication_name"
                        value="<?= $modal_book["publication_name"] ?? "" ?>">
                    <p class="errors"><?= $errors["publication_name"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="publication_year">Publication Year<span>*</span> : </label>
                    <input type="text" class="input-field" name="publication_year" id="publication_year"
                        value="<?= $modal_book["publication_year"] ?? "" ?>">
                    <p class="errors"><?= $errors["publication_year"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="ISBN">ISBN<span>*</span> : </label>
                    <input type="text" class="input-field" name="ISBN" id="add_ISBN"
                        value="<?= $modal_book["ISBN"] ?? "" ?>">
                    <p class="errors"><?= $errors["ISBN"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="book_copies">No. of Copies<span>*</span> : </label>
                    <input type="text" class="input-field" name="book_copies" id="book_copies"
                        value="<?= $modal_book["book_copies"] ?? "" ?>">
                    <p class="errors"><?= $errors["book_copies"] ?? "" ?></p>
                </div>

                <div class="book_condition">
                    <label for="book_condition">Condition<span>*</span> : </label>
                    <select name="book_condition" id="add_book_condition">
                        <option value="">---Select Option---</option>
                        <?php
                        $condition_options = ["Good", "Damaged", "Lost"];
                        foreach ($condition_options as $option) {
                            $selected = (($modal_book["book_condition"] ?? '') == $option) ? "selected" : "";
                            echo "<option value='{$option}' {$selected}>{$option}</option>";
                        }
                        ?>
                    </select>
                    <p class="errors"><?= $errors["book_condition"] ?? "" ?></p>
                </div>

                <br>
                <input type="submit" value="Add Book" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
            </form>
        </div>
    </div>


    <div id="editBookModal" class="modal <?= $open_modal == 'editBookModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="editBookModal">&times;</span>
            <h2>Edit Book</h2>
            <form id="editBookForm"
                action="../../../app/controllers/bookController.php?action=edit&id=<?= $current_id ?>" method="POST">
                <input type="hidden" name="bookID" value="<?= $current_id ?>">
                <div class="input">
                    <label for="book_title">Book Title<span>*</span> : </label>
                    <input type="text" class="input-field" name="book_title" id="edit_book_title"
                        value="<?= $modal_book["book_title"] ?? "" ?>">
                    <p class="errors">
                        <?= $errors["book_title"] ?? "" ?>
                    </p>
                </div>

                <div class="input">
                    <label for="author">Author<span>*</span> : </label>
                    <input type="text" class="input-field" name="author" id="edit_author"
                        value="<?= $modal_book["author"] ?? "" ?>">
                    <p class="errors">
                        <?= $errors["author"] ?? "" ?>
                    </p>
                </div>

                <div class="categoryID">
                    <label for="categoryID">Category<span>*</span> : </label>
                    <select name="categoryID" id="edit_categoryID">
                        <option value="">---Select Option---</option>
                        <?php foreach ($category as $cat) {
                            $selected = (($modal_book['categoryID'] ?? '') == $cat['categoryID']) ? 'selected' : '';
                            ?>
                            <option value="<?= $cat['categoryID'] ?>" <?= $selected ?>>
                                <?= $cat['category_name'] ?>
                            </option>
                        <?php } ?>
                    </select>
                    <p class="errors">
                        <?= $errors["categoryID"] ?? "" ?>
                    </p>
                </div>

                <div class="input">
                    <label for="publication_name">Publication Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="publication_name" id="edit_publication_name"
                        value="<?= $modal_book["publication_name"] ?? "" ?>">
                    <p class="errors">
                        <?= $errors["publication_name"] ?? "" ?>
                    </p>
                </div>

                <div class="input">
                    <label for="publication_year">Publication Year<span>*</span> : </label>
                    <input type="text" class="input-field" name="publication_year" id="edit_publication_year"
                        value="<?= $modal_book["publication_year"] ?? "" ?>">
                    <p class="errors">
                        <?= $errors["publication_year"] ?? "" ?>
                    </p>
                </div>

                <div class="input">
                    <label for="ISBN">ISBN<span>*</span> : </label>
                    <input type="text" class="input-field" name="ISBN" id="edit_ISBN"
                        value="<?= $modal_book["ISBN"] ?? "" ?>">
                    <p class="errors">
                        <?= $errors["ISBN"] ?? "" ?>
                    </p>
                </div>

                <div class="input">
                    <label for="book_copies">No. of Copies<span>*</span> : </label>
                    <input type="text" class="input-field" name="book_copies" id="edit_book_copies"
                        value="<?= $modal_book["book_copies"] ?? "" ?>">
                    <p class="errors">
                        <?= $errors["book_copies"] ?? "" ?>
                    </p>
                </div>

                <div class="book_condition">
                    <label for="book_condition">Condition<span>*</span> : </label>
                    <select name="book_condition" id="edit_book_condition">
                        <option value="">---Select Option---</option>
                        <?php
                        $condition_options = ["Good", "Damaged", "Lost"];
                        foreach ($condition_options as $option) {
                            $selected = (($modal_book["book_condition"] ?? '') == $option) ? "selected" : "";
                            echo "<option value='{$option}' {$selected}>{$option}</option>";
                        }
                        ?>
                    </select>
                    <p class="errors">
                        <?= $errors["book_condition"] ?? "" ?>
                    </p>
                </div>
                <br>
                <input type="submit" value="Save Changes" class="font-bold cursor-pointer mb-8 border-none rounded-lg">
            </form>
        </div>
    </div>

    <div id="viewDetailsModal" class="modal <?= $open_modal == 'viewDetailsModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="viewDetailsModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Full Book Details</h2>
            <div class="book-details grid grid-cols-2 gap-y-2 gap-x-4 text-sm">
                <p class="col-span-2">Title: <?= $modal_book['book_title'] ?? 'N/A' ?></p>

                <p>Author: <?= $modal_book['author'] ?? 'N/A' ?></p>
                <p>Category: <?= $modal_book['category_name'] ?? 'N/A' ?></p>

                <p>Publication: <?= $modal_book['publication_name'] ?? 'N/A' ?></p>
                <p>Pub. Year: <?= $modal_book['publication_year'] ?? 'N/A' ?></p>

                <p>ISBN: <?= $modal_book['ISBN'] ?? 'N/A' ?></p>
                <p>Copies: <?= $modal_book['book_copies'] ?? 'N/A' ?></p>

                <p>Condition: <?= $modal_book['book_condition'] ?? 'N/A' ?></p>
                <p>Date Added: <?= $modal_book['date_added'] ?? 'N/A' ?></p>

                <p>Status: <span class="font-semibold"><?= $modal_book['status'] ?? 'N/A' ?></span></p>

            </div>
            <div class="mt-6 text-right">
                <button type="button"
                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    onclick="document.getElementById('viewDetailsModal').style.display='none';">Close</button>
            </div>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal delete-modal <?= $open_modal == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close" data-modal="deleteConfirmModal">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to delete the book:
                <span class="font-semibold italic"><?= $modal_book['book_title'] ?? 'this book' ?></span>?
                This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    onclick="document.getElementById('deleteConfirmModal').style.display='none';">
                    Cancel
                </button>
                <a href="../../../app/controllers/bookController.php?action=delete&id=<?= $modal_book['bookID'] ?? $delete_book_id ?>"
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
        const addBookModal = document.getElementById('addBookModal');
        const openAddBookBtn = document.getElementById('openAddBookModalBtn');
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
            // Immediately reset form when manually closed
            if (modal.id === 'addBookModal') {
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                }
                //clear error
                document.querySelectorAll('#addBookModal .errors').forEach(p => p.textContent = '');
            }
        };
        // Close Modal using closeBtn
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-modal');
                closeModal(document.getElementById(modalId));

            });
        });

        //Close Modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
            }
        });
    });
</script>

</html>