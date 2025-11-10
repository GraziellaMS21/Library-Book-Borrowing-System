<?php
require_once(__DIR__ . "/../../config/database.php");
require_once(__DIR__ . "/manageBook.php");
$bookObj = new Book();

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
    public $user_notified = NULL;

    protected $db;

    public function addBorrowDetail()
    {
        $sql = "INSERT INTO borrowing_details (userID, bookID, no_of_copies, request_date, pickup_date, return_date, expected_return_date, returned_condition, borrow_request_status, borrow_status, fine_amount, fine_reason, fine_status, user_notified)
                VALUES (:userID, :bookID, :no_of_copies, :request_date, :pickup_date, :return_date, :expected_return_date, :returned_condition, :borrow_request_status, :borrow_status, :fine_amount, :fine_reason, :fine_status, :user_notified)";
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
        $query->bindParam(":user_notified", $this->user_notified);
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
                    user_notified = :user_notified
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
        $query->bindParam(":user_notified", $this->user_notified);
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

    public function fetchPendingAndApprovedCopiesForBook($bookID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_pending
                FROM borrowing_details 
                WHERE bookID = :bookID
                AND borrow_request_status IN ('Pending', 'Approved')
                AND (borrow_status IS NULL OR borrow_status = 'Fined')";

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

    public function updateBorrowDetails($borrowID, $borrow_status, $borrow_request_status, $return_date, $user_notified)
    {
        $sql = "UPDATE borrowing_details SET borrow_request_status = :borrow_request_status, borrow_status = :borrow_status, return_date = :return_date, user_notified = :user_notified WHERE borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":borrow_request_status", $borrow_request_status);
        $query->bindParam(":borrow_status", $borrow_status);
        $query->bindParam(":borrowID", $borrowID);
        $query->bindParam(":return_date", $return_date);
        $query->bindParam(":user_notified", $user_notified);
        return $query->execute();
    }

    public function updateBorrowerNotifiedStatus($borrowID, $status)
    {
        $sql = "UPDATE borrowing_details SET user_notified = :status WHERE borrowID = :borrowID";
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

    public function calculateFinalFine($expected_return_date, $comparison_date_string, Book $bookObj, $bookID)
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
                // VS Code can now resolve this method call:
                $replacement_cost = $bookObj->fetchBookReplacementCost($bookID);

                // Total Fine = Capped Late Fee (300.00) + Replacement Cost 
                // This resolves the inconsistency and ensures a fixed maximum late component.
                $results['fine_amount'] = $MAX_LATE_FEE + $replacement_cost;
                $results['fine_reason'] = 'Lost';
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
        if ($results['fine_amount'] <= 0.00) {
            $results['fine_reason'] = null; // Also clear the reason for perfect consistency
            $results['fine_status'] = null;
        }
        return $results;
    }

    public function countTotalBorrowedBooks()
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed 
                FROM borrowing_details 
                WHERE borrow_status = 'Borrowed'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_borrowed'] ?? 0;
    }

     public function countTotalBooksForPickUp()
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed 
                FROM borrowing_details 
                WHERE borrow_request_status = 'Approved'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_borrowed'] ?? 0;
    }

    public function sumTotalCollectedFines()
    {
        $sql = "SELECT SUM(fine_amount) AS total_fines
                FROM borrowing_details
                WHERE fine_status = 'Paid'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_fines'] ?? 0.00;
    }


    public function sumMonthlyCollectedFines()
    {
        $sql = "SELECT SUM(fine_amount) AS total_fines
                FROM borrowing_details
                WHERE fine_status = 'Paid'
                AND MONTH(return_date) = MONTH(CURDATE())
                AND YEAR(return_date) = YEAR(CURDATE())";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_fines'] ?? 0.00;
    }
    
     public function getCollectedFinesLast7Days()
{
    $sql = "SELECT DATE(return_date) AS fine_date, 
                   SUM(fine_amount) AS total_fines
            FROM borrowing_details
            WHERE fine_status = 'Paid'
              AND return_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(return_date)
            ORDER BY fine_date ASC";
    
    $query = $this->connect()->prepare($sql);
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

    
    public function getTopBorrowedBookName()
    {
        $sql = "SELECT b.book_title
                FROM borrowing_details bd
                JOIN books b ON bd.bookID = b.bookID
                GROUP BY bd.bookID, b.book_title
                ORDER BY COUNT(bd.bookID) DESC
                LIMIT 1";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['book_title'] ?? 'N/A';
    }

    public function getTopBorrowedBooks($limit = 5)
    {
        $sql = "SELECT b.book_title, COUNT(bd.bookID) AS borrow_count
                FROM borrowing_details bd
                JOIN books b ON bd.bookID = b.bookID
                GROUP BY bd.bookID, b.book_title
                ORDER BY borrow_count DESC
                LIMIT :limit";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopActiveBorrowers($limit = 5)
    {
        $sql = "SELECT u.fName, u.lName, COUNT(bd.userID) AS borrow_count
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                GROUP BY bd.userID, u.fName, u.lName
                ORDER BY borrow_count DESC
                LIMIT :limit";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyFinesTrend()
    {
        // SQL for last 12 months fines
        $sql = "SELECT 
                    DATE_FORMAT(return_date, '%Y-%m') AS month,
                    SUM(fine_amount) AS total_fines
                FROM borrowing_details
                WHERE fine_status = 'Paid'
                AND return_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(return_date, '%Y-%m')
                ORDER BY month ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
 
    public function getMonthlyBorrowingTrend()
    {
        $sql = "SELECT 
                    DATE_FORMAT(request_date, '%Y-%m') AS month,
                    SUM(no_of_copies) AS total_borrows
                FROM borrowing_details
                WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  AND (borrow_request_status = 'Approved' OR borrow_status IN ('Borrowed', 'Returned'))
                GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                ORDER BY month ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches the breakdown of all borrow statuses.
     * FOR: Dashboard Chart - Borrow Status Breakdown
     */
    public function getBorrowStatusBreakdown()
{
    $sql = "SELECT 
               CASE
                   WHEN borrow_status = 'Borrowed' AND (expected_return_date >= CURDATE() OR expected_return_date IS NULL) THEN 'Borrowed'
                   WHEN borrow_status = 'Borrowed' AND expected_return_date < CURDATE() THEN 'Overdue'
                   WHEN borrow_status = 'Returned' THEN 'Returned'
                   WHEN borrow_status = 'Lost' THEN 'Lost'
               END AS status_label,
               COUNT(borrowID) AS status_count
            FROM borrowing_details
            WHERE borrow_status IS NOT NULL
            GROUP BY status_label";

    $query = $this->connect()->prepare($sql);
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

public function getTopUnpaidFinesUsers($limit = 5)
    {
        $sql = "SELECT 
                    u.fName, 
                    u.lName, 
                    SUM(bd.fine_amount) AS total_unpaid
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                WHERE bd.fine_status = 'Unpaid'
                GROUP BY bd.userID, u.fName, u.lName
                HAVING total_unpaid > 0
                ORDER BY total_unpaid DESC
                LIMIT :limit";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches the breakdown of all borrows by user type.
     * FOR: Dashboard Chart - Borrower Type Breakdown
     */

    public function getFilteredBorrowHistory($filters = [])
    {
        $sql = "SELECT 
            bd.*, 
            u.fName, 
            u.lName, 
            ut.type_name,
            b.book_title,
            c.category_name
        FROM borrowing_details bd
        JOIN users u ON bd.userID = u.userID
        JOIN user_type ut ON u.userTypeID = ut.userTypeID
        JOIN books b ON bd.bookID = b.bookID
        JOIN category c ON b.categoryID = c.categoryID";

        $whereConditions = [];
        $params = [];

        // Date Range Filter
        if (!empty($filters['start_date'])) {
            $whereConditions[] = "bd.request_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $whereConditions[] = "bd.request_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Category Filter
        if (!empty($filters['category'])) {
            $whereConditions[] = "b.categoryID = :category";
            $params[':category'] = $filters['category'];
        }

        // User Type Filter
        if (!empty($filters['user_type'])) {
            $whereConditions[] = "u.userTypeID = :user_type";
            $params[':user_type'] = $filters['user_type'];
        }

        // Status Filter
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'Pending':
                    $whereConditions[] = "bd.borrow_request_status = 'Pending'";
                    break;
                case 'Approved':
                    $whereConditions[] = "bd.borrow_request_status = 'Approved' AND bd.borrow_status IS NULL";
                    break;
                case 'Borrowed':
                    $whereConditions[] = "bd.borrow_status = 'Borrowed'";
                    break;
                case 'Returned':
                    $whereConditions[] = "bd.borrow_status = 'Returned'";
                    break;
                case 'Overdue':
                    $whereConditions[] = "bd.borrow_status = 'Borrowed' AND bd.expected_return_date < CURDATE()";
                    break;
            }
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }

        $sql .= " ORDER BY bd.request_date DESC";
        
        $query = $this->connect()->prepare($sql);
        $query->execute($params);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailyBorrowingActivity()
    {
        $sql = "SELECT 
                    DATE(request_date) AS borrow_date,
                    SUM(no_of_copies) AS total_borrows
                FROM borrowing_details
                WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                  AND (borrow_request_status = 'Approved' OR borrow_status IN ('Borrowed', 'Returned'))
                GROUP BY DATE(request_date)
                ORDER BY borrow_date ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches total borrowed vs. total returned for the current month.
     * FOR: Dashboard Chart - Books Borrowed vs. Returned
     */
    public function getMonthlyBorrowReturnStats()
    {
        $sql_borrowed = "SELECT SUM(no_of_copies) AS total_borrowed
                         FROM borrowing_details
                         WHERE (borrow_request_status = 'Approved' OR borrow_status IN ('Borrowed', 'Returned'))
                         AND MONTH(request_date) = MONTH(CURDATE())
                         AND YEAR(request_date) = YEAR(CURDATE())";
        
        $query_borrowed = $this->connect()->prepare($sql_borrowed);
        $query_borrowed->execute();
        $borrowed = $query_borrowed->fetch(PDO::FETCH_ASSOC)['total_borrowed'] ?? 0;

        $sql_returned = "SELECT SUM(no_of_copies) AS total_returned
                         FROM borrowing_details
                         WHERE borrow_status = 'Returned'
                         AND MONTH(return_date) = MONTH(CURDATE())
                         AND YEAR(return_date) = YEAR(CURDATE())";
        
        $query_returned = $this->connect()->prepare($sql_returned);
        $query_returned->execute();
        $returned = $query_returned->fetch(PDO::FETCH_ASSOC)['total_returned'] ?? 0;

        return [
            ['status' => 'Borrowed', 'count' => $borrowed],
            ['status' => 'Returned', 'count' => $returned]
        ];
    }

    public function getBooksDueToday() {
        // CURDATE() gets today's date (e.g., '2025-11-11')
        $sql = "SELECT bd.*, u.fName, u.lName, b.book_title
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                JOIN books b ON bd.bookID = b.bookID
                WHERE bd.borrow_status = 'Borrowed'
                AND DATE(bd.expected_return_date) = CURDATE()
                ORDER BY u.lName, u.fName";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}