<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../../models/manageBook.php");
require_once(__DIR__ . "/../../models/manageUsers.php");

$borrowObj = new BorrowDetails();
$bookObj = new Book();
$userObj = new User();

$borrowObj->updateAllUsersFines();
// --- 3NF: Fetch Reason Presets ---
$borrowRejectReasons = $borrowObj->fetchReasonRefs('BorrowReject');
$borrowCancelReasons = $borrowObj->fetchReasonRefs('BorrowCancel');
$blockReasons = $userObj->fetchReasonRefs('Block');
$unblockReasons = $userObj->fetchReasonRefs('Unblock');

$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["old"], $_SESSION["errors"]);

$current_modal = $_GET['modal'] ?? '';
$borrow_id = (int) ($_GET['id'] ?? 0);
$open_modal = '';
$current_tab = $_GET['tab'] ?? 'pending';

$modal_borrow_details = [];
$latestStatus = [];

if (in_array($current_modal, ['edit', 'delete', 'block', 'unblock', 'view', 'return', 'paid', 'print'])) {
    if ($current_modal === 'edit')
        $open_modal = 'editBorrowDetailModal';
    elseif ($current_modal === 'delete')
        $open_modal = 'deleteConfirmModal';
    elseif ($current_modal === 'block')
        $open_modal = 'blockUserModal';
    elseif ($current_modal === 'unblock')
        $open_modal = 'unblockUserModal';
    elseif ($current_modal === 'view')
        $open_modal = 'viewFullDetailsModal';
    elseif ($current_modal === 'return')
        $open_modal = 'returnBookModal';
    elseif ($current_modal === 'paid')
        $open_modal = 'paidConfirmModal';
    elseif ($current_modal === 'print')
        $open_modal = 'printReportModal';
}

if (!empty($open_modal)) {
    if ($open_modal == 'editBorrowDetailModal' && !empty($old)) {
        $fresh_detail = $borrowObj->fetchBorrowDetail($borrow_id) ?: [];
        $modal_borrow_details = array_merge($fresh_detail, $old);
    } elseif (!empty($old)) {
        $fresh_detail = $borrowObj->fetchBorrowDetail($borrow_id) ?: [];
        $modal_borrow_details = array_merge($fresh_detail, $old);
    } else {
        $modal_borrow_details = $borrowObj->fetchBorrowDetail($borrow_id) ?: [];
    }
    $modal_borrow_details['borrowID'] = $borrow_id;

    // --- 3NF: Fetch Status History if Viewing ---
    if ($open_modal == 'viewFullDetailsModal' && $borrow_id) {
        $latestStatus = $borrowObj->fetchLatestBorrowReasons($borrow_id);
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$borrow_details = $borrowObj->viewBorrowDetails($search, $current_tab) ?: [];
$original_book_condition = $modal_borrow_details['book_condition'] ?? 'N/A';
$condition_options = ['Good', 'Fair', 'Damaged', 'Lost'];

foreach ($borrow_details as &$detail) {
    // Since fines are updated at the start, fine_amount is the calculated value.
    $detail['calculated_fine'] = $detail['fine_amount'];
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
    <link rel="stylesheet" href="../../../public/assets/css/admin.css" />
    <style>.modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); } .modal.open { display: block; } .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; border-radius: 8px; }</style>
</head>

