<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageBorrowDetails.php");
$borrowObj = new BorrowDetails();
$userID = $_SESSION["user_id"];

// Fetch user data for limits
$borrower = $borrowObj->fetchUser($userID);
$userTypeID = (int) ($borrower["userTypeID"] ?? 1);
$borrow_limit = (int) ($borrower["borrower_limit"] ?? 1);

// --- PHP list management (Session-based) has been removed ---
// The list is now managed entirely in JavaScript using localStorage.

// --- CHECKOUT SUCCESS/ERROR HANDLER ---
$checkout_status = $_GET['status'] ?? null;
$total_copies_success = $_GET['total_copies'] ?? null;
$clear_list_flag = $_GET['clear_list'] ?? '0';

$success_message = null;
$error_message = null;

if ($checkout_status === 'success_checkout' && $total_copies_success > 0) {
    $success_message = "Successfully requested {$total_copies_success} copies for checkout! They are now in the 'Pending' status.";
} elseif ($checkout_status === 'partial_success' && $total_copies_success > 0) {
    $success_message = "Partially requested {$total_copies_success} copies. Some items failed due to restrictions and are still in your list.";
} elseif ($checkout_status === 'error') {
     $error_message = "Checkout failed. Please review your selection and try again.";
}

// PHP is only used here to prevent potential POST data carry-over in the URL
// when the user updates copies (Staff) or removes an item.
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['bookID'])) {
    // This is for redirecting after a removal to clean the URL, but removal is done in JS.
    header("Location: myList.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $userTypeID == 2 && isset($_POST['update_copies_trigger'])) {
    // This POST is triggered by JS to force a clean redirect after a localStorage update
    header("Location: myList.php?success=copies_updated");
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
    <link rel="stylesheet" href="../../../public/assets/css/header_footer1.css" />
    <style>
        .table-auto-layout {
            table-layout: auto;
            width: 100%;
        }

        .fixed-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
    </style>
</head>

<body class="min-h-screen pb-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-red-800">My List</h1>
            <p class="text-xl mt-2">Books pending for checkout.</p>
        </header>

        <?php if ($success_message): ?>
            <div class="max-w-4xl mx-auto bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= $success_message ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="max-w-4xl mx-auto bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= $error_message ?></span>
            </div>
        <?php endif; ?>

        <?php if ($userID): // Only show the list section if a user is logged in ?>
            <form id="checkout-form" method="GET" action="confirmation.php">
                <input type="hidden" name="action" value="list_checkout">
                <input type="hidden" name="checkout_data_json" id="checkout-data-json" value=""> 

                <div class="mb-12 bg-white p-6 rounded-xl shadow-lg overflow-x-auto list-container">
                    <table class="table-auto-layout text-left whitespace-nowrap">
                        <thead>
                            <tr class="text-gray-600 border-b-2 border-red-700">
                                <th class="py-3 px-2 w-10"></th>
                                <th class="py-3 px-4" colspan="2">Book</th>
                                <th class="py-3 px-4">Author</th>
                                <th class="py-3 px-4">Condition</th>
                                <th class="py-3 px-4 w-40">Copies Requested</th>
                                <th class="py-3 px-4 w-20">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="list-body">

                        </tbody>
                    </table>
                </div>

                <div class="fixed-footer bg-white border-t-2 border-red-700 shadow-2xl p-4">
                    <div class="max-w-7xl mx-auto flex justify-between items-center">
                        <p class="text-lg font-bold text-gray-800">Selected Books: <span id="selected-count"
                                class="text-red-700">0</span></p>
                        <button type="submit" id="checkout-btn" disabled
                            class="px-8 py-3 bg-red-800 text-white rounded-lg font-semibold hover:bg-red-700 transition shadow-md disabled:bg-gray-400 disabled:cursor-not-allowed">
                            Checkout Selected Books
                        </button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-xl shadow-lg">
                <p class="text-xl text-gray-500">Please log in to view your list.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>

    <script>
        // --- Core Variables ---
        let myList = {}; // Global variable to hold the list loaded from localStorage
        const userID = <?= $userID ?>;
        const userTypeID = <?= $userTypeID ?>;
        const borrowLimit = <?= $borrow_limit ?>;
        const listBody = document.getElementById('list-body');
        const checkoutForm = document.getElementById('checkout-form');
        const checkoutDataJsonInput = document.getElementById('checkout-data-json');
        const selectedCountSpan = document.getElementById('selected-count');
        const checkoutBtn = document.getElementById('checkout-btn');
        const listContainer = document.querySelector('.list-container');


        // --- Local Storage Functions ---

        /**
         * Generates a unique key for the user's list in localStorage.
         * This prevents one user from seeing another user's list.
         */
        function getPersistentListKey() {
            return `user_borrow_list_${userID}`;
        }

        /**
         * Loads the book list from the user's Local Storage key.
         */
        function loadMyList() {
            const key = getPersistentListKey();
            try {
                // Get the data from localStorage and parse it. If null, use an empty object.
                myList = JSON.parse(localStorage.getItem(key)) || {};
            } catch (e) {
                console.error("Error loading list from localStorage:", e);
                myList = {}; // Reset list on error
            }
        }

        /**
         * Saves the current in-memory list object back to Local Storage.
         */
        function saveMyList() {
            const key = getPersistentListKey();
            // Stringify the JavaScript object before saving it.
            localStorage.setItem(key, JSON.stringify(myList));
        }
        
        /**
         * Clears the entire list from Local Storage.
         */
        function clearMyList() {
            localStorage.removeItem(getPersistentListKey());
            myList = {}; // Also clear in-memory list
        }

        // --- List Manipulation Functions ---

        /**
         * Removes a book from the list and re-renders the table.
         */
        function removeBook(bookID) {
            // bookID must be converted to string for object key lookup
            const idString = String(bookID); 
            if (confirm("Are you sure you want to remove this book from your list?")) {
                delete myList[idString];
                saveMyList();
                renderList(); // Re-render the table
            }
        }

        /**
         * Updates the number of copies requested for a book (Staff only).
         */
        function handleCopiesUpdate(bookID, inputElement) {
            // bookID must be converted to string for object key lookup
            const idString = String(bookID); 
            let newCopies = parseInt(inputElement.value);
            const maxAvailable = myList[idString].copies_available;

            // Simple validation to ensure copies are within limits
            if (isNaN(newCopies) || newCopies < 1) {
                newCopies = 1;
            } else if (newCopies > maxAvailable) {
                newCopies = maxAvailable;
            }

            // Update input element visually and update the global list object
            inputElement.value = newCopies;
            myList[idString].copies_requested = newCopies;
            saveMyList();
            updateCheckoutState(); // Update selected count/button state just in case
        }

        /**
         * Renders the current list object to the HTML table.
         */
        function renderList() {
            listBody.innerHTML = ''; // Clear existing rows

            if (Object.keys(myList).length === 0) {
                // If the list is empty, show a friendly message
                const emptyMessageHtml = `
                    <div class="text-center py-16">
                        <p class="text-xl text-gray-500">Your list is empty. Add books from the <a href="catalogue.php" class="text-red-700 font-semibold">Catalogue</a>!</p>
                    </div>`;
                
                // If the table container exists, replace its content with the message
                if (listContainer) {
                    listContainer.classList.remove('overflow-x-auto', 'p-6'); // Clean up styling
                    listContainer.classList.add('py-0');
                    listContainer.innerHTML = emptyMessageHtml;
                }
                
                checkoutBtn.disabled = true;
                selectedCountSpan.textContent = '0';
                return;
            }
            
            // If the list is not empty, ensure the container styling is correct
            if (listContainer && listContainer.querySelector('table') === null) {
                 listContainer.classList.add('overflow-x-auto', 'p-6');
                 listContainer.classList.remove('py-0');
                 // Re-inserting the table is handled by the overall structure, just ensure the container looks right
            }
            

            Object.values(myList).forEach(book => {
                const row = document.createElement('tr');
                row.className = 'border-b hover:bg-gray-50';

                // Conditional rendering for the Copies Requested cell
                const copiesCell = userTypeID === 2 ?
                    // Staff: Editable input field
                    `
                    <div class="flex items-center space-x-2">
                        <input type="number" name="copies_${book.bookID}" value="${book.copies_requested}"
                            min="1" max="${book.copies_available}"
                            onchange="handleCopiesUpdate(${book.bookID}, this)"
                            oninput="handleCopiesUpdate(${book.bookID}, this)"
                            data-book-id="${book.bookID}"
                            class="w-20 p-2 border border-gray-300 rounded-md shadow-sm text-center staff-copies-input">
                    </div>
                    <span class="text-sm text-gray-500 block">(Max: ${book.copies_available})</span>
                    ` :
                    // Non-staff (Student/Guest): Fixed 1 copy
                    `
                    <span class="font-extrabold text-xl text-red-800">1</span>
                    <span class="text-sm text-gray-500 block">(Max: 1)</span>
                    <input type="hidden" name="copies_${book.bookID}" value="1">
                    `;

                // Book Cover
                const coverHtml = book.cover_dir ?
                    `<img src="../../../${book.cover_dir}" alt="Cover" class="w-full h-full object-cover">` :
                    `<div class="flex items-center justify-center w-full h-full text-xs text-gray-500 text-center p-1">No Cover</div>`;

                // Build the table row content
                row.innerHTML = `
                    <td class="py-4 px-2">
                        <input type="checkbox" name="selected_books[]" value="${book.bookID}" 
                            checked
                            class="h-5 w-5 text-red-600 border-gray-300 rounded focus:ring-red-500 checkbox-item">
                    </td>
                    <td class="py-4 px-4">
                        <div class="w-16 h-24 shadow-md rounded-md overflow-hidden bg-gray-200 border">
                            ${coverHtml}
                        </div>
                    </td>
                    <td class="py-4 px-4 text-red-800 font-bold max-w-xs">${book.title}</td>
                    <td class="py-4 px-4">${book.author}</td>
                    <td class="py-4 px-4">${book.condition}</td>
                    <td class="py-4 px-4">
                        ${copiesCell}
                    </td>
                    <td class="py-4 px-4">
                        <a href="#" onclick="event.preventDefault(); removeBook(${book.bookID})" class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Remove
                        </a>
                    </td>
                `;
                listBody.appendChild(row);
            });

            // Re-attach event listeners for checkboxes after rendering
            document.querySelectorAll('.checkbox-item').forEach(checkbox => {
                checkbox.addEventListener('change', updateCheckoutState);
            });
            updateCheckoutState();
        }

        /**
         * Updates the selected count display and enables/disables the checkout button.
         */
        function updateCheckoutState() {
            const checkboxes = document.querySelectorAll('.checkbox-item');
            let selectedCount = 0;
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedCount++;
                }
            });

            selectedCountSpan.textContent = selectedCount;

            // Enable the button only if at least one book is selected
            checkoutBtn.disabled = selectedCount === 0;
        }

        /**
         * Prepares the form data for submission to confirmation.php.
         * It packages the selected books and their requested copies into a JSON string.
         */
        checkoutForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent default GET submission for now

            const selectedBooks = {};
            const checkboxes = document.querySelectorAll('.checkbox-item:checked');
            let selectedCount = checkboxes.length;

            if (selectedCount === 0) {
                alert("Please select at least one book to checkout.");
                return;
            }

            checkboxes.forEach(checkbox => {
                const bookID = String(checkbox.value); // Use string to match myList keys
                const book = myList[bookID];
                
                // Only include checked items from the list
                if (book) {
                    // Get the final copies requested, which is stored in myList
                    const copies = book.copies_requested;

                    // Add all necessary data for confirmation.php validation
                    selectedBooks[bookID] = {
                        ...book,
                        copies_requested: copies
                    };
                }
            });
            
            // Check again in case items were checked but removed from the list manually
            if (Object.keys(selectedBooks).length === 0) {
                 alert("Please select at least one valid book to checkout.");
                 return;
            }

            // Set the JSON data to be processed by confirmation.php
            checkoutDataJsonInput.value = JSON.stringify(selectedBooks);

            // Construct the final URL for the GET request
            const url = new URL(checkoutForm.action);
            url.searchParams.set('action', 'list_checkout');
            url.searchParams.set('list_source', 'localstorage');
            
            // NOTE: The JSON string must be URL-encoded for safety in a GET request
            url.searchParams.set('checkout_data_json', encodeURIComponent(checkoutDataJsonInput.value));

            // Optional: for confirmation.php to check if any books were selected
            url.searchParams.set('selected_books', Object.keys(selectedBooks).join(',')); 

            // Final step: Redirect the browser to the confirmation page with the prepared URL
            window.location.href = url.toString();
        });

        // --- Initialization ---
        document.addEventListener('DOMContentLoaded', function () {
            // Check for success and clear list if necessary
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const clearList = urlParams.get('clear_list');

            loadMyList();

            // Clear list ONLY if successful checkout AND it came from the list (flag is '1')
            if (status === 'success_checkout' && clearList === '1') {
                clearMyList();
                // Strip the URL parameters to prevent re-clearing and re-displaying message on refresh
                history.replaceState({}, document.title, "myList.php?status=success_checkout&total_copies=" + urlParams.get('total_copies'));
            }
            
            renderList();
        });
    </script>
</body>

</html>