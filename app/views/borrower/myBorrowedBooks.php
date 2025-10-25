<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
$borrowObj = new BorrowDetails();

$userID = $_SESSION["user_id"];

// Determine the active tab from the URL, default to 'pending'
$active_tab = $_GET['tab'] ?? 'pending';
$status_filter = '';

// Set the database filter based on the active tab
switch ($active_tab) {
    case 'pending':
        $status_filter = 'Pending';
        $tab_title = 'Pending Loan Requests';
        break;
    case 'borrowed':
        // We fetch 'Approved' OR 'Borrowed' for this tab
        $status_filter = ['Approved', 'Borrowed'];
        $tab_title = 'Currently Borrowed Books';
        break;
    case 'returned':
        $status_filter = 'Returned';
        $tab_title = 'Loan History';
        break;
    case 'fined':
        $status_filter = 'Fined'; // Custom status used in the fetchUserBorrowDetails method
        $tab_title = 'Outstanding Fines';
        break;
    default:
        $status_filter = 'Pending';
        $tab_title = 'Pending Loan Requests';
        $active_tab = 'pending';
}


// --- Data Fetching Logic ---
$borrowed_books = [];

if (is_array($status_filter)) {
    // For 'Currently Borrowed' tab (Approved OR Borrowed)
    foreach ($status_filter as $status) {
        $borrowed_books = array_merge($borrowed_books, $borrowObj->fetchUserBorrowDetails($userID, $status));
    }
} else {
    // For Pending, Returned, and Fined tabs
    $borrowed_books = $borrowObj->fetchUserBorrowDetails($userID, $status_filter);
}

// Helper function to display the correct status class
function getStatusClass($status) {
    return match ($status) {
        'Pending' => 'bg-yellow-100 text-yellow-800',
        'Approved', 'Borrowed' => 'bg-green-100 text-green-800',
        'Returned' => 'bg-blue-100 text-blue-800',
        default => 'bg-gray-100 text-gray-800',
    };
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowed Books</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/borrower.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer1.css" />
</head>

<body class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-red-800">My Loan Status</h1>
            <p class="text-xl mt-2">Track your requested and borrowed items.</p>
        </header>

        <div class="bg-white p-6 rounded-xl shadow-lg">
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="?tab=pending"
                        class="<?= $active_tab == 'pending' ? 'border-red-700 text-red-800' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg transition duration-150">
                        Pending Books
                    </a>
                    <a href="?tab=borrowed"
                        class="<?= $active_tab == 'borrowed' ? 'border-red-700 text-red-800' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg transition duration-150">
                        Currently Borrowed
                    </a>
                    <a href="?tab=returned"
                        class="<?= $active_tab == 'returned' ? 'border-red-700 text-red-800' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg transition duration-150">
                        Returned Books
                    </a>
                    <a href="?tab=fined"
                        class="<?= $active_tab == 'fined' ? 'border-red-700 text-red-800' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg transition duration-150">
                        Fined Books
                    </a>
                </nav>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-4"><?= $tab_title ?></h2>

            <?php if (empty($borrowed_books)): ?>
                <div class="py-10 text-center bg-gray-100 rounded-lg">
                    <p class="text-lg text-gray-500">No books found in the 
                        <strong class="text-red-700"><?= strtolower($tab_title) ?></strong> section.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($borrowed_books as $book): ?>
                        <div class="flex items-start p-4 border border-gray-200 rounded-lg shadow-sm bg-white">
                            <div
                                class="flex-shrink-0 w-20 h-28 mr-4 shadow-md rounded-md overflow-hidden bg-gray-200 border-2 border-gray-100">
                                <?php if ($book['book_cover_dir']): ?>
                                    <img src="<?= "../../../" . htmlspecialchars($book['book_cover_dir']) ?>" alt="Cover"
                                        class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="flex items-center justify-center w-full h-full text-xs text-gray-500 text-center p-1">No Cover</div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-grow">
                                <h3 class="text-lg font-bold text-red-800 mb-1">
                                    <?= htmlspecialchars($book['book_title']) ?>
                                </h3>
                                <p class="text-sm text-gray-700"><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></p>
                                <p class="text-sm text-gray-700"><strong>Copies:</strong> <?= htmlspecialchars($book['no_of_copies']) ?></p>
                                <p class="text-sm text-gray-700 mt-2"><strong>Requested:</strong> <?= date('M d, Y', strtotime($book['request_date'])) ?></p>
                                
                                <div class="mt-3 space-y-1">
                                    <?php if ($active_tab == 'pending'): ?>
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusClass($book['borrow_request_status']) ?>">
                                            Loan Status: <?= htmlspecialchars($book['borrow_request_status']) ?>
                                        </span>
                                        <p class="text-sm text-gray-600">
                                            <strong>Expected Pickup:</strong> <?= date('M d, Y', strtotime($book['pickup_date'])) ?>
                                        </p>
                                    <?php elseif ($active_tab == 'borrowed'): ?>
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?= getStatusClass($book['borrow_request_status']) ?>">
                                            Status: <?= htmlspecialchars($book['borrow_request_status']) ?>
                                        </span>
                                        <p class="text-sm text-gray-600">
                                            <strong>Due Date:</strong> <span class="text-red-600 font-semibold"><?= date('M d, Y', strtotime($book['expected_return_date'])) ?></span>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            * Please return by the due date to avoid fines.
                                        </p>
                                    <?php elseif ($active_tab == 'returned'): ?>
                                        <p class="text-sm text-gray-600">
                                            <strong>Return Date:</strong> <?= $book['return_date'] ? date('M d, Y', strtotime($book['return_date'])) : 'N/A' ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <strong>Condition:</strong> <?= htmlspecialchars($book['returned_condition'] ?? 'N/A') ?>
                                        </p>
                                        <?php if ($book['fine_amount'] > 0): ?>
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                Fine Incurred: ₱<?= number_format($book['fine_amount'], 2) ?> (<?= htmlspecialchars($book['fine_status']) ?>)
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($active_tab == 'fined'): ?>
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Fine Status: <?= htmlspecialchars($book['fine_status']) ?>
                                        </span>
                                        <p class="text-lg text-red-800 font-bold mt-2">
                                            ₱<?= number_format($book['fine_amount'], 2) ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <strong>Reason:</strong> <?= htmlspecialchars($book['fine_reason']) ?>
                                        </p>
                                        <button class="mt-2 px-3 py-1 text-xs font-medium bg-red-700 text-white rounded hover:bg-red-800 transition">
                                            Pay Fine (Simulated)
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
    <script src="../../../public/assets/js/borrower.js"></script>
</body>

</html>