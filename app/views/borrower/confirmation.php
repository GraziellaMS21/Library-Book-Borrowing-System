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
$borrowObj = new BorrowDetails();
$bookObj = new Book();

$bookID = $_GET['bookID'] ?? null;

$no_of_copies = (int) ($_GET['copies'] ?? 1);

$userID = $_SESSION["user_id"];
$borrower = $borrowObj->fetchUser($userID);
$userTypeID = $borrower["userTypeID"];


$userName = $borrower ? $borrower["lName"] . " " . $borrower["fName"] : 'Borrower Name Not Found';
//fetch user_type infos
$userTypeName = $borrower["type_name"];
$borrow_limit = $borrower["borrower_limit"];
$borrow_period = $borrower["borrower_period"];


$pickup_date = date("Y-m-d");
$expected_return_date = date("Y-m-d", strtotime("+$borrow_period days"));


// Fetch book data
$book = null;
if ($bookID) {
    $book = $bookObj->fetchBook($bookID);
}
$max_available = (int) $book['book_copies'];

$current_borrowed_count = $borrowObj->getTotalCurrentlyBorrowedCount($userID); //fetch how many books were processed/borrowed
$borrow_many_copies = $borrowObj->hasManyCopyBooks($userID); //fetch no of copies borrowed by the user
$is_borrowed = $borrowObj->isBookBorrowed($userID, $bookID); //fetch if the book was already borrowed

// Restriction for the maximum copies allowed
$max_copies_allowed = 1; // Default for Students and Guests
if ($userTypeID == 2) {
    // Staff can try to borrow up to the available stock or their limit (whichever is lower)
    $max_copies_allowed = min($max_available, $borrow_limit);
} else {
    // Student (1) and Guest (3) are restricted to 1 copy total of any *single* book
    $max_copies_allowed = 1;
}


//Restriction for Students (1) and Guests (3)
if ($userTypeID == 1 || $userTypeID == 3) {
    if ($is_borrowed) {
        $errors['borrow_restriction'] = "You cannot borrow multiple copies of the same book. Please return the existing copy first.";
    }
    // Also limit by the general borrower_limit (Total borrowed + requested copies)
    $available_slots = $borrow_limit - $current_borrowed_count;

    if ($available_slots <= 0) {
        $errors['borrow_limit'] = "You have reached your total borrowing limit of {$borrow_limit} books. You currently have {$current_borrowed_count} books requested or checked out.";
    }

    // Clamp max_copies_allowed by available slots (which is max 1 for this group anyway)
    $max_copies_allowed = min($max_copies_allowed, max(0, $available_slots));
}


// Restriction for Staff
if ($userTypeID == 2) {
    $available_slots = $borrow_limit - $current_borrowed_count;

    if ($borrow_many_copies && !$is_borrowed) {
        // Staff borrowed books with same copies (of a DIFFERENT book) but is trying to borrow a NEW book.
        $errors['borrow_restriction'] = "You must return all books from borrowing books with same copies before borrowing a different book.";
        $max_copies_allowed = 0; // Prevent borrowing
    } else {
        // Staff is either borrowing the same book, or has only single-copy loans, or no loans. They are limited by total slots.
        if ($available_slots <= 0) {
            $errors['borrow_limit'] = "You have reached your total borrowing limit of {$borrow_limit} copies. You currently have {$current_borrowed_count} copies requested or checked out.";
        }

        // Clamp max_copies_allowed by available stock and available slots
        $max_copies_allowed = min($max_available, max(0, $available_slots));
    }
}


// Final check: clamp the requested copies
if ($no_of_copies > $max_available) {
    $no_of_copies = $max_available;
}
if ($no_of_copies > $max_copies_allowed) {
    $no_of_copies = $max_copies_allowed;
}

// If max available is 0, or max allowed is 0 due to restriction, add an error if not already set.
if ($max_available <= 0 && empty($errors['availability'])) {
    $errors['availability'] = "All copies of this book are currently borrowed.";
}

