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

// Clear the session variables related to form errors/old data
unset($_SESSION["old"], $_SESSION["errors"]);

// Determines the current Modal
$current_modal = $_GET['modal'] ?? '';
$borrow_id = (int) ($_GET['id'] ?? 0);
$open_modal = '';

// Determines the current tab
$current_tab = $_GET['tab'] ?? 'pending';

// Load User Data for Modals
$modal_borrow_details = [];
if ($current_modal === 'edit') {
    $open_modal = 'editBorrowDetailModal';
} elseif ($current_modal === 'delete') {
    $open_modal = 'deleteConfirmModal';
} elseif ($current_modal === 'block') {
    $open_modal = 'blockUserModal';
} elseif ($current_modal === 'view') {
    $open_modal = 'viewFullDetailsModal';
} elseif ($current_modal === 'return') {
    $open_modal = 'returnBookModal';
} elseif ($current_modal === 'paid') {
    $open_modal = 'paidConfirmModal';
}

if (!empty($open_modal)) {
    if ($open_modal == 'editBorrowDetailModal' && !empty($old)) {
        $modal_borrow_details = $old;
    } elseif (($open_modal == 'returnBookModal' || $open_modal == 'deleteConfirmModal' || $open_modal == 'blockUserModal' || $open_modal == 'paidConfirmModal') && !empty($old)) {
        $fresh_detail = $borrowObj->fetchBorrowDetail($borrow_id) ?: [];
        $modal_borrow_details = array_merge($fresh_detail, $old);
    } else {
        $modal_borrow_details = $borrowObj->fetchBorrowDetail($borrow_id) ?: [];
    }
    if ($open_modal != 'viewDetailsUserModal') { //delete 
        $modal_borrow_details['borrowID'] = $borrow_id;
    }
}


$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$userTypeID = isset($_GET['userType']) ? trim($_GET['userType']) : "";

$borrow_details = $borrowObj->viewBorrowDetails($search, $current_tab);
$all_books = $bookObj->viewBook();

$original_book_condition = $modal_borrow_details['book_condition'] ?? 'N/A';

$condition_options = ['Good', 'Fair', 'Damaged', 'Lost'];

