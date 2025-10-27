<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../../models/manageUsers.php");

$borrowObj = new BorrowDetails();
$userObj = new User();

//fetch user
$userID = $_SESSION["user_id"];
$user = $userObj->fetchUser($userID);

$active_tab = $_GET['tab'] ?? 'pending';

$status_filter = match ($active_tab) {
    'pending' => ['Pending', 'Approved'],
    'borrowed' => 'Borrowed',
    'returned' => ['Returned', 'Rejected', 'Cancelled'],
    'fined' => 'Fined',
    default => 'Pending',
};

$borrowed_books_raw = $borrowObj->fetchUserBorrowDetails($userID, $status_filter);

$borrowed_books = [];
//Includes the borrows per tab
if ($active_tab === 'returned') {
    $returned_books = array_filter($borrowed_books_raw, fn($book) => $book['borrow_request_status'] === 'Returned');
    $rejected_requests = array_filter($borrowed_books_raw, fn($book) => $book['borrow_request_status'] === 'Rejected');
    $cancelled_requests = array_filter($borrowed_books_raw, fn($book) => $book['borrow_request_status'] === 'Cancelled');

    // The main variable for the loop will be an array containing all three groups
    $borrowed_books = [
        'Returned' => $returned_books,
        'Rejected' => $rejected_requests,
        'Cancelled' => $cancelled_requests,
    ];
} elseif ($active_tab === 'pending') {
    $pending_requests = array_filter($borrowed_books_raw, fn($book) => $book['borrow_request_status'] === 'Pending');
    $approved_requests = array_filter($borrowed_books_raw, fn($book) => ($book['borrow_request_status'] === 'Approved' && $book['borrow_status'] !== 'Borrowed'));
    $rejected_requests = array_filter($borrowed_books_raw, fn($book) => $book['borrow_request_status'] === 'Rejected');
    $cancelled_requests = array_filter($borrowed_books_raw, fn($book) => $book['borrow_request_status'] === 'Cancelled');

    // Combine them back into a single array for the main loop, but grouped
    $borrowed_books = array_merge($pending_requests, $approved_requests, $rejected_requests, $cancelled_requests);

} else {
    // For all other tabs, $borrowed_books remains the simple array of results
    $borrowed_books = $borrowed_books_raw;
}

// Helper function to display the correct status class
function getStatusClass($status)
{
    return match ($status) {
        'Pending' => 'bg-yellow-100 text-yellow-800',
        'Approved' => 'bg-indigo-100 text-indigo-800',
        'Borrowed' => 'bg-green-100 text-green-800',
        'Rejected', 'Cancelled' => 'bg-red-100 text-red-800',
        'Returned' => 'bg-blue-100 text-blue-800',
        default => 'bg-gray-100 text-gray-800',
    };
}

function getTabTitle($tab)
{
    return match ($tab) {
        'pending' => 'Pending, Approved & Cancelled Requests',
        'borrowed' => 'Currently Borrowed Books',
        'returned' => 'Book Loan History', // Changed title to be more general
        'fined' => 'Unpaid Fines',
        default => 'Loan Status',
    };
}

$borrow_limit = (int) ($user["borrower_limit"] ?? 1);
$current_borrowed_count = $borrowObj->fetchTotalBorrowedBooks($userID); //fetch how many books were processed/borrowed
$available_slots = $borrow_limit - $current_borrowed_count;

