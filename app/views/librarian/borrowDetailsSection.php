<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../../models/manageBook.php");

$detailObj = new BorrowDetails();
$bookObj = new Book();

// --- Session and URL Setup (unchanged) ---
$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
$open_modal = $_SESSION['open_modal'] ?? '';
$edit_borrow_id = $_SESSION['edit_borrow_id'] ?? null;
$delete_borrow_id = $_SESSION['delete_borrow_id'] ?? null;
$view_borrow_id = $_SESSION['view_borrow_id'] ?? null;

$current_tab = $_GET['tab'] ?? 'pending';

$status_map = [
    'pending' => 'Pending',
    'pickup' => 'Approved',
    'currently_borrowed' => 'Borrowed',
    'returned' => 'Returned',
    'finedUsers' => 'FinedUsers'
];
$db_status_filter = $status_map[$current_tab] ?? 'Pending';


$initial_edit_id = isset($_GET['id']) && $_GET['action'] == 'edit-initial' ? (int) $_GET['id'] : null;
$initial_delete_id = isset($_GET['id']) && $_GET['action'] == 'delete-initial' ? (int) $_GET['id'] : null;
$initial_block_id = isset($_GET['id']) && $_GET['action'] == 'block-initial' ? (int) $_GET['id'] : null;
$initial_view_id = isset($_GET['id']) && $_GET['action'] == 'view-initial' ? (int) $_GET['id'] : null;

if ($initial_edit_id) {
    $_SESSION['open_modal'] = 'editBorrowDetailModal';
    $_SESSION['edit_borrow_id'] = $initial_edit_id;
    header("Location: borrowDetailsSection.php?tab={$current_tab}");
    exit;
}
if ($initial_delete_id) {
    $_SESSION['open_modal'] = 'deleteConfirmModal';
    $_SESSION['delete_borrow_id'] = $initial_delete_id;
    header("Location: borrowDetailsSection.php?tab={$current_tab}");
    exit;
}
if ($initial_block_id) {
    $_SESSION['open_modal'] = 'blockUserModal';
    $_SESSION['block_user_id'] = $initial_block_id;
    header("Location: borrowDetailsSection.php?tab={$current_tab}");
    exit;
}
if ($initial_view_id) {
    $_SESSION['open_modal'] = 'viewFullDetailsModal';
    $_SESSION['view_borrow_id'] = $initial_view_id;
    header("Location: borrowDetailsSection.php?tab={$current_tab}");
    exit;
}

// Load Borrow Detail Data for Modals
$current_detail_data = [];
$load_id = $edit_borrow_id ?? $delete_borrow_id ?? $view_borrow_id;

if ($load_id) {
    if ($open_modal == 'editBorrowDetailModal' && !empty($old)) {
        $current_detail_data = $old;
        $current_detail_data['borrowID'] = $edit_borrow_id;
    } else {
        $current_detail_data = $detailObj->fetchBorrowDetail($load_id);
    }
    if (!$current_detail_data) {
        $current_detail_data = ['borrowID' => 'Error', 'book_title' => 'Detail not found.'];
    }
} elseif ($open_modal == 'addBorrowDetailModal' && !empty($old)) {
    $current_detail_data = $old;
}


// Clear the session variables after getting them for the current request
unset($_SESSION["old"], $_SESSION["errors"], $_SESSION['open_modal'], $_SESSION['edit_borrow_id'], $_SESSION['delete_borrow_id'], $_SESSION['view_borrow_id'], $_SESSION['block_user_id']);

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch details based on the tab
if ($current_tab === 'finedUsers') {
    $details = $detailObj->viewFinedBorrowDetails($search);
} else {
    $details = $detailObj->viewBorrowDetails($search, $db_status_filter);
}