foreach ($borrow_details as &$detail) {
    $db_update_required = false;
    if ((($detail['borrow_status'] === 'Borrowed' || $detail['fine_status'] === 'Unpaid' ) && $detail['return_date'] === null)){
        $fine_results = $borrowObj->calculateFinalFine(
            $detail['expected_return_date'], 
            date("Y-m-d"), // Today's date for display calculation
            $bookObj, 
            $detail['bookID']
        );
        if ($fine_results['fine_amount'] > $detail['fine_amount']) {

            $detail['calculated_fine'] = $fine_results['fine_amount'];
            $detail['fine_amount'] = $fine_results['fine_amount']; 
            $detail['fine_reason'] = $fine_results['fine_reason'];
            $detail['fine_status'] = $fine_results['fine_status'];

            $db_update_required = true;

        } else {
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

unset($detail);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Manage Borrow Details</title>
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
                <div class="section manage_users h-full">
                    <div class="title flex w-full items-center justify-between mb-4">
                        <h1 class="text-red-800 font-bold text-4xl">MANAGE BORROW DETAILS</h1>
                    </div>

                    <div class="tabs flex border-b border-gray-200 mb-6">
                        <a href="?tab=pending" class="tab-btn <?= $current_tab == 'pending' ? 'active' : '' ?>">Pending
                            Request</a>
                        <a href="?tab=approved"
                            class="tab-btn <?= $current_tab == 'approved' ? 'active' : '' ?>">Approved Request</a>
                        <a href="?tab=borrowed"
                            class="tab-btn <?= $current_tab == 'borrowed' ? 'active' : '' ?>">Currently
                            Borrowed</a>
                        <a href="?tab=unpaid"
                            class="tab-btn <?= $current_tab == 'unpaid' ? 'active' : '' ?>">Fined</a>
                        <a href="?tab=returned"
                            class="tab-btn <?= $current_tab == 'returned' ? 'active' : '' ?>">Returned</a>
                        <a href="?tab=cancelled"
                            class="tab-btn <?= $current_tab == 'cancelled' ? 'active' : '' ?>">Cancelled</a>
                            <a href="?tab=rejected"
                            class="tab-btn <?= $current_tab == 'rejected' ? 'active' : '' ?>">Rejected</a>
                            
                    </div>

                    <h2 class="text-2xl font-semibold mb-4 text-gray-700">
                        <?php
                        switch ($current_tab) {
                            case 'pending':
                                echo 'Pending Request';
                                break;
                            case 'approved':
                                echo 'Approved Request';
                                break;
                            case 'borrowed':
                                echo 'Currently Borrowed';
                                break;
                            case 'unpaid':
                                echo 'Fined';
                                break;
                            case 'returned':
                                echo 'Returned';
                                break;
                            case 'cancelled':
                                echo 'Cancelled';
                                break;
                            case 'rejected':
                                echo 'Rejected';
                                break;
                        }
                        ?>
                    </h2>

                    <div class="view">
                        <table>
                            <?php if ($current_tab == 'pending' || $current_tab == 'approved'):?> 
                                <tr>
                                    <th>No</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Copies</th>
                                    <th>Current Condition</th>
                                    <th>Request Date</th>
                                    <th>Pickup Date</th>
                                    <th>Exp. Return Date</th>
                                    <th>Actions</th>
                                </tr>
                            <?php elseif ($current_tab == 'borrowed'): ?>
                                <tr>
                                    <th>No</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Copies</th>
                                    <th>Exp. Return Date</th>
                                    <th>Fine Amount</th>
                                    <th>Fine Reason</th>
                                    <th>Fine Status</th>
                                    <th>Actions</th>
                                </tr>

                            <?php elseif ($current_tab == 'unpaid'):  ?>
                                <tr>
                                    <th>No</th>
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
                                    <th>No</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Copies</th>
                                    <th>Return Date</th>
                                    <th>Returned Condition</th>
                                    <th>Fine Reason</th>
                                    <th>Fine Amount</th>
                                    <th>Fine Status</th>
                                    <th>Actions</th>
                                </tr>
                            <?php elseif ($current_tab == 'cancelled' || $current_tab == 'rejected'):?>
                                <tr>
                                    <th>No</th>
                                    <th>User Name</th>
                                    <th>Book Title</th>
                                    <th>Copies</th>
                                    <th>Current Condition</th>
                                    <th>Request Date</th>
                                    <th>Exp. Return Date</th>
                                    <th>Actions</th>
                                </tr>
                            <?php endif; ?>

                            <?php
                            $no = 1;
                            if (empty($borrow_details)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-gray-500">
                                        No <?= strtolower(str_replace('d', 'd ', $current_tab)) ?> records found.
                                    </td>
                                </tr>
                            <?php else:
                             foreach ($borrow_details as $detail) {
                                    $fullName = htmlspecialchars($detail["lName"] . ", " . $detail["fName"]);
                                    $bookTitle = htmlspecialchars($detail["book_title"]);
                                    $borrowID = $detail["borrowID"];
                                    $userID = $detail['userID'];
                                    ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= $fullName ?></td>
                                        <td><?= $bookTitle ?></td>

                                        <?php if ($current_tab == 'pending'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["book_condition"] ?></td>
                                            <td><?= $detail["request_date"] ?></td>
                                            <td><?= $detail["pickup_date"] ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td class="action text-center">
                                                <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/borrowDetailsController.php?tab=<?= $current_tab ?>&action=accept&id=<?= $borrowID ?>">Accept</a>
                                                <a class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/borrowDetailsController.php?tab=<?= $current_tab ?>&action=reject&id=<?= $borrowID ?>">Reject</a>
                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php elseif ($current_tab == 'approved'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["book_condition"] ?></td>
                                            <td><?= $detail["request_date"] ?></td>
                                            <td><?= $detail["pickup_date"] ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td class="action text-center">
                                                <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/borrowDetailsController.php?tab=<?= $current_tab ?>&action=pickup&id=<?= $borrowID ?>">Claimed</a>
                                                <a class="actionBtn bg-amber-500 hover:bg-amber-600 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/borrowDetailsController.php?tab=<?= $current_tab ?>&action=cancel&id=<?= $borrowID ?>">Cancel</a>
                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        <?php elseif ($current_tab == 'borrowed'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td
                                                class="font-semibold <?= ($detail["calculated_fine"] > 0) ? 'text-red-700' : 'text-gray-700' ?>">
                                                ₱<?= number_format($detail["calculated_fine"] ?? 0, 2) ?>
                                            </td>
                                            <td><?= $detail["fine_reason"] ?? 'N/A' ?></td>
                                            <td class="<?= ($detail["calculated_fine"] > 0 && $detail["fine_status"] === 'Unpaid') ? 'text-red-700' : 'text-gray-700' ?>">
                                                <?= ($detail["calculated_fine"] > 0 && $detail["fine_status"] === 'Unpaid') ? "Unpaid" : "N/A" ?>
                                            </td>
                                            <td class="action text-center">
                                                <?php if($detail["calculated_fine"] > 0) {?>
                                                     <a class="actionBtn bg-green-600 hover:bg-green-700 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=paid&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Paid</a>
                                                <a class="actionBtn bg-yellow-600 hover:bg-yellow-700 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=block&id=<?= $userID ?>&tab=<?= $current_tab ?>">Block User</a>
                                                <?php } else {?>
                                                
                                                <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=return&id=<?= $borrowID ?>&tab=<?= $current_tab ?>"
                                                    data-borrow-id="<?= $borrowID ?>"
                                                    data-original-condition="<?= htmlspecialchars($detail["book_condition"] ?? $detail["book_condition"]) ?>">
                                                    Returned
                                                </a>
                                                <?php }?>
                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                            </td>
                                        
                                        <?php elseif ($current_tab == 'unpaid'): ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["expected_return_date"] ?></td>
                                            <td
                                                class="font-semibold <?= ($detail["calculated_fine"] > 0) ? 'text-red-700' : 'text-gray-700' ?>">
                                                ₱<?= number_format($detail["calculated_fine"] ?? 0, 2) ?>
                                            </td>
                                            <td><?= $detail["fine_reason"] ?? 'N/A' ?></td>
                                            <td class="<?= ($detail["calculated_fine"] > 0 && $detail["fine_status"] === 'Unpaid') ? 'text-red-700' : 'text-gray-700' ?>">
                                                <?= ($detail["calculated_fine"] > 0 && $detail["fine_status"] === 'Unpaid') ? "Unpaid" : "N/A" ?>
                                            </td>
                                            <td class="action text-center">
                                                <?php if($detail["calculated_fine"] > 0) {?>
                                                     <a class="actionBtn bg-green-600 hover:bg-green-700 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=paid&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Paid</a>
                                                <a class="actionBtn bg-yellow-600 hover:bg-yellow-700 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=block&id=<?= $userID ?>&tab=<?= $current_tab ?>">Block User</a>
                                                <?php } else {?>
                                                
                                                <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1"
                                                    href="borrowDetailsSection.php?modal=return&id=<?= $borrowID ?>&tab=<?= $current_tab ?>"
                                                    data-borrow-id="<?= $borrowID ?>"
                                                    data-original-condition="<?= htmlspecialchars($detail["book_condition"] ?? $detail["book_condition"]) ?>">
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
                                            <td><?= $detail["fine_reason"] ?? 'N/A' ?></td>
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
                                        <?php elseif ($current_tab == 'cancelled' || $current_tab == 'rejected'): // Consolidated CANCELLED/REJECTED BODY ?>
                                            <td><?= $detail["no_of_copies"] ?></td>
                                            <td><?= $detail["book_condition"] ?></td>
                                            <td><?= $detail["request_date"] ?></td>
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
                action="../../../app/controllers/borrowDetailsController.php?action=return&id=<?= $borrow_id ?? '' ?>"
                method="POST">
                <input type="hidden" name="borrowID" value="<?= $borrow_id ?? '' ?>">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                
                <p class="mb-4 text-gray-700">
                    You are confirming the return of the book: 
                    <span class="font-semibold text-red-800"><?= $modal_borrow_details['book_title'] ?? 'N/A' ?></span>.
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
                        <?php 
                        $failed_condition = $modal_borrow_details['returned_condition'] ?? '';
                        $original_condition = $original_book_condition; 
                        ?>
                        
                        <option value="" <?= empty($failed_condition) ? 'selected' : '' ?>>---Select Condition---</option>

                        <?php 
                        // Iterate through all possible options
                        foreach ($condition_options as $option): 
                            $selected = '';

                            // Check if this is the failed submission value
                            if ($failed_condition === $option) {
                                $selected = 'selected';
                            } 
                            //If no failed value, and this option matches the original condition, select it
                            elseif (empty($failed_condition) && $option === $original_condition) {
                                $selected = 'selected';
                            }
                        ?>
                            <option value="<?= htmlspecialchars($option) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($option) ?>
                                <?= ($option === $original_condition && empty($failed_condition)) ? ' (Original)' : '' ?>
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
            <h2 class="text-2xl font-bold mb-4">Edit Borrow Detail (ID: <?= $borrow_id ?>)</h2>
            <form id="editBorrowDetailForm"
                action="../../../app/controllers/borrowDetailsController.php?action=edit&id=<?= $borrow_id ?>"
                method="POST">
                <input type="hidden" name="borrowID" value="<?= $borrow_id ?>">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                <div class="grid grid-cols-2 gap-4">
                    <div class="input">
                        <label for="userID">User ID<span>*</span> : </label>
                        <input type="number" class="input-field" name="userID" id="edit_userID"
                            value="<?= $modal_borrow_details["userID"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["userID"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="bookID">Book ID<span>*</span> : </label>
                        <input type="number" class="input-field" name="bookID" id="edit_bookID"
                            value="<?= $modal_borrow_details["bookID"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["bookID"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="no_of_copies">No. of Copies<span>*</span> : </label>
                        <input type="number" class="input-field" name="no_of_copies" id="edit_no_of_copies"
                            value="<?= $modal_borrow_details["no_of_copies"] ?? "1" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["no_of_copies"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="request_date">Borrow/Request Date<span>*</span> : </label>
                        <input type="date" class="input-field" name="request_date" id="edit_request_date"
                            value="<?= $modal_borrow_details["request_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["request_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="pickup_date">Pickup Date (Optional) : </label>
                        <input type="date" class="input-field" name="pickup_date" id="edit_pickup_date"
                            value="<?= $modal_borrow_details["pickup_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["pickup_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="expected_return_date">Exp. Return Date<span>*</span> : </label>
                        <input type="date" class="input-field" name="expected_return_date"
                            id="edit_expected_return_date"
                            value="<?= $modal_borrow_details["expected_return_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["expected_return_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="return_date">Actual Return Date (Optional) : </label>
                        <input type="date" class="input-field" name="return_date" id="edit_return_date"
                            value="<?= $modal_borrow_details["return_date"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["return_date"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="borrow_request_status">Request Status<span>*</span> : </label>
                        <select name="borrow_request_status" id="edit_borrow_request_status" class="input-field">
                            <option value="">---Select Status---</option>
                            <?php 
                             $request_statuses = ['Pending', 'Approved', 'Rejected', 'Cancelled'];
                             foreach ($request_statuses as $status) {
                                $selected = (($modal_borrow_details['borrow_request_status'] ?? '') == $status) ? 'selected' : '';
                                echo "<option value='{$status}' {$selected}>{$status}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["borrow_request_status"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="borrow_status">Borrow Status<span>*</span> : </label>
                        <select name="borrow_status" id="edit_borrow_status" class="input-field">
                            <option value="">---Select Status---</option>
                            <?php 
                             $borrow_statuses = ['Borrowed', 'Returned'];
                             foreach ($borrow_statuses as $status) {
                                $selected = (($modal_borrow_details['borrow_status'] ?? '') == $status) ? 'selected' : '';
                                echo "<option value='{$status}' {$selected}>{$status}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["borrow_status"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="returned_condition">Returned Condition (Optional) : </label>
                        <select name="returned_condition" id="edit_returned_condition" class="input-field">
                            <option value="">---Select Condition---</option>
                            <?php 
                            foreach ($condition_options as $option) {
                                $selected = (($modal_borrow_details['returned_condition'] ?? '') == $option) ? 'selected' : '';
                                echo "<option value='{$option}' {$selected}>{$option}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["returned_condition"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="fine_amount">Fine Amount : </label>
                        <input type="number" step="0.01" class="input-field" name="fine_amount" id="edit_fine_amount"
                            value="<?= $modal_borrow_details["fine_amount"] ?? "0.00" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["fine_amount"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="fine_reason">Fine Reason : </label>
                        <select name="fine_reason" id="edit_fine_reason" class="input-field">
                            <option value="">---Select Reason---</option>
                            <?php 
                            // Assuming $fine_reasons is defined elsewhere
                            $fine_reasons = ['Late', 'Lost', 'Damaged'];
                            foreach ($fine_reasons as $reason) {
                                $selected = (($modal_borrow_details['fine_reason'] ?? '') == $reason) ? 'selected' : '';
                                echo "<option value='{$reason}' {$selected}>{$reason}</option>";
                            } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["fine_reason"] ?? "" ?></p>
                    </div>

                    <div class="input">
                        <label for="fine_status">Fine Status : </label>
                        <select name="fine_status" id="edit_fine_status" class="input-field">
                            <option value="">---Select Status---</option>
                            <?php
                            $fine_statuses = ['Unpaid', 'Paid'];
                            foreach ($fine_statuses as $status) {
                                $selected = (($modal_borrow_details['fine_status'] ?? '') == $status) ? 'selected' : '';
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
                    class="font-semibold italic"><?= $modal_borrow_details['fName'] ?? 'N/A' . ' ' . $modal_borrow_details['lName'] ?? '' ?></span>
                (Borrow ID: <span
                    class="font-semibold"><?= $modal_borrow_details['borrowID'] ?? $borrow_id ?></span>)?
                This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    data-modal="deleteConfirmModal" data-tab="<?= $current_tab ?>">
                    Cancel
                </button>
                <a href="../../../app/controllers/borrowDetailsController.php?action=delete&id=<?= $modal_borrow_details['borrowID'] ?? $borrow_id ?>"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 cursor-pointer">
                    Confirm Delete
                </a>
            </div>
        </div>
    </div>

    <div id="paidConfirmModal" class="modal delete-modal <?= $open_modal == 'paidConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close close-times" data-modal="paidConfirmModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-green-700">Confirm Fine Payment & Return</h2>
            <p class="mb-4 text-gray-700">
                You are marking the fine as <strong>Paid</strong> and the loan as <strong>Returned</strong> for:
                <span class="font-semibold text-red-800"><?= $modal_borrow_details['book_title'] ?? 'N/A' ?></span>.
            </p>
            
            <div class="grid grid-cols-2 gap-y-2 mb-4 p-3 bg-gray-100 rounded-lg">
                <p class="font-semibold col-span-2 text-md text-blue-800 border-b pb-1">Loan and Fine Details</p>
                
                <p class="font-semibold">Original Condition:</p>
                <p class="text-blue-600"><?= htmlspecialchars($modal_borrow_details['book_condition'] ?? 'N/A') ?></p>

                <p class="font-semibold">Fine Amount:</p>
                <p class="font-bold text-red-700">₱<?= number_format($modal_borrow_details['fine_amount'] ?? 0, 2) ?></p>

                <p class="font-semibold">Fine Reason:</p>
                <p><?= $modal_borrow_details['fine_reason'] ?? 'N/A' ?></p>

                <p class="font-semibold">Fine Status:</p>
                <p class="font-bold text-red-700"><?= $modal_borrow_details['fine_status'] ?? 'N/A' ?></p>
            </div>
            
            <p class="mb-6 text-gray-700 font-bold">
                Do you want to proceed? This will update book stock and mark the fine as Paid.
            </p>

            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    data-modal="paidConfirmModal" data-tab="<?= $current_tab ?>">
                    Cancel
                </button>
                <a href="../../../app/controllers/borrowDetailsController.php?action=paid&id=<?= $borrow_id ?? '' ?>&tab=<?= $current_tab ?>"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 cursor-pointer">
                    Confirm Paid & Return
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
                <span class="font-semibold italic"><?= $borrow_id ?? 'N/A' ?></span>?
                Blocked users cannot make new loan requests.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    data-modal="blockUserModal" data-tab="<?= $current_tab ?>">
                    Cancel
                </button>
                <a href="../../../app/controllers/borrowDetailsController.php?action=blockUser&id=<?= $borrow_id ?? '' ?>"
                    class="bg-orange-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-orange-700 cursor-pointer">
                    Confirm Block
                </a>
            </div>
        </div>
    </div>

    <div id="viewFullDetailsModal" class="modal <?= $open_modal == 'viewFullDetailsModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-lg">
            <span class="close close-times" data-modal="viewFullDetailsModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-red-800">Borrow Detail (ID: <?= $borrow_id ?? 'N/A' ?>)</h2>

            <div class="grid grid-cols-2 gap-4 text-gray-700">
                <div class="col-span-2 border-b pb-2 mb-2">
                    <h3 class="font-semibold text-lg text-red-700">Book & Borrower Information</h3>
                </div>
                <div>
                    <p class="font-semibold">Borrow ID:</p>
                    <p><?= $modal_borrow_details['borrowID'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Book Title (ID):</p>
                    <p><?= htmlspecialchars($modal_borrow_details['book_title'] ?? 'N/A') ?>
                        (<?= $modal_borrow_details['bookID'] ?? 'N/A' ?>)</p>
                </div>
                <div>
                    <p class="font-semibold">Borrower Name (ID):</p>
                    <p><?= htmlspecialchars($modal_borrow_details['fName'] ?? 'N/A') . ' ' . htmlspecialchars($modal_borrow_details['lName'] ?? '') ?>
                        (<?= $modal_borrow_details['userID'] ?? 'N/A' ?>)</p>
                </div>
                <div>
                    <p class="font-semibold">Book Condition:</p>
                    <p><?= htmlspecialchars($modal_borrow_details['book_condition'] ?? 'N/A')?></p>
                </div>
                <div>
                    <p class="font-semibold">Copies Requested/Borrowed:</p>
                    <p><?= $modal_borrow_details['no_of_copies'] ?? 'N/A' ?></p>
                </div>

                <div class="col-span-2 border-b pt-4 pb-2 mb-2">
                    <h3 class="font-semibold text-lg text-red-700">Timeline & Status</h3>
                </div>
                <div>
                    <p class="font-semibold">Request Date:</p>
                    <p><?= $modal_borrow_details['request_date'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Pickup Date:</p>
                    <p><?= $modal_borrow_details['pickup_date'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Expected Return Date:</p>
                    <p><?= $modal_borrow_details['expected_return_date'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Actual Return Date:</p>
                    <p><?= $modal_borrow_details['return_date'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Request Status:</p>
                    <p class="font-bold text-blue-600"><?= $modal_borrow_details['borrow_request_status'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Borrow Status:</p>
                    <p class="font-bold text-blue-600"><?= $modal_borrow_details['borrow_status'] ?? 'N/A' ?></p>
                </div>
                <div>
                    <p class="font-semibold">Returned Condition:</p>
                    <p><?= $modal_borrow_details['returned_condition'] ?? 'N/A' ?></p>
                </div>

                <div class="col-span-2 border-b pt-4 pb-2 mb-2">
                    <h3 class="font-semibold text-lg text-red-700">Fine Details</h3>
                </div>
                <div>
                    <p class="font-semibold">Fine Amount:</p>
                    <p class="font-bold text-red-600">₱<?= number_format($modal_borrow_details['fine_amount'] ?? 0, 2) ?>
                    </p>
                </div>
                <div>
                    <p class="font-semibold">Fine Status:</p>
                    <p
                        class="font-bold <?= ($modal_borrow_details['fine_status'] === 'Unpaid') ? 'text-red-600' : 'text-green-600' ?>">
                        <?= $modal_borrow_details['fine_status'] ?? 'N/A' ?>
                    </p>
                </div>
                <div class="col-span-2">
                    <p class="font-semibold">Fine Reason:</p>
                    <p><?= $modal_borrow_details['fine_reason'] ?? 'N/A' ?></p>
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
<script src="../../../public/assets/js/modal.js"></script>

</html>