<div class="w-full h-screen flex flex-col">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <div class="w-full h-full flex flex-col overflow-y-auto">
        <div class="section manage_users ">
            <div class="title flex w-full items-center justify-between mb-4">
                <h1 class="text-red-800 font-bold text-4xl">MANAGE BORROW DETAILS</h1>
            </div>

            <div class="tabs flex border-b border-gray-200 mb-6">
                <a href="?tab=pending" class="tab-btn <?= $current_tab == 'pending' ? 'active' : '' ?>">Pending Request</a>
                <a href="?tab=approved" class="tab-btn <?= $current_tab == 'approved' ? 'active' : '' ?>">Approved Request</a>
                <a href="?tab=borrowed" class="tab-btn <?= $current_tab == 'borrowed' ? 'active' : '' ?>">Currently Borrowed</a>
                <a href="?tab=unpaid" class="tab-btn <?= $current_tab == 'unpaid' ? 'active' : '' ?>">Fined</a>
                <a href="?tab=returned" class="tab-btn <?= $current_tab == 'returned' ? 'active' : '' ?>">Returned</a>
                <a href="?tab=cancelled" class="tab-btn <?= $current_tab == 'cancelled' ? 'active' : '' ?>">Cancelled</a>
                <a href="?tab=rejected" class="tab-btn <?= $current_tab == 'rejected' ? 'active' : '' ?>">Rejected</a>
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
                    <?php if ($current_tab == 'pending' || $current_tab == 'approved'): ?>
                                <tr><th>No</th><th>User Name</th><th>Book Title</th><th>Copies</th><th>Current Condition</th><th>Request Date</th><th>Pickup Date</th><th>Exp. Return Date</th><th>Actions</th></tr>
                    <?php elseif ($current_tab == 'borrowed'): ?>
                                <tr><th>No</th><th>User Name</th><th>Book Title</th><th>Copies</th><th>Exp. Return Date</th><th>Fine Amount</th><th>Fine Reason</th><th>Fine Status</th><th>Actions</th></tr>
                    <?php elseif ($current_tab == 'unpaid'): ?>
                                <tr><th>No</th><th>User Name</th><th>Book Title</th><th>Copies</th><th>Exp. Return Date</th><th>Fine Amount</th><th>Fine Reason</th><th>Fine Status</th><th>Actions</th></tr>
                    <?php elseif ($current_tab == 'returned'): ?>
                                <tr><th>No</th><th>User Name</th><th>Book Title</th><th>Copies</th><th>Return Date</th><th>Returned Condition</th><th>Fine Reason</th><th>Fine Amount</th><th>Fine Status</th><th>Actions</th></tr>
                    <?php elseif ($current_tab == 'cancelled' || $current_tab == 'rejected'): ?>
                                <tr><th>No</th><th>User Name</th><th>Book Title</th><th>Copies</th><th>Current Condition</th><th>Request Date</th><th>Exp. Return Date</th><th>Actions</th></tr>
                    <?php endif; ?>

                    <?php
                    $no = 1;
                    if (empty($borrow_details)): ?>
                                <tr><td colspan="10" class="text-center py-4 text-gray-500">No <?= strtolower(str_replace('d', 'd ', $current_tab)) ?> records found.</td></tr>
                    <?php else:
                        foreach ($borrow_details as $detail) {
                            $fullName = htmlspecialchars($detail["lName"] . ", " . $detail["fName"]);
                            $bookTitle = htmlspecialchars($detail["book_title"]);
                            $borrowID = $detail["borrowID"];
                            ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><?= $fullName ?></td>
                                                <td><?= $bookTitle ?></td>

                                                <?php if ($current_tab == 'pending'): ?>
                                                            <td><?= $detail["no_of_copies"] ?></td><td><?= $detail["book_condition"] ?></td><td><?= $detail["request_date"] ?></td><td><?= $detail["pickup_date"] ?></td><td><?= $detail["expected_return_date"] ?></td>
                                                            <td class="action text-center">
                                                                <?php if ($detail['userID'] == $_SESSION['user_id']): ?>
                                                                            <span class="text-xs text-gray-500 font-bold block mb-1 cursor-not-allowed opacity-50 border border-gray-300 rounded px-2 py-1" title="You cannot approve your own request">Accept (Restricted)</span>
                                                                <?php else: ?>
                                                                            <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1" href="../../../app/controllers/borrowDetailsController.php?tab=<?= $current_tab ?>&action=accept&id=<?= $borrowID ?>">Accept</a>
                                                                <?php endif; ?>
                                                                <button class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1 cursor-pointer open-modal-btn" data-target="rejectRequestModal" data-id="<?= $borrowID ?>" data-title="<?= $bookTitle ?>">Reject</button>
                                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                                            </td>
                                                <?php elseif ($current_tab == 'approved'): ?>
                                                            <td><?= $detail["no_of_copies"] ?></td><td><?= $detail["book_condition"] ?></td><td><?= $detail["request_date"] ?></td><td><?= $detail["pickup_date"] ?></td><td><?= $detail["expected_return_date"] ?></td>
                                                            <td class="action text-center">
                                                                <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1" href="../../../app/controllers/borrowDetailsController.php?tab=<?= $current_tab ?>&action=pickup&id=<?= $borrowID ?>">Claimed</a>
                                                                <button class="actionBtn bg-amber-500 hover:bg-amber-600 text-sm inline-block mb-1 cursor-pointer open-modal-btn" data-target="cancelRequestModal" data-id="<?= $borrowID ?>" data-title="<?= $bookTitle ?>">Cancel</button>
                                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                                            </td>
                                                <?php elseif ($current_tab == 'borrowed'): ?>
                                                    <td><?= $detail["no_of_copies"] ?></td>
                                                    <td><?= $detail["expected_return_date"] ?></td>
                                                    <td class="font-semibold <?= ($detail["calculated_fine"] > 0) ? 'text-red-700' : 'text-gray-700' ?>">₱<?= number_format($detail["calculated_fine"] ?? 0, 2) ?></td>
                                                    <td><?= $detail["fine_reason"] ?? 'N/A' ?></td>
                                                    <td class="<?= ($detail["calculated_fine"] > 0 && $detail["fine_status"] === 'Unpaid') ? 'text-red-700' : 'text-gray-700' ?>"><?= ($detail["calculated_fine"] > 0 && $detail["fine_status"] === 'Unpaid') ? "Unpaid" : "N/A" ?></td>
                                                    
                                                    <td class="action text-center">
                                                        <?php if ($detail["calculated_fine"] > 0): ?>
                                                            <div class="w-full">
                                                                <a href="borrowDetailsSection.php?tab=unpaid" class="block w-full text-center px-2 py-1 text-md bg-red-100 text-red-700 font-extrabold rounded">Fined</a>
                                                            </div>
                                                        <?php else: ?>
                                                            <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=return&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Returned</a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($detail["calculated_fine"] == 0): ?>
                                                        <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                        <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                                        <?php endif; ?>
                                                    </td>

                                            <?php elseif ($current_tab == 'unpaid'): ?>
                                                <td><?= $detail["no_of_copies"] ?></td>
                                                <td><?= $detail["expected_return_date"] ?></td>
                                                <td class="font-semibold text-red-700">₱<?= number_format($detail["calculated_fine"] ?? 0, 2) ?></td>
                                                <td><?= $detail["fine_reason"] ?? 'N/A' ?></td>
                                                <td class="text-red-700 font-bold">Unpaid</td>

                                                <td class="action text-center">
                                                    <a class="actionBtn bg-green-600 hover:bg-green-700 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=paid&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Paid</a>
                                                    
                                                    <?php if ($detail['account_status'] === 'Blocked'): ?>
                                                        <button class="actionBtn bg-yellow-600 hover:bg-green-700 text-sm inline-block mb-1 cursor-pointer open-modal-btn" data-target="unblockUserModal" data-id="<?= $borrowID ?>" data-user="<?= $fullName ?>">Unblock</button>
                                                    <?php else: ?>
                                                        <button class="actionBtn bg-yellow-600 hover:bg-yellow-700 text-sm inline-block mb-1 cursor-pointer open-modal-btn" data-target="blockUserModal" data-id="<?= $borrowID ?>" data-user="<?= $fullName ?>">Block User</button>
                                                    <?php endif; ?>

                                                    <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                    <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                                </td>
                                                <?php elseif ($current_tab == 'returned'): ?>
                                                            <td><?= $detail["no_of_copies"] ?></td><td><?= $detail["return_date"] ?? 'N/A' ?></td><td><?= $detail["returned_condition"] ?? 'N/A' ?></td><td><?= $detail["fine_reason"] ?? 'N/A' ?></td>
                                                           <td class="font-semibold text-red-700">
                                                                <?= empty($detail["calculated_fine"]) 
                                                                    ? 'N/A' 
                                                                    : '₱' . number_format($detail["calculated_fine"], 2) ?>
                                                            </td>

                                                            <td class="text-center font-semibold
                                                                <?= empty($detail['fine_status']) 
                                                                    ? '' 
                                                                    : (($detail['fine_status'] === 'Paid') 
                                                                        ? 'bg-green-100 text-green-700' 
                                                                        : 'bg-red-100 text-red-700') ?>">
                                                                <?= empty($detail['fine_status']) ? 'N/A' : $detail['fine_status'] ?>
                                                            </td>

                                                            <td class="action text-center">
                                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                                <a class="actionBtn bg-red-600 hover:bg-red-700 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=delete&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Delete</a>
                                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
                                                            </td>
                                                <?php elseif ($current_tab == 'cancelled' || $current_tab == 'rejected'): ?>
                                                            <td><?= $detail["no_of_copies"] ?></td><td><?= $detail["book_condition"] ?></td><td><?= $detail["request_date"] ?></td><td><?= $detail["expected_return_date"] ?></td>
                                                            <td class="action text-center">
                                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=edit&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Edit</a>
                                                                <a class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=delete&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">Delete</a>
                                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>&tab=<?= $current_tab ?>">View</a>
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
</div>

    <div id="rejectRequestModal" class="modal">
        <div class="modal-content max-w-md">
            <span class="close close-modal text-3xl cursor-pointer float-right">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-red-700">Reject Request</h2>
            <form action="../../../app/controllers/borrowDetailsController.php" method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                <input type="hidden" name="borrowID" id="reject_borrowID">

                <p class="mb-4 text-gray-700">Reason for rejecting request for <span class="font-bold book-title-span"></span>:</p>

                <div class="bg-gray-100 p-4 rounded mb-4 text-sm h-32 overflow-y-auto">
                    <?php if (empty($borrowRejectReasons)): ?>
                                <p class="text-gray-500 italic">No preset reasons available.</p>
                    <?php else: ?>
                                <?php foreach ($borrowRejectReasons as $reason): ?>
                                            <label class="flex items-center mb-2 cursor-pointer">
                                                <input type="checkbox" name="reason_presets[]" value="<?= $reason['reasonID'] ?>" class="mr-2"> 
                                                <?= htmlspecialchars($reason['reason_text']) ?>
                                            </label>
                                <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="flex items-center mb-1 border-t pt-2 mt-1">
                        <input type="checkbox" name="reason_presets[]" value="other" class="mr-2">
                        <label class="text-sm cursor-pointer font-semibold text-red-700">Others (Please specify below)</label>
                    </div>
                </div>

                <label class="font-semibold block mb-1">Other Reason:</label>
                <textarea name="reason_custom" rows="3" class="w-full border rounded p-2" placeholder="Type specific reason here..."></textarea>
                <input type="submit" value="Confirm Reject" class="mt-4 bg-red-700 text-white font-bold py-2 px-4 rounded w-full cursor-pointer hover:bg-red-800">
            </form>
        </div>
    </div>

    <div id="cancelRequestModal" class="modal">
        <div class="modal-content max-w-md">
            <span class="close close-modal text-3xl cursor-pointer float-right">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-amber-600">Cancel Request</h2>
            <form action="../../../app/controllers/borrowDetailsController.php" method="POST">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                <input type="hidden" name="borrowID" id="cancel_borrowID">

                <p class="mb-4 text-gray-700">Reason for cancelling request for <span class="font-bold book-title-span"></span>:</p>

                <div class="bg-gray-100 p-4 rounded mb-4 text-sm h-32 overflow-y-auto">
                    <?php if (empty($borrowCancelReasons)): ?>
                                <p class="text-gray-500 italic">No preset reasons available.</p>
                    <?php else: ?>
                                <?php foreach ($borrowCancelReasons as $reason): ?>
                                            <label class="flex items-center mb-2 cursor-pointer">
                                                <input type="checkbox" name="reason_presets[]" value="<?= $reason['reasonID'] ?>" class="mr-2"> 
                                                <?= htmlspecialchars($reason['reason_text']) ?>
                                            </label>
                                <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="flex items-center mb-1 border-t pt-2 mt-1">
                        <input type="checkbox" name="reason_presets[]" value="other" class="mr-2">
                        <label class="text-sm cursor-pointer font-semibold text-amber-700">Others (Please specify below)</label>
                    </div>
                </div>

                <label class="font-semibold block mb-1">Other Reason:</label>
                <textarea name="reason_custom" rows="3" class="w-full border rounded p-2" placeholder="Type specific reason here..."></textarea>
                <input type="submit" value="Confirm Cancel" class="mt-4 bg-amber-600 text-white font-bold py-2 px-4 rounded w-full cursor-pointer hover:bg-amber-700">
            </form>
        </div>
    </div>

    <div id="unblockUserModal" class="modal">
        <div class="modal-content max-w-md">
            <span class="close close-modal text-3xl cursor-pointer float-right">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-green-700">Unblock User</h2>
            <form action="../../../app/controllers/borrowDetailsController.php" method="POST">
                <input type="hidden" name="action" value="unblockUser">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                <input type="hidden" name="borrowID" id="unblock_borrowID">

                <p class="mb-4 text-gray-700">Reason for restoring access to <span class="font-bold user-name-span"></span>:</p>

                <div class="bg-gray-100 p-4 rounded mb-4 text-sm h-32 overflow-y-auto">
                    <?php if (empty($unblockReasons)): ?>
                                <p class="text-gray-500 italic">No preset reasons available.</p>
                    <?php else: ?>
                                <?php foreach ($unblockReasons as $reason): ?>
                                            <label class="flex items-center mb-2 cursor-pointer">
                                                <input type="checkbox" name="reason_presets[]" value="<?= $reason['reasonID'] ?>" class="mr-2"> 
                                                <?= htmlspecialchars($reason['reason_text']) ?>
                                            </label>
                                <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="flex items-center mb-1 border-t pt-2 mt-1">
                        <input type="checkbox" name="reason_presets[]" value="other" class="mr-2">
                        <label class="text-sm cursor-pointer font-semibold text-green-700">Others (Please specify below)</label>
                    </div>
                </div>

                <div class="flex justify-center flex-col">
                    <label class="font-semibold block mb-1">Additional Details:</label>
                    <textarea name="reason_custom" rows="3" class="w-full border rounded p-2" placeholder="Type specific details here..."></textarea>
                </div>
                <input type="submit" value="Confirm Unblock" class="mt-4 bg-green-600 text-white font-bold py-2 px-4 rounded w-full cursor-pointer hover:bg-green-700">
            </form>
        </div>
    </div>

    <div id="blockUserModal" class="modal <?= $open_modal == 'blockUserModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-md">
            <span class="close close-modal text-3xl cursor-pointer float-right" data-modal="blockUserModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-yellow-700">Block User</h2>
            <form action="../../../app/controllers/borrowDetailsController.php" method="POST">
                <input type="hidden" name="action" value="blockUser">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                <input type="hidden" name="borrowID" id="block_borrowID" value="<?= $borrow_id ?? '' ?>">

                <p class="mb-4 text-gray-700">Select reason for blocking <span class="font-bold user-name-span"><?= $modal_borrow_details['fName'] ?? '' ?></span>:</p>

                <div class="bg-gray-100 p-4 rounded mb-4 text-sm h-32 overflow-y-auto">
                    <?php if (empty($blockReasons)): ?>
                                <p class="text-gray-500 italic">No preset reasons available.</p>
                    <?php else: ?>
                                <?php foreach ($blockReasons as $reason): ?>
                                            <label class="flex items-center mb-2 cursor-pointer">
                                                <input type="checkbox" name="reason_presets[]" value="<?= $reason['reasonID'] ?>" class="mr-2"> 
                                                <?= htmlspecialchars($reason['reason_text']) ?>
                                            </label>
                                <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="flex items-center mb-1 border-t pt-2 mt-1">
                        <input type="checkbox" name="reason_presets[]" value="other" class="mr-2">
                        <label class="text-sm cursor-pointer font-semibold text-yellow-700">Others (Please specify below)</label>
                    </div>
                </div>

                <div class="flex justify-center flex-col">
                    <label class="font-semibold block mb-1">Additional Details:</label>
                    <textarea name="reason_custom" rows="3" class="w-full border rounded p-2" placeholder="Type specific details here..."></textarea>
                </div>
                <input type="submit" value="Confirm Block User" class="mt-4 bg-yellow-700 text-white font-bold py-2 px-4 rounded w-full cursor-pointer hover:bg-yellow-800">
            </form>
        </div>
    </div>

    <div id="viewFullDetailsModal" class="modal <?= $open_modal == 'viewFullDetailsModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-lg">
            <span class="close close-times" data-modal="viewFullDetailsModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-red-800">Borrow Detail</h2>
            <div class="grid grid-cols-2 gap-4 text-gray-700">
                <div class="col-span-2 border-b pb-2 mb-2"><h3 class="font-semibold text-lg text-red-700">Book & Borrower Information</h3></div>
                <div><p class="font-semibold">Borrower Name:</p><p><?= htmlspecialchars($modal_borrow_details['fName'] ?? 'N/A') . ' ' . htmlspecialchars($modal_borrow_details['lName'] ?? '') ?></p></div>
                <div><p class="font-semibold">Book Title:</p><p><?= htmlspecialchars($modal_borrow_details['book_title'] ?? 'N/A') ?></p></div>
                <div><p class="font-semibold">Book Condition:</p><p><?= htmlspecialchars($modal_borrow_details['book_condition'] ?? 'N/A') ?></p></div>
                <div><p class="font-semibold">Copies Requested:</p><p><?= $modal_borrow_details['no_of_copies'] ?? 'N/A' ?></p></div>

                <div class="col-span-2 border-b pt-4 pb-2 mb-2"><h3 class="font-semibold text-lg text-red-700">Timeline & Status</h3></div>
                <div><p class="font-semibold">Request Date:</p><p><?= $modal_borrow_details['request_date'] ?? 'N/A' ?></p></div>
                <div><p class="font-semibold">Pickup Date:</p><p><?= $modal_borrow_details['pickup_date'] ?? 'N/A' ?></p></div>
                <div><p class="font-semibold">Expected Return Date:</p><p><?= $modal_borrow_details['expected_return_date'] ?? 'N/A' ?></p></div>
                <div><p class="font-semibold">Actual Return Date:</p><p><?= $modal_borrow_details['return_date'] ?? 'N/A' ?></p></div>
                <div><p class="font-semibold">Request Status:</p><p class="font-bold text-blue-600"><?= $modal_borrow_details['borrow_request_status'] ?? 'N/A' ?></p></div>
                <div><p class="font-semibold">Borrow Status:</p><p class="font-bold text-blue-600"><?= $modal_borrow_details['borrow_status'] ?? 'N/A' ?></p></div>

                <div class="col-span-2 border-b pt-4 pb-2 mb-2"><h3 class="font-semibold text-lg text-red-700">Fine Details</h3></div>
                <div><p class="font-semibold">Fine Amount:</p><p class="font-bold text-red-600">₱<?= number_format($modal_borrow_details['fine_amount'] ?? 0, 2) ?></p></div>
                <div><p class="font-semibold">Fine Status:</p><p class="font-bold <?= ($modal_borrow_details['fine_status'] === 'Unpaid') ? 'text-red-600' : 'text-green-600' ?>"><?= $modal_borrow_details['fine_status'] ?? 'N/A' ?></p></div>
                <div class="col-span-2"><p class="font-semibold">Fine Reason:</p><p><?= $modal_borrow_details['fine_reason'] ?? 'N/A' ?></p></div>

                <?php if (!empty($latestStatus['admin_name']) || !empty($latestStatus['reasons']) || !empty($latestStatus['remarks'])): ?>
                            <div class="col-span-2 mt-4 bg-red-50 p-3 rounded border border-red-100">
                                <p class="font-bold text-red-800 text-sm mb-1">
                                    <?= !empty($latestStatus['action_type']) ? htmlspecialchars($latestStatus['action_type']) . ' Details:' : 'Last Action Details:' ?>
                                </p>
                        
                                <?php if (!empty($latestStatus['reasons'])): ?>
                                            <ul class="list-disc list-inside text-sm text-gray-700">
                                                <?php foreach ($latestStatus['reasons'] as $r): ?>
                                                            <li><?= htmlspecialchars($r) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                <?php endif; ?>
                        
                                <?php if (!empty($latestStatus['remarks'])): ?>
                                            <p class="text-sm text-gray-600 mt-1 italic">"<?= htmlspecialchars($latestStatus['remarks']) ?>"</p>
                                <?php endif; ?>
                        
                                <?php if (!empty($latestStatus['admin_name'])): ?>
                                            <p class="text-xs text-gray-500 mt-2 text-right">
                                                Processed by: <span class="font-semibold"><?= htmlspecialchars($latestStatus['admin_name']) ?></span>
                                                on <?= date('M d, Y h:i A', strtotime($latestStatus['date'])) ?>
                                            </p>
                                <?php endif; ?>
                            </div>
                <?php endif; ?>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" class="close viewBtn bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400" data-modal="viewFullDetailsModal" data-tab="<?= $current_tab ?>">Close</button>
            </div>
        </div>
    </div>

    <div id="editBorrowDetailModal" class="modal <?= $open_modal == 'editBorrowDetailModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close close-times" data-modal="editBorrowDetailModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Edit Borrow Detail</h2>
            <form id="editBorrowDetailForm" action="../../../app/controllers/borrowDetailsController.php?action=edit&id=<?= $borrow_id ?>" method="POST">
                <input type="hidden" name="borrowID" value="<?= $borrow_id ?>">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                <input type="hidden" name="userID" value="<?= $modal_borrow_details['userID'] ?? '' ?>">
                <input type="hidden" name="bookID" value="<?= $modal_borrow_details['bookID'] ?? '' ?>">
                <div class="grid grid-cols-2 gap-4">
                    <div class="input"><label>No. of Copies<span>*</span> : </label><input type="number" class="input-field !w-5/6" name="no_of_copies" value="<?= $modal_borrow_details["no_of_copies"] ?? "1" ?>"></div>
                    <div class="input"><label>Request Date<span>*</span> : </label><input type="date" class="input-field !w-5/6" name="request_date" value="<?= $modal_borrow_details["request_date"] ?? "" ?>"></div>
                    <div class="input"><label>Pickup Date: </label><input type="date" class="input-field !w-5/6" name="pickup_date" value="<?= $modal_borrow_details["pickup_date"] ?? "" ?>"></div>
                    <div class="input"><label>Exp. Return Date<span>*</span> : </label><input type="date" class="input-field !w-5/6" name="expected_return_date" value="<?= $modal_borrow_details["expected_return_date"] ?? "" ?>"></div>
                    <div class="input"><label>Actual Return Date: </label><input type="date" class="input-field !w-5/6" name="return_date" value="<?= $modal_borrow_details["return_date"] ?? "" ?>"></div>
                    <div class="input">
                        <label>Request Status<span>*</span> : </label>
                        <select name="borrow_request_status" class="input-field !w-5/6">
                            <option value="">---Select Status---</option>
                            <?php foreach (['Pending', 'Approved', 'Rejected', 'Cancelled'] as $s)
                                echo "<option value='$s' " . (($modal_borrow_details['borrow_request_status'] ?? '') == $s ? 'selected' : '') . ">$s</option>"; ?>
                        </select>
                    </div>
                    <div class="input">
                        <label>Borrow Status<span>*</span> : </label>
                        <select name="borrow_status" class="input-field !w-5/6">
                            <option value="">---Select Status---</option>
                            <?php foreach (['Borrowed', 'Returned'] as $s)
                                echo "<option value='$s' " . (($modal_borrow_details['borrow_status'] ?? '') == $s ? 'selected' : '') . ">$s</option>"; ?>
                        </select>
                    </div>
                    <div class="input">
                        <label>Returned Condition : </label>
                        <select name="returned_condition" class="input-field !w-5/6">
                            <option value="">---Select---</option>
                            <?php foreach ($condition_options as $o)
                                echo "<option value='$o' " . (($modal_borrow_details['returned_condition'] ?? '') == $o ? 'selected' : '') . ">$o</option>"; ?>
                        </select>
                    </div>
                    <div class="input"><label>Fine Amount: </label><input type="number" step="0.01" class="input-field !w-5/6" name="fine_amount" value="<?= $modal_borrow_details["fine_amount"] ?? "0.00" ?>"></div>
                    <div class="input"><label>Fine Reason: </label><select name="fine_reason" class="input-field !w-5/6"><option value="">---Select---</option><?php foreach (['Late', 'Lost', 'Damaged'] as $r)
                        echo "<option value='$r' " . (($modal_borrow_details['fine_reason'] ?? '') == $r ? 'selected' : '') . ">$r</option>"; ?></select></div>
                    <div class="input"><label>Fine Status: </label><select name="fine_status" class="input-field !w-5/6"><option value="">---Select---</option><?php foreach (['Unpaid', 'Paid'] as $s)
                        echo "<option value='$s' " . (($modal_borrow_details['fine_status'] ?? '') == $s ? 'selected' : '') . ">$s</option>"; ?></select></div>
                </div>
                <br>
                <div class="cancelConfirmBtns">
                    <button type="button" class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400" data-modal="editBorrowDetailModal" data-tab="<?= $current_tab ?>">Cancel</button>
                    <input type="submit" value="Save Changes" class="font-bold cursor-pointer mt-4 border-none rounded-lg bg-red-800 text-white p-2 w-full hover:bg-red-700">
                </div>
            </form>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal <?= $open_modal == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close close-times" data-modal="deleteConfirmModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">Are you sure you want to delete this record? This cannot be undone.</p>
            <div class="cancelConfirmBtns">
                <button type="button" class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400" data-modal="deleteConfirmModal">Cancel</button>
                <a href="../../../app/controllers/borrowDetailsController.php?action=delete&id=<?= $borrow_id ?>&tab=<?= $current_tab ?>" class="text-white px-4 py-2 rounded-lg font-semibold cursor-pointer bg-red-600 hover:bg-red-700">Confirm Delete</a>
            </div>
        </div>
    </div>

    <div id="returnBookModal" class="modal <?= $open_modal == 'returnBookModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-md">
            <span class="close close-times" data-modal="returnBookModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-green-700">Confirm Book Return</h2>
            <form action="../../../app/controllers/borrowDetailsController.php?action=return&id=<?= $borrow_id ?>" method="POST">
                <input type="hidden" name="borrowID" value="<?= $borrow_id ?>">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                <label class="block font-semibold mb-1">Returned Condition<span>*</span> :</label>
                <select name="returned_condition" class="input-field w-full p-2 border rounded-lg">
                    <option value="">---Select Condition---</option>
                    <?php foreach ($condition_options as $option)
                        echo "<option value='$option'>$option</option>"; ?>
                </select>
                <input type="submit" value="Confirm Return" class="font-bold cursor-pointer mt-6 border-none rounded-lg bg-green-700 text-white p-3 w-full hover:bg-green-800">
            </form>
        </div>
    </div>

    <div id="paidConfirmModal" class="modal <?= $open_modal == 'paidConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-lg">
            <span class="close close-times" data-modal="paidConfirmModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-green-700">Confirm Fine Payment & Return</h2>
            <form action="../../../app/controllers/borrowDetailsController.php?action=paid&id=<?= $borrow_id ?>" method="POST">
                <input type="hidden" name="borrowID" value="<?= $borrow_id ?>">
                <input type="hidden" name="current_tab" value="<?= $current_tab ?>">
                <label class="block font-semibold mb-1">Returned Condition<span>*</span> :</label>
                <select name="returned_condition" class="input-field w-full p-2 border rounded-lg">
                    <option value="">---Select Condition---</option>
                    <?php foreach ($condition_options as $option)
                        echo "<option value='$option'>$option</option>"; ?>
                </select>
                <div class="w-full grid grid-cols-[auto,1fr] gap-y-2 my-4 p-3 bg-gray-100 rounded-lg">
                    <p class="font-semibold">Fine Amount:</p><p class="font-bold text-red-700">₱<?= number_format($modal_borrow_details['fine_amount'] ?? 0, 2) ?></p>
                </div>
                <div class="cancelConfirmBtns">
                    <button type="button" class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400" data-modal="paidConfirmModal">Cancel</button>
                    <input type="submit" value="Confirm Paid" class="font-bold cursor-pointer mt-6 border-none rounded-lg bg-green-600 text-white p-3 w-full hover:bg-green-800">
                </div>
            </form>
        </div>
    </div>

    <script src="../../../public/assets/js/modal.js"></script>
    <script>
        document.querySelectorAll('.open-modal-btn').forEach(button => {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const borrowId = this.getAttribute('data-id');
                const modal = document.getElementById(targetId);
                if (modal) {
                    modal.style.display = 'block';
                    modal.classList.add('open');
                    if (targetId === 'rejectRequestModal') document.getElementById('reject_borrowID').value = borrowId;
                    if (targetId === 'cancelRequestModal') document.getElementById('cancel_borrowID').value = borrowId;
                    if (targetId === 'blockUserModal') document.getElementById('block_borrowID').value = borrowId;
                    if (targetId === 'unblockUserModal') document.getElementById('unblock_borrowID').value = borrowId;
                    const title = this.getAttribute('data-title');
                    const user = this.getAttribute('data-user');
                    if (title) modal.querySelector('.book-title-span').textContent = title;
                    if (user) modal.querySelector('.user-name-span').textContent = user;
                }
            });
        });
        document.querySelectorAll('.close-modal, .close, .close-times').forEach(span => {
            span.addEventListener('click', function () {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('open');
                }
            });
        });
    </script>
</body>
</html>