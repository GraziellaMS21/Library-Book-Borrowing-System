<?php
session_start();

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../../models/manageUsers.php");
require_once(__DIR__ . "/../../models/manageBook.php");

$borrowObj = new BorrowDetails();
$userObj = new User();
$bookObj = new Book();

// --- Core Data Fetching ---
$userID = $_SESSION["temp_blocked_user_id"] ?? null;
unset($_SESSION["temp_blocked_user_id"]);
$user = $userObj->fetchUser($userID);

// 1. Fetch all records that currently have an 'unpaid' fine status
$borrowed_books = $borrowObj->fetchUserBorrowDetails($userID, 'unpaid');
// ----------------------------

$books_to_display = [];

// 2. Iterate through the records to dynamically calculate any overdue fines
if (!empty($borrowed_books)) {
    foreach ($borrowed_books as $detail) {
        $db_update_required = false;

        // Only check for overdue fine calculation on 'Borrowed' loans that haven't been returned
        // The original fetch might include loans that have been returned but still have an unpaid fine.
        if ($detail['borrow_status'] === 'Borrowed' && $detail['return_date'] === null) {

            // Calculate fine based on today's date
            $fine_results = $borrowObj->calculateFinalFine(
                $detail['expected_return_date'],
                date("Y-m-d"), // Today's date for comparison
                $bookObj,
                $detail['bookID']
            );

            // Check if a new or higher fine is calculated
            if ($fine_results['fine_amount'] > $detail['fine_amount']) {
                $detail['fine_amount'] = $fine_results['fine_amount'];
                $detail['fine_reason'] = $fine_results['fine_reason'];
                $detail['fine_status'] = $fine_results['fine_status'];

                $db_update_required = true;
            }
        }

        // If a higher fine was calculated or status changed, update the DB
        if ($db_update_required) {
            $borrowObj->updateFineDetails(
                $detail['borrowID'],
                $detail['fine_amount'],
                $detail['fine_reason'],
                $detail['fine_status']
            );
        }

        // Since we only fetched 'unpaid' records, we only need to check the final status.
        // This ensures any loan whose fine was just updated to 'Paid' (unlikely on this page, but safe) is excluded.
        if ($detail['fine_status'] === 'Unpaid') {
            $books_to_display[] = $detail;
        }
    }
}

// 3. Helper functions (Simplified, only keeping necessary fine-related classes)
function getStatusClass($status)
{
    return match ($status) {
        'Unpaid' => 'bg-red-200 text-red-800 font-bold',
        'Paid' => 'bg-green-200 text-green-800',
        default => 'bg-gray-100 text-gray-800',
    };
}

// No need for getTabTitle, we'll hardcode the title
$total_unpaid_fines = array_sum(array_column($books_to_display, 'fine_amount'));


$success_message = '';
// Removed success messages related to request cancellation

// Removed unnecessary borrow limit checks
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-width=1.0">
    <title>Unpaid Fines</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/borrower.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
</head>

<body class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="title text-4xl sm:text-5xl font-extrabold text-white">Account Blocked: Unpaid Fines</h1>
            <p class="text-xl mt-2 text-red-100 font-semibold">Please settle these fines to restore your account access.
            </p>
        </header>

        <div class="bg-white p-6 rounded-xl shadow-lg">

            <?php if (empty($books_to_display)): ?>
                    <div class="py-10 text-center bg-gray-100 rounded-lg">
                        <p class="text-lg text-gray-500">
                            No **Unpaid Fines** currently found. Please contact the administrator for more details on your
                            blocked account status.
                        </p>
                        <a href="../../app/views/borrower/login.php"
                            class="mt-4 inline-block bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                            Return to Login
                        </a>
                    </div>
            <?php else: ?>

                    <div
                        class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 flex justify-between items-center">
                        <span class="text-xl font-bold">Total Unpaid Fine Amount:</span>
                        <span class="text-3xl font-extrabold">₱<?= number_format($total_unpaid_fines, 2) ?></span>
                    </div>

                    <div class="overflow-x-auto list-container">
                        <table class="table-auto-layout text-left whitespace-nowrap w-full">
                            <thead>
                                <tr class="text-gray-600 border-b-2 border-red-700">
                                    <th class="py-3 px-4">Book Title</th>
                                    <th class="py-3 px-4 hidden sm:table-cell">Author</th>
                                    <th class="py-3 px-4">Copies</th>
                                    <th class="py-3 px-4 hidden md:table-cell">Request Date</th>
                                    <th class="py-3 px-4">Fine Reason</th>
                                    <th class="py-3 px-4">Fine Amount</th>
                                    <th class="py-3 px-4">Fine Status</th>
                                    <th class="py-3 px-4 w-20">Details</th>
                                </tr>
                            </thead>
                            <tbody id="list-body">
                                <?php foreach ($books_to_display as $book): ?>
                                        <tr class="border-b hover:bg-gray-50" data-borrow-id="<?= $book['borrowID'] ?>">
                                            <td class="py-4 px-4 text-red-800 font-bold max-w-xs">
                                                <div class="flex items-center space-x-2 min-w-0">
                                                    <div
                                                        class="w-12 h-16 shadow-md rounded-sm overflow-hidden bg-gray-200 border flex-shrink-0 hidden sm:block">
                                                        <?php if ($book['book_cover_dir']): ?>
                                                                <img src="<?= "../../../" . htmlspecialchars($book['book_cover_dir']) ?>"
                                                                    alt="Cover" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                                <div
                                                                    class="flex items-center justify-center w-full h-full text-xs text-gray-500 text-center p-1">
                                                                    N/A
                                                                </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="break-words whitespace-normal overflow-hidden">
                                                        <?= htmlspecialchars($book['book_title']) ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="py-4 px-4 hidden sm:table-cell text-gray-700 whitespace-normal">
                                                <?= htmlspecialchars($book['author']) ?>
                                            </td>

                                            <td class="py-4 px-4 text-gray-700">
                                                <?= htmlspecialchars($book['no_of_copies']) ?>
                                            </td>


                                            <td class="py-4 px-4 hidden md:table-cell text-sm">
                                                <?= date('M d, Y', strtotime($book['request_date'])) ?>
                                            </td>
                                            <td class="py-4 px-4 text-sm">
                                                <?= htmlspecialchars($book['fine_reason'] ?? 'N/A') ?>
                                            </td>
                                            <td class="py-4 px-4 text-sm font-bold">
                                                <span class="text-red-800">₱<?= number_format($book['fine_amount'], 2) ?></span>
                                            </td>
                                            <td class="py-4 px-4 text-sm">
                                                <span
                                                    class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusClass($book['fine_status']) ?>">
                                                    <?= htmlspecialchars($book['fine_status'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4">
                                                <button
                                                    class="px-2 py-1 rounded text-white bg-gray-600 hover:bg-gray-700 text-sm font-medium">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
    <script src="../../../public/assets/js/borrower.js"></script>
</body>

</html>