<?php session_start();
require_once(__DIR__ . "/../models/manageBook.php");
$bookObj = new Book();
$book = [];
$errors = [];
$category = $bookObj->fetchCategory();
$action = $_GET["action"] ?? null; // Use null coalescing to avoid 'Undefined index' if not set
$bookID = $_POST["bookID"] ?? $_GET["id"] ?? null;

// The file upload variables are set outside the POST check to handle both 'add' and 'edit' logic flow better
$book["book_cover_name"] = $_FILES["book_cover"]["name"] ?? '';
$upload_dir = __DIR__ . "/../../public/uploads/book_cover_images/";
$book["book_cover_dir"] = $upload_dir . basename($book["book_cover_name"]);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize all POST data
    $book["book_title"] = trim(htmlspecialchars($_POST["book_title"] ?? ''));
    $book["author"] = trim(htmlspecialchars($_POST["author"] ?? ''));
    $book["categoryID"] = trim(htmlspecialchars($_POST["categoryID"] ?? ''));
    $book["publication_name"] = trim(htmlspecialchars($_POST["publication_name"] ?? ''));
    $book["publication_year"] = trim(htmlspecialchars($_POST["publication_year"] ?? ''));
    $book["ISBN"] = trim(htmlspecialchars($_POST["ISBN"] ?? ''));
    $book["book_copies"] = trim(htmlspecialchars($_POST["book_copies"] ?? ''));
    $book["book_condition"] = trim(htmlspecialchars($_POST["book_condition"] ?? ''));


    // General validation for fields common to ADD and EDIT
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

    if ($action === 'add') {
        // ADD action specific validation
        if (empty($book["book_title"])) {
            $errors["book_title"] = "Book Title is required";
        } else if ($bookObj->isTitleExist($book["book_title"], null)) {
            $errors["book_title"] = "Book Title already exist";
        }

        // Image validation for ADD: Image is required
        if (empty($book["book_cover_name"]) || $_FILES["book_cover"]["error"] == UPLOAD_ERR_NO_FILE) {
            $errors["book_cover"] = "Upload Book Cover is required";
        } elseif ($_FILES["book_cover"]["error"] !== UPLOAD_ERR_OK) {
            $errors["book_cover"] = "File upload failed (Code: " . $_FILES["book_cover"]["error"] . ")";
        }

        if (empty(array_filter($errors))) {
            // Attempt to upload image
            if (move_uploaded_file($_FILES["book_cover"]["tmp_name"], $book["book_cover_dir"])) {

                // Set model properties
                $bookObj->book_title = $book["book_title"];
                $bookObj->author = $book["author"];
                $bookObj->categoryID = $book["categoryID"];
                $bookObj->publication_name = $book["publication_name"];
                $bookObj->publication_year = $book["publication_year"];
                $bookObj->ISBN = $book["ISBN"];
                $bookObj->book_copies = $book["book_copies"];
                $bookObj->book_condition = $book["book_condition"];
                $bookObj->date_added = date("Y-m-d");
                // Set book cover image properties
                $bookObj->book_cover_name = $book["book_cover_name"];
                // Save the relative path in the database
                $bookObj->book_cover_dir = "public/uploads/book_cover_images/" . basename($book["book_cover_name"]);

                if ($bookObj->addBook()) {
                    header("Location: ../../app/views/librarian/booksSection.php?success=add");
                    exit;
                } else {
                    // Database error, delete uploaded file
                    if (file_exists($book["book_cover_dir"])) {
                        unlink($book["book_cover_dir"]);
                    }
                    $_SESSION["errors"] = ["general" => "Failed to add book due to a database error."];
                    $_SESSION["old"] = $book;
                    header("Location: ../../app/views/librarian/booksSection.php?modal=add");
                    exit;
                }
            } else {
                $errors["book_cover"] = "Failed to save the uploaded image.";
            }
        }
        // If there are errors (including image save failure), redirect back with old data
        if (!empty($errors)) {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $book;
            header("Location: ../../app/views/librarian/booksSection.php?modal=add");
            exit;
        }

    } else if ($action === 'edit' && $bookID) {
        // EDIT action specific validation
        if (empty($book["book_title"])) {
            $errors["book_title"] = "Book Title is required";
        } else if ($bookObj->isTitleExist($book["book_title"], $bookID)) {
            $errors["book_title"] = "Book Title already exist";
        }

        // Image validation for EDIT: Check if a *new* file was uploaded
        $update_image = false;
        if (!empty($book["book_cover_name"]) && $_FILES["book_cover"]["error"] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES["book_cover"]["error"] !== UPLOAD_ERR_OK) {
                $errors["book_cover"] = "File upload failed (Code: " . $_FILES["book_cover"]["error"] . ")";
            } else {
                $update_image = true;
            }
        }

        if (empty(array_filter($errors))) {
            $current_book = $bookObj->fetchBook($bookID);
            $old_cover_dir = $current_book['book_cover_dir'] ?? null;
            $upload_success = true;

            if ($update_image) {
                // Attempt to upload new image
                if (!move_uploaded_file($_FILES["book_cover"]["tmp_name"], $book["book_cover_dir"])) {
                    $errors["book_cover"] = "Failed to save the uploaded image.";
                    $upload_success = false;
                }
            }

            if ($upload_success) {
                // Set model properties for update
                $bookObj->book_title = $book["book_title"];
                $bookObj->author = $book["author"];
                $bookObj->categoryID = $book["categoryID"];
                $bookObj->publication_name = $book["publication_name"];
                $bookObj->publication_year = $book["publication_year"];
                $bookObj->ISBN = $book["ISBN"];
                $bookObj->book_copies = $book["book_copies"];
                $bookObj->book_condition = $book["book_condition"];

                if ($update_image) {
                    $bookObj->book_cover_name = $book["book_cover_name"];
                    // Save the relative path in the database
                    $bookObj->book_cover_dir = "public/uploads/book_cover_images/" . basename($book["book_cover_name"]);
                }

                if ($bookObj->editBook($bookID, $update_image)) {
                    // On successful update, if a new image was uploaded, delete the old one
                    if ($update_image && $old_cover_dir) {
                        $absolute_old_path = __DIR__ . "/../../" . $old_cover_dir;
                        if (file_exists($absolute_old_path)) {
                            @unlink($absolute_old_path); // Delete old file
                        }
                    }
                    header("Location: ../../app/views/librarian/booksSection.php?success=edit");
                    exit;
                } else {
                    // Database error during update
                    if ($update_image) {
                        // If new image was uploaded but DB failed, delete the newly uploaded file
                        if (file_exists($book["book_cover_dir"])) {
                            @unlink($book["book_cover_dir"]);
                        }
                    }
                    $_SESSION["errors"] = ["general" => "Failed to edit book due to a database error."];
                    $_SESSION["old"] = $book;
                    header("Location: ../../app/views/librarian/booksSection.php?modal=edit&id={$bookID}");
                    exit;
                }
            }
        }
        // If there are errors (including image save failure), redirect back with old data
        if (!empty($errors)) {
            $_SESSION["errors"] = $errors;
            $_SESSION["old"] = $book;
            header("Location: ../../app/views/librarian/booksSection.php?modal=edit&id={$bookID}");
            exit;
        }
    }
}

// Handling GET request for deletion
if ($action === 'delete' && isset($_GET['id'])) {
    $bookID = $_GET['id'];
    // Note: The deleteBook method in manageBook.php has been updated to handle file deletion.
    if ($bookObj->deleteBook($bookID)) {
        header("Location: ../../app/views/librarian/booksSection.php?success=delete");
        exit;
    } else {
        echo "<script>alert('Failed to delete book.');</script>";
        // Fall-through or redirect back if needed
        header("Location: ../../app/views/librarian/booksSection.php?error=delete");
        exit;
    }
}
?>