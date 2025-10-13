<?php
require_once(__DIR__ . "/../models/manageBook.php");

$bookObj = new Book();

if (isset($_GET['id'])) {
    $bookID = $_GET['id'];

    if ($bookObj->deleteBook($bookID)) {
        header("Location: ../../app/views/librarian/booksSection.php");
        exit;
    } else {
        echo "<script>alert('Failed to delete book.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('No book ID provided.'); window.history.back();</script>";
}
?>