// --- Late/Lost Fine Calculation Function (Must mirror controller logic) ---
function calculateDisplayFine($expected_return_date, $bookObj, $bookID)
{
    $expected = new DateTime($expected_return_date);
    $today = new DateTime(date("Y-m-d"));

    $results = [
        'fine_amount' => 0.00,
        'fine_reason' => null,
        'fine_status' => null,
    ];

    if ($today > $expected) {
        $interval = $expected->diff($today);
        $days_late = $interval->days;

        $weeks_late = ceil($days_late / 7);
        $late_fine_amount = $weeks_late * 20.00;

        // Check for Lost Status (12 weeks = 84 days)
        if ($days_late >= 84) {
            $replacement_cost = $bookObj->fetchBookReplacementCost($bookID);

            // Total Fine = Accumulated Late Fee + Replacement Cost (Base ₱400)
            $results['fine_amount'] = $late_fine_amount + $replacement_cost;
            $results['fine_reason'] = 'Lost (Overdue)';
            $results['fine_status'] = 'Unpaid';

        } else {
            // Standard Late Fine
            $results['fine_amount'] = $late_fine_amount;
            $results['fine_reason'] = 'Late';
            $results['fine_status'] = 'Unpaid';
        }
    }
    return $results;
}


// --- Apply Fine Logic for 'Borrowed' items for display/action redirection ---
foreach ($details as &$detail) {
    if ($detail['borrow_request_status'] === 'Borrowed' && $detail['return_date'] === null) {

        $fine_results = calculateDisplayFine($detail['expected_return_date'], $bookObj, $detail['bookID']);

        if ($fine_results['fine_amount'] > 0) {
            $detail['calculated_fine'] = $fine_results['fine_amount'];
            $detail['fine_reason'] = $fine_results['fine_reason'];
            $detail['fine_status'] = $fine_results['fine_status'];
        } else {
            $detail['calculated_fine'] = $detail['fine_amount'];
        }
    } else {
        // For 'Returned' or other statuses, use the amount stored in DB
        $detail['calculated_fine'] = $detail['fine_amount'];
    }
}
unset($detail);

