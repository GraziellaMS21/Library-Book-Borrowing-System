<?php

//ensures that a user is logged in
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../app/views/borrower/login.php");
  exit;
}

require_once(__DIR__ . "/../../models/manageBook.php");
require_once(__DIR__ . "/../../models/manageCategory.php");
require_once(__DIR__ . "/../../models/manageBorrowDetails.php");

$bookObj = new Book();
$categoryObj = new Category();
$borrowObj = new BorrowDetails();

$userID = $_SESSION["user_id"];
$borrower = $borrowObj->fetchUser($userID);
$userTypeID = $borrower["userTypeID"];
// var_dump($userTypeID);

$categories = $bookObj->fetchCategory();
$categoryID = $_GET['view_category'] ?? null;

$booksByCategory = [];
if (isset($categoryID)) { //Show all books for the selected category
  // fetch ALL books for the selected category
  $categoryBooks = $bookObj->viewBook(null, $categoryID);
  $category = $categoryObj->fetchCategory($categoryID);
  if (!empty($categoryBooks)) {
    $booksByCategory[] = [
      'category_name' => $category["category_name"],
      'books' => $categoryBooks,
      'full_view' => true,
    ];
  }
} else { // Show All Categories and display at least 3 books;
  foreach ($categories as $category) {
    $categoryID = $category['categoryID'];
    // Fetch the limited books for the display grid
    $books = $bookObj->showThreeBooks($categoryID);

    if (!empty($books)) {
      // Get the total count for the 'View All' link
      $total_count = $bookObj->countBooksByCategory($categoryID);

      // Show "View All" button when there are more than three
      $show_view_all = ($total_count > 3);

      $booksByCategory[] = [
        'category_name' => $category['category_name'],
        'books' => $books,
        'categoryID' => $categoryID,
        'show_view_all' => $show_view_all,
        'total_count' => $total_count,
        'full_view' => false,
      ];
    }
  }
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
  <link rel="stylesheet" href="../../../public/assets/css/header_footer1.css" />
  <link href="https://fonts.googleapis.com/css2?family=Licorice&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

    <header class="text-center my-10">
      <h1 class="text-4xl sm:text-5xl font-extrabold">
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
            <a href="catalogue.php?view_category=<?= $categoryGroup['categoryID'] ?? 0 ?>"
              class="<?= $categoryGroup['show_view_all'] ? '' : 'hidden' ?> text-red-700 hover:text-red-900 font-semibold transition duration-150">
              View All (<?= $categoryGroup['total_count'] ?? '' ?>) &rarr;
            </a>
          <?php } ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <?php foreach ($categoryGroup['books'] as $book) { ?>
            <div class="book-card-base">
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
                  <h3 class="text-xl font-bold text-red-800 mb-1 truncate leading-tight"><a
                      href="viewBook.php?bookID=<?= $book["bookID"] ?>"><?= $book['book_title'] ?></a></h3>
                  <p class="text-sm text-gray-700"><strong>Author:</strong> <?= $book['author'] ?></p>
                  <p class="text-sm text-gray-700"><strong>Year:</strong>
                    <?= $book['publication_year'] ?></p>

                  <p class="text-sm text-gray-600 mt-2"><strong>Copies:</strong>
                    <?= $book['book_copies'] ?></p>
                  <p class="text-sm text-gray-600"><strong>Condition:</strong>
                    <?= $book['book_condition'] ?></p>
                </div>
              </div>

              <div class="mt-auto p-4 border-t border-gray-100 flex justify-between items-center">
                <?php
                $copies = $book['book_copies'] ?? 0;
                $status = "Available";
                $status_class = 'text-green-600 bg-green-100';
                $button_disabled = false;
                $bookTitle = htmlspecialchars($book["book_title"], ENT_QUOTES); // Sanitize for JS string

                if ($copies <= 0) {
                  $status = "Borrowed";
                  $status_class = 'text-blue-600 bg-blue-100';
                  $button_disabled = true;
                }

                // Determine action based on user type (1=Student, 2=Staff, 3=Guest)
                $borrow_action = '';
                if (!$button_disabled) {
                  if ($userTypeID == 2) {
                    // Staff: Show Borrow Modal
                    $borrow_action = "onclick=\"event.preventDefault(); showBorrowModal({$book['bookID']}, '{$bookTitle}', {$copies})\"";
                  } else {
                    // Student/Guest: Direct redirect to confirmation
                    $borrow_action = "href='confirmation.php?bookID={$book['bookID']}&copies=1'";
                  }
                } else {
                  $borrow_action = "onclick=\"event.preventDefault()\"";
                }

                $add_to_list_action = '';
                if (!$button_disabled) {
                  if ($userTypeID == 2) {
                    // Staff: Show Add To List Modal (to select copies)
                    $add_to_list_action = "onclick=\"event.preventDefault(); showAddToListModal({$book['bookID']}, '{$bookTitle}', {$copies})\"";
                  } else {
                    // Student/Guest: Direct show success modal (assuming 1 copy added to list)
                    $add_to_list_action = "onclick=\"event.preventDefault(); success('{$bookTitle}', 1)\"";
                  }
                } else {
                  $add_to_list_action = "onclick=\"event.preventDefault()\"";
                }
                ?>
                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $status_class ?>">
                  <?= $status ?>
                </span>

                <a <?= $borrow_action ?>
                  class="text-sm font-medium cursor-pointer transition duration-300 px-4 py-2 rounded-full <?= $button_disabled ? 'bg-gray-300 text-gray-600 cursor-not-allowed pointer-events-none' : 'bg-red-800 text-white shadow-md' ?>">
                  <?= $button_disabled ? 'Unavailable' : '+ Borrow Now' ?>
                </a>

                <button <?= $add_to_list_action ?>
                  class="text-sm font-medium transition duration-300 px-4 py-2 rounded-full <?= $button_disabled ? 'hidden' : 'bg-red-800 text-white shadow-md' ?>">
                  + Add To List
                </button>
              </div>
            </div>
            </a>
          <?php } ?>
        </div>
      </div>
    <?php } ?>
  </div>

  <?php require_once(__DIR__ . '/../shared/footer.php'); ?>

  <div id="borrow-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-2xl p-6 w-full max-w-md mx-4">
      <div class="flex justify-between items-center border-b pb-3 mb-4">
        <h3 class="text-xl font-bold text-red-800">Borrow Copies</h3>
        <button onclick="hideModal('borrow-modal')" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
      </div>

      <p class="text-gray-700 mb-3">Book: <strong id="modal-borrow-book-title"></strong></p>
      <form id="borrow-form" method="GET" action="confirmation.php">
        <input type="hidden" name="bookID" id="modal-borrow-book-id">

        <div class="mb-4">
          <label for="copies" class="block text-sm font-medium text-gray-700 mb-1">Number of Copies to Borrow:</label>
          <input type="number" name="copies" id="modal-borrow-copies-input" min="1" required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
            oninput="checkCopiesLimit('modal-borrow-copies-input', 'modal-borrow-submit-btn', 'borrow-copies-error', 'max-borrow-copies')">
          <p id="borrow-copies-error" class="text-sm text-red-600 mt-1 hidden">Cannot borrow more than <span
              id="max-borrow-copies"></span> copies.</p>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" onclick="hideModal('borrow-modal')"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">Cancel</button>
          <button type="submit" id="modal-borrow-submit-btn"
            class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition">Confirm Borrow</button>
        </div>
      </form>
    </div>
  </div>

  <div id="list-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-2xl p-6 w-full max-w-md mx-4">
      <div class="flex justify-between items-center border-b pb-3 mb-4">
        <h3 class="text-xl font-bold text-red-800">Add to List Copies</h3>
        <button onclick="hideModal('list-modal')" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
      </div>

      <p class="text-gray-700 mb-3">Book: <strong id="modal-list-book-title"></strong></p>
      <form onsubmit="event.preventDefault(); confirmAddToList()">
        <input type="hidden" id="modal-list-book-id">

        <div class="mb-4">
          <label for="list-copies" class="block text-sm font-medium text-gray-700 mb-1">Number of Copies to Add to
            List:</label>
          <input type="number" name="list-copies" id="modal-list-copies-input" min="1" required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-500 focus:border-red-500"
            oninput="checkCopiesLimit('modal-list-copies-input', 'modal-list-submit-btn', 'list-copies-error', 'max-list-copies')">
          <p id="list-copies-error" class="text-sm text-red-600 mt-1 hidden">Cannot add more than <span
              id="max-list-copies"></span> copies.</p>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" onclick="hideModal('list-modal')"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">Cancel</button>
          <button type="submit" id="modal-list-submit-btn"
            class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition">Confirm Add</button>
        </div>
      </form>
    </div>
  </div>

  <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-2xl p-6 w-full max-w-md mx-4 text-center">
      <div class="flex justify-center mb-4">
        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <h3 class="text-xl font-bold text-red-800 mb-2">Success!</h3>
      <p class="text-gray-700"><span id="success-book-title" class="font-semibold"></span> (<span
          id="success-no-copies"></span> copies) has been successfully added to your list!</p>
      <div class="mt-6 flex justify-center">
        <button type="button" onclick="hideModal('success-modal')"
          class="px-4 py-2 bg-red-800 text-white rounded-md hover:bg-red-900 transition">Close</button>
      </div>
    </div>
  </div>

