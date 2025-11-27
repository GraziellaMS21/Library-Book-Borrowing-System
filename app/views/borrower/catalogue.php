<?php

//ensures that a user is logged in
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../app/views/borrower/login.php");
  exit;
}

require_once(__DIR__ . "/../../models/manageBook.php");
require_once(__DIR__ . "/../../models/manageCategory.php");
require_once(__DIR__ . "/../../models/manageUsers.php");
require_once(__DIR__ . "/../../models/manageList.php");
require_once(__DIR__ . "/../../models/manageBorrowDetails.php");

$bookObj = new Book();
$categoryObj = new Category();
$userObj = new User();
$borrowListObj = new BorrowLists();
$borrowDetailsObj = new BorrowDetails();

//fetch user informatio based on ID
$userID = $_SESSION["user_id"];
$user = $userObj->fetchUser($userID);
$userTypeID = $user["userTypeID"];

// --- MODAL/STATUS LOGIC ---
$list_status = $_GET['status'] ?? null;
$copies_added = (int) ($_GET['copies'] ?? 0);
$book_id_added = (int) ($_GET['bookID'] ?? 0); // Correctly using bookID

// New parameters for borrow denial errors
$error_code = $_GET['error_code'] ?? null;
$current_borrowed_count = (int) ($_GET['count'] ?? 0);
$borrow_limit = (int) ($_GET['limit'] ?? 0);

// Check for modal open requests
$current_modal = $_GET['modal'] ?? '';
$modal_book_id = (int) ($_GET['bookID'] ?? 0);
$open_modal = '';

if ($current_modal === 'borrow') {
  $open_modal = 'borrow-modal';
} elseif ($current_modal === 'list') {
  $open_modal = 'list-modal';
}

// Fetch modal data if a modal is requested
$modal_book = null;
if ($open_modal && $modal_book_id) {
  $modal_book = $bookObj->fetchBook($modal_book_id);
  // If book is found, update available copies for JS validation
  if ($modal_book) {
    // --- START NEW LOGIC FOR MODAL AVAILABLE COPIES ---
    $pending_copies_modal = $borrowDetailsObj->fetchPendingAndApprovedCopiesForBook($modal_book_id);
    // This is the true number of copies available for a new request/list addition
    $modal_available_copies = max(0, $modal_book['book_copies'] - $pending_copies_modal);
    // --- END NEW LOGIC FOR MODAL AVAILABLE COPIES ---

    $modal_book_title = addslashes(htmlspecialchars($modal_book['book_title']));
  }
}
// -------------------------


//fetch categories
$categories = $bookObj->fetchCategory();
$categoryID = $_GET['view_category'] ?? null;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$booksByCategory = [];

if (!empty($search) || ($categoryID !== null && $categoryID !== "")) {
    // --- SEARCH/FILTER MODE (Similar to booksSection.php) ---
    // If a search term is present OR a specific category is selected, use viewBook
    
    $filteredBooks = $bookObj->viewBook($search, $categoryID); 

    // Determine the title for the search/filter results block
    if (!empty($search) && $categoryID) {
        $category = $categoryObj->fetchCategory($categoryID);
        $category_name = "Results in " . $category["category_name"] . " for \"" . htmlspecialchars($search) . "\"";
    } elseif (!empty($search)) {
        $category_name = "Search Results for \"" . htmlspecialchars($search) . "\"";
    } elseif ($categoryID) {
        $category = $categoryObj->fetchCategory($categoryID);
        $category_name = $category["category_name"];
    }

    if (!empty($filteredBooks) || !empty($search)) { // Always show block if searching/filtering
        $booksByCategory[] = [
            'category_name' => $category_name,
            'books' => $filteredBooks,
            'full_view' => true, // Treat search/filter results as a full view
        ];
    }

} else { 
  // --- DEFAULT CATALOGUE VIEW MODE (No Search Term, All Categories selected) ---
  // Show All Categories and display at least 3 books;
  foreach ($categories as $category) {
    $currentCategoryID = $category['categoryID'];
    // Fetch the limited books for the display grid
    $books = $bookObj->showThreeBooks($currentCategoryID);

    if (!empty($books)) {
      // Get the total count for the 'View All' link
      $total_count = $bookObj->countBooksByCategory($currentCategoryID);

      // Show "View All" button when there are more than three
      $show_view_all = ($total_count > 3);

      $booksByCategory[] = [
        'category_name' => $category['category_name'],
        'books' => $books,
        'categoryID' => $currentCategoryID,
        'show_view_all' => $show_view_all,
        'total_view' => $total_count,
        'full_view' => false,
      ];
    }
  }
}
$books_data = $bookObj->fetchBookTitles();

