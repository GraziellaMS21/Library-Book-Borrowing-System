<?php
session_start();
require_once(__DIR__ . "/../models/manageCategory.php");

$errors = [];
$category = [];

$categoryObj = new Category();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $category["category_name"] = trim(htmlspecialchars($_POST["category_name"]));

        if (empty($category["category_name"])) {
            $errors["category_name"] = "Category Name is required.";
        } elseif ($categoryObj->isCategoryExist($category["category_name"], "")) {
            $errors["category_name"] = "Category Name already exists.";
        }

        if (empty($errors)) {
            $categoryObj->category_name = $category["category_name"];
            if ($categoryObj->addCategory()) {
                header("Location: ../../app/views/librarian/categorySection.php");
                exit;
            } else {
                echo "Failed to add category.";
            }
        } else {
            // Use $_SESSION["errors"] and $_SESSION["old"] for consistency with bookController.php
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $category;
            header("Location: ../../app/views/librarian/categorySection.php?modal=add");
            exit;
        }
        break;
    case 'edit':
        $categoryID = $_POST["categoryID"] ?? $_GET["id"];
        $category["category_name"] = trim(htmlspecialchars($_POST["category_name"]));

        if (empty($category["category_name"])) {
            $errors["category_name"] = "Category Name is required.";
        } elseif ($categoryObj->isCategoryExist($category["category_name"], $categoryID)) {
            $errors["category_name"] = "Category Name already exists.";
        }

        if (empty($errors)) {
            $categoryObj->category_name = $category["category_name"];

            if ($categoryObj->editCategory($categoryID)) {
                header("Location: ../../app/views/librarian/categorySection.php");
                exit;
            } else {
                echo "Failed to update category.";
            }
        } else {
            // Use $_SESSION["errors"] and $_SESSION["old"] for consistency with bookController.php
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $category;
            // CORRECTED: Added &id={$categoryID} to the failure redirect
            header("Location: ../../app/views/librarian/categorySection.php?modal=edit&id={$categoryID}");
            exit;
        }
        break;
    case 'delete':
        if (isset($_GET['id'])) {
            $categoryID = $_GET['id'];

            if ($categoryObj->deleteCategory($categoryID)) {
                header("Location: ../../app/views/librarian/categorySection.php");
                exit;
            } else {
                echo "<script>alert('Failed to delete category.');</script>";
                header("Location: ../../app/views/librarian/categorySection.php");
                exit;
            }
        } else {
            echo "<script>alert('No category ID provided.');</script>";
            header("Location: ../../app/views/librarian/categorySection.php");
            exit;
        }
}