<?php
session_start();
// Removed mandatory login check to allow guest viewing

require_once(__DIR__ . "/../../models/manageBook.php");
require_once(__DIR__ . "/../../models/manageUsers.php");

$bookObj = new Book();
$userObj = new User();

// Fetch user information if logged in
$userID = $_SESSION["user_id"] ?? null;
$user = null;
$userTypeID = 0; // Default to 0 (Guest)

if ($userID) {
    $user = $userObj->fetchUser($userID);
    if ($user) {
        $userTypeID = $user["userTypeID"];
    }
}

$bookID = $_GET['bookID'] ?? null;
$book = null;

// --- MODAL/STATUS LOGIC ---
$list_status = $_GET['status'] ?? null;
$copies_added = (int) ($_GET['copies'] ?? 0);

// Initialize error variables to avoid undefined warnings if used
$error_message_code = '';
$current_borrowed_count = 0;
$borrow_limit = 0;

// Check for modal open requests (only staff uses these for multi-copy borrowing/listing)
$current_modal = $_GET['modal'] ?? '';
$modal_book_id = $bookID;
$open_modal = '';

$modal_book_title = '';
$modal_available_copies = 0;

if ($current_modal === 'borrow') {
    $open_modal = 'borrow-modal';
} elseif ($current_modal === 'list') {
    $open_modal = 'list-modal';
}
// -------------------------

if ($bookID) {
    // fetchBook includes category name
    $book = $bookObj->fetchBook($bookID);
}

if (!$book) {
    // Redirect or show an error if the book is not found
    header("Location: catalogue.php");
    exit;
}

// Extract essential book details (used for display and passing to JS)
$book_title = htmlspecialchars($book['book_title']);
$author = htmlspecialchars($book['author_names'] ?? 'N/A');
$category_name = htmlspecialchars($book['category_name']);
$publication_name = htmlspecialchars($book['publication_name']);
$publication_year = htmlspecialchars($book['publication_year']);
$ISBN = htmlspecialchars($book['ISBN']);
$book_copies = htmlspecialchars($book['book_copies']);
$book_condition = htmlspecialchars($book['book_condition']);
$book_cover_dir = $book['book_cover_dir'] ?? null;

$copies = $book['book_copies'] ?? 0;
$status = $copies <= 0 ? "Borrowed" : "Available";
$status_class = $copies <= 0 ? 'text-blue-600 bg-blue-100' : 'text-green-600 bg-green-100';
$button_disabled = $copies <= 0;
$js_book_title = addslashes($book_title);

