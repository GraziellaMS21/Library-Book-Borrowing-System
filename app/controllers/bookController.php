<?php session_start();
require_once(__DIR__ . "/../models/manageBook.php");
$bookObj = new Book();
$book = [];
$errors = [];
$category = $bookObj->fetchCategory();
$action = $_GET["action"];
$bookID = $_POST["bookID"] ?? $_GET["id"] ?? null;

$book["book_title"] = trim(htmlspecialchars($_POST["book_title"]));
$book["author"] = trim(htmlspecialchars($_POST["author"]));
$book["categoryID"] = trim(htmlspecialchars($_POST["categoryID"]));
$book["publication_name"] = trim(htmlspecialchars($_POST["publication_name"]));
$book["publication_year"] = trim(htmlspecialchars($_POST["publication_year"]));
$book["ISBN"] = trim(htmlspecialchars($_POST["ISBN"]));
$book["book_copies"] = trim(htmlspecialchars($_POST["book_copies"]));
$book["book_condition"] = trim(htmlspecialchars($_POST["book_condition"]));

if (empty($book["author"])) {
    $errors["author"] = "Author is required";
}
if (empty($book["categoryID"])) {
    $errors["categoryID"] = "Please Select a Category";
}
if (empty($book["publication_name"])) {
    $errors["publication_name"] = "Publication Name is required";
}
if (empty($book["publication_year"])) {
    $errors["publication_year"] = "Publication Year is required";
} elseif (!is_numeric($book["publication_year"])) {
    $errors["publication_year"] = "Publication Year must be a number";
} elseif ($book["publication_year"] > date("Y")) {
    $errors["publication_year"] = "Publication Year cannot be in the future";
}
if (empty($book["ISBN"])) {
    $errors["ISBN"] = "ISBN is required";
}
if (empty($book["book_copies"])) {
    $errors["book_copies"] = "Please Enter Number of Copies";
} elseif (!is_numeric($book["book_copies"])) {
    $errors["book_copies"] = "Invalid Format";
}
if (empty($book["book_condition"])) {
    $errors["book_condition"] = "Please Describe Book Condition";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($action === 'add') {
        if (empty($book["book_title"])) {
            $errors["book_title"] = "Book Title is required";
        } else if ($bookObj->isTitleExist($book["book_title"], null)) {
            $errors["book_title"] = "Book Title already exist";
        }

        if (empty(array_filter($errors))) {
            $bookObj->book_title = $book["book_title"];
            $bookObj->author = $book["author"];
            $bookObj->categoryID = $book["categoryID"];
            $bookObj->publication_name = $book["publication_name"];
            $bookObj->publication_year = $book["publication_year"];
            $bookObj->ISBN = $book["ISBN"];
            $bookObj->book_copies = $book["book_copies"];
            $bookObj->book_condition = $book["book_condition"];
            $bookObj->date_added = date("Y-m-d");
            if ($bookObj->addBook()) {
                header("Location: ../../app/views/librarian/booksSection.php");
                exit;
            } else {
                echo "FAILED";
            }
        } else {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $book;
            header("Location: ../../app/views/librarian/booksSection.php?modal=add");
            exit;
        }
    } else if ($action === 'edit') {

        if (empty($book["book_title"])) {
            $errors["book_title"] = "Book Title is required";
        } else if ($bookObj->isTitleExist($book["book_title"], $bookID)) {
            $errors["book_title"] = "Book Title already exist";
        }

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
                echo "FAILED";
            }
        } else {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $book;
            header("Location: ../../app/views/librarian/booksSection.php?modal=edit&id={$bookID}");
            exit;
        }
    }
}

if (isset($_GET['id'])) {
    $bookID = $_GET['id'];
    if ($bookObj->deleteBook($bookID)) {
        header("Location: ../../app/views/librarian/booksSection.php");
        exit;
    } else {
        echo "<script>alert('Failed to delete book.');";
    }
} else {
    echo "<script>alert('No book ID provided.');";
}
?>