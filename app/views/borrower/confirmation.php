<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["errors"]);

require_once(__DIR__ . "/../../models/manageBook.php");
require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../../models/manageUsers.php");

$borrowObj = new BorrowDetails();
$bookObj = new Book();
$userObj = new User();

$bookID = $_GET['bookID'] ?? null;
$action = $_GET['action'] ?? null;
$is_list_checkout = ($action === 'list_checkout' && $_SERVER["REQUEST_METHOD"] === 'POST');

$userID = $_SESSION["user_id"];
$user = $userObj->fetchUser($userID);
$userTypeID = $user["userTypeID"];

$userName = $user ? $user["lName"] . ", " . $user["fName"] : 'Borrower Name Not Found';
//fetch user_type infos
$userTypeName = $user["type_name"];
$borrow_limit = (int) ($user["borrower_limit"] ?? 1);
$borrow_period = (int) ($user["borrower_period"] ?? 7);


$pickup_date = trim(htmlspecialchars($_POST['pickup_date'] ?? date("Y-m-d")));
$expected_return_date = trim(htmlspecialchars($_POST['expected_return_date'] ?? date("Y-m-d", strtotime("+$borrow_period days"))));


$current_borrowed_count = $borrowObj->fetchTotalBorrowedBooks($userID);
$available_slots = $borrow_limit - $current_borrowed_count;


// Borrowing restrictions 
function calculateMaxCopiesAllowed($userTypeID, $borrow_limit, $current_borrowed_count, $max_available, $is_borrowed)
{
    $available_slots = $borrow_limit - $current_borrowed_count;

    if ($available_slots <= 0) {
        return 0; // No available slots overall
    }

    if ($userTypeID == 1 || $userTypeID == 3) { // Student, Guest: Max 1 copy of any single book
        if ($is_borrowed) {
            return 0; // Already borrowed this specific book (must return it first)
        }
        // Limit to 1 copy, available slots, and stock
        return min(1, $available_slots, $max_available);
    }

    if ($userTypeID == 2) {
        if ($is_borrowed) {
            return 0;
        }

        // Allowed by stock and overall limit
        return min($max_available, $available_slots);
    }

    return 0; // Default safety
}



$books_to_checkout = [];
$total_requested_copies = 0;
// For Multiple list borrow
if ($is_list_checkout) {
    // Process List Data received from myList.php POST
    $list_items_post = $_POST['list_data'] ?? [];

    if (empty($list_items_post)) {
        $errors['list_empty'] = "No books selected for list checkout.";
    } else {
        foreach ($list_items_post as $listID => $item) {
            $bookID_local = (int) ($item['bookID'] ?? 0);
            $no_of_copies = (int) ($item['copies_requested'] ?? 1);
            $max_available = (int) ($item['book_copies'] ?? 0);

            // Fetch current borrowing status for the *actual* book
            $is_borrowed = $borrowObj->isBookBorrowed($userID, $bookID_local);

            // Removed $borrow_many_copies
            $max_copies_allowed = calculateMaxCopiesAllowed($userTypeID, $borrow_limit, $current_borrowed_count, $max_available, $is_borrowed);

            // Clamp requested copies against restrictions and availability
            $final_copies = min($no_of_copies, $max_available, max(0, $max_copies_allowed));

            if ($final_copies > 0) {
                $books_to_checkout[$listID] = [
                    'listID' => $listID,
                    'bookID' => $bookID_local,
                    'book_title' => $item['book_title'],
                    'author' => $item['author'],
                    'book_condition' => $item['book_condition'],
                    'book_cover_dir' => $item['book_cover_dir'],
                    'copies_requested' => $final_copies,
                    'max_allowed' => $max_copies_allowed,
                    'max_available' => $max_available,
                ];
                $total_requested_copies += $final_copies;
            } else {
                $errors['list_restriction_' . $bookID_local] = "Cannot borrow '{$item['book_title']}' due to borrowing limits, multi-copy restriction, or unavailability.";
            }
        }
    }

    // Total copies requested can't exceed total available slots
    if ($total_requested_copies > $available_slots) {
        $errors['total_limit'] = "The total number of books requested ({$total_requested_copies}) exceeds your available slots ({$available_slots}).";
        if (!empty($errors['total_limit'])) {
            $books_to_checkout = [];
        }
    }

} else {
    // Single Book Borrow 
    $no_of_copies = (int) ($_GET['copies'] ?? 1);

    if ($bookID) {
        $book = $bookObj->fetchBook($bookID);
    }

    if (!$book) {
        $errors['book_not_found'] = "Book details could not be loaded.";
    } else {
        $max_available = (int) $book['book_copies'];
        $is_borrowed = $borrowObj->isBookBorrowed($userID, $bookID);

        // UPDATED CALL: Removed $borrow_many_copies
        $max_copies_allowed = calculateMaxCopiesAllowed($userTypeID, $borrow_limit, $current_borrowed_count, $max_available, $is_borrowed);

        // Final check: clamp the requested copies
        $final_copies = min($no_of_copies, $max_available, max(0, $max_copies_allowed));

        // Also ensure it doesn't exceed total available slots (which should be handled by max_copies_allowed but is a good final check)
        if ($final_copies > $available_slots) {
            $final_copies = $available_slots;
        }


        if ($final_copies <= 0) {
            if ($max_available <= 0) {
                $errors['availability'] = "All copies of this book are currently borrowed.";
            } else {
                $errors['borrow_limit'] = "You cannot borrow any more books at this time due to your current borrow status or limit (You have {$current_borrowed_count} out of {$borrow_limit} allowed).";
            }
        } else {
            // Book is valid for checkout
            $books_to_checkout[$bookID] = [
                'listID' => 0,
                'bookID' => $bookID,
                'book_title' => $book['book_title'],
                'author' => $book['author_names'],
                'book_condition' => $book['book_condition'],
                'book_cover_dir' => $book['book_cover_dir'],
                'copies_requested' => $final_copies,
                'max_allowed' => $max_copies_allowed,
                'max_available' => $max_available,
            ];
            $total_requested_copies = $final_copies;
        }
    }
}