// Options for Modals
$fine_reasons = ['Late', 'Damaged', 'Lost'];
$fine_statuses = ['Paid', 'Unpaid'];
$request_statuses = ['Pending', 'Approved', 'Borrowed', 'Returned', 'Rejected'];
$condition_options = ['Good', 'Fair', 'Damaged', 'Lost'];
$all_books = $bookObj->viewBook();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Borrow Details</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/adminFinal.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <div class="flex flex-col w-10/12">
        <nav>
            <h1 class="text-xl font-semibold">Borrow Details</h1>
        </nav>
        <main>
            <div class="container">
                <div class="section h-full">
                    <div class="title flex w-full items-center justify-between">
                        <h1 class="text-red-800 font-bold text-4xl">MANAGE BORROW DETAILS</h1>
                    </div>

                    <div class="tabs flex border-b border-gray-200 mb-6">
                        <a href="?tab=pending" class="tab-btn <?= $current_tab == 'pending' ? 'active' : '' ?>">Pending
                            Request</a>
                        <a href="?tab=pickup" class="tab-btn <?= $current_tab == 'pickup' ? 'active' : '' ?>">Pending
                            PickUp</a>
                        <a href="?tab=currently_borrowed"
                            class="tab-btn <?= $current_tab == 'currently_borrowed' ? 'active' : '' ?>">Currently
                            Borrowed</a>
                        <a href="?tab=returned"
                            class="tab-btn <?= $current_tab == 'returned' ? 'active' : '' ?>">Returned
                            Books</a>
                        <a href="?tab=finedUsers"
                            class="tab-btn <?= $current_tab == 'finedUsers' ? 'active' : '' ?>">Fined Users</a>
                    </div>

                    <form method="GET" class="search mb-4 flex gap-2 items-center">
                        <input type="hidden" name="tab" value="<?= $current_tab ?>">
                        <input type="text" name="search" placeholder="Search by Borrow ID, Name, or Title"
                            value="<?= $search ?>" class="border border-red-800 rounded-lg p-2 w-1/3">
                        <button type="submit"
                            class="bg-red-800 text-white rounded-lg px-4 py-2 hover:bg-red-700">Search</button>
                    </form>

                    <?php if (!empty($errors['general'])): ?>
                        <p class="text-red-600 font-semibold mb-4 bg-red-100 p-2 rounded-lg"><?= $errors['general'] ?></p>
                    <?php endif; ?>

                    <div class="view">
                        <table class="text-xs">
                            <?php if ($current_tab == 'pending'): ?>
                                <tr>
                                    <th>ID</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Copies</th>
                                    <th>Current Condition</th>
                                    <th>Request Date</th>
                                    <th>Pickup Date</th>
                                    <th>Exp. Return Date</th>
                                    <th>Actions</th>
                                </tr>
                            <?php elseif ($current_tab == 'pickup'): ?>
                                <tr>
                                    <th>ID</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Copies</th>
                                    <th>Current Condition</th>
                                    <th>Request Date</th>
                                    <th>Pickup Date</th>
                                    <th>Exp. Return Date</th>
                                    <th>Actions</th>
                                </tr>
                            <?php elseif ($current_tab == 'currently_borrowed'): ?>
                                <tr>
                                    <th>ID</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Copies</th>
                                    <th>Current Condition</th>
                                    <th>Pickup Date</th>
                                    <th>Exp. Return Date</th>
                                    <th>Fine (Late/Lost)</th>
                                    <th>Actions</th>
                                </tr>
                            <?php elseif ($current_tab == 'returned'): ?>
                                <tr>
                                    <th>ID</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Copies</th>
                                    <th>Return Date</th>
                                    <th>Returned Condition</th>
                                    <th>Fine Amount</th>
                                    <th>Fine Status</th>
                                    <th>Actions</th>
                                </tr>
                            <?php else: // finedUsers ?>
                                <tr>
                                    <th>ID</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Fine Amount</th>
                                    <th>Fine Reason</th>
                                    <th>Fine Status</th>
                                    <th>Exp. Return Date</th>
                                    <th>Actions</th>
                                </tr>
                            <?php endif; ?>

                            <?php
                            if (empty($details)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-gray-500">
                                        No <?= strtolower($db_status_filter) ?> borrow details found.
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($details as $detail) {
                                    $fullName = htmlspecialchars($detail["lName"] . " " . $detail["fName"]);
                                    $bookTitle = htmlspecialchars($detail["book_title"]);
                                    $borrowID = $detail["borrowID"];
                                    $userID = $detail['userID'];
                                    ?>
                                    <tr>
                                        <td><?= $borrowID ?></td>
                                        <td><?= $fullName ?></td>
                                        <td><?= $bookTitle ?></td>

                                        <?php if ($current_tab == 'pending'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["current_book_condition"] ?></td>
                                            <td><?= $detail["request_date"] ?></td>
                                            <td><?= $detail["pickup_date"] ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td class="action text-center space-x-1 flex justify-center items-center">
                                                <a class="px-2 py-1 rounded text-white bg-green-600 hover:bg-green-700"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=accept&id=<?= $borrowID ?>">Accept</a>
                                                <a class="px-2 py-1 rounded text-white bg-red-600 hover:bg-red-700"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=reject&id=<?= $borrowID ?>">Reject</a>
                                                <a class="px-2 py-1 rounded text-white bg-blue-600 hover:bg-blue-700"
                                                    href="borrowDetailsSection.php?action=edit-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="px-2 py-1 rounded text-white bg-gray-600 hover:bg-gray-700"
                                                    href="borrowDetailsSection.php?action=view-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php elseif ($current_tab == 'pickup'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["current_book_condition"] ?></td>
                                            <td><?= $detail["request_date"] ?></td>
                                            <td><?= $detail["pickup_date"] ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td class="action text-center space-x-1 flex justify-center items-center">
                                                <a class="px-2 py-1 rounded text-white bg-blue-600 hover:bg-blue-700"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=pickup&id=<?= $borrowID ?>">Claimed</a>
                                                <a class="px-2 py-1 rounded text-white bg-red-600 hover:bg-red-700"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=reject&id=<?= $borrowID ?>">Cancel</a>
                                                <a class="px-2 py-1 rounded text-white bg-blue-600 hover:bg-blue-700"
                                                    href="borrowDetailsSection.php?action=edit-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="px-2 py-1 rounded text-white bg-gray-600 hover:bg-gray-700"
                                                    href="borrowDetailsSection.php?action=view-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php elseif ($current_tab == 'currently_borrowed'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["current_book_condition"] ?></td>
                                            <td><?= $detail["pickup_date"] ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td
                                                class="font-semibold <?= ($detail["calculated_fine"] > 0) ? 'text-red-700' : 'text-gray-700' ?>">
                                                ₱<?= number_format($detail["calculated_fine"] ?? 0, 2) ?>
                                            </td>
                                            <td class="action text-center space-x-1 flex justify-center items-center">
                                                <a class="px-2 py-1 rounded text-white bg-green-600 hover:bg-green-700"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=return&id=<?= $borrowID ?>">Returned</a>
                                                <a class="px-2 py-1 rounded text-white bg-blue-600 hover:bg-blue-700"
                                                    href="borrowDetailsSection.php?action=edit-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="px-2 py-1 rounded text-white bg-gray-600 hover:bg-gray-700"
                                                    href="borrowDetailsSection.php?action=view-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php elseif ($current_tab == 'returned'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["return_date"] ?? 'N/A' ?></td>
                                            <td><?= $detail["returned_condition"] ?? 'N/A' ?></td>
                                            <td class="font-semibold text-red-700">
                                                ₱<?= number_format($detail["calculated_fine"] ?? 0, 2) ?></td>
                                            <td><?= $detail["fine_status"] ?? 'N/A' ?></td>
                                            <td class="action text-center space-x-1 flex justify-center items-center">
                                                <a class="px-2 py-1 rounded text-white bg-blue-600 hover:bg-blue-700"
                                                    href="borrowDetailsSection.php?action=edit-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="px-2 py-1 rounded text-white bg-red-600 hover:bg-red-700"
                                                    href="borrowDetailsSection.php?action=delete-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Delete</a>
                                                <a class="px-2 py-1 rounded text-white bg-gray-600 hover:bg-gray-700"
                                                    href="borrowDetailsSection.php?action=view-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php else: // finedUsers ?>
                                            <td><?= $detail["calculated_fine"] ?? 0 ?></td>
                                            <td><?= $detail["fine_reason"] ?? 'N/A' ?></td>
                                            <td class="font-semibold text-red-700"><?= $detail["fine_status"] ?? 'Unpaid' ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td class="action text-center space-x-1 flex justify-center items-center">
                                                <a class="px-2 py-1 rounded text-white bg-green-600 hover:bg-green-700"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=paid&id=<?= $borrowID ?>">Paid</a>
                                                <a class="px-2 py-1 rounded text-white bg-blue-600 hover:bg-blue-700"
                                                    href="borrowDetailsSection.php?action=edit-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="px-2 py-1 rounded text-white bg-orange-600 hover:bg-orange-700"
                                                    href="borrowDetailsSection.php?action=block-initial&id=<?= $userID ?>&tab=<?= $current_tab ?>">Block
                                                    User</a>
                                                <a class="px-2 py-1 rounded text-white bg-gray-600 hover:bg-gray-700"
                                                    href="borrowDetailsSection.php?action=view-initial&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php endif; ?>

                                    </tr>
                                    <?php
                                }
                            endif;
                            ?>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals (Edit, Delete, Block) -->

    <div id="editBorrowDetailModal" class="modal <?= $open_modal == 'editBorrowDetailModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="editBorrowDetailModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Edit Borrow Detail (ID: <?= $edit_borrow_id ?>)</h2>
            <form id="editBorrowDetailForm"
                action="../../../app/controllers/borrowDetailsController.php?action=edit&id=<?= $edit_borrow_id ?>"
                method="POST">
                <input type="hidden" name="borrowID" value="<?= $edit_borrow_id ?>">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>"> <!-- ADDED for redirection -->

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
                        <label for="no_of_copies">No. of Copies<span>*</span> : </label>
                        <input type="number" class="input-field" name="no_of_copies" id="edit_no_of_copies"
                            value="<?= $current_detail_data["no_of_copies"] ?? "1" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["no_of_copies"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="request_date">Borrow/Request Date<span>*</span> : </label>
                        <input type="date" class="input-field" name="request_date" id="edit_request_date"
                            value="<?= $current_detail_data["request_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["request_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="pickup_date">Pickup Date (Optional) : </label>
                        <input type="date" class="input-field" name="pickup_date" id="edit_pickup_date"
                            value="<?= $current_detail_data["pickup_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["pickup_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="expected_return_date">Exp. Return Date<span>*</span> : </label>
                        <input type="date" class="input-field" name="expected_return_date"
                            id="edit_expected_return_date"
                            value="<?= $current_detail_data["expected_return_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["expected_return_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="return_date">Actual Return Date (Optional) : </label>
                        <input type="date" class="input-field" name="return_date" id="edit_return_date"
                            value="<?= $current_detail_data["return_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["return_date"] ?? "" ?></p>
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
                        <label for="returned_condition">Returned Condition (Optional) : </label>
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
                        <label for="fine_amount">Fine Amount : </label>
                        <input type="number" step="0.01" class="input-field" name="fine_amount" id="edit_fine_amount"
                            value="<?= $current_detail_data["fine_amount"] ?? "0.00" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["fine_amount"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="fine_reason">Fine Reason : </label>
                        <select name="fine_reason" id="edit_fine_reason" class="input-field">
                            <option value="">---Select Reason---</option>
                            <?php foreach ($fine_reasons as $reason) {
                                $selected = (($current_detail_data['fine_reason'] ?? '') == $reason) ? 'selected' : '';
                                echo "<option value='{$reason}' {$selected}>{$reason}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["fine_reason"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="fine_status">Fine Status : </label>
                        <select name="fine_status" id="edit_fine_status" class="input-field">
                            <option value="">---Select Status---</option>
                            <?php foreach ($fine_statuses as $status) {
                                $selected = (($current_detail_data['fine_status'] ?? '') == $status) ? 'selected' : '';
                                echo "<option value='{$status}' {$selected}>{$status}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["fine_status"] ?? "" ?></p>
                    </div>
                </div>

                <br>
                <input type="submit" value="Save Changes"
                    class="font-bold cursor-pointer mt-4 border-none rounded-lg bg-red-800 text-white p-2 w-full hover:bg-red-700">
                <p class="errors text-red-500 text-sm mt-2"><?= $errors["general"] ?? "" ?></p>
            </form>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal delete-modal <?= $open_modal == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close" data-modal="deleteConfirmModal">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to delete the borrow detail for:
                <span
                    class="font-semibold italic"><?= $current_detail_data['fName'] ?? 'N/A' . ' ' . $current_detail_data['lName'] ?? '' ?></span>
                (Borrow ID: <span
                    class="font-semibold"><?= $current_detail_data['borrowID'] ?? $delete_borrow_id ?></span>)?
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

    <div id="blockUserModal" class="modal delete-modal <?= $open_modal == 'blockUserModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close" data-modal="blockUserModal">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-orange-700">Confirm User Block</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to **Block** user ID:
                <span class="font-semibold italic"><?= $_SESSION['block_user_id'] ?? 'N/A' ?></span>?
                Blocked users cannot make new loan requests.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    onclick="document.getElementById('blockUserModal').style.display='none';">
                    Cancel
                </button>
                <a href="../../../app/controllers/borrowDetailsController.php?action=blockUser&id=<?= $_SESSION['block_user_id'] ?? '' ?>"
                    class="bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-orange-700 cursor-pointer">
                    Confirm Block
                </a>
            </div>
        </div>
    </div>

    <div id="viewFullDetailsModal" class="modal <?= $open_modal == 'viewFullDetailsModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-lg">
            <span class="close" data-modal="viewFullDetailsModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-red-800">Borrow Detail (ID: <?= $view_borrow_id ?? 'N/A' ?>)</h2>

            <div class="grid grid-cols-2 gap-4 text-gray-700">
                <div class="col-span-2 border-b pb-2 mb-2">
                    <h3 class="font-semibold text-lg text-red-700">Book & Borrower Information</h3>
                </div>
                <div>
                    <p class="font-semibold">Borrow ID:</p>
                    <p><?= $current_detail_data['borrowID'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Book Title (ID):</p>
                    <p><?= htmlspecialchars($current_detail_data['book_title'] ?? 'N/A') ?>
                        (<?= $current_detail_data['bookID'] ?? 'N/A' ?>)</p>
                </div>
                <div>
                    <p class="font-semibold">Borrower Name (ID):</p>
                    <p><?= htmlspecialchars($current_detail_data['fName'] ?? 'N/A') . ' ' . htmlspecialchars($current_detail_data['lName'] ?? '') ?>
                        (<?= $current_detail_data['userID'] ?? 'N/A' ?>)</p>
                </div>
                <div>
                    <p class="font-semibold">Copies Requested/Borrowed:</p>
                    <p><?= $current_detail_data['no_of_copies'] ?? 'N/A' ?></p>
                </div>

                <div class="col-span-2 border-b pt-4 pb-2 mb-2">
                    <h3 class="font-semibold text-lg text-red-700">Timeline & Status</h3>
                </div>
                <div>
                    <p class="font-semibold">Request Date:</p>
                    <p><?= $current_detail_data['request_date'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Pickup Date:</p>
                    <p><?= $current_detail_data['pickup_date'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Expected Return Date:</p>
                    <p><?= $current_detail_data['expected_return_date'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Actual Return Date:</p>
                    <p><?= $current_detail_data['return_date'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Current Status:</p>
                    <p class="font-bold text-blue-600"><?= $current_detail_data['borrow_request_status'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Returned Condition:</p>
                    <p><?= $current_detail_data['returned_condition'] ?? 'N/A' ?></p>
                </div>

                <div class="col-span-2 border-b pt-4 pb-2 mb-2">
                    <h3 class="font-semibold text-lg text-red-700">Fine Details</h3>
                </div>
                <div>
                    <p class="font-semibold">Fine Amount:</p>
                    <p class="font-bold text-red-600">₱<?= number_format($current_detail_data['fine_amount'] ?? 0, 2) ?>
                    </p>
                </div>
                <div>
                    <p class="font-semibold">Fine Status:</p>
                    <p
                        class="font-bold <?= ($current_detail_data['fine_status'] === 'Unpaid') ? 'text-red-600' : 'text-green-600' ?>">
                        <?= $current_detail_data['fine_status'] ?? 'N/A' ?></p>
                </div>
                <div class="col-span-2">
                    <p class="font-semibold">Fine Reason:</p>
                    <p><?= $current_detail_data['fine_reason'] ?? 'N/A' ?></p>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="button"
                    class="bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-700"
                    onclick="document.getElementById('viewFullDetailsModal').style.display='none';">
                    Close
                </button>
            </div>
        </div>
    </div>


</body>
<script src="../../../public/assets/js/admin.js"></script>

</html>