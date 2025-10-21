<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
$detailObj = new BorrowDetails();

$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
$open_modal = $_SESSION['open_modal'] ?? ''; // // Stores the modal to open on load
$edit_borrow_id = $_SESSION['edit_borrow_id'] ?? null; // Stores the ID of borrow detail being edited
$delete_borrow_id = $_SESSION['delete_borrow_id'] ?? null; // Stores the ID of borrow detail being deleted 

$initial_edit_id = isset($_GET['id']) && $_GET['action'] == 'edit-initial' ? (int) $_GET['id'] : null;
$initial_delete_id = isset($_GET['id']) && $_GET['action'] == 'delete-initial' ? (int) $_GET['id'] : null;

if ($initial_edit_id) {
    $_SESSION['open_modal'] = 'editBorrowDetailModal';
    $_SESSION['edit_borrow_id'] = $initial_edit_id;
    header("Location: borrowDetailsSection.php");
    exit;
}
if ($initial_delete_id) {
    $_SESSION['open_modal'] = 'deleteConfirmModal';
    $_SESSION['delete_borrow_id'] = $initial_delete_id;
    header("Location: borrowDetailsSection.php");
    exit;
}

// Load Borrow Detail Data for Modals
$current_detail_data = [];
$load_id = $edit_borrow_id ?? $delete_borrow_id;

if ($open_modal == 'editBorrowDetailModal' && $edit_borrow_id) {
    if (empty($old)) {
        // Initial load for edit
        $current_detail_data = $detailObj->fetchBorrowDetail($edit_borrow_id);
    } else {
        // When submission fails, use old data
        $current_detail_data = $old;
        $current_detail_data['borrowID'] = $edit_borrow_id;
    }
} elseif ($open_modal == 'deleteConfirmModal' && $delete_borrow_id) {
    // Load for delete confirmation
    $current_detail_data = $detailObj->fetchBorrowDetail($delete_borrow_id);
    if (!$current_detail_data) {
        $current_detail_data = ['borrowID' => 'Error', 'book_title' => 'Detail not found.'];
    }
    $current_detail_data['borrowID'] = $delete_borrow_id;
} elseif ($open_modal == 'addBorrowDetailModal' && !empty($old)) {
    // Failed submission for add, use old data
    $current_detail_data = $old;
}


// Clear the session variables after getting them for the current request
unset($_SESSION["old"], $_SESSION["errors"], $_SESSION['open_modal'], $_SESSION['edit_borrow_id'], $_SESSION['delete_borrow_id']);

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$details = $detailObj->viewBorrowDetails($search);

