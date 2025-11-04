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
    public $borrow_status = "";
    public $fine_amount = 0.00;
    public $fine_reason = "";
    public $fine_status = "";
    public $borrower_notified = NULL;

    protected $db;

    public function addBorrowDetail()
    {
        $sql = "INSERT INTO borrowing_details (userID, bookID, no_of_copies, request_date, pickup_date, return_date, expected_return_date, returned_condition, borrow_request_status, borrow_status, fine_amount, fine_reason, fine_status, borrower_notified)
                VALUES (:userID, :bookID, :no_of_copies, :request_date, :pickup_date, :return_date, :expected_return_date, :returned_condition, :borrow_request_status, :borrow_status, :fine_amount, :fine_reason, :fine_status, :borrower_notified)";
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
        $query->bindParam(":borrow_status", $this->borrow_status);
        $query->bindParam(":fine_amount", $this->fine_amount);
        $query->bindParam(":fine_reason", $this->fine_reason);
        $query->bindParam(":fine_status", $this->fine_status);
        $query->bindParam(":borrower_notified", $this->borrower_notified);
        return $query->execute();
    }

    public function viewBorrowDetails($search = "", $statusFilter = "")
    {
        $whereConditions = [];
        $dbStatus = "";
        $statusColumn = "";

        $sql = "SELECT 
            bd.*, 
            u.fName, 
            u.lName, 
            b.book_title,
            b.book_condition
        FROM borrowing_details bd
        JOIN users u ON bd.userID = u.userID
        JOIN books b ON bd.bookID = b.bookID";

        if ($statusFilter != "") {
            if ($statusFilter == 'borrowed') {
                $statusColumn = "bd.borrow_status";
                $dbStatus = 'Borrowed';
            } elseif ($statusFilter == 'returned') {
                $statusColumn = "bd.borrow_status";
                $dbStatus = 'Returned';
            } elseif ($statusFilter == 'unpaid') {
                $whereConditions[] = "bd.fine_status = :statusFilter";
                $dbStatus = 'Unpaid';
            } else {
                $statusColumn = "bd.borrow_request_status";
                $dbStatus = ucfirst($statusFilter);
                if ($statusFilter == 'approved') {
                    $whereConditions[] = "bd.borrow_status IS NULL";
                }
            }

            if ($statusFilter != 'unpaid') {
                $whereConditions[] = "{$statusColumn} = :statusFilter";
            }
        }

        if ($search != "") {
            $whereConditions[] = " (bd.borrowID LIKE CONCAT('%', :search, '%') 
                            OR u.fName LIKE CONCAT('%', :search, '%')
                            OR u.lName LIKE CONCAT('%', :search, '%')
                            OR b.book_title LIKE CONCAT('%', :search, '%'))";
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }

        $sql .= " ORDER BY bd.borrowID DESC";

        $query = $this->connect()->prepare($sql);

        if ($search != "") {
            $query->bindParam(":search", $search);
        }

        if ($statusFilter != "") {
            $query->bindParam(":statusFilter", $dbStatus);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function countPendingRequests()
    {
        $sql = "SELECT SUM(no_of_copies) AS total_pending FROM borrowing_details WHERE borrow_request_status = 'Pending'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_pending'] ?? 0;
    }

    public function countOverdueBooks()
    {
        $sql = "SELECT SUM(no_of_copies) AS total_overdue 
                FROM borrowing_details 
                WHERE borrow_status = 'Borrowed' 
                AND expected_return_date < CURDATE()";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_overdue'] ?? 0;
    }

    public function viewActiveBorrowDetails($search = "")
    {
        $sql = "SELECT 
                    bd.*, 
                    u.fName, 
                    u.lName, 
                    b.book_title,
                    b.book_condition
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                JOIN books b ON bd.bookID = b.bookID
                WHERE bd.borrow_status = 'Borrowed'";

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
                    borrow_status = :borrow_status,
                    fine_amount = :fine_amount,
                    fine_reason = :fine_reason,
                    fine_status = :fine_status,
                    borrower_notified = :borrower_notified
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
        $query->bindParam(":borrow_status", $this->borrow_status);
        $query->bindParam(":fine_amount", $this->fine_amount);
        $query->bindParam(":fine_reason", $this->fine_reason);
        $query->bindParam(":fine_status", $this->fine_status);
        $query->bindParam(":borrower_notified", $this->borrower_notified);
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

    public function isBookBorrowed($userID, $bookID)
    {
        $sql = "SELECT COUNT(borrowID)
                FROM borrowing_details 
                WHERE userID = :userID 
                AND bookID = :bookID
                AND (borrow_request_status = 'Approved' OR borrow_status = 'Borrowed')"; 
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->bindParam(":bookID", $bookID);

        $record = null;
        if ($query->execute()) {
            $record = $query->fetchColumn();
        }

        if (!empty($record) && $record > 0) {
            return true;
        } else {
            return false;
        }

    }

    public function hasManyCopyBooks($userID)
    {
        $sql = "SELECT COUNT(borrowID) as total_copies
                FROM borrowing_details 
                WHERE userID = :userID 
                AND no_of_copies > 1
                AND (borrow_request_status = 'Approved' OR borrow_status = 'Borrowed')"; 
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);

        $record = NULL;
        if ($query->execute()) {
            $record = $query->fetch();
        }

        if (!empty($record) && $record["total_copies"] > 0) {
            return true;
        } else
            return false;
    }

    public function fetchTotalBorrowedBooks($userID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed
                FROM borrowing_details 
                WHERE userID = :userID 
                AND (borrow_request_status = 'Pending' OR borrow_request_status = 'Approved' OR borrow_status = 'Borrowed')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $record = NULL;
        if ($query->execute()) {
            $record = $query->fetch();
        }

        if (!empty($record) && !empty($record["total_borrowed"])) {
            return (int) $record["total_borrowed"];
        } else
            return 0;
    }


    public function fetchBorrowedBooks($userID, $bookID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed
                FROM borrowing_details C
                WHERE userID = :userID AND bookID = :bookID
                AND (borrow_request_status = 'Pending' OR borrow_request_status = 'Approved' OR borrow_status = 'Borrowed')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->bindParam(':bookID', $bookID);
        $record = NULL;
        if ($query->execute()) {
            $record = $query->fetch();
        }

        if (!empty($record) && !empty($record["total_borrowed"])) {
            return (int) $record["total_borrowed"];
        } else
            return 0;
    }

    public function fetchPendingBooks($userID, $bookID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed
                FROM borrowing_details C
                WHERE userID = :userID AND bookID = :bookID
                AND borrow_request_status = 'Pending'";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->bindParam(':bookID', $bookID);
        $record = NULL;
        if ($query->execute()) {
            $record = $query->fetch();
        }

        if (!empty($record) && !empty($record["total_borrowed"])) {
            return (int) $record["total_borrowed"];
        } else
            return 0;
    }

    // NEW FUNCTION: Fetch the total number of copies tied up in PENDING or APPROVED requests for a specific book.
    public function fetchPendingAndApprovedCopiesForBook($bookID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_pending
                FROM borrowing_details 
                WHERE bookID = :bookID
                AND borrow_request_status IN ('Pending', 'Approved')
                AND (borrow_status IS NULL OR borrow_status = 'Fined')"; // Requests not yet returned/cancelled/rejected

        $query = $this->connect()->prepare($sql);
        $query->bindParam(':bookID', $bookID);

        $record = NULL;
        if ($query->execute()) {
            $record = $query->fetch();
        }

        if (!empty($record) && !empty($record["total_pending"])) {
            return (int) $record["total_pending"];
        } else {
            return 0;
        }
    }

    public function updateBorrowDetails($borrowID, $borrow_status, $borrow_request_status, $return_date, $borrower_notified)
    {
        $sql = "UPDATE borrowing_details SET borrow_request_status = :borrow_request_status, borrow_status = :borrow_status, return_date = :return_date, borrower_notified = :borrower_notified WHERE borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":borrow_request_status", $borrow_request_status);
        $query->bindParam(":borrow_status", $borrow_status);
        $query->bindParam(":borrowID", $borrowID);
        $query->bindParam(":return_date", $return_date);
        $query->bindParam(":borrower_notified", $borrower_notified);
        return $query->execute();
    }

    public function updateBorrowerNotifiedStatus($borrowID, $status)
    {
        $sql = "UPDATE borrowing_details SET borrower_notified = :status WHERE borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":status", $status);
        $query->bindParam(":borrowID", $borrowID);
        return $query->execute();
    }

    public function fetchBorrowDetail($borrowID)
    {
        $sql = "SELECT 
                    bd.*, 
                    u.fName, 
                    u.lName, 
                    b.book_title,
                    b.book_condition,
                    b.replacement_cost
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                JOIN books b ON bd.bookID = b.bookID
                WHERE bd.borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":borrowID", $borrowID);
        if ($query->execute()) {
            return $query->fetch(); 
        } else
            return null;
    }

    public function fetchUserBorrowDetails($userID, $status_filter)
    {
        $sql = "SELECT 
                bd.*, 
                b.book_title,
                b.author,
                b.book_cover_dir
            FROM borrowing_details bd
            JOIN books b ON bd.bookID = b.bookID
            WHERE bd.userID = ? ";

        $exec_params = [$userID];

        $status_filter = ucfirst($status_filter);

        if ($status_filter === 'unpaid') {
            $sql .= " AND bd.fine_amount > 0 AND bd.fine_status = ?";
            $exec_params[] = 'Unpaid';

        } elseif ($status_filter === 'Returned') {
            $sql .= " AND bd.borrow_status = ?";
            $exec_params[] = 'Returned';

        } elseif ($status_filter === 'Borrowed') {
            $sql .= " AND bd.borrow_status = ?";
            $exec_params[] = 'Borrowed';

        } elseif ($status_filter === 'Rejected') {
            $sql .= " AND bd.borrow_request_status = ?";
            $exec_params[] = 'Rejected';

        } elseif ($status_filter === 'Cancelled') {
            $sql .= " AND bd.borrow_request_status = ?";
            $exec_params[] = 'Cancelled';

        } else {
            $sql .= " AND bd.borrow_request_status IN ('Pending', 'Approved')";
        }

        $sql .= " ORDER BY bd.request_date DESC";

        $query = $this->connect()->prepare($sql);
        $query->execute($exec_params);

        return $query->fetchAll();
    }

    public function updateFineDetails($borrowID, $fine_amount, $fine_reason, $fine_status)
    {
        $sql = "UPDATE borrowing_details
            SET fine_amount = :fine_amount,
                fine_reason = :fine_reason,
                fine_status = :fine_status
            WHERE borrowID = :borrowID";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(":fine_amount", $fine_amount);
        $query->bindParam(":fine_reason", $fine_reason);
        $query->bindParam(":fine_status", $fine_status);
        $query->bindParam(":borrowID", $borrowID);

        return $query->execute();
    }

    public function calculateFinalFine($expected_return_date, $comparison_date_string, $bookObj, $bookID)
    {
        $comparison_date_string = $comparison_date_string ?: date("Y-m-d");

        $comparison = new DateTime($comparison_date_string);
        $expected = new DateTime($expected_return_date);

        $results = [
            'is_lost' => false,
            'fine_amount' => 0.00,
            'fine_reason' => null,
            'fine_status' => null,
        ];

        if ($comparison > $expected) {
            $interval = $expected->diff($comparison);
            $days_late = $interval->days;

            $MAX_LATE_DAYS = 105;
            $MAX_LATE_FEE = 300.00; // 15 weeks * 20.00/week

            if ($days_late >= $MAX_LATE_DAYS) {
                $results['is_lost'] = true;
                $replacement_cost = $bookObj->fetchBookReplacementCost($bookID);

                // Total Fine = Capped Late Fee (300.00) + Replacement Cost 
                // This resolves the inconsistency and ensures a fixed maximum late component.
                $results['fine_amount'] = $MAX_LATE_FEE + $replacement_cost;
                $results['fine_reason'] = 'Lost (Overdue)';
                $results['fine_status'] = 'Unpaid';

            } else {
                // Standard Late Fine: Calculate based on full weeks late
                $weeks_late = ceil($days_late / 7); //immediately apply fine even after a day
                $late_fine_amount = $weeks_late * 20.00;

                $results['fine_amount'] = $late_fine_amount;
                $results['fine_reason'] = 'Late';
                $results['fine_status'] = 'Unpaid';
            }
        }
        return $results;
    }

}