// Convert to an array for JS lookup
$book_titles_map = [];
foreach ($books_data as $book) {
  $book_titles_map[$book['bookID']] = htmlspecialchars($book['book_title']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WMSU Library Catalogue</title>
  <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
  <link rel="stylesheet" href="../../../public/assets/css/borrower1.css" />
  <link rel="stylesheet" href="../../../public/assets/css/header_footer2.css" />
  <style>
    /* Admin modal style fix for borrower page */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5);
      align-items: center;
      justify-content: center;
    }

    .modal.open {
      display: flex;
    }

    .modal-content {
      background-color: #fefefe;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      width: 90%;
      max-width: 500px;
      position: relative;
    }

    .close-times {
      position: absolute;
      top: 10px;
      right: 20px;
      color: #aaa;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close-times:hover,
    .close-times:focus {
      color: #000;
      text-decoration: none;
      cursor: pointer;
    }

    /* search */

    /* ---- SEARCH ---- */
    .search {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .search input,
    .search select {
      border: 2px solid #ccc;
      border-radius: 0.5rem;
      padding: 0.6rem 1rem;
      outline: none;
      transition: border 0.3s ease, box-shadow 0.3s ease;
    }

    .search input {
      font-size: 1rem;
      width: 60%;
    }

    .search select {
      width: 30% !important;
    }

    .search input:focus,
    .search select:focus {
      border-color: #931c19;
      box-shadow: 0 0 0 3px rgba(147, 28, 25, 0.2);
    }

    .search button {
      padding: 0.6rem 1.2rem;
      font-weight: 500;
      border-radius: 0.5rem;
      background-color: #931c19;
      color: white;
      border: none;
      transition: background-color 0.3s ease, transform 0.2s ease;
      cursor: pointer;
      width: 10%;
    }

    .search button:hover {
      background-color: #610101;
      transform: translateY(-1px);
    }
  </style>
</head>

<body class="min-h-screen">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

    <header class="text-center my-10">
      <h1 class="title  text-4xl sm:text-5xl font-extrabold">
        <span class="text-6xl block">Discover</span>
        Library Collection
      </h1>
      <p class="text-xl mt-2">Explore the best books from every category.</p>
    </header>

    <?php if (empty($booksByCategory)) { ?>
      <div class="text-center py-16 bg-white rounded-xl shadow-lg">
        <p class="text-xl text-gray-500">The library catalogue is currently empty. Check back soon!</p>
      </div>
    <?php } ?>

    <form method="GET" class="search">
      <input type="text" name="search" placeholder="Search book title..." value="<?= $search ?>">
      <select name="view_category" id="category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat["categoryID"] ?>" <?= $categoryID == $cat["categoryID"] ? 'selected' : '' ?>>
            <?= $cat["category_name"] ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="bg-red-800 text-white rounded-lg px-4 py-2">Search</button>
    </form>


    <?php foreach ($booksByCategory as $categoryGroup) { ?>
      <div class="mb-12 bg-white p-6 rounded-xl shadow-lg">
        <div class="flex justify-between items-center border-b-2 border-red-700 pb-3 mb-6">
          <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">
            <?= htmlspecialchars($categoryGroup['category_name']) ?>
          </h2>

          <?php if ($categoryGroup['full_view'] ?? false) { ?>
            <a href="catalogue.php"
              class="text-red-700 hover:text-red-900 font-semibold transition duration-150 flex items-center">
              &larr; Back to Catalogue
            </a>
          <?php } else { ?>
            <a href="catalogue.php?view_category=<?= $categoryGroup['categoryID'] ?>"
              class="<?= $categoryGroup['show_view_all'] ? '' : 'hidden' ?> text-red-700 hover:text-red-900 font-semibold transition duration-150">
              View All (<?= $categoryGroup['total_view'] ?? '' ?>) &rarr;
            </a>
          <?php } ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <?php foreach ($categoryGroup['books'] as $book) {
            $has_active_pending = false;
            $has_active_borrowed = false;
            ?>
            <div class="book-card-base" data-book-id="<?= $book['bookID'] ?>">
              <div class="flex items-start p-4">
                <div
                  class="flex-shrink-0 w-20 h-32 mr-4 shadow-lg rounded-md overflow-hidden bg-gray-200 border-2 border-gray-100">
                  <?php
                  $book_cover_dir = $book['book_cover_dir'] ?? null;
                  if ($book_cover_dir) { ?>
                    <img src="<?= "../../../" . $book_cover_dir ?>" alt="<?= $book['book_title'] ?> Cover"
                      class="w-full h-full object-cover">
                  <?php } else { ?>
                    <div class="book-cover-placeholder">
                      No Cover Available
                    </div>
                  <?php } ?>
                </div>

                <div class="flex-grow leading-tight">
                  <h3 class="text-xl font-bold text-red-800 mb-1 break-words"><a
                      href="viewBook.php?bookID=<?= $book["bookID"] ?>"><?= $book['book_title'] ?></a></h3>
                  <p class="text-sm text-gray-700 break-words"><strong>Author:</strong> <?= htmlspecialchars($book['author_names'] ?? 'N/A') ?></p>
                  <p class="text-sm text-gray-700"><strong>Year:</strong>
                    <?= $book['publication_year'] ?></p>

                  <p class="text-sm text-gray-600 mt-2"><strong>Stock:</strong>
                    <?= $book['book_copies'] ?></p>
                  <p class="text-sm text-gray-600"><strong>Condition:</strong>
                    <?= $book['book_condition'] ?></p>
                </div>
              </div>

              <div class="mt-auto p-4 border-t border-gray-100 flex justify-between items-center">
                <?php
                $copies = $book['book_copies'] ?? 0;
                $status = "Available";
                $status_color = 'text-green-600 bg-green-100';
                $button_disabled = false;
                $bookTitle = htmlspecialchars($book["book_title"]); // HTML encode for JS/PHP output
                $bookID = $book["bookID"]; // Get bookID for the check
            
                // --- START NEW LOGIC: Available for Request Calculation (affects ALL users) ---
                $pending_copies_count = $borrowDetailsObj->fetchPendingAndApprovedCopiesForBook($bookID);
                $available_for_request = max(0, $copies - $pending_copies_count);
                // --- END NEW LOGIC ---
            
                // --- BORROWING LIMIT CHECK START (Non-Staff) ---
                $current_borrowed_count = 0;
                $borrow_limit = 0;
                $final_copies = $available_for_request; // Use the new available count
                $borrow_denied = false;
                $error_message_code = '';
                $max_available = $available_for_request;

                if ($userTypeID != 2) {
                  // Non-staff specific checks
                  $borrow_limit = $userObj->fetchUserLimit($user['userTypeID']);
                  $current_borrowed_count = $borrowDetailsObj->fetchTotalBorrowedBooks($userID);

                  // If user has pending/borrowed copy of *this* book, they are fully disabled
                  $has_active_pending = $borrowDetailsObj->fetchPendingBooks($userID, $bookID);
                  $has_active_borrowed = $borrowDetailsObj->fetchBorrowedBooks($userID, $bookID);

                  if ($has_active_pending || $has_active_borrowed) {
                    $button_disabled = true;
                    $status = $has_active_pending ? "Request Pending" : "Borrowed";
                    $status_color = 'text-yellow-600 bg-yellow-100';
                    // No further borrow denial check needed if disabled by per-book limit
                  } else {
                    // Check overall limit vs availability
                    $max_available_to_borrow = $borrow_limit - $current_borrowed_count;
                    $final_copies = min($available_for_request, $max_available_to_borrow);

                    if ($final_copies <= 0) {
                      $borrow_denied = true;
                      $button_disabled = true; // Disable button immediately
                      if ($available_for_request <= 0) {
                        $error_message_code = 'unavailable'; // All copies of this book are currently reserved.
                      } else {
                        $error_message_code = 'limit'; // User cannot borrow due to personal limit.
                      }
                      $status = "Unavailable";
                      $status_color = 'text-red-600 bg-red-100';
                    }
                  }
                }
                // --- BORROWING LIMIT CHECK END ---
            
                // If copies are reserved, show status as fully reserved/unavailable
                if ($available_for_request <= 0 && !$borrow_denied) {
                  $status = "Fully Reserved";
                  $status_color = 'text-blue-600 bg-blue-100';
                  $button_disabled = true;
                }

                //for borrow button (Staff uses modal, Non-Staff uses direct redirect or error link)
                $borrow_action = '';
                if ($userTypeID == 2) {
                  // Staff: use URL parameter to open modal
                  $borrow_action = "href='catalogue.php?modal=borrow&bookID={$bookID}'";
                } elseif (!$borrow_denied) {
                  // Non-Staff (allowed): direct link to confirmation.php
                  $borrow_action = "href='confirmation.php?bookID={$book['bookID']}&copies=1'";
                } else {
                  // Non-Staff (denied): link to error status page
                  // We must pass the bookID, the status, and the specific error code.
                  $borrow_action = "href='catalogue.php?status=borrow_denied&bookID={$bookID}&error_code={$error_message_code}&count={$current_borrowed_count}&limit={$borrow_limit}'";
                }

                // for add to list button
                $add_to_list_action = '';
                if (!$button_disabled) {
                  if ($userTypeID == 2) {
                    $add_to_list_action = "href='catalogue.php?modal=list&bookID={$bookID}'";
                  } else {
                    // Non-staff still uses JS function for direct add-to-list action (no modal)
                    $add_to_list_action = "onclick=\"event.preventDefault(); addToList({$book['bookID']})\"";
                  }
                }
                ?>


                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $status_color ?>">
                  <?= $status ?>
                </span>

                <a <?= $borrow_action ?>
                  class="text-sm font-medium cursor-pointer transition duration-300 px-4 py-2 rounded-full <?= $button_disabled ? 'bg-gray-300 text-gray-600 cursor-not-allowed pointer-events-none' : 'bg-red-800 text-white shadow-md' ?>">
                  <?= $button_disabled ? (($has_active_borrowed || $has_active_pending) ? 'Limit 1 Copy' : 'Unavailable') : '+ Borrow Now' ?>
                </a>

                <a <?= $add_to_list_action ?>
                  class="text-sm font-medium transition duration-300 cursor-pointer px-4 py-2 rounded-full <?= $button_disabled ? 'hidden' : 'bg-red-800 text-white shadow-md' ?>">
                  + Add To List
                </a>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>
    <?php } ?>
  </div>

  <?php require_once(__DIR__ . '/../shared/footer.php'); ?>

  <div id="borrow-modal" class="modal <?= $open_modal == 'borrow-modal' ? 'open' : '' ?>">
    <div class="modal-content">
      <span class="close-times" onclick="closeModalAndRedirect()">&times;</span>
      <h3 class="text-xl font-bold text-red-800 border-b pb-3 mb-4">Borrow Copies (Staff)</h3>

      <p class="text-gray-700 mb-3">Book: <strong id="modal-borrow-book-title">
          <?= $modal_book_title ?? '' ?>
        </strong></p>
      <form id="borrow-form" method="GET" action="confirmation.php">
        <input type="hidden" name="bookID" id="modal-borrow-book-id" value="<?= $modal_book_id ?>">

        <div class="mb-4">
          <label for="copies" class="block text-sm font-medium text-gray-700 mb-1">Number of Copies to Borrow:</label>
          <input type="number" name="copies" id="modal-borrow-copies-input" min="1"
            value="<?= $modal_available_copies > 0 ? 1 : 0 ?>" required max="<?= $modal_available_copies ?? 0 ?>"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
            oninput="checkCopiesLimit('modal-borrow-copies-input', 'modal-borrow-submit-btn', 'borrow-copies-error', '<?= $modal_available_copies ?? 0 ?>')">
          <p id="borrow-copies-error" class="text-sm text-red-600 mt-1 hidden">Cannot borrow more than <span
              id="max-borrow-copies"><?= $modal_available_copies ?? 0 ?></span> copies.</p>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" onclick="closeModalAndRedirect()"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">Cancel</button>
          <button type="submit" id="modal-borrow-submit-btn"
            class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition" <?= ($modal_available_copies ?? 0) > 0 ? '' : 'disabled' ?>>Confirm Borrow</button>
        </div>
      </form>
    </div>
  </div>

  <div id="list-modal" class="modal <?= $open_modal == 'list-modal' ? 'open' : '' ?>">
    <div class="modal-content">
      <span class="close-times" onclick="closeModalAndRedirect()">&times;</span>
      <h3 class="text-xl font-bold text-red-800 border-b pb-3 mb-4">Add to List Copies (Staff)</h3>

      <p class="text-gray-700 mb-3">Book: <strong id="modal-list-book-title">
          <?= $modal_book_title ?? '' ?>
        </strong></p>
      <form onsubmit="event.preventDefault(); confirmAddToList()">
        <input type="hidden" id="modal-list-book-id" value="<?= $modal_book_id ?>">

        <div class="mb-4">
          <label for="list-copies" class="block text-sm font-medium text-gray-700 mb-1">Number of Copies to Add to
            List:</label>
          <input type="number" name="list-copies" id="modal-list-copies-input" min="1"
            value="<?= $modal_available_copies > 0 ? 1 : 0 ?>" required max="<?= $modal_available_copies ?? 0 ?>"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
            oninput="checkCopiesLimit('modal-list-copies-input', 'modal-list-submit-btn', 'list-copies-error', '<?= $modal_available_copies ?? 0 ?>')">
          <p id="list-copies-error" class="text-sm text-red-600 mt-1 hidden">Cannot add more than <span
              id="max-list-copies"><?= $modal_available_copies ?? 0 ?></span> copies.</p>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" onclick="closeModalAndRedirect()"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">Cancel</button>
          <button type="submit" id="modal-list-submit-btn"
            class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition" <?= ($modal_available_copies ?? 0) > 0 ? '' : 'disabled' ?>>Confirm Add</button>
        </div>
      </form>
    </div>
  </div>

  <div id="success-modal" class="modal <?= $list_status == 'added' ? 'open' : '' ?>">
    <div class="modal-content max-w-sm text-center">
      <span class="close-times" onclick="closeStatusModal()">&times;</span>
      <h3 class="text-xl font-bold text-red-800 mb-2">Success!</h3>
      <p class="text-gray-700">
        <span id="success-book-title" class="font-semibold"></span> has been successfully added to your list!
        You now have <span id="success-total-copies" class="font-bold"></span> copies of that book.
      </p>
      <div class="mt-6 flex justify-center">
        <button type="button" onclick="closeStatusModal()"
          class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition">Close</button>
      </div>
    </div>
  </div>

  <div id="message-modal" class="modal <?= ($list_status && $list_status != 'added') ? 'open' : '' ?>">
    <div class="modal-content max-w-xs mx-4">
      <span class="close-times" onclick="closeStatusModal()">&times;</span>
      <div class="flex justify-center flex-col pt-4">
        <span id="book-title" class="font-bold text-center"></span>
        <p id="message-modal-body" class="mb-4 font-medium text-center text-gray-700"> </p>
      </div>
      <div class="flex justify-center">
        <button onclick="closeStatusModal()"
          class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition text-sm">Dismiss</button>
      </div>
    </div>
  </div>


</body>

<script src="../../../public/assets/js/borrower.js"></script>

<script>
  // PHP variables dumped for JS use
  const book_titles_map = <?= json_encode($book_titles_map) ?>;
  const LIST_STATUS = "<?= $list_status ?>";
  const COPIES_ADDED = <?= $copies_added ?>; // This variable now holds the TOTAL copies in list
  const BOOK_ID_ADDED = <?= $book_id_added ?>;
  const MODAL_BOOK_ID = <?= $modal_book_id ?>;
  const MODAL_BOOK_TITLE = "<?= $modal_book_title ?? '' ?>";
  const MODAL_AVAILABLE_COPIES = <?= $modal_available_copies ?? 0 ?>;
  const ERROR_CODE = "<?= $error_code ?>";
  const BORROWED_COUNT = <?= $current_borrowed_count ?>;
  const BORROW_LIMIT = <?= $borrow_limit ?>;

  function getBookTitle(bookID) {
    return book_titles_map[String(bookID)] || 'Unknown Book';
  }

  // --- NEW MODAL OPENING/CLOSING LOGIC (Admin style) ---

  // Function to close the primary operational modals (borrow/list) by clearing URL parameters
  function closeModalAndRedirect() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.delete("modal");
    currentUrl.searchParams.delete("bookID");

    window.location.href = currentUrl.toString();
  }

  // Function to close the success/error status modals
  function closeStatusModal() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.delete("status");
    currentUrl.searchParams.delete("copies");
    currentUrl.searchParams.delete("bookID");
    currentUrl.searchParams.delete("error_code"); // Clear specific error parameters
    currentUrl.searchParams.delete("count");
    currentUrl.searchParams.delete("limit");

    window.location.href = currentUrl.toString();
  }

  // Close Modal when clicking outside the content area
  document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener("click", (e) => {
      if (e.target.classList.contains("modal")) {
        // Check if it's an operation modal (borrow/list)
        if (e.target.id === 'borrow-modal' || e.target.id === 'list-modal') {
          closeModalAndRedirect();
        }
        // Check if it's a status modal (success/message)
        if (e.target.id === 'success-modal' || e.target.id === 'message-modal') {
          closeStatusModal();
        }
      }
    });
  });

  // --- END NEW MODAL LOGIC ---


  // Handle direct Add To List for non staff (no modal)
  function addToList(bookID) {
    window.location.href = `../../../app/controllers/borrowListController.php?action=add&bookID=${bookID}&copies=1&source=catalogue.php`;
  }

  // Handle confirmation for Add To List for staff (from modal)
  function confirmAddToList() {
    const bookID = document.getElementById('modal-list-book-id').value;
    const copies = parseInt(document.getElementById('modal-list-copies-input').value);

    // This redirects and triggers a status modal on success/failure
    window.location.href = `../../../app/controllers/borrowListController.php?action=add&bookID=${bookID}&copies=${copies}&source=catalogue.php`;
  }

  // Function to check copies limit for modals (unchanged functionality)
  function checkCopiesLimit(inputID, submitBtnID, errorMsgID, maxCopiesValue) {
    const input = document.getElementById(inputID);
    const submitBtn = document.getElementById(submitBtnID);
    const errorMsg = document.getElementById(errorMsgID);
    const requestedCopies = parseInt(input.value);
    const maxCopies = parseInt(maxCopiesValue);

    if (requestedCopies > maxCopies || requestedCopies < 1 || isNaN(requestedCopies)) {
      errorMsg.classList.remove('hidden');
      submitBtn.disabled = true;
    } else {
      errorMsg.classList.add('hidden');
      submitBtn.disabled = false;
    }
  }

  // Initial check and population for status messages on load
  document.addEventListener('DOMContentLoaded', function () {
    if (LIST_STATUS && BOOK_ID_ADDED) {
      const title = getBookTitle(BOOK_ID_ADDED);
      const messageModalBody = document.getElementById('message-modal-body');

      // Populate success modal elements if status is 'added'
      if (LIST_STATUS === 'added') {
        document.getElementById('success-book-title').textContent = title;
        // UPDATE: Display the total copies (COPIES_ADDED) in the new location
        document.getElementById('success-total-copies').textContent = COPIES_ADDED;
        return;
      }

      // Populate message modal elements for other statuses (error/existing/denied)
      document.getElementById('book-title').textContent = title;
      let message;

      switch (LIST_STATUS) {
        case 'existing':
          message = ` is already in your list. (Limit 1 Copy)`;
          break;
        case 'error_unavailable':
          message = 'ERROR: Book copies unavailable or action invalid.';
          break;
        case 'error':
          message = 'ERROR: Action failed. Please try again.';
          break;
        case 'borrow_denied':
          if (ERROR_CODE === 'unavailable') {
            message = `All copies of this book are currently reserved.`;
          } else if (ERROR_CODE === 'limit') {
            // The requested error message, populated dynamically
            message = `You cannot borrow any more books at this time due to your current borrow status or limit (You have ${BORROWED_COUNT} out of ${BORROW_LIMIT} allowed).`;
          } else {
            message = 'Borrowing denied due to system restrictions.';
          }
          document.getElementById('book-title').textContent = title;
          break;
        default:
          return;
      }
      messageModalBody.textContent = message;
    }
  });

</script>