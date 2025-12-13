<?php

session_start();
// REMOVED: The check that redirects to login if user_id is not set.

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

// UPDATED: Handle Guest vs Logged In User
$userID = $_SESSION["user_id"] ?? null;
$user = null;
$userTypeID = 0; // 0 indicates Guest/Not Logged In

if ($userID) {
  $user = $userObj->fetchUser($userID);
  $userTypeID = $user["userTypeID"];
}

// --- MODAL/STATUS LOGIC ---
$list_status = $_GET['status'] ?? null;
$copies_added = (int) ($_GET['copies'] ?? 0);
$book_id_added = (int) ($_GET['bookID'] ?? 0);

$error_code = $_GET['error_code'] ?? null;
$current_borrowed_count = (int) ($_GET['count'] ?? 0);
$borrow_limit = (int) ($_GET['limit'] ?? 0);

$current_modal = $_GET['modal'] ?? '';
$modal_book_id = (int) ($_GET['bookID'] ?? 0);
$open_modal = '';

if ($current_modal === 'borrow') {
  $open_modal = 'borrow-modal';
} elseif ($current_modal === 'list') {
  $open_modal = 'list-modal';
}

$modal_book = null;
$modal_available_copies = 0;
if ($open_modal && $modal_book_id) {
  $modal_book = $bookObj->fetchBook($modal_book_id);
  if ($modal_book) {
    $pending_copies_modal = $borrowDetailsObj->fetchPendingAndApprovedCopiesForBook($modal_book_id);
    $modal_available_copies = max(0, $modal_book['book_copies'] - $pending_copies_modal);
    $modal_book_title = addslashes(htmlspecialchars($modal_book['book_title']));
  }
}

$categories = $bookObj->fetchCategory();
$categoryID = $_GET['view_category'] ?? null;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$booksByCategory = [];