</body>

<script src="../../../public/assets/js/borrower.js"></script>

<script>
  let maxBorrowCopies = 0;
  let maxListCopies = 0;


  //show borrow modal (Staff only)
  function showBorrowModal(bookID, title, availableCopies) {
    maxBorrowCopies = availableCopies;
    document.getElementById('modal-borrow-book-id').value = bookID;
    document.getElementById('modal-borrow-book-title').textContent = title;
    document.getElementById('modal-borrow-copies-input').value = 1;
    document.getElementById('modal-borrow-copies-input').setAttribute('max', availableCopies);
    document.getElementById('max-borrow-copies').textContent = availableCopies;

    // Reset error/button state
    document.getElementById('borrow-copies-error').classList.add('hidden');
    document.getElementById('modal-borrow-submit-btn').disabled = false;

    document.getElementById('borrow-modal').classList.remove('hidden');
    document.getElementById('borrow-modal').classList.add('flex');
  }

  //show add to list modal (Staff only)
  function showAddToListModal(bookID, title, availableCopies) {
    maxListCopies = availableCopies;
    document.getElementById('modal-list-book-id').value = bookID;
    document.getElementById('modal-list-book-title').textContent = title;
    document.getElementById('modal-list-copies-input').value = 1;
    document.getElementById('modal-list-copies-input').setAttribute('max', availableCopies);
    document.getElementById('max-list-copies').textContent = availableCopies;

    // Reset error/button state
    document.getElementById('list-copies-error').classList.add('hidden');
    document.getElementById('modal-list-submit-btn').disabled = false;

    document.getElementById('list-modal').classList.remove('hidden');
    document.getElementById('list-modal').classList.add('flex');
  }

  // Handle confirmation for Add To List (Staff only)
  function confirmAddToList() {
    const title = document.getElementById('modal-list-book-title').textContent;
    const copies = document.getElementById('modal-list-copies-input').value;
    // In a real application, you would perform an AJAX request here to add the book to the list.
    // For this fix, we simply show the success modal.
    hideModal('list-modal');
    success(title, copies);
  }

  // Show success modal (Used by Student/Guest for direct Add To List, and Staff after confirmAddToList)
  function success(title, copies) {
    document.getElementById('success-book-title').textContent = title;
    document.getElementById('success-no-copies').textContent = copies;
    document.getElementById('success-modal').classList.remove('hidden');
    document.getElementById('success-modal').classList.add('flex');
  }

  //close modal
  function hideModal(modalID) {
    document.getElementById(modalID).classList.add('hidden');
    document.getElementById(modalID).classList.remove('flex'); // Ensure it's fully hidden
  }

  // Universal function to check copies limit for modals
  function checkCopiesLimit(inputID, submitBtnID, errorMsgID, maxCopiesSpanID) {
    const input = document.getElementById(inputID);
    const submitBtn = document.getElementById(submitBtnID);
    const errorMsg = document.getElementById(errorMsgID);
    const maxCopiesValue = parseInt(document.getElementById(maxCopiesSpanID).textContent);
    const requestedCopies = parseInt(input.value);

    if (requestedCopies > maxCopiesValue || requestedCopies < 1 || isNaN(requestedCopies)) {
      errorMsg.classList.remove('hidden');
      submitBtn.disabled = true;
    } else {
      errorMsg.classList.add('hidden');
      submitBtn.disabled = false;
    }
  }
</script>

</html>