$request_statuses = ['Pending', 'Approved', 'Rejected', 'Returned'];
$book_statuses = ['Borrowed', 'Available', 'Lost', 'Damaged'];
$condition_options = ['Good', 'Fair', 'Damaged', 'Lost'];

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Borrow Details</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/admin.css" />
    </style>
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="section h-full">
                <div class="title flex w-full items-center justify-between">
                    <h1 class="text-red-800 font-bold text-4xl">MANAGE BORROW DETAILS</h1>
                    <a id="openAddBorrowDetailModalBtn" class="addBtn" href="#">+ Add Borrow Detail</a>
                </div>

                <form method="GET" class="search mb-4">
                    <input type="text" name="search" placeholder="Search by Borrow ID, Name, or Title"
                        class="border border-red-800 rounded-lg p-2 w-1/3">
                    <button type="submit" class="bg-red-800 text-white rounded-lg px-4 py-2">Search</button>
                </form>

                <?php if (!empty($errors['general'])): ?>
                    <p class="text-red-600 font-semibold mb-4 bg-red-100 p-2 rounded-lg"><?= $errors['general'] ?></p>
                <?php endif; ?>

                <div class="view">
                    <table class="text-xs">
                        <tr>
                            <th>ID</th>
                            <th>User Name</th>
                            <th>Book Title</th>
                            <th>Borrow Date</th>
                            <th>Pickup Date</th>
                            <th>Return Date</th>
                            <th>Req. Status</th>
                            <th>Penalty Type</th>
                            <th>Penalty Status</th>
                            <th>Actions</th>
                        </tr>

                        <?php
                        foreach ($details as $detail) {
                            $fullName = $detail["fName"] ?? 'N/A' . ' ' . $detail["lName"] ?? '';
                            $bookTitle = $detail["book_title"] ?? 'N/A';
                            $reqStatusClass = match ($detail['borrow_request_status']) {
                                'Approved' => 'text-green-600',
                                'Pending' => 'text-yellow-600',
                                'Rejected' => 'text-red-600',
                                default => 'text-gray-600',
                            };
                            $penaltyStatusClass = match ($detail['penalty_status']) {
                                'Resolved' => 'text-green-500',
                                'Unresolved' => 'text-red-500',
                                default => 'text-gray-500',
                            };
                            ?>
                            <tr>
                                <td><?= $detail["borrowID"] ?></td>
                                <td><?= $fullName ?></td>
                                <td><?= $bookTitle ?></td>
                                <td><?= $detail["borrow_date"] ?></td>
                                <td><?= $detail["pickup_date"] ?? 'N/A' ?></td>
                                <td><?= $detail["return_date"] ?? 'N/A' ?></td>
                                <td class="<?= $reqStatusClass ?> font-semibold"><?= $detail["borrow_request_status"] ?>
                                </td>
                                <td class="text-center"><?= $detail["penalty_type"] ?? 'None' ?></td>
                                <td class="<?= $penaltyStatusClass ?> font-semibold text-center">
                                    <?= $detail["penalty_status"] ?? 'N/A' ?>
                                </td>

                                <td class="action text-center space-x-1 flex justify-center items-center">
                                    <a class="editBtn px-2 py-1 rounded text-white bg-blue-600 hover:bg-blue-700"
                                        href="borrowDetailsSection.php?action=edit-initial&id=<?= $detail['borrowID'] ?>">Edit</a>
                                    <a class="deleteBtn px-2 py-1 rounded text-white bg-red-600 hover:bg-red-700"
                                        href="borrowDetailsSection.php?action=delete-initial&id=<?= $detail['borrowID'] ?>">
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

    <!-- ADD BORROW DETAIL MODAL -->
    <div id="addBorrowDetailModal" class="modal <?= $open_modal == 'addBorrowDetailModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="addBorrowDetailModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Add New Borrow Detail</h2>
            <form action="../../../app/controllers/borrowDetailsController.php?action=add" method="POST">

                <div class="grid grid-cols-2 gap-4">
                    <div class="input">
                        <label for="userID">User ID<span>*</span> : </label>
                        <input type="number" class="input-field" name="userID" id="add_userID"
                            value="<?= $current_detail_data["userID"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["userID"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="bookID">Book ID<span>*</span> : </label>
                        <input type="number" class="input-field" name="bookID" id="add_bookID"
                            value="<?= $current_detail_data["bookID"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["bookID"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="borrow_date">Borrow Date<span>*</span> : </label>
                        <input type="date" class="input-field" name="borrow_date" id="add_borrow_date"
                            value="<?= $current_detail_data["borrow_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["borrow_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="pickup_date">Pickup Date : </label>
                        <input type="date" class="input-field" name="pickup_date" id="add_pickup_date"
                            value="<?= $current_detail_data["pickup_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["pickup_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="return_date">Return Date : </label>
                        <input type="date" class="input-field" name="return_date" id="add_return_date"
                            value="<?= $current_detail_data["return_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["return_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="returned_condition">Returned Condition : </label>
                        <select name="returned_condition" id="add_returned_condition" class="input-field">
                            <option value="">---Select Condition---</option>
                            <?php foreach ($condition_options as $option) {
                                $selected = (($current_detail_data['returned_condition'] ?? '') == $option) ? 'selected' : '';
                                echo "<option value='{$option}' {$selected}>{$option}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["returned_condition"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="borrow_request_status">Request Status<span>*</span> : </label>
                        <select name="borrow_request_status" id="add_borrow_request_status" class="input-field">
                            <option value="">---Select Status---</option>
                            <?php foreach ($request_statuses as $status) {
                                $selected = (($current_detail_data['borrow_request_status'] ?? '') == $status) ? 'selected' : '';
                                echo "<option value='{$status}' {$selected}>{$status}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["borrow_request_status"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="book_status">Book Status<span>*</span> : </label>
                        <select name="book_status" id="add_book_status" class="input-field">
                            <option value="">---Select Status---</option>
                            <?php foreach ($book_statuses as $status) {
                                $selected = (($current_detail_data['book_status'] ?? '') == $status) ? 'selected' : '';
                                echo "<option value='{$status}' {$selected}>{$status}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["book_status"] ?? "" ?></p>
                    </div>

                    <div class="input col-span-2">
                        <label for="penaltyID">Penalty ID (Optional) : </label>
                        <input type="number" class="input-field" name="penaltyID" id="add_penaltyID"
                            value="<?= $current_detail_data["penaltyID"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["penaltyID"] ?? "" ?></p>
                    </div>
                </div>

                <br>
                <input type="submit" value="Add Detail"
                    class="font-bold cursor-pointer mt-4 border-none rounded-lg bg-red-800 text-white p-2 w-full hover:bg-red-700">
                <p class="errors text-red-500 text-sm mt-2"><?= $errors["general"] ?? "" ?></p>
            </form>
        </div>
    </div>


    <!-- EDIT BORROW DETAIL MODAL -->
    <div id="editBorrowDetailModal" class="modal <?= $open_modal == 'editBorrowDetailModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="editBorrowDetailModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Edit Borrow Detail (ID: <?= $edit_borrow_id ?>)</h2>
            <form id="editBorrowDetailForm"
                action="../../../app/controllers/borrowDetailsController.php?action=edit&id=<?= $edit_borrow_id ?>"
                method="POST">
                <input type="hidden" name="borrowID" value="<?= $edit_borrow_id ?>">

                <div class="grid grid-cols-2 gap-4">
                    <div class="input">
                        <label for="userID">User ID<span>*</span> : </label>
                        <input type="number" class="input-field" name="userID" id="edit_userID"
                            value="<?= $current_detail_data["userID"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["userID"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="bookID">Book ID<span>*</span> : </label>
                        <input type="number" class="input-field" name="bookID" id="edit_bookID"
                            value="<?= $current_detail_data["bookID"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["bookID"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="borrow_date">Borrow Date<span>*</span> : </label>
                        <input type="date" class="input-field" name="borrow_date" id="edit_borrow_date"
                            value="<?= $current_detail_data["borrow_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["borrow_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="pickup_date">Pickup Date : </label>
                        <input type="date" class="input-field" name="pickup_date" id="edit_pickup_date"
                            value="<?= $current_detail_data["pickup_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["pickup_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="return_date">Return Date : </label>
                        <input type="date" class="input-field" name="return_date" id="edit_return_date"
                            value="<?= $current_detail_data["return_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["return_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="returned_condition">Returned Condition : </label>
                        <select name="returned_condition" id="edit_returned_condition" class="input-field">
                            <option value="">---Select Condition---</option>
                            <?php foreach ($condition_options as $option) {
                                $selected = (($current_detail_data['returned_condition'] ?? '') == $option) ? 'selected' : '';
                                echo "<option value='{$option}' {$selected}>{$option}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["returned_condition"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="borrow_request_status">Request Status<span>*</span> : </label>
                        <select name="borrow_request_status" id="edit_borrow_request_status" class="input-field">
                            <option value="">---Select Status---</option>
                            <?php foreach ($request_statuses as $status) {
                                $selected = (($current_detail_data['borrow_request_status'] ?? '') == $status) ? 'selected' : '';
                                echo "<option value='{$status}' {$selected}>{$status}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["borrow_request_status"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="book_status">Book Status<span>*</span> : </label>
                        <select name="book_status" id="edit_book_status" class="input-field">
                            <option value="">---Select Status---</option>
                            <?php foreach ($book_statuses as $status) {
                                $selected = (($current_detail_data['book_status'] ?? '') == $status) ? 'selected' : '';
                                echo "<option value='{$status}' {$selected}>{$status}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["book_status"] ?? "" ?></p>
                    </div>

                    <div class="input col-span-2">
                        <label for="penaltyID">Penalty ID (Optional) : </label>
                        <input type="number" class="input-field" name="penaltyID" id="edit_penaltyID"
                            value="<?= $current_detail_data["penaltyID"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["penaltyID"] ?? "" ?></p>
                    </div>
                </div>

                <br>
                <input type="submit" value="Save Changes"
                    class="font-bold cursor-pointer mt-4 border-none rounded-lg bg-red-800 text-white p-2 w-full hover:bg-red-700">
                <p class="errors text-red-500 text-sm mt-2"><?= $errors["general"] ?? "" ?></p>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="deleteConfirmModal" class="modal delete-modal <?= $open_modal == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close" data-modal="deleteConfirmModal">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to delete the borrow detail for:
                <span
                    class="font-semibold italic"><?= $current_detail_data['fName'] ?? 'N/A' . ' ' . $current_detail_data['lName'] ?? '' ?></span>
                (Borrow ID: <span class="font-semibold"><?= $current_detail_data['borrowID'] ?? 'N/A' ?></span>)?
                This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    onclick="document.getElementById('deleteConfirmModal').style.display='none';">
                    Cancel
                </button>
                <a href="../../../app/controllers/borrowDetailsController.php?action=delete&id=<?= $current_detail_data['borrowID'] ?? $delete_borrow_id ?>"
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
        const addBorrowDetailModal = document.getElementById('addBorrowDetailModal');
        const openAddBorrowDetailBtn = document.getElementById('openAddBorrowDetailModalBtn');
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
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
        };

        openAddBorrowDetailBtn.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelector('#addBorrowDetailModal form').reset();
            document.querySelectorAll('#addBorrowDetailModal .errors').forEach(p => p.textContent = '');

            openModal(addBorrowDetailModal);
        });

        // Close Modal when clicking closeButtons
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
    });
</script>

</html>