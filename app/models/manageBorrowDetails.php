<?php
require_once(__DIR__ . "/../../config/database.php");

class BorrowDetails extends Database
{
    // Fields from the borrowing_details table
        public $userID = "";
        public $bookID = "";
        public $borrow_date = "";
        public $pickup_date = "";
        public $return_date = "";
        public $returned_condition = "";
        public $borrow_request_status = "";

        public $penaltyID = "";
        public $book_status = "";

    protected $db;

    public function viewBorrowDetails($search = "")
    {
        // SQL Joins borrowing_details (bd) -> users (u) -> books (b) -> penalty (p)
        $sql = "SELECT 
                    bd.*, 
                    u.fName, 
                    u.lName, 
                    b.book_title,
                    p.penalty_type,
                    p.penalty_status
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                JOIN books b ON bd.bookID = b.bookID
                LEFT JOIN penalty p ON bd.penaltyID = p.PenaltyID"; // LEFT JOIN because penaltyID might be NULL

        $where_clauses = [];
        $params = [];
        
        if ($search != "") {
            // Search criteria checks relevant display fields (name, title) and IDs/status
            $where_clauses[] = " (bd.borrowID LIKE CONCAT('%', :search, '%') 
                                OR u.fName LIKE CONCAT('%', :search, '%')
                                OR u.lName LIKE CONCAT('%', :search, '%')
                                OR b.book_title LIKE CONCAT('%', :search, '%')
                                OR bd.borrow_request_status LIKE CONCAT('%', :search, '%'))";
            $params[":search"] = $search;
        } 
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " ORDER BY bd.borrowID DESC";
        
        $query = $this->connect()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $query->bindParam($key, $value);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }
    
    public function fetchBorrowDetail($borrowID)
    {
        // Fetch raw details plus joined display fields for use in modals
        $sql = "SELECT 
                    bd.*, 
                    u.fName, 
                    u.lName, 
                    b.book_title,
                    p.penalty_type,
                    p.penalty_status
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                JOIN books b ON bd.bookID = b.bookID
                LEFT JOIN penalty p ON bd.penaltyID = p.PenaltyID
                WHERE bd.borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":borrowID", $borrowID);
        $query->execute();
        return $query->fetch();
    }
    
    public function addBorrowDetail()
    {
        // NOTE: borrowID is likely auto-incrementing, so we omit it from INSERT
        $sql = "INSERT INTO borrowing_details (userID, bookID, borrow_date, pickup_date, return_date, returned_condition, borrow_request_status, penaltyID, book_status)
                VALUES (:userID, :bookID, :borrow_date, :pickup_date, :return_date, :returned_condition, :borrow_request_status, :penaltyID, :book_status)";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":userID", $this->userID);
        $query->bindParam(":bookID", $this->bookID);
        $query->bindParam(":borrow_date", $this->borrow_date);
        $query->bindParam(":pickup_date", $this->pickup_date);
        $query->bindParam(":return_date", $this->return_date);
        $query->bindParam(":returned_condition", $this->returned_condition);
        $query->bindParam(":borrow_request_status", $this->borrow_request_status);
        $query->bindParam(":penaltyID", $this->penaltyID);
        $query->bindParam(":book_status", $this->book_status);

        return $query->execute();
    }

    public function editBorrowDetail($borrowID)
    {
        $sql = "UPDATE borrowing_details
                SET userID = :userID,
                    bookID = :bookID,
                    borrow_date = :borrow_date,
                    pickup_date = :pickup_date,
                    return_date = :return_date,
                    returned_condition = :returned_condition,
                    borrow_request_status = :borrow_request_status,
                    penaltyID = :penaltyID,
                    book_status = :book_status
                WHERE borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":userID", $this->userID);
        $query->bindParam(":bookID", $this->bookID);
        $query->bindParam(":borrow_date", $this->borrow_date);
        $query->bindParam(":pickup_date", $this->pickup_date);
        $query->bindParam(":return_date", $this->return_date);
        $query->bindParam(":returned_condition", $this->returned_condition);
        $query->bindParam(":borrow_request_status", $this->borrow_request_status);
        $query->bindParam(":penaltyID", $this->penaltyID);
        $query->bindParam(":book_status", $this->book_status);
        $query->bindParam(":borrowID", $borrowID);

        return $query->execute();
    }
    public function deleteBorrowDetail($borrowID)
    {
        $sql = "DELETE FROM borrowing_details WHERE borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":borrowID", $borrowID);
        return $query->execute();
    }
}
?>
