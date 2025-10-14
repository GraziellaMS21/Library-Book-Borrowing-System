<?php
require_once(__DIR__ . "/../models/manageCategory.php");

$categoryObj = new Category();

if (isset($_GET['id'])) {
    $categoryID = $_GET['id'];

    if ($categoryObj->deleteCategory($categoryID)) {
        header("Location: ../../app/views/librarian/booksSection.php");
        exit;
    } else {
        echo "<script>alert('Failed to delete book.');";
    }
} else {
    echo "<script>alert('No book ID provided.');";
}

?>
