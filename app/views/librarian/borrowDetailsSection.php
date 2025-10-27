<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../../models/manageBook.php");

$borrowObj = new BorrowDetails();
$bookObj = new Book();

$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];

// Determines the current Modal and ID from URL
$current_modal = $_GET['modal'] ?? '';
$load_id = (int) ($_GET['id'] ?? 0);
$current_tab = $_GET['tab'] ?? 'pending';

// Clear the session variables related to form errors/old data
unset($_SESSION["old"], $_SESSION["errors"]);

// --- Modal State Cleanup/Determination ---

$open_modal = '';
$modal_load_id = $load_id; // Default ID to load detail for

// If there are POST errors, the controller redirects back and sets session variables.
// Prioritize the session-based state (i.e., re-open the modal after a failed POST).
if (!empty($_SESSION['open_modal'])) {
    $open_modal = $_SESSION['open_modal'];
    // Use the ID preserved in session by the controller, if applicable
    $modal_load_id = $_SESSION['edit_borrow_id'] ?? $_SESSION['return_borrow_id'] ?? $load_id;
    
    // Clear the specific session flags after reading them
    unset($_SESSION['open_modal'], $_SESSION['edit_borrow_id'], $_SESSION['return_borrow_id'], $_SESSION['delete_borrow_id'], $_SESSION['view_borrow_id'], $_SESSION['block_user_id']);
} 
// Otherwise, use the direct URL parameters (like in usersSection.php)
elseif ($current_modal === 'edit') {
    $open_modal = 'editBorrowDetailModal';
} elseif ($current_modal === 'delete') {
    $open_modal = 'deleteConfirmModal';
} elseif ($current_modal === 'block') {
    $open_modal = 'blockUserModal';
} elseif ($current_modal === 'view') {
    $open_modal = 'viewFullDetailsModal';
} elseif ($current_modal === 'return') {
    $open_modal = 'returnBookModal';
}


// Load Borrow Detail Data for Modals
$current_detail_data = [];

if ($modal_load_id) {
    if ($open_modal == 'editBorrowDetailModal' && !empty($old)) {
        // Use old POST data if available for edit modal
        $current_detail_data = $old;
        $current_detail_data['borrowID'] = $modal_load_id;
    } else {
        // Use the fetchBorrowDetail method (which joins book data) for all modals needing detail
        $current_detail_data = $borrowObj->fetchBorrowDetail($modal_load_id);
    }
    if (!$current_detail_data) {
        $current_detail_data = ['borrowID' => 'Error', 'book_title' => 'Detail not found.'];
    }
} elseif ($open_modal == 'addBorrowDetailModal' && !empty($old)) {
    $current_detail_data = $old;
}

// Extract the original book condition for the return modal display
$original_book_condition = $current_detail_data['book_condition'] ?? 'N/A';


$status_map = [
    'pending' => 'Pending',
    'pickup' => 'Approved',
    'currently_borrowed' => 'Borrowed', 
    'returned' => 'Returned',
    'cancelled' => 'Cancelled', // ADDED MAPPING
    'rejected' => 'Rejected'   // ADDED MAPPING
];
$db_status_filter = $status_map[$current_tab] ?? 'Pending';


$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch details based on the tab - FIX APPLIED HERE
if ($current_tab === 'currently_borrowed') {
    // This calls the new function to filter by borrow_status = 'Borrowed'
    $details = $borrowObj->viewActiveBorrowDetails($search);
} else {
    // This calls the function for status-based filtering (Pending, Approved, Returned, Cancelled, Rejected)
    $details = $borrowObj->viewBorrowDetails($search, $db_status_filter);
}

