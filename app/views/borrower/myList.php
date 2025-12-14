<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
require_once(__DIR__ . "/../../models/manageUsers.php");
require_once(__DIR__ . "/../../models/manageList.php");

$borrowListObj = new BorrowLists();
$userObj = new User();

// fetch user information based on ID
$userID = $_SESSION["user_id"];
$user = $userObj->fetchUser($userID);
$userTypeID = (int) ($user["userTypeID"] ?? 0);

//fetch borrower limit and period
$borrow_limit = (int) ($user["borrower_limit"] ?? 1);
$borrow_period = (int) ($user["borrower_period"] ?? 7); // Fetch borrow period

// Calculate initial dates
$pickup_date = date("Y-m-d");
$expected_return_date = date("Y-m-d", strtotime("+$borrow_period days"));

//fetch lists of each user
$myList = $borrowListObj->fetchAllBorrrowList($userID);

// checkout success
$checkout_status = $_GET['status'] ?? null;
$total_copies_success = (int) ($_GET['total_copies'] ?? 0);

//for removing a book from list
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['listID'])) {
    header("Location: myList.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowing List</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/borrower.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
</head>

<body class="min-h-screen pb-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="title text-4xl sm:text-5xl font-extrabold">My List</h1>
            <p class="text-xl mt-2">Books Pending for Confirmation</p>
        </header>
        <form id="checkout-form" method="POST" action="confirmation.php?action=list_checkout">

            <input type="hidden" name="userID" value="<?= $userID ?>">
            <input type="hidden" id="pickup_date_input" name="pickup_date" value="<?= $pickup_date ?>">
            <input type="hidden" id="expected_return_date_input" name="expected_return_date"
                value="<?= $expected_return_date ?>">


            <div class="bg-white p-6 rounded-xl shadow-lg overflow-x-auto list-container">
                <table class="table-auto-layout text-left whitespace-nowrap w-full">
                    <thead>
                        <tr class="text-gray-600 border-b-2 border-red-700">
                            <th class="py-4 px-2"><input type="checkbox" name="select_all" id="select_all"
                                    class="checkbox"></th>
                            <th class="py-3 px-4 hidden sm:table-cell">Select All</th>
                            <th class="py-3 px-4">Book</th>
                            <th class="py-3 px-4 hidden sm:table-cell">Author</th>
                            <th class="py-3 px-4 hidden sm:table-cell">Condition</th>
                            <?php if ($userTypeID != 2) { ?>
                                <th class="py-3 px-4 w-40">Copies Requested <br><span class="hidden sm:table-cell">(Max:
                                        1)</span></th>
                            <?php } else { ?>
                                <th class="py-3 px-4 w-40">Copies Requested</th>

                            <?php } ?>
                            <th class="py-3 px-4 w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="list-body">
                        <?php if (empty($myList)) { ?>
                            <tr id="empty-list-row">
                                <td colspan="7" class="py-16 text-center text-xl text-gray-500">
                                    Your list is empty. Add books from the <a href="catalogue.php"
                                        class="text-red-700 font-semibold">Catalogue</a>!
                                </td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($myList as $list) {
                                $listID = $list['listID'];
                                $copiesRequested = $list['no_of_copies'];
                                $copiesAvailable = $list['book_copies'];

                                $maxDisplay = ($userTypeID == 2) ? $copiesAvailable : 1;

                                ?>
                                <tr class="border-b hover:bg-gray-50" data-list-id="<?= $listID ?>"
                                    data-book-id="<?= $list['bookID'] ?>" data-copies-requested="<?= $copiesRequested ?>">
                                    <td class="py-4 px-2">
                                        <input type="checkbox" name="selected_books[]" value="<?= $listID ?>"
                                            class="checkbox checkbox-item">
                                    </td>

                                    <td class="py-4 px-4 hidden sm:table-cell">
                                        <div class="w-16 h-24 shadow-md rounded-md overflow-hidden bg-gray-200 border">
                                            <?php if (!empty($list['book_cover_dir'])): ?>
                                                <img src="../../../<?= $list['book_cover_dir'] ?>" alt="Cover"
                                                    class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div
                                                    class="flex items-center justify-center w-full h-full text-xs text-gray-500 text-center p-1">
                                                    No Cover
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-red-800 font-bold max-w-xs">
                                        <?= $list['book_title'] ?>
                                    </td>
                                    <td class="py-4 px-4 hidden sm:table-cell"><?= $list['author_names'] ?></td>
                                    <td class="py-4 px-4 hidden sm:table-cell"><?= $list['book_condition'] ?></td>

                                    <td class="py-4 px-4">
                                        <div class="flex flex-col items-start space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-extrabold text-xl text-red-800 copies-display">
                                                    <?= $copiesRequested ?>
                                                </span>
                                                <?php if ($userTypeID == 2) { ?>
                                                    <p>(Stock : <?= $maxDisplay ?>)</p>
                                                <?php } ?>

                                            </div>

                                            <?php if (!empty($userTypeID == 2)): ?>
                                                <button type="button" onclick="showEditCopiesModal(<?= $listID ?>, '<?= $list['book_title'] ?>', <?= $copiesRequested ?>, <?= $copiesAvailable ?>
                )" class="text-sm text-blue-600 hover:text-blue-800 font-medium p-1 rounded transition duration-150">
                                                    Edit Copies
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="py-4 px-4">
                                        <a href="../../../app/controllers/borrowListController.php?action=remove&listID=<?= $listID ?>"
                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            Remove
                                        </a>
                                    </td>
                                </tr>
                                <input type="hidden" name="list_data[<?= $listID ?>][bookID]" value="<?= $list['bookID'] ?>">
                                <input type="hidden" name="list_data[<?= $listID ?>][listID]" value="<?= $listID ?>">
                                <input type="hidden" name="list_data[<?= $listID ?>][book_title]"
                                    value="<?= $list['book_title'] ?>">
                                <input type="hidden" name="list_data[<?= $listID ?>][author]"
                                    value="<?= $list['author_names'] ?>">
                                <input type="hidden" name="list_data[<?= $listID ?>][book_condition]"
                                    value="<?= $list['book_condition'] ?>">
                                <input type="hidden" name="list_data[<?= $listID ?>][book_copies]"
                                    value="<?= $list['book_copies'] ?>">
                                <input type="hidden" name="list_data[<?= $listID ?>][book_cover_dir]"
                                    value="<?= $list['book_cover_dir'] ?>">
                                <input type="hidden" name="list_data[<?= $listID ?>][copies_requested]"
                                    class="copies-requested-input-<?= $listID ?>" value="<?= $copiesRequested ?>">
                            <?php }
                            ; ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="list-footer z-50">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <p class="text-lg font-bold text-gray-800">Selected Books: <span id="selected-count"
                            class="text-red-700">0</span></p>

                    <button type="submit" id="checkout-btn" disabled
                        class="px-8 py-3 bg-red-800 text-white rounded-lg font-semibold hover:bg-red-700 transition shadow-md disabled:bg-gray-400 disabled:cursor-not-allowed w-full md:w-auto">
                        Confirm Selected Books
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>

    <div id="copy-edit-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-2xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-xl font-bold text-red-800">Edit Copies for: <strong id="modal-edit-book-title"></strong>
                </h3>
                <button onclick="closeModal('copy-edit-modal')"
                    class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>

            <form onsubmit="event.preventDefault(); submitCopiesUpdate()">
                <input type="hidden" id="modal-edit-list-id">

                <div class="mb-4">
                    <label for="modal-edit-copies-input" class="block text-sm font-medium text-gray-700 mb-1">Number of
                        Copies to Borrow:</label>
                    <input type="number" id="modal-edit-copies-input" min="1" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
                        oninput="validateCopies()">
                    <p id="edit-copies-error" class="text-sm text-red-600 mt-1 hidden">Cannot borrow more than <span
                            id="max-edit-copies-display"></span> copies.</p>
                    <span id="max-edit-copies" class="hidden"></span>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('copy-edit-modal')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">Cancel</button>
                    <button type="button" id="modal-edit-submit-btn" onclick="submitCopiesUpdate()"
                        class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="message-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-2xl p-6 w-full max-w-xs mx-4">
            <p id="message-modal-body" class="mb-4 text-center font-medium text-gray-700"></p>
            <div class="flex justify-center">
                <button onclick="closeModal('message-modal')"
                    class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition text-sm">Dismiss</button>
            </div>
        </div>
    </div>
</body>

<script src="../../../public/assets/js/borrower.js"></script>

<script>
    const checkout_status = "<?= $checkout_status ?>";
    const total_copies_success = <?= $total_copies_success ?>;
    const maxLoanDays = <?= $borrow_period ?>;

    const checkoutForm = document.getElementById('checkout-form');
    const selectedCountSpan = document.getElementById('selected-count');
    const checkoutBtn = document.getElementById('checkout-btn');
    const selectAllBtn = document.getElementById('select_all');
    const copiesInput = document.getElementById('modal-edit-copies-input');
    const submitBtn = document.getElementById('modal-edit-submit-btn');
    const errorMsg = document.getElementById('edit-copies-error');
    const maxCopiesDisplay = document.getElementById('max-edit-copies-display');
    const maxCopiesHidden = document.getElementById('max-edit-copies');
    const messageModalBody = document.getElementById('message-modal-body');

    // Date inputs
    const pickupDateDisplay = document.getElementById('pickup_date_display'); // NEW ID
    const expectedReturnDateDisplay = document.getElementById('expected_return_date_display'); // Not used on this page
    const pickupDateHiddenInput = document.getElementById('pickup_date_input');
    const expectedReturnDateHiddenInput = document.getElementById('expected_return_date_input');


    //open modal
    function openModal(modalID) {
        document.getElementById(modalID).classList.remove('hidden');
        document.getElementById(modalID).classList.add('flex');
    }

    //close modal
    function closeModal(modalID) {
        document.getElementById(modalID).classList.add('hidden');
        document.getElementById(modalID).classList.remove('flex');
    }

    //modal to show messages (either success or fail)
    function showMessageModal(status, copies) {
        let message;

        switch (status) {
            case 'success_checkout':
                message = `SUCCESS: Requested ${copies} copies. Now in 'Pending' status.`;
                break;
            case 'partial_success':
                message = `PARTIAL SUCCESS: Requested ${copies} copies. Some items failed.`;
                break;
            case 'removed':
                message = 'SUCCESS: Book removed from your list.';
                break;
            case 'edit':
                message = 'SUCCESS: No. of Copies has been updated.';
                break;
            case 'error':
                message = 'ERROR: Action failed. Please try again.';
                break;
            default:
                return; // Do nothing if status is not recognized
        }

        messageModalBody.textContent = message;
        openModal('message-modal');
    }


    function validateCopies() {
        const maxAvailable = parseInt(maxCopiesHidden.textContent);
        const requestedCopies = parseInt(copiesInput.value);

        if (requestedCopies > maxAvailable || requestedCopies < 1 || isNaN(requestedCopies)) {
            maxCopiesDisplay.textContent = maxAvailable;
            errorMsg.classList.remove('hidden');
            submitBtn.disabled = true;
        } else {
            errorMsg.classList.add('hidden');
            submitBtn.disabled = false;
        }
    }

    function showEditCopiesModal(listID, title, currentCopies, maxAvailable) {
        document.getElementById('modal-edit-list-id').value = listID;
        document.getElementById('modal-edit-book-title').textContent = title;
        copiesInput.value = currentCopies;

        maxCopiesHidden.textContent = maxAvailable;
        errorMsg.classList.add('hidden');
        submitBtn.disabled = false;

        openModal('copy-edit-modal');
    }


    function submitCopiesUpdate() {
        validateCopies();
        if (document.getElementById('modal-edit-submit-btn').disabled) return;

        const listID = document.getElementById('modal-edit-list-id').value;
        const newCopies = parseInt(copiesInput.value);

        // Update the hidden input in the form to reflect the change immediately
        document.querySelector(`.copies-requested-input-${listID}`).value = newCopies;

        // Redirect to controller to update the database
        window.location.href = `../../../app/controllers/borrowListController.php?action=edit&listID=${listID}&copies=${newCopies}`;

        closeModal('copy-edit-modal');
    }

    function updateCheckoutState() {
        const selectedCount = document.querySelectorAll('.checkbox-item:checked').length;
        selectedCountSpan.textContent = selectedCount;

        const listIsEmpty = document.getElementById('empty-list-row') !== null;
        checkoutBtn.disabled = listIsEmpty || (selectedCount === 0);

        // Disable/Enable the corresponding hidden inputs for form submission
        document.querySelectorAll('.checkbox-item').forEach(checkbox => {
            const listID = checkbox.value;
            const isChecked = checkbox.checked;

            // Find all related hidden inputs for this list item
            document.querySelectorAll(`input[name^="list_data[${listID}]"]`).forEach(input => {
                input.disabled = !isChecked;
            });
        });
    }

    function formatDate(date) {
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        return `${date.getFullYear()}-${month}-${day}`;
    }

    /**
     * Calculates the return date based on the chosen pickup date and max loan period, and updates the form inputs.
     */
    function calculateAndSetDates() {
        const pickupDateValue = pickupDateDisplay.value;
        if (!pickupDateValue) return;

        // 1. Update Hidden Pickup Date Input
        pickupDateHiddenInput.value = pickupDateValue;

        // 2. Calculate Expected Return Date
        const date = new Date(pickupDateValue.replace(/-/g, '/'));
        date.setDate(date.getDate() + maxLoanDays);
        const returnDateISO = formatDate(date);

        // 3. Update Hidden Return Date Inputs
        expectedReturnDateHiddenInput.value = returnDateISO;
    }


    // Form submission validation (Modified for confirmation.php redirection with Pre-Validation)
    checkoutForm.addEventListener('submit', async function (e) {
        e.preventDefault(); // Always prevent default first

        const selectedCount = document.querySelectorAll('.checkbox-item:checked').length;
        if (selectedCount === 0) {
            messageModalBody.textContent = "Please select at least one book to checkout.";
            openModal('message-modal');
            return;
        }

        // Final check on dates
        if (!pickupDateHiddenInput.value || !expectedReturnDateHiddenInput.value) {
            messageModalBody.textContent = "Please select a valid Pickup Date.";
            openModal('message-modal');
            return;
        }

        // Prepare data for validation
        const selectedItems = [];
        document.querySelectorAll('.checkbox-item:checked').forEach(checkbox => {
            const listID = checkbox.value;
            const bookID = document.querySelector(`input[name="list_data[${listID}][bookID]"]`).value;
            // Use the hidden input that tracks copies
            const copies = document.querySelector(`.copies-requested-input-${listID}`).value;

            selectedItems.push({
                bookID: parseInt(bookID),
                copies: parseInt(copies)
            });
        });

        // User ID from PHP
        const userID = <?= json_encode($_SESSION['user_id'] ?? null) ?>;

        try {
            // Disable button during validation
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = "Validating...";

            const response = await fetch('../../../app/controllers/borrowBookController.php?action=validate_borrow', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    userID: userID,
                    items: selectedItems
                })
            });

            const result = await response.json();

            if (result.success) {
                // If valid, submit the form programmatically
                checkoutForm.submit();
            } else {
                // Show errors in modal
                const messageBody = document.getElementById('message-modal-body');
                messageBody.innerHTML = '<strong class="text-red-800 block mb-2">Checkout Failed:</strong>' +
                    result.errors.map(err => `&bull; ${err}`).join('<br>');
                openModal('message-modal');
            }
        } catch (error) {
            console.error('Validation error:', error);
            messageModalBody.textContent = "An error occurred during validation.";
            openModal('message-modal');
        } finally {
            checkoutBtn.disabled = false;
            checkoutBtn.textContent = "Confirm Selected Books";
        }
    });

    // --- Initialization ---
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.checkbox-item').forEach(checkbox => {
            checkbox.addEventListener('change', updateCheckoutState);
        });
        copiesInput.addEventListener('input', validateCopies);

        // Add listeners for date changes
        const pickupDateInput = document.getElementById('pickup_date_display'); // Reference the new input
        pickupDateInput.addEventListener('change', calculateAndSetDates);

        // Set maximum pickup date (e.g., today + 7 days)
        const today = new Date();
        const maxPickupDate = new Date();
        maxPickupDate.setDate(today.getDate() + 7);
        pickupDateInput.setAttribute('max', formatDate(maxPickupDate));

        // Initial setup
        calculateAndSetDates();
        updateCheckoutState();

        // CHECKOUT/REMOVAL MESSAGE DISPLAY
        if (checkout_status) {
            showMessageModal(checkout_status, total_copies_success);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('select_all');
        const items = document.querySelectorAll('.checkbox-item');

        // Toggle all checkboxes when "Select All" is clicked
        selectAll.addEventListener('change', function () {
            items.forEach(item => item.checked = selectAll.checked);
            updateCheckoutState();
        });

        // Update "Select All" checkbox based on individual items
        items.forEach(item => {
            item.addEventListener('change', function () {
                selectAll.checked = Array.from(items).every(i => i.checked);
                updateCheckoutState();
            });
        });
    });
</script>

</html>