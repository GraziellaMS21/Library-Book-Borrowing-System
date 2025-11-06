<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBook.php");
$bookObj = new Book();
$category = $bookObj->fetchCategory();

//retrieves old data and errors
$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];

// Clear the session variables related to form errors/old data for new session
unset($_SESSION["old"], $_SESSION["errors"]);

//retrieves the current modal and id or success modal
$current_modal = $_GET['modal'] ?? '';
$book_id = (int) ($_GET['id'] ?? 0);
$success_modal = $_GET['success'] ?? '';

$open_modal = '';

//open CRUD modals
if ($current_modal === 'add') {
    $open_modal = 'addBookModal';
} elseif ($current_modal === 'edit') {
    $open_modal = 'editBookModal';
} elseif ($current_modal === 'view') {
    $open_modal = 'viewDetailsModal';
} elseif ($current_modal === 'delete') {
    $open_modal = 'deleteConfirmModal';
}

//open Success Modals
if ($success_modal === "add") {
    $success_message = "Adding";
} elseif ($success_modal === "edit") {
    $success_message = "Editing";
} elseif ($success_modal === "delete") {
    $success_message = "Deleting";
}

//Loading information to modals
$modal_book = [];
if ($open_modal == 'editBookModal' || $open_modal == 'viewDetailsModal' || $open_modal == 'deleteConfirmModal') {
    if ($open_modal == 'editBookModal' && !empty($old)) {
        // Populate from session data if there were errors
        $modal_book = $old;
        // If coming from error, fetch original DB ID and cover data
        $db_data = $bookObj->fetchBook(bookID: $book_id);
        $modal_book['bookID'] = $book_id;
        // Preserve image data from DB if not in old array
        $modal_book['book_cover_name'] = $modal_book['existing_cover_name'] ?? $db_data['book_cover_name'];
        $modal_book['book_cover_dir'] = $modal_book['existing_cover_dir'] ?? $db_data['book_cover_dir'];

    } else { //view
        $modal_book = $bookObj->fetchBook(bookID: $book_id) ?: [];
    }
    if ($open_modal != 'viewDetailsModal') { //delete
        $modal_book['bookID'] = $book_id;
    }
} elseif ($open_modal == 'addBookModal') { //add
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
    <link rel="stylesheet" href="../../../public/assets/css/admin.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>
    <div class="flex flex-col w-10/12">
        <nav>
            <h1 class="text-xl font-semibold">Books</h1>
        </nav>
        <main>
            <div class="container">
                <div class="section h-full">
                    <div class="title flex w-full items-center justify-between">
                        <h1 class="text-red-800 font-bold text-4xl">MANAGE BOOKS</h1>
                        <a id="openAddBookModalBtn" class="addBtn" href="booksSection.php?modal=add" ?>+ Add Book</a>
                    </div>

                    <form method="GET" class="search">
                        <input type="text" name="search" placeholder="Search book title..." value="<?= $search ?>">
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
                                <th>Book Cover</th>
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
                                $book_cover_url = !empty($book["book_cover_dir"]) ? "../../../" . $book["book_cover_dir"] : null;
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="text-center"> <?php if ($book_cover_url) { ?>
                                            <img src="<?= $book_cover_url ?>" alt="Cover"
                                                class="w-16 h-16 object-cover rounded mx-auto border border-gray-300"
                                                title="<?= $book["book_cover_name"] ?? 'Book Cover' ?>">
                                        <?php } else { ?>
                                            <span class="text-gray-500 text-xs">N/A</span>
                                        <?php } ?>
                                    </td>
                                    <td><?= $book["book_title"] ?></td>
                                    <td><?= $book["author"] ?></td>
                                    <td><?= $book["category_name"] ?></td>
                                    <td><?= $book["book_copies"] ?></td>
                                    <td><?= $book["book_condition"] ?></td>
                                    <td><?= $book["status"] ?></td>
                                    <td class="action text-center">
                                        <a class="actionBtn bg-blue-500 hover:bg-blue-600"
                                            href="booksSection.php?modal=edit&id=<?= $book['bookID'] ?>">Edit</a>
                                        <a class="actionBtn bg-red-500 hover:bg-red-600"
                                            href="booksSection.php?modal=delete&id=<?= $book['bookID'] ?>">
                                            Delete
                                        </a>
                                        <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-white "
                                            href="booksSection.php?modal=view&id=<?= $book['bookID'] ?>">View</a>
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
            <span class="close close-times" data-modal="addBookModal">&times;</span>
            <h2>Add New Book</h2>
            <form action="../../../app/controllers/bookController.php?action=add" method="POST"
                enctype="multipart/form-data">

                <div class="input" id="book_cover">
                    <label for="book_cover">Upload Book Cover<span>*</span> : </label>
                    <input type="file" name="book_cover" id="book_cover" accept="image/*">
                    <p class="errors"><?= $errors["book_cover"] ?? "" ?></p>
                </div>
                <div class="input">
                    <label for="book_title">Book Title<span>*</span> : </label>
                    <input type="text" class="input-field" name="book_title" id="book_title"
                        value="<?= $modal_book["book_title"] ?? "" ?>">
                    <p class="errors"><?= $errors["book_title"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="author">Author<span>*</span> : </label>
                    <input type="text" class="input-field" name="author" id="author"
                        value="<?= $modal_book["author"] ?? "" ?>">
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

                <div class="input">
                    <label for="replacement_cost">Replacement Cost (₱)<span>*</span> : </label>
                    <input type="number" step="0.01" class="input-field" name="replacement_cost" id="replacement_cost"
                        value="<?= $modal_book["replacement_cost"] ?? "400.00" ?>">
                    <p class="errors"><?= $errors["replacement_cost"] ?? "" ?></p>
                </div>

                <div class="book_condition">
                    <label for="book_condition">Condition<span>*</span> : </label>
                    <select name="book_condition" id="add_book_condition">
                        <option value="">---Select Option---</option>
                        <?php
                        $condition_options = ["Good", "Fair", "Damaged", "Lost"];
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
            <span class="close close-times" data-modal="editBookModal">&times;</span>
            <h2>Edit Book</h2>
            <form id="editBookForm" action="../../../app/controllers/bookController.php?action=edit&id=<?= $book_id ?>"
                method="POST" enctype="multipart/form-data"> <input type="hidden" name="bookID" value="<?= $book_id ?>">
                <input type="hidden" name="existing_book_cover_name"
                    value="<?= $modal_book["book_cover_name"] ?? "" ?>">
                <input type="hidden" name="existing_book_cover_dir" value="<?= $modal_book["book_cover_dir"] ?? "" ?>">

                <div class="input col-span-2"> <label for="new_book_cover">Upload New Book Cover: </label>
                    <input type="file" class="input-field" name="new_book_cover" id="edit_new_book_cover"
                        accept="image/*">
                    <p class="text-xs text-gray-500 mt-1">Current File:
                        <?= $modal_book["book_cover_name"] ?? "None" ?>
                    </p>
                    <p class="errors text-red-500 text-sm"><?= $errors["new_book_cover"] ?? "" ?></p>
                </div>

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

                <div class="input">
                    <label for="replacement_cost">Replacement Cost (₱)<span>*</span> : </label>
                    <input type="number" step="0.01" class="input-field" name="replacement_cost"
                        id="edit_replacement_cost" value="<?= $modal_book["replacement_cost"] ?? "400.00" ?>">
                    <p class="errors">
                        <?= $errors["replacement_cost"] ?? "" ?>
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

                <div class="cancelConfirmBtns">
                    <button type="button" data-modal="editBookModal"
                        class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400 cursor-pointer">
                        Cancel
                    </button>

                    <input type="submit" value="Save Changes"
                        class="text-white px-4 py-2 rounded-lg font-semibold cursor-pointer">
                </div>
            </form>
        </div>
    </div>

    <div id="viewDetailsModal" class="modal <?= $open_modal == 'viewDetailsModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close close-times" data-modal="viewDetailsModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Full Book Details</h2>
            <div class="book-details grid grid-cols-2 gap-y-2 gap-x-4 text-sm">

                <div class="col-span-2 mb-4 relative justify-items-center">
                    <p class="font-semibold mb-2">Book Cover:</p>
                    <?php
                    $modal_book_cover_url = !empty($modal_book['book_cover_dir']) ? "../../../" . $modal_book['book_cover_dir'] : null;

                    if ($modal_book_cover_url) { ?>
                        <img src="<?= $modal_book_cover_url ?>" alt="Book Cover Image"
                            class="max-w-xs max-h-60 border rounded shadow-md object-cover">
                        <button type="button" id="openImage" class="enlarge text-red">⬄</button>
                    <?php } else { ?>
                        <p class="text-gray-500">No Book Cover Uploaded</p>
                    <?php } ?>
                </div>
                <p class="col-span-2"><strong>Title:</strong> <?= $modal_book['book_title'] ?? 'N/A' ?></p>

                <p><strong>Author:</strong> <?= $modal_book['author'] ?? 'N/A' ?></p>
                <p><strong>Category:</strong> <?= $modal_book['category_name'] ?? 'N/A' ?></p>

                <p><strong>Publication:</strong> <?= $modal_book['publication_name'] ?? 'N/A' ?></p>
                <p><strong>Pub. Year:</strong> <?= $modal_book['publication_year'] ?? 'N/A' ?></p>

                <p><strong>ISBN:</strong> <?= $modal_book['ISBN'] ?? 'N/A' ?></p>
                <p><strong>Copies:</strong> <?= $modal_book['book_copies'] ?? 'N/A' ?></p>

                <p><strong>Condition:</strong> <?= $modal_book['book_condition'] ?? 'N/A' ?></p>
                <p><strong>Date Added:</strong> <?= $modal_book['date_added'] ?? 'N/A' ?></p>
                <p><strong>Replacement Cost:</strong> <span
                        class="font-semibold">₱<?= number_format($modal_book['replacement_cost'] ?? 400.00, 2) ?></span>
                </p>
                <p><strong>Status:</strong> <span class="font-semibold"><?= $modal_book['status'] ?? 'N/A' ?></span></p>


            </div>
            <div class="mt-6 text-right">
                <button type="button"
                    class="close viewBtn bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    data-modal="viewDetailsModal">Close</button>
            </div>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal delete-modal <?= $open_modal == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to delete the book:
                <span class="font-semibold italic"><?= $modal_book['book_title'] ?? 'this book' ?></span>?
                This action cannot be undone.
            </p>
            <div class="cancelConfirmBtns">
                <button type="button" data-modal="deleteConfirmModal"
                    class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">
                    Cancel
                </button>
                <a href="../../../app/controllers/bookController.php?action=delete&id=<?= $modal_book['bookID'] ?>"
                    class="text-white px-4 py-2 rounded-lg font-semibold cursor-pointer">
                    Confirm Delete
                </a>
            </div>
        </div>
    </div>

    <div id="success-modal" class="modal <?= $success_modal ? 'open' : '' ?>">
        <div class="modal-content max-w-sm text-center">
            <span class="close close-times" data-modal="success-modal">&times;</span>
            <h3 class="text-xl font-bold text-red-800 mb-2">Success!</h3>
            <p class="text-gray-700">
                <?= $success_message ?> Book Success!
            </p>
            <button type="button" data-modal="success-modal"
                class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">
                Close
            </button>
        </div>
    </div>

    <div id="imageEnlargeModal" class="modal hidden">
        <div class="modal-content !max-w-4xl text-center">
            <span class="close-times" id="closeImage">&times;</span>
            <?php
            $modal_book_cover_url = !empty($modal_book['book_cover_dir']) ? "../../../" . $modal_book['book_cover_dir'] : null;

            if ($modal_book_cover_url) { ?>
                <img src="<?= $modal_book_cover_url ?>" alt="Book Cover Image"
                    class="w-full h-auto max-h-[80vh] object-contain mx-auto">
            <?php } else { ?>
                <p class="text-gray-500">No Book Cover Uploaded</p>
            <?php } ?>
            <!-- </div> -->
        </div>
    </div>


</body>
<script src="../../../public/assets/js/modal.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const closeImage = document.getElementById("closeImage");
        const imageEnlargeModal = document.getElementById("imageEnlargeModal");
        const openImage = document.getElementById("openImage");


        openImage.addEventListener("click", () => {
            imageEnlargeModal.style.display = 'flex';
        })

        closeImage.addEventListener("click", () => {
            imageEnlargeModal.style.display = 'none';
        })

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    })
</script>

</html>