if ($no_of_copies <= 0 && empty($errors)) {
    if ($max_available > 0) {
        $errors['borrow_limit'] = "You cannot borrow any more books at this time due to your current borrow status or limit (You have {$current_borrowed_count} out of {$borrow_limit} allowed).";
    }
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
    <link href="https://fonts.googleapis.com/css2?family=Licorice&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="text-4xl sm:text-5xl font-extrabold">Book Borrowing Details
            </h1>
            <p class="text-xl mt-2">Please verify the details below before finalizing.</p>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="max-w-3xl mx-auto mb-12 bg-white p-8 rounded-xl shadow-lg border-t-4 border-red-700">
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <strong class="font-bold">Cannot proceed with loan request!</strong>
                    <ul class="list-disc ml-5 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="flex justify-center">
                    <a href="catalogue.php"
                        class="mt-4 inline-block px-6 py-3 bg-red-800 text-white rounded-lg font-semibold hover:bg-red-700 transition shadow-md">
                        Return to Catalogue
                    </a>
                </div>
            </div>
        <?php else: ?>

            <form method="POST" action="../../../app/controllers/borrowBookController.php?action=add"
                class="max-w-3xl mx-auto">
                <input type="hidden" name="userID" value="<?= $userID ?? "" ?>">
                <input type="hidden" name="bookID" value="<?= $bookID ?? "" ?>">
                <input type="hidden" name="copies" id="copies_input" value="<?= $no_of_copies ?? "" ?>">

                <div class="mb-12 bg-white p-8 rounded-xl shadow-lg border-t-4 border-red-700">

                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Items to be Borrowed</h2>

                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center gap-6 p-4 border border-gray-200 rounded-lg">
                        <div
                            class="flex-shrink-0 w-24 h-36 shadow-lg rounded-md overflow-hidden bg-gray-200 border-2 border-gray-100">
                            <?php
                            if ($book['book_cover_dir']) { ?>
                                <img src="<?= "../../../" . $book['book_cover_dir'] ?>" alt="<?= $book['book_title'] ?> Cover"
                                    class="w-full h-full object-cover">
                            <?php } else { ?>
                                <div
                                    class="flex items-center justify-center w-full h-full text-xs text-gray-500 text-center p-1">
                                    No Cover
                                </div>
                            <?php } ?>
                        </div>

                        <div class="flex-grow">
                            <h3 class="text-xl font-bold text-red-800 mb-1"><?= $book['book_title'] ?></h3>
                            <p class="text-md text-gray-700"><strong>Author:</strong> <?= $book['author'] ?></p>

                            <p class="text-md text-gray-700 mt-2"><strong>Copies Requested:</strong>
                                <?php if ($userTypeID == 2): // Staff (userTypeID 2) ?>
                                    <input type="number" name="no_of_copies" id="no_of_copies" value="<?= $no_of_copies ?>"
                                        min="1" max="<?= $max_copies_allowed ?>"
                                        class="w-20 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 inline-block text-xl text-red-800 font-extrabold text-center">
                                    <span class="text-sm text-gray-500 block sm:inline-block ml-2">(Max:
                                        <?= $max_copies_allowed ?>)</span>
                                <?php else: // Non-Staff (limited to 1) ?>
                                    <span class="font-extrabold text-2xl text-red-800"
                                        id="copies_display"><?= $no_of_copies ?></span>
                                    <span class="text-sm text-gray-500 block sm:inline-block ml-2">(Max: 1)</span>
                                <?php endif; ?>
                            </p>

                            <p class="text-md text-gray-700"><strong>Current Book Condition:</strong>
                                <span class="font-bold text-red-700"><?= $book['book_condition'] ?></span>
                            </p>
                            <p class="text-sm text-gray-500">Available Stock: <?= $max_available ?></p>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Book Borrowing Details</h3>
                        <ul class="space-y-3 text-lg text-gray-700 mb-8 p-4 bg-gray-50 rounded-lg border">
                            <li><strong class="text-red-800">Borrower:</strong>
                                <span class="font-bold text-gray-900"><?= $userName ?></span>
                            </li>
                            <li><strong class="text-red-800">Maximum Borrow Limit:</strong> <?= $borrow_limit ?> copies</li>
                            <li><strong class="text-red-800">Currently Requested/Borrowed:</strong>
                                <?= $current_borrowed_count ?> copies</li>
                            <li><strong class="text-red-800">Maximum Borrow Period:</strong> <?= $borrow_period ?> Days</li>

                            <li class="flex flex-col sm:flex-row sm:items-center">
                                <strong class="text-red-800 w-full sm:w-1/2">Expected Pickup Date:</strong>
                                <input type="date" name="pickup_date" id="pickup_date" value="<?= $pickup_date ?? "" ?>"
                                    min="<?= $pickup_date ?>"
                                    class="mt-2 sm:mt-0 w-full sm:w-1/2 p-2 border border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">

                            </li>
                            <li class="pt-2"><strong class="text-red-800">Expected Return Date:</strong>

                                <input type="date" name="expected_return_date" id="expected_return_date_display"
                                    value="<?= $expected_return_date ?? "" ?>" readonly>
                                <span class="text-sm text-gray-500 block sm:inline-block">(Calculated based on max loan
                                    period)</span>
                            </li>
                        </ul>

                        <div class="mt-8 p-4 border border-red-200 bg-red-50 rounded-lg">
                            <h3 class="text-xl font-bold text-red-800 mb-3">Terms and Conditions</h3>
                            <ul class="list-disc ml-5 text-gray-700 space-y-2 text-base">
                                <li>Loaned items must be returned on or before the <strong>Expected Return Date</strong>.
                                </li>
                                <li><strong>Late Return Penalty:</strong> A fine of <strong>20 pesos (₱20.00) per
                                        week</strong> will be incurred for
                                    each item returned past the due date.</li>
                                <li><strong>Damage or Loss:</strong> Any damage or loss of the loaned book must be
                                    immediately reported
                                    to the library staff. The associated cost will be discussed with the borrower.</li>
                                <li><strong>Non-Compliance:</strong> Failure to resolve outstanding issues (e.g., unpaid
                                    fines,
                                    unresolved damage/loss) may result in the user's borrowing privileges being suspended
                                    indefinitely.</li>
                            </ul>
                            <div class="mt-4 flex items-center">
                                <input type="checkbox" id="agree_terms" name="agree_terms" required
                                    class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                <label for="agree_terms" class="ml-2 block text-sm font-medium text-gray-900">
                                    I agree to the Terms and Conditions listed above.
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 mt-6">
                            <a href="catalogue.php"
                                class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                                Cancel Loan
                            </a>
                            <button type="submit"
                                class="px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition shadow-md">
                                Confirm and Checkout
                            </button>
                        </div>
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
            const copiesSelectable = document.getElementById('no_of_copies'); // New variable
            const copiesHiddenInput = document.getElementById('copies_input'); // New variable
            const maxLoanDays = <?= $borrow_period ?>; // Max days based on user type

            // Update hidden copies input when the selectable input changes (Staff only)
            if (copiesSelectable) {
                copiesSelectable.addEventListener('input', function () {
                    copiesHiddenInput.value = this.value;
                });
            }


            /**
             * Converts a Date object to a YYYY-MM-DD string format required by input type="date".
             * @param {Date} date
             */
            function formatDateToISO(date) {
                // Get month and day, ensuring they are zero-padded if single digit
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                return `${date.getFullYear()}-${month}-${day}`;
            }

            /**
             * Calculates the return date based on the chosen pickup date and max loan period.
             * The result is set in the YYYY-MM-DD format for the date input.
             */
            function calculateReturnDate() {
                const pickupDateValue = pickupDateInput.value;
                if (!pickupDateValue) return;

                // Use 'yyyy-mm-dd' format for correct date parsing (standard for input[type=date])
                const date = new Date(pickupDateValue.replace(/-/g, '/')); // Handle cross-browser date parsing

                // Set the date for calculation
                date.setDate(date.getDate() + maxLoanDays); // Add max loan days

                // Set the value in the date input (which requires YYYY-MM-DD format)
                expectedreturnDateInput.value = formatDateToISO(date);
            }

            // --- Original Max Pickup Date Logic (Kept for Context) ---
            const today = new Date();
            const maxPickupDate = new Date();
            maxPickupDate.setDate(today.getDate() + 7); // Allow pickup up to 7 days in the future

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