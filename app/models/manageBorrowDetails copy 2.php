<?php
require_once(__DIR__ . "/../../config/database.php");

class BorrowDetails extends Database
{
    public $userID = "";
    public $bookID = "";
    public $no_of_copies = 1;
    public $request_date = "";
    public $pickup_date = "";
    public $return_date = "";
    public $expected_return_date = "";
    public $returned_condition = "";
    public $borrow_request_status = "";
    public $fine_amount = 0.00;
    public $fine_reason = "";
    public $fine_status = "";

    protected $db;

    public function fetchUser($userID)
    {
        $sql = "SELECT u.*, ut.type_name, ut.borrower_limit, ut.borrower_period FROM user_type ut LEFT JOIN users u ON ut.userTypeID = u.userTypeID WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        return $query->fetch();
    }


    public function getBookCondition($bookID)
    {
        $sql = "SELECT book_condition FROM books WHERE bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":bookID", $bookID);
        $query->execute();
        return $query->fetchColumn();
    }
    public function isBookBorrowed($userID, $bookID)
    {
        $sql = "SELECT COUNT(borrowID) 
                FROM borrowing_details 
                WHERE userID = :userID 
                AND bookID = :bookID
                AND (borrow_request_status = 'Approved' OR borrow_request_status = 'Borrowed')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->bindParam(":bookID", $bookID);
        $query->execute();
        return $query->fetchColumn() > 0;
    }
    public function getTotalCurrentlyBorrowedCount($userID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed
                FROM borrowing_details 
                WHERE userID = :userID 
                AND (borrow_request_status = 'Pending' OR borrow_request_status = 'Approved' OR borrow_request_status = 'Borrowed')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        $result = $query->fetch();
        return (int) ($result['total_borrowed'] ?? 0);
    }

    public function hasManyCopyBooks($userID)
    {
        $sql = "SELECT COUNT(borrowID)
                FROM borrowing_details 
                WHERE userID = :userID 
                AND no_of_copies > 1
                AND (borrow_request_status = 'Approved' OR borrow_request_status = 'Borrowed')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        return $query->fetchColumn() > 0;
    }

    public function fetchBorrowDetailByBookID($userID, $bookID)
    {
        $sql = "SELECT borrowID, no_of_copies
                FROM borrowing_details 
                WHERE userID = :userID 
                AND bookID = :bookID
                AND (borrow_request_status = 'Approved' OR borrow_request_status = 'Borrowed')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->bindParam(":bookID", $bookID);
        $query->execute();
        return $query->fetch();
    }

    public function viewBorrowDetails($search = "", $statusFilter = "")
    {
        $sql = "SELECT 
                    bd.*, 
                    u.fName, 
                    u.lName, 
                    b.book_title,
                    b.book_condition AS current_book_condition
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                JOIN books b ON bd.bookID = b.bookID";

        $where_clauses = [];
        $params = [];

        if ($statusFilter == 'Borrowed') {
            $where_clauses[] = "bd.borrow_request_status = :statusFilter";
            $params[":statusFilter"] = $statusFilter;
        } elseif ($statusFilter != "") {
            $where_clauses[] = "bd.borrow_request_status = :statusFilter";
            $params[":statusFilter"] = $statusFilter;
        }

        if ($search != "") {
            $where_clauses[] = " (bd.borrowID LIKE CONCAT('%', :search, '%') 
                                OR u.fName LIKE CONCAT('%', :search, '%')
                                OR u.lName LIKE CONCAT('%', :search, '%')
                                OR b.book_title LIKE CONCAT('%', :search, '%'))";
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

    public function viewFinedBorrowDetails($search = "")
    {
        $sql = "SELECT 
                    bd.*, 
                    u.fName, 
                    u.lName, 
                    b.book_title,
                    b.book_condition AS current_book_condition
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                JOIN books b ON bd.bookID = b.bookID
                WHERE bd.fine_status = 'Unpaid'";

        $params = [];

        if ($search != "") {
            $sql .= " AND (bd.borrowID LIKE CONCAT('%', :search, '%') 
                           OR u.fName LIKE CONCAT('%', :search, '%')
                           OR u.lName LIKE CONCAT('%', :search, '%')
                           OR b.book_title LIKE CONCAT('%', :search, '%'))";
            $params[":search"] = $search;
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
        $sql = "SELECT 
                    bd.*, 
                    u.fName, 
                    u.lName, 
                    b.book_title
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                JOIN books b ON bd.bookID = b.bookID
                WHERE bd.borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":borrowID", $borrowID);
        $query->execute();
        return $query->fetch();
    }
    public function addBorrowDetail()
    {
        $sql = "INSERT INTO borrowing_details (userID, bookID, no_of_copies, request_date, pickup_date, expected_return_date, returned_condition, borrow_request_status, fine_amount, fine_reason, fine_status)
                VALUES (:userID, :bookID, :no_of_copies, :request_date, :pickup_date, :expected_return_date, :returned_condition, :borrow_request_status, :fine_amount, :fine_reason, :fine_status)";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":userID", $this->userID);
        $query->bindParam(":bookID", $this->bookID);
        $query->bindParam(":no_of_copies", $this->no_of_copies); // <--- ADDED BINDING
        $query->bindParam(":request_date", $this->request_date);
        $query->bindParam(":pickup_date", $this->pickup_date);
        $query->bindParam(":expected_return_date", $this->expected_return_date);
        $query->bindParam(":returned_condition", $this->returned_condition);
        $query->bindParam(":borrow_request_status", $this->borrow_request_status);
        $query->bindParam(":fine_amount", $this->fine_amount);
        $query->bindParam(":fine_reason", $this->fine_reason);
        $query->bindParam(":fine_status", $this->fine_status);

        return $query->execute();
    }

    public function editBorrowDetail($borrowID)
    {
        $sql = "UPDATE borrowing_details
                SET userID = :userID,
                    bookID = :bookID,
                    no_of_copies = :no_of_copies,
                    request_date = :request_date,
                    pickup_date = :pickup_date,
                    return_date = :return_date,
                    expected_return_date = :expected_return_date,
                    returned_condition = :returned_condition,
                    borrow_request_status = :borrow_request_status,
                    fine_amount = :fine_amount,
                    fine_reason = :fine_reason,
                    fine_status = :fine_status
                WHERE borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":userID", $this->userID);
        $query->bindParam(":bookID", $this->bookID);
        $query->bindParam(":no_of_copies", $this->no_of_copies);
        $query->bindParam(":request_date", $this->request_date);
        $query->bindParam(":pickup_date", $this->pickup_date);
        $query->bindParam(":return_date", $this->return_date);
        $query->bindParam(":expected_return_date", $this->expected_return_date);
        $query->bindParam(":returned_condition", $this->returned_condition);
        $query->bindParam(":borrow_request_status", $this->borrow_request_status);
        $query->bindParam(":fine_amount", $this->fine_amount);
        $query->bindParam(":fine_reason", $this->fine_reason);
        $query->bindParam(":fine_status", $this->fine_status);
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