// Final check after processing all items
if (empty($books_to_checkout) && empty($errors)) {
    $errors['general_error'] = "No items could be confirmed for checkout.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Borrowing</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/borrower.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
    <style>
        #expected_return_date_display {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: transparent;
            border: none;
            padding: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: #1f2937;
            width: auto;
        }

        .borrow-form-container {
            max-width: 50rem;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="title text-4xl sm:text-5xl font-extrabold text-gray-900">Book Borrowing Confirmation</h1>
            <p class="text-lg mt-2 text-white">Review the details and confirm your borrow request below.</p>
        </header>

        <?php if (!empty($errors) || empty($books_to_checkout)): ?>
                <div class="borrow-form-container mb-12 bg-white p-8 rounded-xl shadow-2xl border-t-8 border-red-900">
                    <div class="bg-red-50 border border-red-400 text-red-700 px-6 py-4 rounded-lg relative" role="alert">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 102 0V7zm-1 9a1 1 0 100-2 1 1 0 000 2z"
                                    clip-rule="evenodd" />
                            </svg>
                            <strong class="font-bold text-lg">Borrow Request Denied</strong>
                        </div>
                        <ul class="list-disc ml-10 mt-3 space-y-1">
                            <?php foreach ($errors as $error): ?>
                                    <li>
                                        <?= $error ?>
                                    </li>
                            <?php endforeach; ?>
                            <?php if (empty($books_to_checkout) && !$is_list_checkout): ?>
                                    <li>No book selected or available for checkout.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="flex justify-center mt-8">
                        <a href="<?= $is_list_checkout ? 'myList.php' : 'catalogue.php' ?>"
                            class="inline-block px-8 py-3 bg-red-700 text-white font-semibold rounded-full hover:bg-red-600 transition shadow-lg transform hover:scale-105">
                            &larr; Return to
                            <?= $is_list_checkout ? 'My List' : 'Catalogue' ?>
                        </a>
                    </div>
                </div>
        <?php else: ?>

                <form method="POST"
                    action="../../../app/controllers/borrowBookController.php?action=<?= $is_list_checkout ? 'add_multiple' : 'add' ?>"
                    class="borrow-form-container">
                    <input type="hidden" name="userID" value="<?= $userID ?>">
                    <input type="hidden" name="is_list_checkout" value="<?= $is_list_checkout ? '1' : '0' ?>">

                    <div class="bg-white p-8 rounded-xl shadow-2xl border-t-8 border-red-900 space-y-10">

                        <div class="border border-gray-200 rounded-lg p-6 bg-red-50/50">
                            <h2 class="text-2xl font-bold text-red-800 mb-4 pb-2 border-b border-red-200">Borrower Summary
                                & Borrow Limits</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4 text-gray-700">
                                <div>
                                    <strong class="block text-sm font-medium text-gray-500">User Name</strong>
                                    <span class="text-xl font-bold text-gray-900">
                                        <?= $userName ?>
                                    </span>
                                </div>
                                <div>
                                    <strong class="block text-sm font-medium text-gray-500">Total Books Requested</strong>
                                    <span class="text-3xl font-extrabold text-green-700">
                                        <?= $total_requested_copies ?>
                                    </span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t pt-4 mt-2">
                                <div>
                                    <strong class="block text-sm font-medium text-gray-500">User Type: </strong>
                                    <span class="text-lg font-semibold text-gray-800">
                                        <?= $userTypeName ?>
                                    </span>
                                </div>
                                <div>
                                    <strong class="block text-sm font-medium text-gray-500">Borrowing Capacity</strong>
                                    <span class="text-lg font-semibold text-gray-800">
                                        <?= $current_borrowed_count ?> out of
                                        <?= $borrow_limit ?> total slots used. <br>
                                        <span class="text-sm text-gray-600">(<?= $borrow_period ?> days borrow
                                            period)</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                            <h2 class="text-2xl font-bold text-red-800 mb-4 pb-2 border-b border-gray-200">Date Confirmation
                            </h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <strong class="block text-sm font-medium text-gray-700 mb-2">Expected Pickup Date
                                    </strong>
                                    <input type="date" name="pickup_date" id="pickup_date" value="<?= $pickup_date ?? "" ?>"
                                        min="<?= $pickup_date ?>"
                                        class="w-full p-3 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 text-lg font-semibold transition duration-150 ease-in-out">
                                </div>
                                <div>
                                    <strong class="block text-sm font-medium text-gray-700 mb-2">Expected Return Date
                                    </strong>
                                    <input type="date" name="expected_return_date" id="expected_return_date_display"
                                        value="<?= $expected_return_date ?? "" ?>" readonly>
                                    <span class="text-xs text-gray-500 block mt-1">Borrow period is
                                        <?= $borrow_period ?> days.</span>
                                </div>
                            </div>
                        </div>


                        <div class="border border-gray-200 rounded-lg p-6 bg-white">
                            <h2 class="text-2xl font-bold text-red-800 mb-6 pb-2 border-b border-gray-200">Items for Borrow
                            </h2>
                            <div class="space-y-6">
                                <?php foreach ($books_to_checkout as $key => $book_data): ?>
                                        <div
                                            class="flex flex-col sm:flex-row items-start gap-5 p-4 border border-gray-100 rounded-xl bg-gray-50 hover:shadow-md transition duration-200">
                                            <div
                                                class="flex-shrink-0 w-20 h-28 shadow-lg rounded-md overflow-hidden bg-gray-200 border-2 border-red-300/50">
                                                <?php
                                                if ($book_data['book_cover_dir']) { ?>
                                                        <img src="<?= "../../../" . $book_data['book_cover_dir'] ?>"
                                                            alt="<?= $book_data['book_title'] ?> Cover" class="w-full h-full object-cover">
                                                <?php } else { ?>
                                                        <div
                                                            class="flex items-center justify-center w-full h-full text-xs text-gray-500 text-center p-1">
                                                            No Cover
                                                        </div>
                                                <?php } ?>
                                            </div>

                                            <div class="flex-grow">
                                                <h3 class="text-xl font-bold text-gray-900 line-clamp-1">
                                                    <?= $book_data['book_title'] ?>
                                                </h3>
                                                <p class="text-sm text-gray-600">by <span class="font-medium">
                                                        <?= $book_data['author'] ?>
                                                    </span></p>
                                                <p class="text-md text-gray-700">
                                                    <strong>Condition:</strong>
                                                    <span class="font-semibold text-red-700">
                                                        <?= $book_data['book_condition'] ?>
                                                    </span>
                                                </p>
                                                <p class="text-md text-gray-700 pt-1">
                                                    <strong>Stock:</strong>
                                                    <span class="font-semibold text-gray-700">
                                                        <?= $book_data['max_available'] ?>
                                                    </span>
                                                </p>

                                                <p class="mt-1">
                                                    <strong class="text-md text-gray-700 mr-2">Total Copies Requested:</strong>
                                                    <?php if ($is_list_checkout): ?>
                                                            <span
                                                                class="font-extrabold text-lg text-red-800"><?= $book_data['copies_requested'] ?></span>
                                                            <span class="text-xs text-gray-500 ml-2">(Max Allowed:
                                                                <?= $book_data['max_allowed'] ?>)</span>

                                                            <input type="hidden" name="book_requests[<?= $key ?>][bookID]"
                                                                value="<?= $book_data['bookID'] ?>">
                                                            <input type="hidden" name="book_requests[<?= $key ?>][listID]"
                                                                value="<?= $book_data['listID'] ?>">
                                                            <input type="hidden" name="book_requests[<?= $key ?>][copies_requested]"
                                                                value="<?= $book_data['copies_requested'] ?>">
                                                    <?php elseif ($userTypeID == 2): ?>
                                                            <span
                                                                class="font-extrabold text-lg text-red-800"><?= $book_data['copies_requested'] ?></span>
                                                            <span class="text-xs text-gray-500 ml-2">(Max Allowed:
                                                                <?= $book_data['max_allowed'] ?>)</span>
                                                            <input type="hidden" name="bookID" value="<?= $book_data['bookID'] ?>">
                                                            <input type="hidden" name="copies" id="copies_input"
                                                                value="<?= $book_data['copies_requested'] ?>">
                                                    <?php else: // Single checkout, Non-Staff (limited to 1) ?>
                                                            <span class="font-extrabold text-3xl text-red-800"
                                                                id="copies_display"><?= $book_data['copies_requested'] ?></span>
                                                            <span class="text-xs text-gray-500 ml-2">(Max: 1)</span>
                                                            <input type="hidden" name="bookID" value="<?= $book_data['bookID'] ?>">
                                                            <input type="hidden" name="copies" value="<?= $book_data['copies_requested'] ?>">
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                <?php endforeach; ?>
                            </div>
                        </div>


                        <div class="p-4 border border-red-200 bg-red-50 rounded-lg">
                            <h3 class="text-xl font-bold text-red-800 mb-3 border-b border-red-200 pb-2">Terms and Conditions
                            </h3>
                            <ul class="list-disc ml-5 text-gray-700 space-y-2 text-base">
                                <li>Borrowed items must be returned on or before the <strong>Expected Return Date</strong>.
                                </li>
                                <li><strong>Late Return Penalty:</strong> A fine of <strong class="text-red-700">₱5.00 per
                                        day</strong> will be incurred for each item returned past the due date.</li>
                                <li><strong>Damage or Loss:</strong> Borrower is responsible for replacement/repair costs of
                                    damaged or lost books.</li>
                                <li><strong>Non-Compliance:</strong> May result in the suspension of borrowing privileges.</li>
                            </ul>
                            <div class="mt-6 flex items-start">
                                <input type="checkbox" id="agree_terms" name="agree_terms" required
                                    class="mt-1 h-5 w-5 text-red-600 border-gray-300 rounded focus:ring-red-500 cursor-pointer">
                                <label for="agree_terms" class="ml-3 block text-base font-medium text-gray-900">
                                    I confirm that I have read and agree to the Terms and Conditions listed above.
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                            <a href="<?= $is_list_checkout ? 'myList.php' : 'catalogue.php' ?>"
                                class="px-8 py-3 bg-gray-200 text-gray-700 font-semibold rounded-full hover:bg-gray-300 transition shadow-md">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-8 py-3 bg-green-600 text-white font-semibold rounded-full hover:bg-green-700 transition shadow-lg transform hover:scale-105">
                                Confirm and Borrow
                            </button>
                        </div>

                    </div>
                </form>
        <?php endif; ?>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
    <script src="../../../public/assets/js/borrower.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pickupDateInput = document.getElementById('pickup_date');
            const expectedreturnDateInput = document.getElementById('expected_return_date_display');
            const copiesSelectable = document.getElementById('no_of_copies');
            const copiesHiddenInput = document.getElementById('copies_input');
            const maxBorrowDays = <?= $borrow_period ?>;


            // Update hidden copies input when the selectable input changes (Staff only in single mode)
            if (copiesSelectable && copiesHiddenInput) {
                copiesSelectable.addEventListener('input', function () {
                    // This is used for single book checkout only
                    copiesHiddenInput.value = this.value;
                });
            }

            function formatDateToISO(date) {
                // Get month and day, ensuring they are zero-padded if single digit
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                return `${date.getFullYear()}-${month}-${day}`;
            }

            function calculateReturnDate() {
                const pickupDateValue = pickupDateInput.value;
                if (!pickupDateValue) return;

                // Use 'yyyy-mm-dd' format for correct date parsing (standard for input[type=date])
                const date = new Date(pickupDateValue.replace(/-/g, '/')); // Handle cross-browser date parsing

                // Set the date for calculation
                date.setDate(date.getDate() + maxBorrowDays); // Add max borrow days

                // Set the value in the date input (which requires YYYY-MM-DD format)
                expectedreturnDateInput.value = formatDateToISO(date);
            }

            // --- Original Max Pickup Date Logic (Kept for Context) ---
            const today = new Date();
            const maxPickupDate = new Date();
            maxPickupDate.setDate(today.getDate() + 2); // Allow pickup up to 3 days in the future

            const maxDateString = formatDateToISO(maxPickupDate); // Use the new function
            pickupDateInput.setAttribute('max', maxDateString);
            // ---------------------------------------------------------

            // Recalculate return date whenever the pickup date changes
            pickupDateInput.addEventListener('change', calculateReturnDate);

            // Initial calculation (to ensure the date is correctly set on load)
            calculateReturnDate();
        });
    </script>
</body>

</html>