<?php
require_once(__DIR__ . "/../../config/database.php");

class Book extends Database
{
    public $book_title = "";
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

    private function formatAuthorString($author_names)
    {
        if (empty($author_names))
            return "";

        $authors = explode(', ', $author_names);
        $count = count($authors);

        if ($count == 2) {
            return $authors[0] . ' and ' . $authors[1];
        } elseif ($count > 2) {
            $last = array_pop($authors);
            return implode(', ', $authors) . ', and ' . $last;
        }
        return $author_names;
    }

    public function addBook($authorsArray, $publisherName)
    {
        $pdo = $this->connect();
        try {
            $pdo->beginTransaction();
            $pubID = $this->getOrCreatePublisher($publisherName, $pdo);

            $sql = "INSERT INTO books (book_title, publisherID, categoryID, publication_year, ISBN, book_copies, book_condition, date_added, book_cover_name, book_cover_dir, replacement_cost) 
                    VALUES (:book_title, :publisherID, :categoryID, :publication_year, :ISBN, :book_copies, :book_condition, :date_added, :book_cover_name, :book_cover_dir, :replacement_cost)";

            $query = $pdo->prepare($sql);
            $query->bindValue(":book_title", $this->book_title);
            $query->bindValue(":publisherID", $pubID);
            $query->bindValue(":categoryID", $this->categoryID);
            $query->bindValue(":publication_year", $this->publication_year);
            $query->bindValue(":ISBN", $this->ISBN);
            $query->bindValue(":book_copies", $this->book_copies);
            $query->bindValue(":book_condition", $this->book_condition);
            $query->bindValue(":date_added", $this->date_added);
            $query->bindValue(":book_cover_name", $this->book_cover_name);
            $query->bindValue(":book_cover_dir", $this->book_cover_dir);
            $query->bindValue(":replacement_cost", $this->replacement_cost);
            $query->execute();
 
            $newBookID = $pdo->lastInsertId();

            $sqlAuthor = "INSERT INTO book_authors (bookID, authorID) VALUES (:bookID, :authorID)";
            $stmtAuthor = $pdo->prepare($sqlAuthor);

            foreach ($authorsArray as $authorName) {
                if (!empty(trim($authorName))) {
                    $authID = $this->getOrCreateAuthor($authorName, $pdo);
                    $stmtAuthor->execute([':bookID' => $newBookID, ':authorID' => $authID]);
                }
            }
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function editBook($pid, $authorsArray, $update_image = false)
    {
        $pdo = $this->connect();
        try {
            $pdo->beginTransaction();
            $pubID = $this->getOrCreatePublisher($this->publication_name, $pdo);

            $sql = "UPDATE books SET 
                    book_title = :book_title, 
                    publisherID = :publisherID, 
                    categoryID = :categoryID, 
                    publication_year = :publication_year, 
                    ISBN = :ISBN, 
                    book_copies = :book_copies, 
                    book_condition = :book_condition, 
                    replacement_cost = :replacement_cost";

            if ($update_image) {
                $sql .= ", book_cover_name = :book_cover_name, book_cover_dir = :book_cover_dir";
            }
            $sql .= " WHERE bookID = :bookID";

            $query = $pdo->prepare($sql);
            $query->bindValue(":book_title", $this->book_title);
            $query->bindValue(":publisherID", $pubID);
            $query->bindValue(":categoryID", $this->categoryID);
            $query->bindValue(":publication_year", $this->publication_year);
            $query->bindValue(":ISBN", $this->ISBN);
            $query->bindValue(":book_copies", $this->book_copies);
            $query->bindValue(":book_condition", $this->book_condition);
            $query->bindValue(":replacement_cost", $this->replacement_cost);
            $query->bindValue(":bookID", $pid);

            if ($update_image) {
                $query->bindValue(":book_cover_name", $this->book_cover_name);
                $query->bindValue(":book_cover_dir", $this->book_cover_dir);
            }
            $query->execute();

            $delSql = "DELETE FROM book_authors WHERE bookID = :bookID";
            $delQuery = $pdo->prepare($delSql);
            $delQuery->execute([':bookID' => $pid]);

            $sqlAuthor = "INSERT INTO book_authors (bookID, authorID) VALUES (:bookID, :authorID)";
            $stmtAuthor = $pdo->prepare($sqlAuthor);

            foreach ($authorsArray as $authorName) {
                if (!empty(trim($authorName))) {
                    $authID = $this->getOrCreateAuthor($authorName, $pdo);
                    $stmtAuthor->execute([':bookID' => $pid, ':authorID' => $authID]);
                }
            }
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function viewBook($search = "", $category = "")
    {
        $sql = "SELECT b.*, 
                   c.category_name, 
                   p.publisher_name,
                   GROUP_CONCAT(a.author_name SEPARATOR ', ') AS author_names
            FROM books b 
            LEFT JOIN category c ON b.categoryID = c.categoryID
            LEFT JOIN publishers p ON b.publisherID = p.publisherID
            LEFT JOIN book_authors ba ON b.bookID = ba.bookID
            LEFT JOIN authors a ON ba.authorID = a.authorID";

        $conditions = ["b.is_removed = 0"];

        if ($search != "") {
            $conditions[] = "(b.book_title LIKE CONCAT('%', :search, '%') 
                      OR a.author_name LIKE CONCAT('%', :search, '%'))";
        }
        if ($category != "") {
            $conditions[] = "c.categoryID = :category";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " GROUP BY b.bookID ORDER BY b.book_title ASC";

        $query = $this->connect()->prepare($sql);

        if ($search != "")
            $query->bindParam(":search", $search);
        if ($category != "")
            $query->bindParam(":category", $category);

        if ($query->execute()) {
            $results = $query->fetchAll();

            foreach ($results as &$row) {
                if (!empty($row['author_names'])) {
                    $row['author_names'] = $this->formatAuthorString($row['author_names']);
                }
            }

            return $results;
        } else {
            return null;
        }
    }

    public function deleteBook($pid)
    {
        $sql = "UPDATE books SET is_removed = 1 WHERE bookID = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $pid);
        $result = $query->execute();
        return $result;
    }

    public function showThreeBooks($categoryID)
    {
        $sql = "SELECT b.*, c.category_name,
                       GROUP_CONCAT(a.author_name SEPARATOR ', ') as author_names
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID 
                LEFT JOIN book_authors ba ON b.bookID = ba.bookID
                LEFT JOIN authors a ON ba.authorID = a.authorID
                WHERE b.categoryID = :categoryID AND b.is_removed = 0
                GROUP BY b.bookID
                ORDER BY b.book_title ASC 
                LIMIT 3";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(":categoryID", $categoryID);

        if ($query->execute()) {
            $results = $query->fetchAll();
            foreach ($results as &$row) {
                if (!empty($row['author_names'])) {
                    $row['author_names'] = $this->formatAuthorString($row['author_names']);
                }
            }
            return $results;
        } else {
            return null;
        }
    }

    public function fetchBookAuthors($bookID)
    {
        $sql = "SELECT a.author_name 
                FROM authors a 
                JOIN book_authors ba ON a.authorID = ba.authorID 
                WHERE ba.bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->execute([':bookID' => $bookID]);
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getOrCreatePublisher($name, $pdo = null)
    {
        $conn = $pdo ?? $this->connect();
        $name = trim($name);
        $query = $conn->prepare("SELECT publisherID FROM publishers WHERE publisher_name = :name");
        $query->execute([':name' => $name]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if ($row)
            return $row['publisherID'];

        $query = $conn->prepare("INSERT INTO publishers (publisher_name) VALUES (:name)");
        $query->execute([':name' => $name]);
        return $conn->lastInsertId();
    }

    public function getOrCreateAuthor($name, $pdo = null)
    {
        $conn = $pdo ?? $this->connect();
        $name = trim($name);
        $query = $conn->prepare("SELECT authorID FROM authors WHERE author_name = :name");
        $query->execute([':name' => $name]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if ($row)
            return $row['authorID'];

        $query = $conn->prepare("INSERT INTO authors (author_name) VALUES (:name)");
        $query->execute([':name' => $name]);
        return $conn->lastInsertId();
    }

    public function countTotalDistinctBooks()
    {
        $sql = "SELECT COUNT(book_title) AS total_books FROM books WHERE is_removed = 0";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_books'] ?? 0;
    }

    public function countTotalBookCopies()
    {
        $sql = "SELECT (SELECT SUM(book_copies) FROM books WHERE is_removed = 0) AS total_books";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_books'] ?? 0;
    }

    public function fetchCategory()
    {
        $sql = "SELECT * FROM category WHERE is_removed = 0";
        $query = $this->connect()->prepare($sql);
        if ($query->execute()) {
            return $query->fetchAll();
        } else
            return null;
    }

    public function fetchBook($bookID)
    {
        $sql = "SELECT b.*, 
                       c.category_name,
                       p.publisher_name as publication_name,
                       GROUP_CONCAT(a.author_name SEPARATOR ', ') as author_names
                FROM books b 
                LEFT JOIN category c ON b.categoryID = c.categoryID
                LEFT JOIN publishers p ON b.publisherID = p.publisherID
                LEFT JOIN book_authors ba ON b.bookID = ba.bookID
                LEFT JOIN authors a ON ba.authorID = a.authorID
                WHERE b.bookID = :bookID
                GROUP BY b.bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':bookID', $bookID);
        $query->execute();
        $result = $query->fetch();

        if ($result && !empty($result['author_names'])) {
            $result['author_names'] = $this->formatAuthorString($result['author_names']);
        }

        return $result;
    }

    public function fetchBookTitles()
    {
        $sql = "SELECT bookID, book_title FROM books WHERE is_removed = 0";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }

    public function isTitleExist($book_title, $bookID = "")
    {
        if ($bookID) {
            $sql = "SELECT COUNT(bookID) as total_books FROM books WHERE book_title = :book_title AND bookID <> :bookID AND is_removed = 0";
        } else {
            $sql = "SELECT COUNT(bookID) as total_books FROM books WHERE book_title = :book_title AND is_removed = 0";
        }
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":book_title", $book_title);
        if ($bookID)
            $query->bindParam(":bookID", $bookID);
        if ($query->execute()) {
            $record = $query->fetch();
            return ($record["total_books"] > 0);
        }
        return false;
    }

    public function isISBNExist($isbn, $bookID = "")
    {
        if ($bookID) {
            $sql = "SELECT COUNT(bookID) as total_books FROM books WHERE ISBN = :isbn AND bookID <> :bookID AND is_removed = 0";
        } else {
            $sql = "SELECT COUNT(bookID) as total_books FROM books WHERE ISBN = :isbn AND is_removed = 0";
        }
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":isbn", $isbn);
        if ($bookID)
            $query->bindParam(":bookID", $bookID);
        if ($query->execute()) {
            $record = $query->fetch();
            return ($record["total_books"] > 0);
        }
        return false;
    }

    public function countBooksByCategory($categoryID)
    {
        $sql = "SELECT COUNT(*) AS total FROM books WHERE categoryID = :categoryID AND is_removed = 0";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':categoryID', $categoryID);
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
        return (float) $query->fetchColumn(0);
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
?>