// --- Late/Lost Fine Calculation Function (Must mirror controller logic) --
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

        // Calculate the late fine for the entire period
        $weeks_late = ceil($days_late / 7);
        $late_fine_amount = $weeks_late * 20.00;

        // FIX: Check for Lost Status (15 weeks = 105 days).
        if ($days_late >= 105) {
            // FIX: Capping the late fine component at 15 weeks (₱300.00) and adding replacement cost.
            // Late fee component is capped at the maximum possible late fee (₱300.00)
            $replacement_cost = $bookObj->fetchBookReplacementCost($bookID);

            // Total Fine = Capped Late Fee (₱300.00) + Replacement Cost
            $results['fine_amount'] = 300.00 + $replacement_cost;
            $results['fine_reason'] = 'Lost';
            $results['fine_status'] = 'Unpaid';

        } else {
            // Standard Late Fine: Uses the calculated late fine for the entire period
            $results['fine_amount'] = $late_fine_amount;
            $results['fine_reason'] = 'Late';
            $results['fine_status'] = 'Unpaid';
        }
    }
    return $results;
}


foreach ($details as &$detail) {
    // Flag to track if a DB update is necessary
    $db_update_required = false;

    // This condition correctly identifies active, unreturned loans ('Borrowed' status with no return date)
    if (($detail['borrow_status'] === 'Borrowed' && $detail['return_date'] === null)){

        $fine_results = calculateDisplayFine($detail['expected_return_date'], $bookObj, $detail['bookID']);

        // Check if calculated fine is greater OR if the DB fine is 0 but a new fine is calculated.
        if ($fine_results['fine_amount'] > $detail['fine_amount']) {

            // 1. Update the local array details for display
            $detail['calculated_fine'] = $fine_results['fine_amount'];
            $detail['fine_amount'] = $fine_results['fine_amount']; // Persist new amount locally
            $detail['fine_reason'] = $fine_results['fine_reason'];
            $detail['fine_status'] = $fine_results['fine_status'];

            // 2. Mark for database update
            $db_update_required = true;

        } else {
            // Use the database value if it's already higher or if no new fine is calculated
            $detail['calculated_fine'] = $detail['fine_amount'];
        }
    } else {
        // For other tabs (Pending, Pickup, Returned, Cancelled, Rejected), just use the database fine amount for display
        $detail['calculated_fine'] = $detail['fine_amount'];
    }

    if ($db_update_required && $detail['fine_amount'] > 0) {
        $borrowObj->updateFineDetails(
            $detail['borrowID'],
            $detail['fine_amount'],
            $detail['fine_reason'],
            $detail['fine_status']
        );
    }

}

