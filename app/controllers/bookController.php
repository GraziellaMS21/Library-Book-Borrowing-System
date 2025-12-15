<?php session_start();
require_once(__DIR__ . "/../models/manageBook.php");
$bookObj = new Book();
$book = [];
$errors = [];
$category = $bookObj->fetchCategory();
$action = $_GET["action"] ?? null;
$bookID = $_POST["bookID"] ?? $_GET["id"] ?? null;

$upload_dir = __DIR__ . "/../../public/uploads/book_cover_images/";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $book["book_title"] = trim(htmlspecialchars($_POST["book_title"]));

    // CAPTURE AUTHORS ARRAY
    $authorsInput = $_POST["authors"] ?? [];
    // Remove empty strings
    $authorsInput = array_filter($authorsInput, function ($a) {
        return !empty(trim($a));
    });

    // Save to book array for session persistence if error occurs
    $book["authors"] = $authorsInput;

    $book["categoryID"] = trim(htmlspecialchars($_POST["categoryID"]));
    $book["publication_name"] = trim(htmlspecialchars($_POST["publication_name"]));
    $book["publication_year"] = trim(htmlspecialchars($_POST["publication_year"]));
    $book["ISBN"] = trim(htmlspecialchars($_POST["ISBN"]));
    $book["book_copies"] = trim(htmlspecialchars($_POST["book_copies"]));
    $book["book_condition"] = trim(htmlspecialchars($_POST["book_condition"]));
    $book["replacement_cost"] = trim(htmlspecialchars($_POST["replacement_cost"]));


    // VALIDATION
    if (empty($authorsInput)) {
        $errors["author"] = "At least one Author is required";
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
    if (empty($book["replacement_cost"]) || !is_numeric($book["replacement_cost"]) || $book["replacement_cost"] <= 0) {
        $errors["replacement_cost"] = "Replacement cost is required and must be a positive number.";
    }


    if ($action === 'add') {
        $book["book_cover_name"] = $_FILES["book_cover"]["name"] ?? '';
        $book_cover_full_path = $upload_dir . basename($book["book_cover_name"]);

        if (empty($book["book_title"])) {
            $errors["book_title"] = "Book Title is required";
        } else if ($bookObj->isTitleExist($book["book_title"], null)) {
            $errors["book_title"] = "Book Title already exist";
        }

        if (empty($book["ISBN"])) {
            // Already validated above
        } else if ($bookObj->isISBNExist($book["ISBN"], null)) {
            $errors["ISBN"] = "ISBN already exist";
        } // End of ISBN check

        if (empty($book["book_cover_name"]) || $_FILES["book_cover"]["error"] == UPLOAD_ERR_NO_FILE) {
            $errors["book_cover"] = "Upload Book Cover is required";
        } elseif ($_FILES["book_cover"]["error"] !== UPLOAD_ERR_OK) {
            $errors["book_cover"] = "File upload failed (Code: " . $_FILES["book_cover"]["error"] . ")";
        }

        if (empty(array_filter($errors))) {
            if (move_uploaded_file($_FILES["book_cover"]["tmp_name"], $book_cover_full_path)) {
                $bookObj->book_title = $book["book_title"];
                $bookObj->categoryID = $book["categoryID"];
                $bookObj->publication_name = $book["publication_name"];
                $bookObj->publication_year = $book["publication_year"];
                $bookObj->ISBN = $book["ISBN"];
                $bookObj->book_copies = $book["book_copies"];
                $bookObj->book_condition = $book["book_condition"];
                $bookObj->date_added = date("Y-m-d");
                $bookObj->book_cover_name = $book["book_cover_name"];
                $bookObj->book_cover_dir = "public/uploads/book_cover_images/" . basename($book["book_cover_name"]);
                $bookObj->replacement_cost = $book["replacement_cost"];

                // Pass authors array here
                if ($bookObj->addBook($authorsInput, $book["publication_name"])) {
                    header("Location: ../../app/views/librarian/booksSection.php?success=add");
                    exit;
                } else {
                    if (file_exists($book_cover_full_path)) {
                        unlink($book_cover_full_path);
                    }
                    $_SESSION["errors"] = ["general" => "Failed to add book due to a database error. Check for duplicates or invalid data."];
                    $_SESSION["old"] = $book;
                    header("Location: ../../app/views/librarian/booksSection.php?modal=add");
                    exit;
                }
            } else {
                $errors["book_cover"] = "Failed to save the uploaded image.";
            }
        }
        if (!empty($errors)) {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $book;
            header("Location: ../../app/views/librarian/booksSection.php?modal=add");
            exit;
        }

    } else if ($action === 'edit' && $bookID) {
        if (empty($book["book_title"])) {
            $errors["book_title"] = "Book Title is required";
        } else if ($bookObj->isTitleExist($book["book_title"], $bookID)) {
            $errors["book_title"] = "Book Title already exist";
        }

        if (empty($book["ISBN"])) {
            // Already validated
        } else if ($bookObj->isISBNExist($book["ISBN"], $bookID)) {
            $errors["ISBN"] = "ISBN already exist";
        }

        $existing_cover_name = trim(htmlspecialchars($_POST["existing_cover_name"] ?? ''));
        $existing_cover_dir = trim(htmlspecialchars($_POST["existing_cover_dir"] ?? ''));

        $new_cover_name = $existing_cover_name;
        $new_cover_dir = $existing_cover_dir;

        if (isset($_FILES['new_book_cover']) && $_FILES['new_book_cover']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['new_book_cover'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed)) {
                $errors['new_book_cover'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            } else {
                $new_name = uniqid('cover_', true) . "." . $ext;
                $target_path = $upload_dir . $new_name;
                $db_dir_path = "public/uploads/book_cover_images/" . $new_name;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $new_cover_name = $new_name;
                    $new_cover_dir = $db_dir_path;

                    if ($existing_cover_name && file_exists(__DIR__ . "/../../" . $existing_cover_dir)) {
                        unlink(__DIR__ . "/../../" . $existing_cover_dir);
                    }
                } else {
                    $errors['new_book_cover'] = "Failed to move uploaded file.";
                }
            }
        } elseif (isset($_FILES['new_book_cover']) && $_FILES['new_book_cover']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errors['new_book_cover'] = "Upload error (Code: " . $_FILES['new_book_cover']['error'] . ").";
        }


        if (empty(array_filter($errors))) {
            $bookObj->book_title = $book["book_title"];
            // No need to set $bookObj->author anymore, we pass $authorsInput directly
            $bookObj->categoryID = $book["categoryID"];
            $bookObj->publication_name = $book["publication_name"];
            $bookObj->publication_year = $book["publication_year"];
            $bookObj->ISBN = $book["ISBN"];
            $bookObj->book_copies = $book["book_copies"];
            $bookObj->book_condition = $book["book_condition"];
            $bookObj->replacement_cost = $book["replacement_cost"];

            $bookObj->book_cover_name = $new_cover_name;
            $bookObj->book_cover_dir = $new_cover_dir;

            $update_image = ($new_cover_name != $existing_cover_name);

            // PASS $authorsInput to editBook
            if ($bookObj->editBook($bookID, $authorsInput, $update_image)) {
                header("Location: ../../app/views/librarian/booksSection.php?success=edit");
                exit;
            } else {
                $_SESSION["errors"] = ["general" => "Failed to edit book due to a database error."];
                $book['existing_cover_dir'] = $new_cover_dir;
                $book['existing_cover_name'] = $new_cover_name;
                $_SESSION["old"] = $book;
                header("Location: ../../app/views/librarian/booksSection.php?modal=edit&id={$bookID}");
                exit;
            }
        }

        if (!empty($errors)) {
            $book['existing_cover_dir'] = $new_cover_dir;
            $book['existing_cover_name'] = $new_cover_name;
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $book;
            header("Location: ../../app/views/librarian/booksSection.php?modal=edit&id={$bookID}");
            exit;
        }
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $bookID = $_GET['id'];
    if ($bookObj->deleteBook($bookID)) {
        header("Location: ../../app/views/librarian/booksSection.php?success=delete");
        exit;
    } else {
        $_SESSION["errors"] = ["general" => "Failed to delete book."];
        header("Location: ../../app/views/librarian/booksSection.php?error=delete");
        exit;
    }

}
?>