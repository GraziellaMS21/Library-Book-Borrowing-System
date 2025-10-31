<?php
session_start();
require_once(__DIR__ . "/../models/manageCategory.php");

$errors = [];
$category = [];

$categoryObj = new Category();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$categoryID = $_POST["categoryID"] ?? $_GET["id"] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category["category_name"] = trim(htmlspecialchars($_POST["category_name"] ?? ''));
    if (empty($category["category_name"])) {
        $errors["category_name"] = "Category Name is required.";
    }

    if (empty($errors) && $categoryObj->isCategoryExist($category["category_name"], null)) {
        $errors["category_name"] = "Category Name already exists.";
    }

    $categoryObj->category_name = $category["category_name"];
    if ($action === 'add') {
        if (empty(array_filter($errors))) {
            if ($categoryObj->addCategory()) {
                header("Location: ../../app/views/librarian/categorySection.php?success=add");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to add category due to a database error."];
                $_SESSION["old"] = $category;
                header("Location: ../../app/views/librarian/categorySection.php?modal=add");
                exit;
            }
        } else {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $category;
            header("Location: ../../app/views/librarian/categorySection.php?modal=add");
            exit;
        }
    } elseif ($action === 'edit' && $categoryID) {
        if (empty(array_filter($errors))) {
            $categoryObj->category_name = $category["category_name"];

            if ($categoryObj->editCategory($categoryID)) {
                header("Location: ../../app/views/librarian/categorySection.php?success=edit");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to update category due to a database error."];
                $_SESSION["old"] = $category;
                header("Location: ../../app/views/librarian/categorySection.php?modal=edit&id={$categoryID}");
                exit;
            }
        } else {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $category;
            header("Location: ../../app/views/librarian/categorySection.php?modal=edit&id={$categoryID}");
            exit;
        }
    }
}

if ($action === 'delete' && $categoryID) {
    if ($categoryObj->deleteCategory($categoryID)) {
        header("Location: ../../app/views/librarian/categorySection.php?success=delete");
        exit;
    } else {
        $_SESSION["errors"] = ["general" => "Failed to delete category."];
        header("Location: ../../app/views/librarian/categorySection.php?error=delete");
        exit;
    }
}

if (!isset($_GET['action']) && !isset($_POST['action']) && $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../../app/views/librarian/categorySection.php");
    exit;
}
?>