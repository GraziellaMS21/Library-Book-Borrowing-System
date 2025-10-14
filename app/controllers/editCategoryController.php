<?php
session_start();
require_once(__DIR__ . "/../models/manageCategory.php");

$errors = [];
$category = [];

$categoryObj = new Category();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoryID = isset($_GET["id"]) ? trim(htmlspecialchars($_GET["id"])) : null;
    $category["category_name"] = trim(htmlspecialchars($_POST["category_name"]));

    if (empty($category["category_name"])) {
        $errors["category_name"] = "Category Name is required.";
    } elseif ($categoryObj->isCategoryExist($category["category_name"], $categoryID)) {
        $errors["category_name"] = "Category Name already exists.";
    }

    if (empty($errors)) {
        $categoryObj->category_name = $category["category_name"];

        if ($categoryObj->editCategory($categoryID)) {
            header("Location: ../../app/views/librarian/addCategory.php");
            exit;
        } else {
            echo "Failed to update category.";
        }
    } else {
        $_SESSION["errors"] = $errors;
        $_SESSION["old"] = $category;

        header("Location: ../../app/views/librarian/editCategory.php?id=$categoryID");
        exit;
    }
}
