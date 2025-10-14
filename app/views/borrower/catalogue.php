<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../app/views/borrower/login.php");
  exit;
}
?>


<?php
require_once(__DIR__ . "/../../models/manageBook.php");

$bookObj = new Book();
$books = $bookObj->viewBook();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Books by Category</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../../../public/assets/css/borrower/catalogue.css" />
  <link rel="stylesheet" href="../../../public/assets/css/components/header_footer.css" />
  <link href="https://fonts.googleapis.com/css2?family=Licorice&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-100">
  <div class="container p-6">
    <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>
    <h1 class="text-3xl font-bold mb-6 text-center text-red-800">Library Collection by Category</h1>

    <?php
    $categories = [];
    foreach ($books as $book) {
      $categories[$book['category_name']][] = $book;
    }

    foreach ($categories as $category => $bookList) {
      ?>
      <div class="mb-10">
        <h2 class="text-2xl font-semibold text-gray-800 border-b-2 border-red-700 pb-2 mb-4">
          <?= htmlspecialchars($category) ?>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          <?php foreach ($bookList as $book) { ?>
            <div class="bg-white rounded-xl shadow-md p-4 border border-gray-200 hover:shadow-lg transition">
              <h3 class="text-lg font-bold text-red-800 mb-1"><?= htmlspecialchars($book['book_title']) ?></h3>
              <p class="text-gray-700"><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></p>
              <p class="text-gray-700"><strong>Year:</strong> <?= htmlspecialchars($book['publication_year']) ?></p>

              <?php
              $status = "Available";
              if ($book['book_copies'] <= 0) {
                $status = "Borrowed";
              }
              ?>
              <div class="info flex justify-between items-center">
                <p class="mt-2 font-semibold 
                                <?= $status == 'Available' ? 'text-green-600' :
                                  ($status == 'Borrowed' ? 'text-blue-600' :
                                    ($status == 'Lost' ? 'text-red-600' : 'text-gray-500')) ?>">
                  Status: <?= htmlspecialchars($status) ?>
                </p>

                <a href="../../views/borrower/borrow.php?bookID=<?= $book['bookID'] ?>"
                  class="bg-red-800 rounded-xl p-1 text-white">Borrow Now</a>


              </div>
            </div>
          <?php } ?>
        </div>
      </div>
    <?php } ?>
  </div>

  <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>

</html>