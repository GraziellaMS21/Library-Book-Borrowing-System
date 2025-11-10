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
    public $replacement_cost = "";

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
        $query->bindParam(":replacement_cost", $this->replacement_cost);
        return $query->execute();
    }

    public function viewBook($search = "", $category = "")
    {
        if ($search != "" && $category != "") { //both search and category exists
            $sql = "SELECT b.*, c.category_name
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID
                WHERE b.book_title LIKE CONCAT('%', :search, '%') 
                  AND c.categoryID = :category
                ORDER BY b.book_title ASC";
        } else if ($search != "") { // only search of books or category exist
            $sql = "SELECT b.*, c.category_name
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID
                WHERE b.book_title LIKE CONCAT('%', :search, '%')
                ORDER BY b.book_title ASC";
        } else if ($category != "") { //searching for books by category
            $sql = "SELECT b.*, c.category_name
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID
                WHERE c.categoryID = :category
                ORDER BY b.book_title ASC";
        } else { //general search
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

    public function editBook($pid, $update_image = false)
    {
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

    //====HELPER FUNCTIONS====
    public function countTotalBooks()
    {
        $sql = "SELECT COUNT(book_copies) AS total_books FROM books";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_books'] ?? 0;
    }

    public function countTotalBookCopies()
    {
        $sql = "SELECT SUM(book_copies) AS total_books FROM books";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_books'] ?? 0;
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
        $sql = "SELECT books.*, category.category_name FROM books JOIN category ON books.categoryID = category.categoryID WHERE bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':bookID', $bookID);
        $query->execute();
        return $query->fetch();
    }

    public function fetchBookTitles()
    {
        $sql = "SELECT bookID, book_title FROM books";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll();
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
        $sql = "SELECT COUNT(*) AS total FROM books WHERE categoryID = :categoryID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':categoryID', $categoryID);
        $result = NULL;

        $query->execute();
        $result = $query->fetch();

        return $result['total'] ?? 0;
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


    public function fetchBookReplacementCost($bookID)
    {
        $sql = "SELECT replacement_cost FROM books WHERE bookID = :bookID";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(":bookID", $bookID, PDO::PARAM_INT);
        $query->execute();

        $cost = $query->fetchColumn(0);

        return (float) $cost;
    }

    public function getTopPopularCategories($limit = 5)
    {
        $sql = "SELECT c.category_name, COUNT(bd.borrowID) AS borrow_count
                FROM borrowing_details bd
                JOIN books b ON bd.bookID = b.bookID
                JOIN category c ON b.categoryID = c.categoryID
                GROUP BY c.category_name
                ORDER BY borrow_count DESC
                LIMIT :limit";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopCategoryName()
    {
        $sql = "SELECT c.category_name
                FROM borrowing_details bd
                JOIN books b ON bd.bookID = b.bookID
                JOIN category c ON b.categoryID = c.categoryID
                GROUP BY c.category_name
                ORDER BY COUNT(bd.borrowID) DESC
                LIMIT 1";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['category_name'] ?? 'N/A';
    }
}