if (!empty($search) || ($categoryID !== null && $categoryID !== "")) {
  $filteredBooks = $bookObj->viewBook($search, $categoryID);

  if (!empty($search) && $categoryID) {
    $category = $categoryObj->fetchCategory($categoryID);
    $category_name = "Results in " . $category["category_name"] . " for \"" . htmlspecialchars($search) . "\"";
  } elseif (!empty($search)) {
    $category_name = "Search Results for \"" . htmlspecialchars($search) . "\"";
  } elseif ($categoryID) {
    $category = $categoryObj->fetchCategory($categoryID);
    $category_name = $category["category_name"];
  }

  if (!empty($filteredBooks) || !empty($search)) {
    $booksByCategory[] = [
      'category_name' => $category_name,
      'books' => $filteredBooks,
      'full_view' => true,
    ];
  }

} else {
  foreach ($categories as $category) {
    $currentCategoryID = $category['categoryID'];
    $books = $bookObj->showThreeBooks($currentCategoryID);

    if (!empty($books)) {
      $total_count = $bookObj->countBooksByCategory($currentCategoryID);
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
  <link rel="stylesheet" href="../../../public/assets/css/borrower.css" />
  <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
</head>

<body class="min-h-screen">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <?php if (isset($_SESSION["user_id"])) {
      require_once(__DIR__ . '/../shared/headerBorrower.php');
    } else {
      require_once(__DIR__ . '/../shared/header.php');
    }
    ?>

    <header class="text-center my-10">
      <h1 class="title  text-4xl sm:text-5xl font-extrabold">
        <span class="text-6xl block">Discover</span>
        Library Collection
      </h1>
      <p class="text-xl mt-2 subheading">Explore the best books from every category.</p>
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
                  class="<?= $categoryGroup['show_view_all'] ? '' : 'hidden' ?> text-red-700 viewAll font-semibold">
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
                      <h3 class="text-xl font-bold text-red-800 mb-1 break-words book-title"><a
                          href="viewBook.php?bookID=<?= $book["bookID"] ?>"><?= $book['book_title'] ?></a></h3>
                      <p class="text-sm text-gray-700 break-words"><strong>Author:</strong>
                        <?= htmlspecialchars($book['author_names'] ?? 'N/A') ?></p>
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
                    $bookTitle = htmlspecialchars($book["book_title"]);
                    $bookID = $book["bookID"];

                    $pending_copies_count = $borrowDetailsObj->fetchPendingAndApprovedCopiesForBook($bookID);
                    $available_for_request = max(0, $copies - $pending_copies_count);

                    $current_borrowed_count = 0;
                    $borrow_limit = 0;
                    $final_copies = $available_for_request;
                    $borrow_denied = false;
                    $error_message_code = '';
                    $max_available = $available_for_request;

                    // --- UPDATED: Borrow Logic ---
                    if ($userID) {
                      if ($userTypeID != 2) {
                        // Logged-in Non-staff
                        $borrow_limit = $userObj->fetchUserLimit($userTypeID);
                        $current_borrowed_count = $borrowDetailsObj->fetchTotalBorrowedBooks($userID);

                        $has_active_pending = $borrowDetailsObj->fetchPendingBooks($userID, $bookID);
                        $has_active_borrowed = $borrowDetailsObj->fetchBorrowedBooks($userID, $bookID);

                        if ($has_active_pending || $has_active_borrowed) {
                          $button_disabled = true;
                          $status = $has_active_pending ? "Request Pending" : "Borrowed";
                          $status_color = 'text-yellow-600 bg-yellow-100';
                        } else {
                          $max_available_to_borrow = $borrow_limit - $current_borrowed_count;
                          $final_copies = min($available_for_request, $max_available_to_borrow);

                          if ($final_copies <= 0) {
                            $borrow_denied = true;
                            $button_disabled = true;
                            if ($available_for_request <= 0) {
                              $error_message_code = 'unavailable';
                            } else {
                              $error_message_code = 'limit';
                            }
                            $status = "Unavailable";
                            $status_color = 'text-red-600 bg-red-100';
                          }
                        }
                      }
                    } else {
                      // Not Logged In (Guest)
                      // They can see "Available" status but cannot borrow
                      if ($available_for_request <= 0) {
                        $status = "Fully Reserved";
                        $status_color = 'text-blue-600 bg-blue-100';
                        $button_disabled = true;
                      }
                    }

                    // Status for reserved
                    if ($available_for_request <= 0 && !$borrow_denied && !$button_disabled) {
                      $status = "Fully Reserved";
                      $status_color = 'text-blue-600 bg-blue-100';
                      $button_disabled = true;
                    }

                    $borrow_action = '';
                    $add_to_list_action = '';

                    if ($userID) {
                      // Logged In Logic
                      if ($userTypeID == 2) {
                        $borrow_action = "href='catalogue.php?modal=borrow&bookID={$bookID}'";
                        $add_to_list_action = "href='catalogue.php?modal=list&bookID={$bookID}'";
                      } elseif (!$borrow_denied) {
                        $borrow_action = "href='confirmation.php?bookID={$book['bookID']}&copies=1'";
                        $add_to_list_action = "onclick=\"event.preventDefault(); addToList({$book['bookID']})\"";
                      } else {
                        $borrow_action = "href='catalogue.php?status=borrow_denied&bookID={$bookID}&error_code={$error_message_code}&count={$current_borrowed_count}&limit={$borrow_limit}'";
                      }
                    } else {
                      // Guest Logic: Redirect to Login
                      $borrow_action = "href='login.php'";
                      $add_to_list_action = "href='login.php'";
                    }
                    ?>


                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $status_color ?>">
                      <?= $status ?>
                    </span>

                    <a <?= $borrow_action ?>
                      class="text-sm font-medium cursor-pointer px-4 py-2 rounded-full <?= $button_disabled ? 'bg-gray-300 text-gray-600 cursor-not-allowed pointer-events-none' : 'bg-red-800 borrow-button text-white shadow-md' ?>">
                      <?= $button_disabled ? (($has_active_borrowed || $has_active_pending) ? 'Limit 1 Copy' : 'Unavailable') : ($userID ? '+ Borrow Now' : 'Login to Borrow') ?>
                    </a>

                    <?php if ($userID && !$button_disabled): ?>
                        <a <?= $add_to_list_action ?>
                          class="text-sm font-medium borrow-button cursor-pointer px-4 py-2 rounded-full bg-red-800 text-white shadow-md">
                          + Add To List
                        </a>
                    <?php endif; ?>
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
      <p class="text-gray-700 mb-3">Book: <strong id="modal-borrow-book-title"><?= $modal_book_title ?? '' ?></strong>
      </p>
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
      <p class="text-gray-700 mb-3">Book: <strong id="modal-list-book-title"><?= $modal_book_title ?? '' ?></strong></p>
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
  const COPIES_ADDED = <?= $copies_added ?>;
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

  function closeModalAndRedirect() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.delete("modal");
    currentUrl.searchParams.delete("bookID");
    window.location.href = currentUrl.toString();
  }

  function closeStatusModal() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.delete("status");
    currentUrl.searchParams.delete("copies");
    currentUrl.searchParams.delete("bookID");
    currentUrl.searchParams.delete("error_code");
    currentUrl.searchParams.delete("count");
    currentUrl.searchParams.delete("limit");
    window.location.href = currentUrl.toString();
  }

  document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener("click", (e) => {
      if (e.target.classList.contains("modal")) {
        if (e.target.id === 'borrow-modal' || e.target.id === 'list-modal') {
          closeModalAndRedirect();
        }
        if (e.target.id === 'success-modal' || e.target.id === 'message-modal') {
          closeStatusModal();
        }
      }
    });
  });

  function addToList(bookID) {
    window.location.href = `../../../app/controllers/borrowListController.php?action=add&bookID=${bookID}&copies=1&source=catalogue.php`;
  }

  function confirmAddToList() {
    const bookID = document.getElementById('modal-list-book-id').value;
    const copies = parseInt(document.getElementById('modal-list-copies-input').value);
    window.location.href = `../../../app/controllers/borrowListController.php?action=add&bookID=${bookID}&copies=${copies}&source=catalogue.php`;
  }

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

  document.addEventListener('DOMContentLoaded', function () {
    if (LIST_STATUS && BOOK_ID_ADDED) {
      const title = getBookTitle(BOOK_ID_ADDED);
      const messageModalBody = document.getElementById('message-modal-body');

      if (LIST_STATUS === 'added') {
        document.getElementById('success-book-title').textContent = title;
        document.getElementById('success-total-copies').textContent = COPIES_ADDED;
        return;
      }

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

</html>