// Populate modal data if book is available
$modal_book_title = $js_book_title;
$modal_available_copies = $copies;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Book: <?= $book_title ?></title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/borrower.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
    <style>
        /* Admin modal style fix for borrower page */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal.open {
            display: flex;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close-times {
            position: absolute;
            top: 10px;
            right: 20px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-times:hover,
        .close-times:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="color-layer"></div>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php if (isset($_SESSION["user_id"])) {
            require_once(__DIR__ . '/../shared/headerBorrower.php');
        } else {
            require_once(__DIR__ . '/../shared/header.php');
        }
        ?>

        <header class="text-center text-white my-10">
            <h1 class="title text-4xl sm:text-5xl font-extrabold text-red-800 tracking-tight">Detail View</h1>
            <p class="text-xl mt-2">Complete information about the book.</p>
        </header>

        <div class="mb-12 bg-white p-8 rounded-xl shadow-lg border-t-4 border-red-700">
            <div class="flex justify-start items-center border-b-2 border-gray-200 pb-3 mb-6">
                <a href="catalogue.php"
                    class="text-red-700 hover:text-red-900 font-semibold transition duration-150 flex items-center">
                    &larr; Back to Catalogue
                </a>
            </div>

            <div class="flex flex-col md:flex-row gap-8">
                <div class="flex-shrink-0 w-full md:w-1/3 max-w-sm mx-auto md:mx-0">
                    <div class="w-full h-96 shadow-2xl rounded-lg overflow-hidden bg-gray-200 border-4 border-gray-100">
                        <?php
                        if ($book_cover_dir) { ?>
                                <img src="<?= "../../../" . $book_cover_dir ?>" alt="<?= $book_title ?> Cover"
                                    class="w-full h-full object-cover">
                        <?php } else { ?>
                                <div
                                    class="flex items-center justify-center w-full h-full text-lg text-gray-500 text-center p-4">
                                    No Cover Available
                                </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="flex-grow md:w-2/3">
                    <h2 class="text-3xl font-bold text-red-900 mb-4"><?= $book_title ?></h2>

                    <div class="space-y-4 text-base">
                        <p class="text-gray-700"><strong>Author:</strong> <?= $author ?></p>
                        <p class="text-gray-700"><strong>Category:</strong> <?= $category_name ?></p>
                        <p class="text-gray-700"><strong>Publisher:</strong> <?= $publication_name ?></p>
                        <p class="text-gray-700"><strong>Publication Year:</strong> <?= $publication_year ?></p>
                        <p class="text-gray-700"><strong>ISBN:</strong> <?= $ISBN ?></p>
                        <p class="text-gray-700"><strong>Book Condition:</strong> <?= $book_condition ?></p>
                        <p class="text-gray-700"><strong>Replacement Cost:</strong>
                            <?= htmlspecialchars($book['replacement_cost']) ?></p>
                        </p>
                        <p class="text-gray-700">
                            <strong>Copies Available:</strong>
                            <span
                                class="font-bold <?= $copies > 0 ? 'text-green-600' : 'text-red-600' ?>"><?= $book_copies ?></span>
                        </p>


                        <div class="mt-auto p-4 border-t border-gray-100 flex gap-8 items-center">
                            <?php
                            $copies = $book['book_copies'] ?? 0;
                            $status = "Available";
                            $status_color = 'text-green-600 bg-green-100';
                            $button_disabled = false;
                            $borrow_denied = false;

                            if ($copies <= 0) {
                                $status = "Borrowed";
                                $status_color = 'text-blue-600 bg-blue-100';
                                $button_disabled = true;
                            }

                            // --- Borrow Button Logic ---
                            $borrow_action = '';
                            if ($userID) {
                                // Logged In Logic
                                if ($userTypeID == 2) {
                                    // Staff: use URL parameter to open modal
                                    $borrow_action = "href='catalogue.php?modal=borrow&bookID={$bookID}'";
                                } elseif (!$borrow_denied) {
                                    // Non-Staff (allowed): direct link to confirmation.php
                                    $borrow_action = "href='confirmation.php?bookID={$book['bookID']}&copies=1'";
                                } else {
                                    // Non-Staff (denied): link to error status page
                                    $borrow_action = "href='catalogue.php?status=borrow_denied&bookID={$bookID}&error_code={$error_message_code}&count={$current_borrowed_count}&limit={$borrow_limit}'";
                                }
                            } else {
                                // Guest Logic: Redirect to Login
                                $borrow_action = "href='login.php'";
                            }


                            // --- Add to List Button Logic ---
                            $add_to_list_action = '';
                            if (!$button_disabled && $userID) {
                                if ($userTypeID == 2) {
                                    $add_to_list_action = "href='catalogue.php?modal=list&bookID={$bookID}'";
                                } else {
                                    // Non-staff still uses JS function for direct add-to-list action (no modal)
                                    $add_to_list_action = "onclick=\"event.preventDefault(); addToList({$book['bookID']})\"";
                                }
                            }
                            ?>


                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $status_color ?>">
                                <?= $status ?>
                            </span>

                            <a <?= $borrow_action ?>
                                class="text-sm font-medium cursor-pointer transition duration-300 px-4 py-2 rounded-full <?= $button_disabled ? 'bg-gray-300 text-gray-600 cursor-not-allowed pointer-events-none' : 'bg-red-800 text-white shadow-md hover:bg-red-900' ?>">
                                <?= $button_disabled ? 'Unavailable' : ($userID ? '+ Borrow Now' : 'Login to Borrow') ?>
                            </a>

                            <?php if ($userID && !$button_disabled): ?>
                                    <a <?= $add_to_list_action ?>
                                        class="text-sm font-medium transition duration-300 px-4 py-2 rounded-full bg-red-800 text-white shadow-md hover:bg-red-900">
                                        + Add To List
                                    </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="borrow-modal" class="modal <?= $open_modal == 'borrow-modal' ? 'open' : '' ?>">
            <div class="modal-content">
                <span class="close-times" onclick="closeModalAndRedirect()">&times;</span>
                <h3 class="text-xl font-bold text-red-800 border-b pb-3 mb-4">Borrow Copies (Staff/Admin)</h3>

                <p class="text-gray-700 mb-3">Book: <strong id="modal-borrow-book-title">
                        <?= $modal_book_title ?>
                    </strong></p>
                <form id="borrow-form" method="GET" action="../../../app/controllers/borrowBookController.php">
                    <input type="hidden" name="action" value="borrow">
                    <input type="hidden" name="source" value="viewBook.php">
                    <input type="hidden" name="bookID" id="modal-borrow-book-id" value="<?= $modal_book_id ?>">

                    <div class="mb-4">
                        <label for="copies" class="block text-sm font-medium text-gray-700 mb-1">Number of Copies to
                            Borrow:</label>
                        <input type="number" name="copies" id="modal-borrow-copies-input" min="1"
                            value="<?= $modal_available_copies > 0 ? 1 : 0 ?>" required
                            max="<?= $modal_available_copies ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
                            oninput="checkCopiesLimit('modal-borrow-copies-input', 'modal-borrow-submit-btn', 'borrow-copies-error', '<?= $modal_available_copies ?>')">
                        <p id="borrow-copies-error" class="text-sm text-red-600 mt-1 hidden">Cannot borrow more than
                            <span id="max-borrow-copies"><?= $modal_available_copies ?></span> copies.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModalAndRedirect()"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">Cancel</button>
                        <button type="submit" id="modal-borrow-submit-btn"
                            class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition"
                            <?= $modal_available_copies > 0 ? '' : 'disabled' ?>>Confirm Borrow</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="list-modal" class="modal <?= $open_modal == 'list-modal' ? 'open' : '' ?>">
            <div class="modal-content">
                <span class="close-times" onclick="closeModalAndRedirect()">&times;</span>
                <h3 class="text-xl font-bold text-red-800 border-b pb-3 mb-4">Add to List Copies (Staff/Admin)</h3>

                <p class="text-gray-700 mb-3">Book: <strong id="modal-list-book-title">
                        <?= $modal_book_title ?>
                    </strong></p>
                <form id="list-form" onsubmit="event.preventDefault(); confirmAddToList()">
                    <input type="hidden" id="modal-list-book-id" value="<?= $modal_book_id ?>">

                    <div class="mb-4">
                        <label for="list-copies" class="block text-sm font-medium text-gray-700 mb-1">Number of Copies
                            to Add to
                            List:</label>
                        <input type="number" name="list-copies" id="modal-list-copies-input" min="1"
                            value="<?= $modal_available_copies > 0 ? 1 : 0 ?>" required
                            max="<?= $modal_available_copies ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
                            oninput="checkCopiesLimit('modal-list-copies-input', 'modal-list-submit-btn', 'list-copies-error', '<?= $modal_available_copies ?>')">
                        <p id="list-copies-error" class="text-sm text-red-600 mt-1 hidden">Cannot add more than <span
                                id="max-list-copies"><?= $modal_available_copies ?></span> copies.</p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModalAndRedirect()"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">Cancel</button>
                        <button type="submit" id="modal-list-submit-btn"
                            class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition"
                            <?= $modal_available_copies > 0 ? '' : 'disabled' ?>>Confirm Add</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="success-modal" class="modal <?= $list_status == 'added' ? 'open' : '' ?>">
            <div class="modal-content max-w-sm text-center">
                <span class="close-times" onclick="closeStatusModal()">&times;</span>
                <h3 class="text-xl font-bold text-red-800 mb-2">Success!</h3>
                <p class="text-gray-700"><span id="success-book-title" class="font-semibold"></span> (<span
                        id="success-no-copies"></span> copies) has been successfully added to your list!</p>
                <div class="mt-6 flex justify-center">
                    <button type="button" onclick="closeStatusModal()"
                        class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition">Close</button>
                </div>
            </div>
        </div>

        <div id="message-modal" class="modal <?= ($list_status && $list_status != 'added') ? 'open' : '' ?>">
            <div class="modal-content max-w-xs mx-4">
                <span class="close-times" onclick="closeStatusModal()">&times;</span>
                <div class="flex justify-center flex-col pt-4">
                    <span id="book-title" class="font-bold text-center"></span>
                    <p id="message-modal-body" class="mb-4 font-medium text-center text-gray-700"> </p>
                </div>
                <div class="flex justify-center">
                    <button onclick="closeStatusModal()"
                        class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition text-sm">Dismiss</button>
                </div>
            </div>
        </div>
    </div>
</body>
<?php require_once(__DIR__ . '/../shared/footer.php'); ?>
<script src="../../../public/assets/js/borrower.js"></script>

<script>
    // PHP variables dumped for JS use
    const LIST_STATUS = "<?= $list_status ?>";
    const COPIES_ADDED = <?= $copies_added ?>;
    const CURRENT_BOOK_ID = "<?= $bookID ?>";
    const CURRENT_BOOK_TITLE = "<?= $js_book_title ?>";
    const MODAL_AVAILABLE_COPIES = <?= $modal_available_copies ?>;

    function closeModalAndRedirect() {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete("modal");
        window.location.href = currentUrl.toString();
    }

    function closeStatusModal() {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete("status");
        currentUrl.searchParams.delete("copies");
        window.location.href = currentUrl.toString();
    }

    // Close Modal when clicking outside the content area
    document.addEventListener('DOMContentLoaded', () => {
        window.addEventListener("click", (e) => {
            if (e.target.classList.contains("modal")) {
                // Check if it's an operation modal (borrow/list)
                if (e.target.id === 'borrow-modal' || e.target.id === 'list-modal') {
                    closeModalAndRedirect();
                }
                // Check if it's a status modal (success/message)
                if (e.target.id === 'success-modal' || e.target.id === 'message-modal') {
                    closeStatusModal();
                }
            }
        });
    });

    // Handle confirmation for Add To List for staff (from modal)
    function confirmAddToList() {
        const bookID = document.getElementById('modal-list-book-id').value;
        const copies = parseInt(document.getElementById('modal-list-copies-input').value);

        // This redirects and triggers a status modal on success/failure
        window.location.href = `../../../app/controllers/borrowListController.php?action=add&bookID=${bookID}&copies=${copies}&source=viewBook.php`;
    }


    // Function to check copies limit for modals (unchanged functionality)
    function checkCopiesLimit(inputID, submitBtnID, errorMsgID, maxCopiesValue) {
        const input = document.getElementById(inputID);
        const submitBtn = document.getElementById(submitBtnID);
        const errorMsg = document.getElementById(errorMsgID);
        const requestedCopies = parseInt(input.value);
        const maxCopies = parseInt(maxCopiesValue);

        if (requestedCopies > maxCopies || requestedCopies < 1 || isNaN(requestedCopies)) {
            errorMsg.classList.remove('hidden');
            submitBtn.disabled = true;
        } else {
            errorMsg.classList.add('hidden');
            submitBtn.disabled = false;
        }
    }


    // Initial check and population for status messages on load
    document.addEventListener('DOMContentLoaded', function () {
        if (LIST_STATUS) {
            const title = CURRENT_BOOK_TITLE;
            const messageModalBody = document.getElementById('message-modal-body');

            // Populate success modal elements if status is 'added'
            if (LIST_STATUS === 'added') {
                document.getElementById('success-book-title').textContent = title;
                document.getElementById('success-no-copies').textContent = COPIES_ADDED;
                return; // Success modal is now open via PHP/CSS
            }

            // Populate message modal elements for other statuses (error/existing)
            document.getElementById('book-title').textContent = title;
            let message;

            switch (LIST_STATUS) {
                case 'existing':
                    message = ` is already in your list. (Limit 1 Copy)`;
                    break;
                case 'borrowed':
                    message = ` is currently borrowed or requested. (Limit 1 Copy)`;
                    break;
                case 'error_unavailable':
                    message = 'ERROR: Book copies unavailable or action invalid.';
                    break;
                case 'error':
                    message = 'ERROR: Action failed. Please try again.';
                    break;
                default:
                    return;
            }
            messageModalBody.textContent = message;
            // Message modal is now open via PHP/CSS
        }
    });

</script>

</html>