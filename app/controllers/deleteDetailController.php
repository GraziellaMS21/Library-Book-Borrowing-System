<?php
require_once(__DIR__ . "/../models/manageCategory.php");

$detailsObj = new Details();


if (isset($_GET['id'])) {
    $detailsID = $_GET['id'];

    if ($detailsObj->deleteDetail($detailsID)) {
        header("Location: ../../app/views/librarian/detailsSection.php");
        exit;
    } else {
        echo "<script>alert('Failed to delete book.');";
    }
} else {
    echo "<script>alert('No book ID provided.');";
}

?>
