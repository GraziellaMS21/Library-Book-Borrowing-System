<?php
require_once(__DIR__ . "/../../config/database.php");

class Book extends Database
{

    public $book_title = "";
    public $author = "";
    public $categoryID = "";
    public $publication_name = "";
    public $publication_year = "";
    public $ISBN = "";
    public $book_copies = "";
    public $book_condition = "";
    public $date_added = "";
    public $book_cover_name = "";
    public $book_cover_dir = "";
    public $replacement_cost = 400.00; // NEW: Default replacement cost

    protected $db;
    public function addBook()
    {
        $sql = "INSERT INTO books (book_title, author, categoryID, publication_name, publication_year, ISBN, book_copies, book_condition, date_added, book_cover_name, book_cover_dir, replacement_cost) VALUES (:book_title, :author, :categoryID, :publication_name, :publication_year, :ISBN, :book_copies, :book_condition, :date_added, :book_cover_name, :book_cover_dir, :replacement_cost)";
        $query = $this->connect()->prepare($sql);


        $query->bindParam(":book_title", $this->book_title);
        $query->bindParam(":author", $this->author);
        $query->bindParam(":categoryID", $this->categoryID);
        $query->bindParam(":publication_name", $this->publication_name);
        $query->bindParam(":publication_year", $this->publication_year);
        $query->bindParam(":ISBN", $this->ISBN);
        $query->bindParam(":book_copies", $this->book_copies);
        $query->bindParam(":book_condition", $this->book_condition);
        $query->bindParam(":date_added", $this->date_added);
        $query->bindParam(":book_cover_name", $this->book_cover_name);
        $query->bindParam(":book_cover_dir", $this->book_cover_dir);
        $query->bindParam(":replacement_cost", $this->replacement_cost); // NEW BINDING

        return $query->execute();
    }

    public function viewBook($search = "", $category = "")
    {
        // SQL query logic remains the same, but select replacement_cost
        if ($search != "" && $category != "") {
            $sql = "SELECT b.*, c.category_name
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID
                WHERE b.book_title LIKE CONCAT('%', :search, '%') 
                  AND c.categoryID = :category
                ORDER BY b.book_title ASC";
        } else if ($search != "") {
            $sql = "SELECT b.*, c.category_name
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID
                WHERE b.book_title LIKE CONCAT('%', :search, '%')
                ORDER BY b.book_title ASC";
        } else if ($category != "") {
            $sql = "SELECT b.*, c.category_name
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID
                WHERE c.categoryID = :category
                ORDER BY b.book_title ASC";
        } else {
            $sql = "SELECT b.*, c.category_name
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID
                ORDER BY b.book_title ASC";
        }

        $query = $this->connect()->prepare($sql);

        if ($search != "") {
            $query->bindParam(":search", $search);
        }
        if ($category != "") {
            $query->bindParam(":category", $category);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function fetchCategory()
    {
        $sql = "SELECT * FROM category";
        $query = $this->connect()->prepare($sql);
        if ($query->execute()) {
            return $query->fetchAll();
        } else
            return null;
    }

    public function fetchBook($bookID)
    {
        // Select replacement_cost
        $sql = "SELECT books.*, category.category_name FROM books JOIN category ON books.categoryID = category.categoryID WHERE bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':bookID', $bookID);
        $query->execute();
        return $query->fetch();
    }
    
    // NEW: Function to get just the replacement cost
    public function fetchBookReplacementCost($bookID)
    {
        $sql = "SELECT replacement_cost FROM books WHERE bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':bookID', $bookID);
        $query->execute();
        $result = $query->fetchColumn();
        // Ensure a valid number is returned, default to the 400 base if NULL
        return $result !== false ? (float) $result : 400.00; 
    }

    public function isTitleExist($book_title, $bookID = "")
    {
        if ($bookID) {
            $sql = "SELECT COUNT(bookID) as total_books FROM books  WHERE book_title = :book_title AND bookID <> :bookID";
        } else {
            $sql = "SELECT COUNT(bookID) as total_books FROM books WHERE book_title = :book_title";
        }
        $query = $this->connect()->prepare($sql);
        $record = NULL;

        $query->bindParam(":book_title", $book_title);
        if ($bookID) {
            $query->bindParam(":bookID", $bookID);
        }
        if ($query->execute()) {
            $record = $query->fetch();
        }

        if ($record["total_books"] > 0) {
            return true;
        } else
            return false;
    }

    public function editBook($pid, $update_image = false, $old_cover_dir = null)
    {
        // Add replacement_cost to the UPDATE query
        $sql = "UPDATE books SET book_title = :book_title,  author = :author,  categoryID = :categoryID, publication_name = :publication_name,  publication_year = :publication_year,  ISBN = :ISBN, book_copies = :book_copies, book_condition = :book_condition, replacement_cost = :replacement_cost";

        if ($update_image) {
            $sql .= ", book_cover_name = :book_cover_name, book_cover_dir = :book_cover_dir";
        }
        $sql .= " WHERE bookID = :bookID";

        $query = $this->connect()->prepare($sql);

        $query->bindParam(":book_title", $this->book_title);
        $query->bindParam(":author", $this->author);
        $query->bindParam(":categoryID", $this->categoryID);
        $query->bindParam(":publication_name", $this->publication_name);
        $query->bindParam(":publication_year", $this->publication_year);
        $query->bindParam(":ISBN", $this->ISBN);
        $query->bindParam(":book_copies", $this->book_copies);
        $query->bindParam(":book_condition", $this->book_condition);
        $query->bindParam(":replacement_cost", $this->replacement_cost); // NEW BINDING

        if ($update_image) {
            $query->bindParam(":book_cover_name", $this->book_cover_name);
            $query->bindParam(":book_cover_dir", $this->book_cover_dir);
        }

        $query->bindParam(":bookID", $pid);

        return $query->execute();
    }

    public function deleteBook($pid)
    {
        $book = $this->fetchBook($pid);

        $sql = "DELETE FROM books WHERE bookID = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $pid);
        $result = $query->execute();

        if ($result && $book && !empty($book['book_cover_dir'])) {
            $absolute_path = __DIR__ . "/../../" . $book['book_cover_dir'];
            if (file_exists($absolute_path)) {
                @unlink($absolute_path);
            }
        }
        return $result;
    }

    public function showThreeBooks($categoryID)
    {
        $sql = "SELECT b.*, c.category_name 
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID 
                WHERE b.categoryID = :categoryID 
                ORDER BY b.book_title ASC 
                LIMIT 3";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(":categoryID", $categoryID);


        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function countBooksByCategory($categoryID)
    {
        $sql = "SELECT COUNT(bookID) AS total FROM books WHERE categoryID = :categoryID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":categoryID", $categoryID);

        if ($query->execute()) {
            return $query->fetch()['total'];
        } else {
            return 0;
        }
    }

    public function decrementBookCopies($bookID, $quantity)
    {
        $sql = "UPDATE books SET book_copies = book_copies - :quantity WHERE bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":quantity", $quantity);
        $query->bindParam(":bookID", $bookID);
        return $query->execute();
    }

    public function incrementBookCopies($bookID, $quantity)
    {
        $sql = "UPDATE books SET book_copies = book_copies + :quantity WHERE bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":quantity", $quantity);
        $query->bindParam(":bookID", $bookID);
        return $query->execute();
    }
}