unset($detail); // Crucial to unset the reference
// Options for Modals
$fine_reasons = ['Late', 'Damaged', 'Lost'];
$fine_statuses = ['Paid', 'Unpaid'];
// 'Cancelled' added here
$request_statuses = ['Pending', 'Approved', 'Rejected', 'Cancelled'];
$borrow_statuses = ['Borrowed', 'Returned', 'Fined'];
// Ensure $condition_options is available for the new modal
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

                    <div class="tabs flex border-b border-gray-200 mb-6 mt-4">
                        <a href="?tab=pending" class="tab-btn text-sm <?= $current_tab == 'pending' ? 'active' : '' ?>">Pending
                            Request</a>
                        <a href="?tab=pickup" class="tab-btn text-sm <?= $current_tab == 'pickup' ? 'active' : '' ?>">Pending
                            PickUp</a>
                        <a href="?tab=currently_borrowed"
                            class="tab-btn text-sm <?= $current_tab == 'currently_borrowed' ? 'active' : '' ?>">Currently
                            Borrowed</a>
                        <a href="?tab=returned"
                            class="tab-btn text-sm <?= $current_tab == 'returned' ? 'active' : '' ?>">Returned
                            Books</a>
                        <a href="?tab=cancelled"
                            class="tab-btn text-sm <?= $current_tab == 'cancelled' ? 'active' : '' ?>">Cancelled
                            Requests</a>
                        <a href="?tab=rejected"
                            class="tab-btn text-sm <?= $current_tab == 'rejected' ? 'active' : '' ?>">Rejected
                            Requests</a>
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
                                    <th>Exp. Return Date</th>
                                    <th>Fine Amount</th>
                                    <th>Fine Reason</th>
                                    <th>Fine Status</th>
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
                            <?php elseif ($current_tab == 'cancelled' || $current_tab == 'rejected'): // ADDED CANCELLED/REJECTED HEADER ?>
                                <tr>
                                    <th>ID</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Copies</th>
                                    <th>Current Condition</th>
                                    <th>Request Date</th>
                                    <th>Cancellation/Rejection Date</th>
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
                                            <td class="action text-center">
                                                <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=accept&id=<?= $borrowID ?>">Accept</a>
                                                <a class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=reject&id=<?= $borrowID ?>">Reject</a>
                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php elseif ($current_tab == 'pickup'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["current_book_condition"] ?></td>
                                            <td><?= $detail["request_date"] ?></td>
                                            <td><?= $detail["pickup_date"] ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td class="action text-center">
                                                <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=pickup&id=<?= $borrowID ?>">Claimed</a>
                                                <a class="actionBtn bg-amber-500 hover:bg-amber-600 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=cancel&id=<?= $borrowID ?>">Cancel</a>
                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php elseif ($current_tab == 'currently_borrowed'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td
                                                class="font-semibold <?= ($detail["calculated_fine"] > 0) ? 'text-red-700' : 'text-gray-700' ?>">
                                                ₱<?= number_format($detail["calculated_fine"] ?? 0, 2) ?>
                                            </td>
                                            <td><?= $detail["fine_reason"] ?? 'N/A' ?></td>
                                            <td class="<?= ($detail["calculated_fine"] > 0) ? 'text-red-700' : 'text-gray-700' ?>">
                                                <?= ($detail["calculated_fine"] == 0 || $detail["fine_status"] === null) ? "N/A" : "Unpaid" ?>
                                            </td>

                                            <td class="action text-center">
                                                <?php if($detail["calculated_fine"] > 0) {?>
                                                     <a class="actionBtn bg-green-600 hover:bg-green-700 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/borrowDetailsController.php?action=paid&id=<?= $borrowID ?>">Paid</a>
                                                <a class="actionBtn bg-yellow-600 hover:bg-yellow-700 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=block&id=<?= $userID ?>&tab=<?= $current_tab ?>">Block User</a>
                                                <?php } else {?>
                                                
                                                <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=return&id=<?= $borrowID ?>&tab=<?= $current_tab ?>"
                                                    data-borrow-id="<?= $borrowID ?>"
                                                    data-original-condition="<?= htmlspecialchars($detail["book_condition"] ?? $detail["current_book_condition"]) ?>">
                                                    Returned
                                                </a>
                                                <?php }?>
                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php elseif ($current_tab == 'returned'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["return_date"] ?? 'N/A' ?></td>
                                            <td><?= $detail["returned_condition"] ?? 'N/A' ?></td>
                                            <td class="font-semibold text-red-700">
                                                ₱<?= number_format($detail["calculated_fine"] ?? 0, 2) ?></td>
                                            <td><?= $detail["fine_status"] ?? 'N/A' ?></td>
                                            <td class="action text-center">
                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="actionBtn bg-red-600 hover:bg-red-700 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=delete&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Delete</a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php elseif ($current_tab == 'cancelled' || $current_tab == 'rejected'): // ADDED CANCELLED/REJECTED BODY ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["current_book_condition"] ?></td>
                                            <td><?= $detail["request_date"] ?></td>
                                            <td><?= $detail["return_date"] ?? 'N/A' ?></td> 
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td class="action text-center">
                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=delete&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Delete</a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
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

    <div id="returnBookModal" class="modal <?= $open_modal == 'returnBookModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-md">
            <span class="close close-times" data-modal="returnBookModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-green-700">Confirm Book Return</h2>
            <form id="returnBookForm"
                action="../../../app/controllers/borrowDetailsController.php?action=return&id=<?= $modal_load_id ?? '' ?>"
                method="POST">
                <input type="hidden" name="borrowID" value="<?= $modal_load_id ?? '' ?>">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                
                <p class="mb-4 text-gray-700">
                    You are confirming the return of the book: 
                    <span class="font-semibold text-red-800"><?= $current_detail_data['book_title'] ?? 'N/A' ?></span>.
                </p>
                <div class="input mb-4 p-3 bg-gray-100 rounded-lg">
                    <label class="block font-semibold mb-1" for="original_condition_display">
                        Original Condition (Book condition upon loan):
                    </label>
                    <p id="original_condition_display" class="font-bold text-lg text-blue-600">
                        <?= htmlspecialchars($original_book_condition ?? 'N/A') ?>
                    </p>
                </div>

                <div class="input">
                    <label class="block font-semibold mb-1" for="returned_condition">
                        Returned Condition<span>*</span> :
                    </label>
                    <select 
                        name="returned_condition" 
                        id="returned_condition" 
                        class="input-field w-full p-2 border rounded-lg focus:ring-red-800 focus:border-red-800"
                    >
                        <?php if (empty($original_book_condition)): ?>
                            <option value="" selected>---Select Condition---</option>
                        <?php else: ?>
                            <option value="">---Select Condition---</option>
                            <option value="<?= htmlspecialchars($original_book_condition) ?>" selected>
                                <?= htmlspecialchars($original_book_condition) ?> (Original)
                            </option>
                        <?php endif; ?>

                        <?php foreach ($condition_options as $option): ?>
                            <?php 
                            // skip duplicate of original
                            if (!empty($original_book_condition) && $option === $original_book_condition) continue; 
                            ?>
                            <option value="<?= htmlspecialchars($option) ?>">
                                <?= htmlspecialchars($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
<p class="errors text-red-500 text-sm mt-2"><?= $errors["returned_condition"] ?? "" ?></p>
                </div>

                
                <p class="errors text-red-500 text-sm mt-2"><?= $errors["returned_condition"] ?? "" ?></p>

                <input type="submit" value="Confirm Return"
                    class="font-bold cursor-pointer mt-6 border-none rounded-lg bg-green-700 text-white p-3 w-full hover:bg-green-800">
            </form>
        </div>
    </div>


    <div id="editBorrowDetailModal" class="modal <?= $open_modal == 'editBorrowDetailModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close close-times" data-modal="editBorrowDetailModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Edit Borrow Detail (ID: <?= $modal_load_id ?>)</h2>
            <form id="editBorrowDetailForm"
                action="../../../app/controllers/borrowDetailsController.php?action=edit&id=<?= $modal_load_id ?>"
                method="POST">
                <input type="hidden" name="borrowID" value="<?= $modal_load_id ?>">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
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
                        <label for="borrow_status">Borrow Status<span>*</span> : </label>
                        <select name="borrow_status" id="edit_borrow_status" class="input-field">
                            <option value="">---Select Status---</option>
                            <?php foreach ($borrow_statuses as $status) {
                                $selected = (($current_detail_data['borrow_status'] ?? '') == $status) ? 'selected' : '';
                                echo "<option value='{$status}' {$selected}>{$status}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["borrow_status"] ?? "" ?></p>
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
            <span class="close close-times" data-modal="deleteConfirmModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to delete the borrow detail for:
                <span
                    class="font-semibold italic"><?= $current_detail_data['fName'] ?? 'N/A' . ' ' . $current_detail_data['lName'] ?? '' ?></span>
                (Borrow ID: <span
                    class="font-semibold"><?= $current_detail_data['borrowID'] ?? $modal_load_id ?></span>)?
                This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    data-modal="deleteConfirmModal" data-tab="<?= $current_tab ?>">
                    Cancel
                </button>
                <a href="../../../app/controllers/borrowDetailsController.php?action=delete&id=<?= $current_detail_data['borrowID'] ?? $modal_load_id ?>"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 cursor-pointer">
                    Confirm Delete
                </a>
            </div>
        </div>
    </div>

    <div id="blockUserModal" class="modal delete-modal <?= $open_modal == 'blockUserModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close" data-modal="blockUserModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-orange-700">Confirm User Block</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to **Block** user ID:
                <span class="font-semibold italic"><?= $modal_load_id ?? 'N/A' ?></span>?
                Blocked users cannot make new loan requests.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    data-modal="blockUserModal" data-tab="<?= $current_tab ?>">
                    Cancel
                </button>
                <a href="../../../app/controllers/borrowDetailsController.php?action=blockUser&id=<?= $modal_load_id ?? '' ?>"
                    class="bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-orange-700 cursor-pointer">
                    Confirm Block
                </a>
            </div>
        </div>
    </div>

    <div id="viewFullDetailsModal" class="modal <?= $open_modal == 'viewFullDetailsModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-lg">
            <span class="close close-times" data-modal="viewFullDetailsModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-red-800">Borrow Detail (ID: <?= $modal_load_id ?? 'N/A' ?>)</h2>

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
                    <p class="font-semibold">Book Condition:</p>
                    <p><?= htmlspecialchars($current_detail_data['book_condition'] ?? 'N/A')?></p>
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
                    <p class="font-semibold">Request Status:</p>
                    <p class="font-bold text-blue-600"><?= $current_detail_data['borrow_request_status'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Borrow Status:</p>
                    <p class="font-bold text-blue-600"><?= $current_detail_data['borrow_status'] ?? 'N/A' ?></p>
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
                        <?= $current_detail_data['fine_status'] ?? 'N/A' ?>
                    </p>
                </div>
                <div class="col-span-2">
                    <p class="font-semibold">Fine Reason:</p>
                    <p><?= $current_detail_data['fine_reason'] ?? 'N/A' ?></p>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="button"
                    class="close bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-700"
                    data-modal="viewFullDetailsModal"
                    data-tab="<?= $current_tab ?>">
                    Close
                </button>
            </div>
        </div>
    </div>


</body>
<script>
    document.addEventListener("DOMContentLoaded", () => {
  // --- First Redundant Block (Keeping for 'do not remove' constraint) ---
  const closeBtnsRedundant = document.querySelectorAll(".close");
  const modalsRedundant = document.querySelectorAll(".modal");

  const openModal = (modal) => {
    modal.style.display = "flex";
  };

  // Original problematic function
  const closeModalAndRedirectRedundant = () => {
    const currentUrl = new URL(window.location.href);

    if (currentUrl.searchParams.has("modal")) {
      currentUrl.searchParams.delete("modal");
      currentUrl.searchParams.delete("id");

      window.location.href = currentUrl.toString();
    }
  };

  // Function to visually hide the modal (used for outside click only if we don't redirect)
  const closeModalVisual = (modal) => {
    modal.style.display = "none";
    modal.classList.remove("open");
  };

  // Close Modal using closeBtn
  closeBtnsRedundant.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Note: This still uses the problematic redundant function.
      closeModalAndRedirectRedundant();
    });
  });

  //Close Modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      // Note: This still uses the problematic redundant function.
      closeModalAndRedirectRedundant();
    }
  });
  // ------------------------------------------------------------------------

}); // End of first DOMContentLoaded block

