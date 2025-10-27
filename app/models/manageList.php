<?php
require_once(__DIR__ . "/../../config/database.php");

class BorrowLists extends Database
{
    public $userID = "";
    public $bookID = "";
    public $no_of_copies = 1; // New property for copies
    public $date_added = "";

    public function fetchAllBorrrowList($userID)
    {
        $sql = "SELECT bl.*, b.book_title, b.author, b.book_condition, b.book_copies, b.book_cover_dir
                FROM borrowing_lists bl
                JOIN books b ON bl.bookID = b.bookID
                WHERE bl.userID = :userID
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
        $query->bindParam(":no_of_copies", $this->no_of_copies); // New binding
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

    // Corrected the bug in the original file (removed unnecessary bindings)
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