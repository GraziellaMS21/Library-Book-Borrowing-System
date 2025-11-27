<?php
require_once(__DIR__ . "/../../config/database.php");

class BorrowLists extends Database
{
    public $userID = "";
    public $bookID = "";
    public $no_of_copies = 1; 
    public $date_added = "";

    public function fetchAllBorrrowList($userID)
    {
        // Added JOIN to book_authors and authors, and GROUP BY to concatenate names
        $sql = "SELECT bl.*, b.book_title, b.book_condition, b.book_copies, b.book_cover_dir,
                       GROUP_CONCAT(a.author_name SEPARATOR ', ') as author_names
                FROM borrowing_lists bl
                JOIN books b ON bl.bookID = b.bookID
                LEFT JOIN book_authors ba ON b.bookID = ba.bookID
                LEFT JOIN authors a ON ba.authorID = a.authorID
                WHERE bl.userID = :userID
                GROUP BY bl.listID
                ORDER BY bl.date_added DESC";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        return $query->fetchAll();
    }

    public function fetchBorrrowListByBook($userID, $bookID)
    {
        $sql = "SELECT * FROM borrowing_lists WHERE userID = :userID AND bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->bindParam(":bookID", $bookID);
        $query->execute();
        return $query->fetch();
    }

    public function addBorrrowList()
    {
        $sql = "INSERT INTO borrowing_lists (userID, bookID, no_of_copies, date_added) VALUES (:userID, :bookID, :no_of_copies, :date_added)";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $this->userID);
        $query->bindParam(":bookID", $this->bookID);
        $query->bindParam(":no_of_copies", $this->no_of_copies); 
        $query->bindParam(":date_added", $this->date_added);

        return $query->execute();
    }

    public function editBorrrowListCopies($listID)
    {
        $sql = "UPDATE borrowing_lists SET no_of_copies = :no_of_copies WHERE listID = :listID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":no_of_copies", $this->no_of_copies);
        $query->bindParam(":listID", $listID);
        return $query->execute();
    }

    public function editBorrrowList($listID)
    {
        $sql = "UPDATE borrowing_lists SET no_of_copies = :no_of_copies WHERE listID = :listID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":no_of_copies", $this->no_of_copies);
        $query->bindParam(":listID", $listID);
        return $query->execute();
    }

    public function deleteBorrrowList($listID)
    {
        $sql = "DELETE FROM borrowing_lists WHERE listID = :listID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":listID", $listID);
        return $query->execute();
    }

    public function clearBorrrowList($userID)
    {
        $sql = "DELETE FROM borrowing_lists WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        return $query->execute();
    }
}
?>