document.addEventListener("DOMContentLoaded", () => {
  const closeBtns = document.querySelectorAll(".close");
  const modals = document.querySelectorAll(".modal");

  // --- MODIFIED FUNCTION TO PRESERVE TAB ---
  // The 'tab' argument is essential and will be passed from the button's data attribute.
  const closeModalAndRedirect = (tab) => {
    const currentUrl = new URL(window.location.href);

    // 1. Clear modal/id parameters
    currentUrl.searchParams.delete("modal");
    currentUrl.searchParams.delete("id");

    // 2. Preserve or set the tab parameter
    if (tab) {
      // The 'tab' parameter is explicitly read from the button's data attribute (now data-tab).
      currentUrl.searchParams.set("tab", tab);
    } else {
      // If, for some reason, 'tab' is not passed, remove it to let PHP default to 'pending'.
      currentUrl.searchParams.delete("tab");
    }

    // 3. Redirect to the new clean URL
    window.location.href = currentUrl.toString();
  };

  // Close Modal using closeBtn
  closeBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      // FIX: Read the correct data attribute name: data-tab (To match usersSection.php)
      const currentTab = btn.getAttribute("data-tab") || "pending";
      closeModalAndRedirect(currentTab);
    });
  });

  //Close Modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      // Find the correct tab context from the open modal's close button
      const openModal = e.target;
      const closeBtnInside = openModal.querySelector(".close");
      
      // FIX: Read the correct data attribute name: data-tab (To match usersSection.php)
      const currentTab = closeBtnInside
        ? closeBtnInside.getAttribute("data-tab")
        : "pending"; // Default to pending if not found

      closeModalAndRedirect(currentTab);
    }
  });

  // ... (any other logic you might have)
});
</script>
</html>