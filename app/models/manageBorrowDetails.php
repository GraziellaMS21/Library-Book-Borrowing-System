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

    protected $db;

    public function addBorrowDetail()
    {
        $sql = "INSERT INTO borrowing_details (userID, bookID, no_of_copies, request_date, pickup_date, return_date, expected_return_date, returned_condition, borrow_request_status, borrow_status, fine_amount, fine_reason, fine_status)
                VALUES (:userID, :bookID, :no_of_copies, :request_date, :pickup_date, :return_date, :expected_return_date, :returned_condition, :borrow_request_status, :borrow_status, :fine_amount, :fine_reason, :fine_status)";
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

        return $query->execute();
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

        // Filter by borrow_status for 'Returned', otherwise use borrow_request_status
        if ($statusFilter == 'Returned') {
            $where_clauses[] = "bd.borrow_status = :statusFilter";
            $params[":statusFilter"] = $statusFilter;
        } elseif ($statusFilter != "") {
            // Used for 'Pending', 'Approved' (Pickup)
            $where_clauses[] = "bd.borrow_request_status = :statusFilter";
            $params[":statusFilter"] = $statusFilter;
        }

        if ($search != "") {
            // Search functionality is correctly implemented here using the :search placeholder
            $where_clauses[] = " (bd.borrowID LIKE CONCAT('%', :search, '%') 
                                OR u.fName LIKE CONCAT('%', :search, '%')
                                OR u.lName LIKE CONCAT('%', :search, '%')
                                OR b.book_title LIKE CONCAT('%', :search, '%'))";
            // The search term is added to the parameters array
            $params[":search"] = $search;
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $sql .= " ORDER BY bd.borrowID DESC";

        $query = $this->connect()->prepare($sql);

        // *** Refinement: Using bindValue for safer parameter binding in a loop ***
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            // In a real application, you should log the error: $query->errorInfo();
            error_log("Database query failed for viewBorrowDetails.");
            return null;
        }
    }

    // NEW FUNCTION: Count total pending requests (request status 'Pending')
    public function countPendingRequests()
    {
        $sql = "SELECT SUM(no_of_copies) AS total_pending FROM borrowing_details WHERE borrow_request_status = 'Pending'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_pending'] ?? 0;
    }

    // NEW FUNCTION: Count total overdue books (borrow status 'Borrowed' and expected_return_date is past)
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

    // FUNCTION FOR CURRENTLY BORROWED TAB
    public function viewActiveBorrowDetails($search = "")
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
                WHERE bd.borrow_status = 'Borrowed'"; // Filter by borrow_status

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
            WHERE bd.fine_status = 'Unpaid' AND bd.fine_amount > 0"; // Ensure fine_amount > 0 for relevance

        if (!empty($search)) {
            $sql .= " AND (bd.borrowID LIKE CONCAT('%', :search, '%') 
                       OR u.fName LIKE CONCAT('%', :search, '%')
                       OR u.lName LIKE CONCAT('%', :search, '%')
                       OR b.book_title LIKE CONCAT('%', :search, '%'))";
        }

        $sql .= " ORDER BY bd.borrowID DESC";

        $query = $this->connect()->prepare($sql);

        if (!empty($search)) {
            $query->bindParam(":search", $search);
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
        $query->bindParam(":borrow_status", $this->borrow_status);
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

    public function isBookBorrowed($userID, $bookID)
    {
        $sql = "SELECT COUNT(borrowID)
                FROM borrowing_details 
                WHERE userID = :userID 
                AND bookID = :bookID
                AND (borrow_request_status = 'Approved' OR borrow_status = 'Borrowed')"; // Check both states
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
                AND (borrow_request_status = 'Approved' OR borrow_status = 'Borrowed')"; // Check both states
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

    public function fetchBorrowID($userID, $bookID)
    {
        // Note: 'Borrowed' or 'On Loan' should reflect the active fulfillment status
        $sql = "SELECT borrowID FROM borrowing_details 
            WHERE userID = :userID AND bookID = :bookID
            AND no_of_copies > 1 
            AND borrow_status IN ('On Loan', 'Borrowed')"; // Only check fulfilled loans

        try {
            $query = $this->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            $query->execute();

            return $query->fetchColumn() > 0;

        } catch (PDOException $e) {
            // Log the error (optional)
            error_log("Error checking active multi-copy loan: " . $e->getMessage());
            return false;
        }
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


    public function updateBorrowDetails($borrowID, $borrow_status, $borrow_request_status, $return_date)
    {
        $sql = "UPDATE borrowing_details SET borrow_request_status = :borrow_request_status, borrow_status = :borrow_status, return_date = :return_date WHERE borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":borrow_request_status", $borrow_request_status);
        $query->bindParam(":borrow_status", $borrow_status);
        $query->bindParam(":borrowID", $borrowID);
        $query->bindParam(":return_date", $return_date);
        return $query->execute();
    }

    public function fetchBorrowDetail($borrowID)
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

        if (is_array($status_filter)) {
            // This array comes from the 'returned' tab, and includes: ['Returned', 'Rejected', 'Cancelled']
            // It must check statuses in BOTH borrow_status AND borrow_request_status.
            $placeholders = implode(',', array_fill(0, count($status_filter), '?'));

            // --- FIX: Use OR to check for the statuses in EITHER of the columns ---
            $sql .= " AND (bd.borrow_request_status IN ({$placeholders}) OR bd.borrow_status IN ({$placeholders}))";

            // The array must be added twice to $exec_params to match the two sets of placeholders
            $exec_params = array_merge($exec_params, $status_filter, $status_filter);

        } elseif ($status_filter === 'Fined') {
            // Show all records with unpaid fines
            $sql .= " AND bd.fine_amount > 0 AND bd.fine_status = ?";
            $exec_params[] = 'Unpaid';

        } elseif ($status_filter === 'Returned') {
            // This case is no longer needed since 'returned' is now handled by the array check above.
            // I recommend removing this block to prevent confusion, but for safety in the original structure:
            $sql .= " AND bd.borrow_status = ?";
            $exec_params[] = 'Returned';

        } elseif ($status_filter === 'Borrowed') {
            // Currently borrowed books
            $sql .= " AND bd.borrow_status = ?";
            $exec_params[] = 'Borrowed';

        } else {
            // Default (or 'Pending' in your original code): show by request status
            $sql .= " AND bd.borrow_request_status = ?";
            $exec_params[] = $status_filter;
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


}
