<?php
session_start();
require_once(__DIR__ . "/../models/manageBook.php");
$bookObj = new Book();

$errors = [];
$book = [];

// When opening the edit page via GET
if (isset($_GET["id"]) && $_SERVER["REQUEST_METHOD"] === "GET") {
    $pid = (int) $_GET["id"];
    $book = $bookObj->fetchBook($pid);

    if (!$book) {
        echo '<script>alert("No Book Found!");</script>';
        exit;
    }

    $_SESSION["old"] = $book;
    header("Location: ../../app/views/librarian/editBook.php?id=$pid");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bookID = (int) ($_GET["id"] ?? 0); 
    if ($bookID <= 0) {
        echo "Invalid book ID.";
        exit;
    }

    // sanitize input
    $book["book_title"] = trim($_POST["book_title"]);
    $book["author"] = trim($_POST["author"]);
    $book["categoryID"] = trim($_POST["categoryID"]);
    $book["publication_name"] = trim($_POST["publication_name"]);
    $book["publication_year"] = trim($_POST["publication_year"]);
    $book["ISBN"] = trim($_POST["ISBN"]);
    $book["book_copies"] = trim($_POST["book_copies"]);
    $book["book_condition"] = trim($_POST["book_condition"]);

    // Validation
    if (empty($book["book_title"])) $errors["book_title"] = "Book Title is required";
    if (empty($book["author"])) $errors["author"] = "Author is required";
    if (empty($book["categoryID"])) $errors["categoryID"] = "Please Select a Category";
    if (empty($book["publication_name"])) $errors["publication_name"] = "Publication Name is required";
    if (empty($book["publication_year"])) $errors["publication_year"] = "Publication Year is required";
    if (empty($book["ISBN"])) $errors["ISBN"] = "ISBN is required";
    if (empty($book["book_copies"])) $errors["book_copies"] = "Please Enter Number of Copies";
    elseif (!is_numeric($book["book_copies"])) $errors["book_copies"] = "Invalid Format";
    if (empty($book["book_condition"])) $errors["book_condition"] = "Please Describe Book Condition";

    // If validation passes
    if (empty(array_filter($errors))) {
        $bookObj->book_title = $book["book_title"];
        $bookObj->author = $book["author"];
        $bookObj->categoryID = $book["categoryID"];
        $bookObj->publication_name = $book["publication_name"];
        $bookObj->publication_year = $book["publication_year"];
        $bookObj->ISBN = $book["ISBN"];
        $bookObj->book_copies = $book["book_copies"];
        $bookObj->book_condition = $book["book_condition"];

        if ($bookObj->editBook($bookID)) {
            header("Location: ../../app/views/librarian/booksSection.php");
            exit;
        } else {
            echo "FAILED to update.";
        }
    } else {
        // redirect back WITH id
        $_SESSION["errors"] = $errors;
        $_SESSION["old"] = $book;
        header("Location: ../../app/views/librarian/editBook.php?id=$bookID");
        exit;
    }
}
?>
