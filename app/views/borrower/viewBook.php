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

$bookID = $_GET['bookID'] ?? null;
$book = null;

if ($bookID) {
    // fetchBook includes category name
    $book = $bookObj->fetchBook($bookID);
}

if (!$book) {
    // Redirect or show an error if the book is not found
    header("Location: catalogue.php");
    exit;
}

// Extract essential book details
$book_title = htmlspecialchars($book['book_title']);
$author = htmlspecialchars($book['author']);
$category_name = htmlspecialchars($book['category_name']);
$publication_name = htmlspecialchars($book['publication_name']);
$publication_year = htmlspecialchars($book['publication_year']);
$ISBN = htmlspecialchars($book['ISBN']);
$book_copies = htmlspecialchars($book['book_copies']);
$book_condition = htmlspecialchars($book['book_condition']);
$book_cover_dir = $book['book_cover_dir'] ?? null;

$copies = $book['book_copies'] ?? 0;
$status = $copies <= 0 ? "Borrowed" : "Available";
$status_class = $copies <= 0 ? 'text-blue-600 bg-blue-100' : 'text-green-600 bg-green-100';
$button_disabled = $copies <= 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Book: <?= $book_title ?></title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/catalogue.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
    <link href="https://fonts.googleapis.com/css2?family=Licorice&display=swap" rel="stylesheet">
    <style>
        .licorice-font {
            font-family: 'Licorice', cursive;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-red-800 tracking-tight">
                <span class="licorice-font text-6xl block">Detail View</span>
                <?= $book_title ?>
            </h1>
            <p class="text-xl text-gray-600 mt-2">Complete information about the book.</p>
        </header>

        <div class="mb-12 bg-white p-8 rounded-xl shadow-lg border-t-4 border-red-700">
            <div class="flex justify-start items-center border-b-2 border-gray-200 pb-3 mb-6">
                <a href="catalogue.php" class="text-red-700 hover:text-red-900 font-semibold transition duration-150 flex items-center">
                    &larr; Back to Catalogue
                </a>
            </div>

            <div class="flex flex-col md:flex-row gap-8">
                <div class="flex-shrink-0 w-full md:w-1/3 max-w-sm mx-auto md:mx-0">
                    <div class="w-full h-96 shadow-2xl rounded-lg overflow-hidden bg-gray-200 border-4 border-gray-100">
                        <?php 
                        if ($book_cover_dir) { ?>
                            <img src="<?= "../../../" . $book_cover_dir ?>" alt="<?= $book_title ?> Cover"
                                class="w-full h-full object-cover">
                        <?php } else { ?>
                            <div class="flex items-center justify-center w-full h-full text-lg text-gray-500 text-center p-4">
                                No Cover Available
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="flex-grow md:w-2/3">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4"><?= $book_title ?></h2>
                    
                    <div class="space-y-4 text-lg">
                        <p class="text-gray-700"><strong>Author:</strong> <?= $author ?></p>
                        <p class="text-gray-700"><strong>Category:</strong> <?= $category_name ?></p>
                        <p class="text-gray-700"><strong>Publisher:</strong> <?= $publication_name ?></p>
                        <p class="text-gray-700"><strong>Publication Year:</strong> <?= $publication_year ?></p>
                        <p class="text-gray-700"><strong>ISBN:</strong> <?= $ISBN ?></p>
                        <p class="text-gray-700"><strong>Book Condition:</strong> <?= $book_condition ?></p>
                        <p class="text-gray-700">
                            <strong>Copies Available:</strong> 
                            <span class="font-bold <?= $copies > 0 ? 'text-green-600' : 'text-red-600' ?>"><?= $book_copies ?></span>
                        </p>
                        
                        <div class="pt-4 flex items-center gap-4">
                            <span class="px-4 py-2 text-sm font-semibold rounded-full <?= $status_class ?>">
                                Status: <?= $status ?>
                            </span>

                            <a href="borrow.php?bookID=<?= $book['bookID'] ?>"
                                class="text-lg font-medium transition duration-300 px-6 py-2 rounded-full shadow-lg
                                <?= $button_disabled 
                                    ? 'bg-gray-300 text-gray-600 cursor-not-allowed pointer-events-none' 
                                    : 'bg-red-800 text-white hover:bg-red-900 hover:shadow-xl' ?>">
                                <?= $button_disabled ? 'Cannot Borrow' : '+ Borrow This Book' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>

</html>