// Handle success messages after actions (e.g., cancel)
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'cancelled') {
    $success_message = 'Your loan request has been successfully cancelled.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Loans - <?= getTabTitle($active_tab) ?></title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/borrower1.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer2.css" />
</head>

<body class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="title text-4xl sm:text-5xl font-extrabold text-white">My Borrowed Books</h1>
            <p class="text-xl mt-2"><?= getTabTitle($active_tab) ?></p>
        </header>

        <div class="bg-white p-6 rounded-xl shadow-lg">

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($success_message) ?></span>
                </div>
            <?php endif; ?>

            <div class="tabs border-b border-gray-200 mb-6">
                <nav class="mb-px flex space-x-8 font-bold" aria-label="Tabs">
                    <a href="?tab=pending" class="<?= $active_tab == 'pending' ? 'active' : '' ?>">Requests</a>
                    <a href="?tab=borrowed" class="<?= $active_tab == 'borrowed' ? 'active' : '' ?>">Currently
                        Borrowed</a>
                    <a href="?tab=fined" class="<?= $active_tab == 'fined' ? 'active' : '' ?>">Fines</a>
                    <a href="?tab=returned" class="<?= $active_tab == 'returned' ? 'active' : '' ?>">History</a>

                </nav>
            </div>
            <p class="text-right block font-bold text-red-800 mb-4">No. of Books Left to Borrow:
                <?= $available_slots ?>
            </p>

            <?php
            // Check if we are on the 'returned' tab AND if the combined data is empty
            $is_returned_empty = $active_tab === 'returned' &&
                empty($borrowed_books['Returned']) &&
                empty($borrowed_books['Rejected']) &&
                empty($borrowed_books['Cancelled']);

            // Check if any other tab data is empty
            $is_other_tab_empty = $active_tab !== 'returned' && empty($borrowed_books);

            if ($is_returned_empty || $is_other_tab_empty): ?>
                <div class="py-10 text-center bg-gray-100 rounded-lg">
                    <p class="text-lg text-gray-500">No books found in this section.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto list-container">

                    <?php
                    // New logic for 'returned' tab to loop through groups
                    $sections_to_display = $active_tab === 'returned' ? ['Returned', 'Rejected', 'Cancelled'] : ['_single_'];

                    if ($active_tab !== 'returned') {
                        // For non-history tabs, wrap the single list in a structure that the loop below can use
                        $loop_data = ['_single_' => $borrowed_books];
                    } else {
                        // For history tab, use the grouped data
                        $loop_data = $borrowed_books;
                    }
                    ?>

                    <?php foreach ($sections_to_display as $section_name):
                        $section_books = $loop_data[$section_name];
                        $is_history_section = $active_tab === 'returned';

                        // Only display the table if there are books in the section OR if it's not the history tab
                        if (!empty($section_books) || !$is_history_section):

                            // Display Section Header for History Tab
                            if ($is_history_section): ?>
                                <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4 border-b pb-2">
                                    <?= htmlspecialchars($section_name) ?> Items
                                </h2>
                                <?php if (empty($section_books)): ?>
                                    <div class="py-5 text-center bg-gray-50 rounded-lg mb-6">
                                        <p class="text-md text-gray-400">No <?= htmlspecialchars(strtolower($section_name)) ?> items found.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <table
                                class="<?= $is_history_section ? 'mt-4' : '' ?> table-auto-layout text-left whitespace-nowrap w-full">
                                <thead>
                                    <tr class="text-gray-600 border-b-2 border-red-700">
                                        <th class="py-3 px-4">Book Title</th>
                                        <th class="py-3 px-4 hidden sm:table-cell">Author</th>
                                        <th class="py-3 px-4">Copies</th>

                                        <?php if ($active_tab == 'pending'): ?>
                                            <th class="py-3 px-4 hidden md:table-cell">Request Date</th>
                                            <th class="py-3 px-4">PickUp Date</th>
                                            <th class="py-3 px-4">Exp. Return Date</th>
                                            <th class="py-3 px-4">Status</th>
                                            <th class="py-3 px-4 w-20">Actions</th>

                                        <?php elseif ($active_tab == 'borrowed'): ?>
                                            <th class="py-3 px-4 hidden md:table-cell">Exp. Return Date</th>
                                            <th class="py-3 px-4">Fine Amount</th>
                                            <th class="py-3 px-4 hidden lg:table-cell">Fine Reason</th>

                                        <?php elseif ($active_tab == 'fined'): ?>
                                            <th class="py-3 px-4 hidden md:table-cell">Exp. Return Date</th>
                                            <th class="py-3 px-4">Fine Amount</th>
                                            <th class="py-3 px-4">Fine Status</th>
                                            <th class="py-3 px-4">Fine Reason</th>

                                        <?php elseif ($active_tab == 'returned'): ?>
                                            <?php if ($section_name === 'Returned'): ?>
                                                <th class="py-3 px-4 hidden md:table-cell">Return Date</th>
                                                <th class="py-3 px-4 hidden lg:table-cell">Returned Condition</th>
                                                <th class="py-3 px-4">Fine Amount</th>
                                                <th class="py-3 px-4">Fine Status</th>
                                            <?php else: // Rejected or Cancelled ?>
                                                <th class="py-3 px-4 hidden md:table-cell">Request Date</th>
                                                <th class="py-3 px-4">Status</th>
                                                <!-- Processed Date is either return_date or the date set by the librarian -->
                                                <th class="py-3 px-4">Processed Date</th>
                                            <?php endif; ?>

                                            <th class="py-3 px-4 w-20">Details</th>
                                        <?php endif; ?>

                                    </tr>
                                </thead>
                                <tbody id="list-body">
                                    <?php foreach ($section_books as $book): ?>
                                        <tr class="border-b hover:bg-gray-50" data-borrow-id="<?= $book['borrowID'] ?>">

                                            <td class="py-4 px-4 text-red-800 font-bold max-w-xs">
                                                <div class="flex items-center space-x-3">
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
                                                    <span><?= htmlspecialchars($book['book_title']) ?></span>
                                                </div>
                                            </td>

                                            <td class="py-4 px-4 hidden sm:table-cell text-gray-700">
                                                <?= htmlspecialchars($book['author']) ?>
                                            </td>

                                            <td class="py-4 px-4 text-gray-700">
                                                <?= htmlspecialchars($book['no_of_copies']) ?>
                                            </td>

                                            <?php if ($active_tab == 'pending'): ?>
                                                <td class="py-4 px-4 hidden md:table-cell text-sm">
                                                    <?= date('M d, Y', strtotime($book['request_date'])) ?>
                                                    <br>
                                                </td>
                                                <td class="py-4 px-4 hidden md:table-cell text-sm">
                                                    <?= date('M d, Y', strtotime($book['pickup_date'])) ?>
                                                    <br>
                                                </td>
                                                <td class="py-4 px-4 hidden md:table-cell text-sm">
                                                    <?= date('M d, Y', strtotime($book['expected_return_date'])) ?>
                                                    <br>
                                                </td>
                                                <td class="py-4 px-4">
                                                    <span
                                                        class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusClass($book['borrow_request_status']) ?>">
                                                        <?= htmlspecialchars($book['borrow_request_status']) ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-4">
                                                    <?php if ($book['borrow_request_status'] == 'Pending' || $book['borrow_request_status'] == 'Approved'): ?>
                                                        <!-- FIX: Route borrower cancel through borrowBookController.php -->
                                                        <a class="px-2 py-1 rounded text-white bg-red-800 hover:bg-red-900 text-sm font-medium"
                                                            href="../../../app/controllers/borrowBookController.php?action=cancel&id=<?= $book['borrowID'] ?>">
                                                            Cancel
                                                        </a>
                                                    <?php elseif ($book['borrow_request_status'] == 'Cancelled'): ?>
                                                        <a class="px-2 py-1 rounded text-white bg-red-800 hover:bg-red-900 text-sm font-medium"
                                                            href="../../../app/controllers/borrowBookController.php?action=cancel&id=<?= $book['borrowID'] ?>">
                                                            Done
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-sm">N/A</span>
                                                    <?php endif; ?>
                                                </td>

                                            <?php elseif ($active_tab == 'borrowed'): ?>
                                                <td class="py-4 px-4 hidden md:table-cell text-sm">
                                                    <span
                                                        class="font-semibold <?= (new DateTime(date('Y-m-d')) > new DateTime($book['expected_return_date'])) ? 'text-red-600' : 'text-green-600' ?>">
                                                        <?= date('M d, Y', strtotime($book['expected_return_date'])) ?>
                                                    </span>
                                                    <?php if (new DateTime(date('Y-m-d')) > new DateTime($book['expected_return_date'])): ?>
                                                        <p class="text-xs font-bold text-red-700 mt-1">OVERDUE!</p>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-4 px-4 text-sm font-bold">
                                                    <?php if ($book['fine_amount'] > 0): ?>
                                                        <span class="text-red-800">₱<?= number_format($book['fine_amount'], 2) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-gray-500">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-4 px-4 hidden lg:table-cell text-xs">
                                                    <?= htmlspecialchars($book['fine_reason'] ?? 'N/A') ?>
                                                </td>

                                            <?php elseif ($active_tab == 'fined'): ?>
                                                <td class="py-4 px-4 hidden md:table-cell text-sm">
                                                    <span class="font-semibold text-red-600">
                                                        <?= date('M d, Y', strtotime($book['expected_return_date'])) ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-4 text-sm font-bold text-red-800">
                                                    ₱<?= number_format($book['fine_amount'], 2) ?>
                                                </td>
                                                <td class="py-4 px-4 text-sm">
                                                    <span class="font-semibold text-red-600">
                                                        <?= htmlspecialchars($book['fine_status'] ?? 'Unpaid') ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-4 text-xs">
                                                    <?= htmlspecialchars($book['fine_reason'] ?? 'N/A') ?>
                                                </td>\

                                            <?php elseif ($active_tab == 'returned'): ?>
                                                <?php if ($section_name === 'Returned'): ?>
                                                    <td class="py-4 px-4 hidden md:table-cell text-sm">
                                                        <?= $book['return_date'] ? date('M d, Y', strtotime($book['return_date'])) : 'N/A' ?>
                                                    </td>
                                                    <td class="py-4 px-4 hidden lg:table-cell text-sm">
                                                        <?= htmlspecialchars($book['returned_condition'] ?? 'N/A') ?>
                                                    </td>
                                                    <td class="py-4 px-4 text-sm">
                                                        <span
                                                            class="font-bold <?= ($book['fine_amount'] > 0) ? 'text-red-700' : 'text-gray-500' ?>">
                                                            ₱<?= number_format($book['fine_amount'], 2) ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-4 text-sm">
                                                        <span
                                                            class="font-semibold <?= ($book['fine_status'] === 'Paid') ? 'text-green-600' : 'text-gray-500' ?>">
                                                            <?= htmlspecialchars($book['fine_status'] ?? 'N/A') ?>
                                                        </span>
                                                    </td>
                                                <?php else: // Rejected or Cancelled ?>
                                                    <td class="py-4 px-4 hidden md:table-cell text-sm">
                                                        <?= date('M d, Y', strtotime($book['request_date'])) ?>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <span
                                                            class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusClass($book['borrow_request_status']) ?>">
                                                            <?= htmlspecialchars($book['borrow_request_status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <?= $book['return_date'] ? date('M d, Y', strtotime($book['return_date'])) : 'N/A' ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td class="py-4 px-4">
                                                    <button
                                                        class="px-2 py-1 rounded text-white bg-gray-600 hover:bg-gray-700 text-sm font-medium">
                                                        View
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; // End of check if section has books or is not history tab ?>
                    <?php endforeach; // End of section loop ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
    <script src="../../../public/assets/js/borrower.js"></script>
</body>

</html>