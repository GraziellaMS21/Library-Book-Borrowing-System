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

    protected $db;
    public function addBook()
    {
        $sql = "INSERT INTO books (book_title, author, categoryID, publication_name, publication_year, ISBN, book_copies, book_condition, date_added) VALUES (:book_title, :author, :categoryID, :publication_name, :publication_year, :ISBN, :book_copies, :book_condition, :date_added)";
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

        return $query->execute();
    }

    public function viewBook($search = "", $category = "")
    {
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
            // Default: show all books
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
        $sql = "SELECT books.*, category.category_name FROM books JOIN category ON books.categoryID = category.categoryID WHERE bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':bookID', $bookID);
        $query->execute();
        return $query->fetch();
    }


    public function isTitleExist($book_title, $bookID)
    {
        $sql = "SELECT COUNT(bookID) as total_books FROM books WHERE book_title = :book_title AND bookID <> :bookID";
        $query = $this->connect()->prepare($sql);
        $record = NULL;

        $query->bindParam(":book_title", $book_title);
        $query->bindParam(":bookID", $bookID);
        if ($query->execute()) {
            $record = $query->fetch();
        }

        if ($record["total_books"] > 0) {
            return true;
        } else
            return false;
    }

    public function editBook($pid)
    {
        $sql = "UPDATE books SET book_title = :book_title,  author = :author,  categoryID = :categoryID, publication_name = :publication_name,  publication_year = :publication_year,  ISBN = :ISBN, book_copies = :book_copies, book_condition = :book_condition WHERE bookID = :bookID";

        $query = $this->connect()->prepare($sql);

        $query->bindParam(":book_title", $this->book_title);
        $query->bindParam(":author", $this->author);
        $query->bindParam(":categoryID", $this->categoryID);
        $query->bindParam(":publication_name", $this->publication_name);
        $query->bindParam(":publication_year", $this->publication_year);
        $query->bindParam(":ISBN", $this->ISBN);
        $query->bindParam(":book_copies", $this->book_copies);
        $query->bindParam(":book_condition", $this->book_condition);
        $query->bindParam(":bookID", $pid);

        return $query->execute();
    }

    public function deleteBook($pid)
    {
        $sql = "DELETE FROM books WHERE bookID = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $pid);
        return $query->execute();
    }

}

// $obj = new Book();
// print_r($obj->fetchBook(14));
