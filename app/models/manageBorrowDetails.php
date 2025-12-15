<?php
require_once(__DIR__ . "/../../config/database.php");
require_once(__DIR__ . "/manageBook.php");
require_once(__DIR__ . "/manageUsers.php");

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
    public $status_reason = "";

    protected $db;

    public function fetchReasonRefs($category)
    {
        $sql = "SELECT * FROM ref_status_reasons WHERE category = :category";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':category', $category);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addBorrowStatusHistory($borrowID, $actionType, $remarks, $reasonIDs = [], $adminID = null)
    {
        $sqlHist = "INSERT INTO borrowing_status_history 
                (borrowID, action_type, additional_remarks, performed_by)
                VALUES (:bid, :action, :remarks, :adminID)";

        $queryHist = $this->connect()->prepare($sqlHist);
        $queryHist->bindParam(":bid", $borrowID);
        $queryHist->bindParam(":action", $actionType);
        $queryHist->bindParam(":remarks", $remarks);
        $queryHist->bindParam(":adminID", $adminID);

        $result = $queryHist->execute();

        if ($result && !empty($reasonIDs)) {
            $historyID = $this->connect()->lastInsertId();

            $sqlEvent = "INSERT INTO borrowing_status_event_reasons 
                     (historyID, reasonID)
                     VALUES (:hid, :rid)";

            $queryEvent = $this->connect()->prepare($sqlEvent);

            foreach ($reasonIDs as $rid) {
                $queryEvent->bindParam(":hid", $historyID);
                $queryEvent->bindParam(":rid", $rid);
                $queryEvent->execute();
            }
        }

        return $result;
    }

    public function getReasonTexts($reasonIDs)
    {
        if (empty($reasonIDs))
            return [];
        $placeholders = implode(',', array_fill(0, count($reasonIDs), '?'));
        $sql = "SELECT reason_text FROM ref_status_reasons WHERE reasonID IN ($placeholders)";
        $query = $this->connect()->prepare($sql);
        $query->execute($reasonIDs);
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    public function fetchLatestBorrowReasons($borrowID)
    {
        $sqlHistory = "SELECT h.borrowHistoryID, h.action_type, h.additional_remarks, h.created_at, 
                              CONCAT(u.fName, ' ', u.lName) as admin_name
                       FROM borrowing_status_history h
                       LEFT JOIN users u ON h.performed_by = u.userID
                       WHERE h.borrowID = :borrowID 
                       ORDER BY h.created_at DESC LIMIT 1";

        $queryHistory = $this->connect()->prepare($sqlHistory);
        $queryHistory->bindParam(':borrowID', $borrowID);
        $queryHistory->execute();
        $history = $queryHistory->fetch(PDO::FETCH_ASSOC);

        if (!$history)
            return ['action_type' => '', 'remarks' => '', 'reasons' => [], 'admin_name' => 'System', 'date' => ''];

        $sqlReasons = "SELECT r.reason_text FROM borrowing_status_event_reasons e 
                       JOIN ref_status_reasons r ON e.reasonID = r.reasonID 
                       WHERE e.historyID = :historyID";
        $queryReasons = $this->connect()->prepare($sqlReasons);
        $queryReasons->bindParam(':historyID', $history['borrowHistoryID']);
        $queryReasons->execute();

        return [
            'action_type' => $history['action_type'],
            'remarks' => $history['additional_remarks'],
            'admin_name' => $history['admin_name'] ?? 'System',
            'date' => $history['created_at'],
            'reasons' => $queryReasons->fetchAll(PDO::FETCH_COLUMN)
        ];
    }

    public function addBorrowDetail()
    {
        $sql = "INSERT INTO borrowing_details (userID, bookID, no_of_copies, request_date, pickup_date, return_date, expected_return_date, returned_condition, borrow_request_status, borrow_status)
                VALUES (:userID, :bookID, :no_of_copies, :request_date, :pickup_date, :return_date, :expected_return_date, :returned_condition, :borrow_request_status, :borrow_status)";
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
        return $query->execute();
    }

    public function editBorrowDetail($borrowID)
    {
        try {
            // 1. Update the main Borrowing Details Table
            $sql = "UPDATE borrowing_details 
                    SET 
                        userID = :userID,
                        bookID = :bookID,
                        no_of_copies = :no_of_copies,
                        request_date = :request_date,
                        pickup_date = :pickup_date,
                        return_date = :return_date,
                        expected_return_date = :expected_return_date,
                        returned_condition = :returned_condition,
                        borrow_request_status = :borrow_request_status,
                        borrow_status = :borrow_status
                    WHERE borrowID = :borrowID";

            $query = $this->connect()->prepare($sql);

            $query->bindParam(":borrowID", $borrowID);
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

            $updateMain = $query->execute();

            // 2. Update the Fines Table (3NF)
            // This ensures manual edits to fines in the modal are saved to the correct table
            $updateFines = $this->updateFineDetails(
                $borrowID,
                $this->fine_amount,
                $this->fine_reason,
                $this->fine_status
            );

            return $updateMain && $updateFines;

        } catch (PDOException $e) {
            return false;
        }
    }

    // --- AUTOMATIC FINE & LOST BOOK LOGIC START ---

    public function checkAndApplyFines($userID)
    {
        $sql = "SELECT bd.*, f.fine_amount, f.fine_status 
                FROM borrowing_details bd
                LEFT JOIN fines f ON bd.borrowID = f.borrowID
                WHERE bd.userID = :userID 
                AND (bd.borrow_status = 'Borrowed' OR f.fine_status = 'Unpaid')";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        $records = $query->fetchAll(PDO::FETCH_ASSOC);

        $hasUnpaid = false;
        $bookObj = new Book();

        foreach ($records as $detail) {

            // Only apply fine calculation if the book is currently borrowed (not returned)
            if ($detail['borrow_status'] === 'Borrowed' && $detail['return_date'] === null) {

                // Calculate fine based on Today vs Expected Date
                $fine_results = $this->calculateFinalFine(
                    $detail['expected_return_date'],
                    date("Y-m-d"),
                    $bookObj,
                    $detail['bookID']
                );

                // If calculated fine is different/new, update the DB
                if ($fine_results['fine_amount'] > $detail['fine_amount']) {
                    $this->updateFineDetails(
                        $detail['borrowID'],
                        $fine_results['fine_amount'],
                        $fine_results['fine_reason'],
                        $fine_results['fine_status']
                    );

                    // Update local loop variables
                    $detail['fine_status'] = $fine_results['fine_status'];
                    $detail['fine_amount'] = $fine_results['fine_amount'];

                    // *** IF LOST: Update Book Condition in Books Table ***
                    if ($fine_results['is_lost'] === true) {
                        $this->updateBookConditionLost($detail['bookID']);
                    }
                }
            }

            // Check if user should be blocked (Any unpaid fine > 0)
            if ($detail['fine_status'] === 'Unpaid' && $detail['fine_amount'] > 0) {
                $hasUnpaid = true;
            }
        }

        if ($hasUnpaid) {
            $userObj = new User();
            $userObj->updateUserStatus(
                $userID,
                "",
                "Blocked",
                "Block",
                "System Blocked: Unpaid Fines / Lost Books"
            );
        }

        return $hasUnpaid;
    }

    // Helper: Mark book as lost in inventory
    public function updateBookConditionLost($bookID)
    {
        $sql = "UPDATE books SET book_condition = 'Lost' WHERE bookID = :bookID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':bookID', $bookID);
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
            'fine_status' => null
        ];

        if ($comparison > $expected) {
            $interval = $expected->diff($comparison);
            $days_late = $interval->days;

            // CONFIGURATION
            $MAX_LATE_WEEKS = 10;
            $MAX_LATE_DAYS = $MAX_LATE_WEEKS * 7; // 70 Days
            $DAILY_FINE = 5.00;

            if ($days_late >= $MAX_LATE_DAYS) {
                // --- LOST LOGIC ---
                $results['is_lost'] = true;

                $max_accumulated_fine = $MAX_LATE_DAYS * $DAILY_FINE; // 70 * 5 = 350
                $replacement_cost = $bookObj->fetchBookReplacementCost($bookID);

                $results['fine_amount'] = $max_accumulated_fine + $replacement_cost;
                $results['fine_reason'] = 'Lost';
                $results['fine_status'] = 'Unpaid';
            } else {
                // --- LATE LOGIC ---
                $late_fine_amount = $days_late * $DAILY_FINE;

                $results['fine_amount'] = $late_fine_amount;
                $results['fine_reason'] = 'Late';
                $results['fine_status'] = 'Unpaid';
            }
        }

        return $results;
    }

    // --- AUTOMATIC FINE & LOST BOOK LOGIC END ---

    public function fetchUserBorrowDetails($userID, $status_filter)
    {
        $sql = "SELECT 
                bd.*, 
                f.fine_amount,
                f.fine_reason,
                f.fine_status,
                b.book_title,
                b.book_cover_dir,
                GROUP_CONCAT(DISTINCT a.author_name SEPARATOR ', ') as author,
                (
                    SELECT GROUP_CONCAT(COALESCE(rs.reason_text, '') SEPARATOR '; ')
                    FROM borrowing_status_history bsh
                    LEFT JOIN borrowing_status_event_reasons bser ON bsh.borrowHistoryID = bser.borrowHistoryID
                    LEFT JOIN ref_status_reasons rs ON bser.reasonID = rs.reasonID
                    WHERE bsh.borrowID = bd.borrowID
                    ORDER BY bsh.created_at DESC
                    LIMIT 1
                ) as history_reasons,
                (
                    SELECT additional_remarks 
                    FROM borrowing_status_history bsh
                    WHERE bsh.borrowID = bd.borrowID
                    ORDER BY bsh.created_at DESC
                    LIMIT 1
                ) as history_remarks
            FROM borrowing_details bd
            JOIN books b ON bd.bookID = b.bookID
            LEFT JOIN fines f ON bd.borrowID = f.borrowID
            LEFT JOIN book_authors ba ON b.bookID = ba.bookID
            LEFT JOIN authors a ON ba.authorID = a.authorID
            WHERE bd.userID = ? ";

        $exec_params = [$userID];
        $status_filter = ucfirst($status_filter);

        if ($status_filter === 'unpaid') {
            $sql .= " AND f.fine_amount > 0 AND f.fine_status = ?";
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

        $sql .= " GROUP BY bd.borrowID ORDER BY bd.request_date DESC";

        $query = $this->connect()->prepare($sql);
        $query->execute($exec_params);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$row) {
            $parts = [];
            if (!empty($row['history_reasons']))
                $parts[] = $row['history_reasons'];
            if (!empty($row['history_remarks']))
                $parts[] = $row['history_remarks'];
            $row['status_reason'] = implode(" - ", $parts);
        }

        return $results;
    }

    public function getTopBorrowedBooks($limit = 5)
    {
        $sql = "SELECT b.book_title, 
                       GROUP_CONCAT(DISTINCT a.author_name SEPARATOR ', ') as author, 
                       COUNT(bd.bookID) AS borrow_count
                FROM borrowing_details bd
                JOIN books b ON bd.bookID = b.bookID
                LEFT JOIN book_authors ba ON b.bookID = ba.bookID
                LEFT JOIN authors a ON ba.authorID = a.authorID
                GROUP BY bd.bookID, b.book_title
                ORDER BY borrow_count DESC
                LIMIT :limit";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
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
            u.account_status,
            b.book_title,
            b.book_condition,
            f.fine_amount,
            f.fine_reason,
            f.fine_status
        FROM borrowing_details bd
        JOIN users u ON bd.userID = u.userID
        JOIN books b ON bd.bookID = b.bookID
        LEFT JOIN fines f ON bd.borrowID = f.borrowID";

        if ($statusFilter != "") {
            if ($statusFilter == 'borrowed') {
                $statusColumn = "bd.borrow_status";
                $dbStatus = 'Borrowed';
            } elseif ($statusFilter == 'returned') {
                $statusColumn = "bd.borrow_status";
                $dbStatus = 'Returned';
            } elseif ($statusFilter == 'unpaid') {
                $whereConditions[] = "f.fine_status = :statusFilter";
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
        $sql = "SELECT SUM(no_of_copies) AS total_overdue FROM borrowing_details WHERE borrow_status = 'Borrowed' AND expected_return_date < CURDATE()";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_overdue'] ?? 0;
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
        $sql = "SELECT bd.*, f.fine_amount, f.fine_reason, f.fine_status, u.fName, u.lName, u.email, b.book_title, b.book_condition, b.replacement_cost,
                (
                    SELECT GROUP_CONCAT(COALESCE(rs.reason_text, '') SEPARATOR '; ')
                    FROM borrowing_status_history bsh
                    LEFT JOIN borrowing_status_event_reasons bser ON bsh.borrowHistoryID = bser.borrowHistoryID
                    LEFT JOIN ref_status_reasons rs ON bser.reasonID = rs.reasonID
                    WHERE bsh.borrowID = bd.borrowID
                    ORDER BY bsh.created_at DESC LIMIT 1
                ) as history_reasons,
                (
                    SELECT additional_remarks FROM borrowing_status_history bsh
                    WHERE bsh.borrowID = bd.borrowID
                    ORDER BY bsh.created_at DESC LIMIT 1
                ) as history_remarks
                FROM borrowing_details bd 
                JOIN users u ON bd.userID = u.userID 
                JOIN books b ON bd.bookID = b.bookID 
                LEFT JOIN fines f ON bd.borrowID = f.borrowID
                WHERE bd.borrowID = :borrowID";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(":borrowID", $borrowID);
        if ($query->execute()) {
            $row = $query->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $parts = [];
                if (!empty($row['history_reasons']))
                    $parts[] = $row['history_reasons'];
                if (!empty($row['history_remarks']))
                    $parts[] = $row['history_remarks'];
                $row['status_reason'] = implode(" - ", $parts);
            }
            return $row;
        }
        return null;
    }

    public function updateFineDetails($borrowID, $fine_amount, $fine_reason, $fine_status)
    {
        try {
            // 1. Check if a fine record already exists for this specific borrowID
            $checkSql = "SELECT COUNT(*) FROM fines WHERE borrowID = :borrowID";
            $checkQuery = $this->connect()->prepare($checkSql);
            $checkQuery->bindParam(":borrowID", $borrowID);
            $checkQuery->execute();
            $exists = $checkQuery->fetchColumn() > 0;

            if ($exists) {
                // 2. If it exists, UPDATE the existing record
                $sql = "UPDATE fines 
                        SET fine_amount = :fine_amount, 
                            fine_reason = :fine_reason, 
                            fine_status = :fine_status 
                        WHERE borrowID = :borrowID";
            } else {
                // 3. If it does NOT exist, INSERT a new record
                $sql = "INSERT INTO fines (borrowID, fine_amount, fine_reason, fine_status) 
                        VALUES (:borrowID, :fine_amount, :fine_reason, :fine_status)";
            }

            $query = $this->connect()->prepare($sql);
            $query->bindParam(":borrowID", $borrowID);
            $query->bindParam(":fine_amount", $fine_amount);
            $query->bindParam(":fine_reason", $fine_reason);
            $query->bindParam(":fine_status", $fine_status);

            return $query->execute();

        } catch (PDOException $e) {
            return false;
        }
    }

    public function countTotalBorrowedBooks()
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed FROM borrowing_details WHERE borrow_status = 'Borrowed'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_borrowed'] ?? 0;
    }

    public function countTotalBooksForPickUp()
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed FROM borrowing_details WHERE borrow_request_status = 'Approved'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_borrowed'] ?? 0;
    }

    public function sumMonthlyCollectedFines()
    {
        $sql = "SELECT SUM(f.fine_amount) AS total_fines 
                FROM fines f
                JOIN borrowing_details bd ON f.borrowID = bd.borrowID
                WHERE f.fine_status = 'Paid' 
                AND MONTH(bd.return_date) = MONTH(CURDATE()) 
                AND YEAR(bd.return_date) = YEAR(CURDATE())";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_fines'] ?? 0.00;
    }

    public function getCollectedFinesLast7Days()
    {
        $sql = "SELECT DATE(bd.return_date) AS fine_date, SUM(f.fine_amount) AS total_fines 
                FROM fines f
                JOIN borrowing_details bd ON f.borrowID = bd.borrowID
                WHERE f.fine_status = 'Paid' 
                AND bd.return_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                GROUP BY DATE(bd.return_date) 
                ORDER BY fine_date ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopActiveBorrowers($limit = 5)
    {
        $sql = "SELECT u.fName, u.lName, COUNT(bd.userID) AS borrow_count FROM borrowing_details bd JOIN users u ON bd.userID = u.userID GROUP BY bd.userID, u.fName, u.lName ORDER BY borrow_count DESC LIMIT :limit";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopUnpaidFinesUsers($limit = 5)
    {
        $sql = "SELECT u.fName, u.lName, SUM(f.fine_amount) AS total_unpaid 
                FROM fines f
                JOIN borrowing_details bd ON f.borrowID = bd.borrowID
                JOIN users u ON bd.userID = u.userID 
                WHERE f.fine_status = 'Unpaid' 
                GROUP BY bd.userID, u.fName, u.lName 
                HAVING total_unpaid > 0 
                ORDER BY total_unpaid DESC 
                LIMIT :limit";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBorrowerTypeBreakdown()
    {
        $sql = "SELECT ut.type_name, COUNT(bd.borrowID) AS borrow_count FROM borrowing_details bd JOIN users u ON bd.userID = u.userID JOIN user_type ut ON u.userTypeID = ut.userTypeID GROUP BY ut.type_name ORDER BY borrow_count DESC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailyBorrowingActivity()
    {
        $sql = "SELECT DATE(request_date) AS borrow_date, SUM(no_of_copies) AS total_borrows FROM borrowing_details WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND (borrow_request_status = 'Approved' OR borrow_status IN ('Borrowed', 'Returned')) GROUP BY DATE(request_date) ORDER BY borrow_date ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyBorrowReturnStats()
    {
        $sql_borrowed = "SELECT SUM(no_of_copies) AS total_borrowed FROM borrowing_details WHERE (borrow_request_status = 'Approved' OR borrow_status IN ('Borrowed', 'Returned')) AND MONTH(request_date) = MONTH(CURDATE()) AND YEAR(request_date) = YEAR(CURDATE())";
        $query_borrowed = $this->connect()->prepare($sql_borrowed);
        $query_borrowed->execute();
        $borrowed = $query_borrowed->fetch(PDO::FETCH_ASSOC)['total_borrowed'] ?? 0;
        $sql_returned = "SELECT SUM(no_of_copies) AS total_returned FROM borrowing_details WHERE borrow_status = 'Returned' AND MONTH(return_date) = MONTH(CURDATE()) AND YEAR(return_date) = YEAR(CURDATE())";
        $query_returned = $this->connect()->prepare($sql_returned);
        $query_returned->execute();
        $returned = $query_returned->fetch(PDO::FETCH_ASSOC)['total_returned'] ?? 0;
        return [['status' => 'Borrowed', 'count' => $borrowed], ['status' => 'Returned', 'count' => $returned]];
    }

    public function getBooksDueToday()
    {
        $sql = "SELECT bd.*, u.fName, u.lName, b.book_title, b.book_condition FROM borrowing_details bd JOIN users u ON bd.userID = u.userID JOIN books b ON bd.bookID = b.bookID WHERE bd.borrow_status = 'Borrowed' AND DATE(bd.expected_return_date) = CURDATE() ORDER BY u.lName, u.fName";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchTotalBorrowedBooks($userID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed FROM borrowing_details WHERE userID = :userID AND (borrow_request_status = 'Pending' OR borrow_request_status = 'Approved' OR borrow_status = 'Borrowed')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        if ($query->execute()) {
            $record = $query->fetch();
            return (int) ($record["total_borrowed"] ?? 0);
        }
        return 0;
    }

    public function fetchPendingBooks($userID, $bookID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed FROM borrowing_details WHERE userID = :userID AND bookID = :bookID AND borrow_request_status = 'Pending'";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->bindParam(':bookID', $bookID);
        if ($query->execute()) {
            $record = $query->fetch();
            return (int) ($record["total_borrowed"] ?? 0);
        }
        return 0;
    }

    public function fetchBorrowedBooks($userID, $bookID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrowed FROM borrowing_details WHERE userID = :userID AND bookID = :bookID AND (borrow_request_status = 'Pending' OR borrow_request_status = 'Approved' OR borrow_status = 'Borrowed')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->bindParam(':bookID', $bookID);
        if ($query->execute()) {
            $record = $query->fetch();
            return (int) ($record["total_borrowed"] ?? 0);
        }
        return 0;
    }

    public function fetchPendingAndApprovedCopiesForBook($bookID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_pending FROM borrowing_details WHERE bookID = :bookID AND borrow_request_status IN ('Pending', 'Approved') AND (borrow_status IS NULL OR borrow_status = 'Fined')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':bookID', $bookID);
        if ($query->execute()) {
            $record = $query->fetch();
            return (int) ($record["total_pending"] ?? 0);
        }
        return 0;
    }

    public function fetchPendingCopiesOnlyForBook($bookID)
    {
        $sql = "SELECT SUM(no_of_copies) AS total_pending FROM borrowing_details WHERE bookID = :bookID AND borrow_request_status = 'Pending'";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':bookID', $bookID);
        if ($query->execute()) {
            $record = $query->fetch();
            return (int) ($record["total_pending"] ?? 0);
        }
        return 0;
    }

    public function isBookBorrowed($userID, $bookID)
    {
        $sql = "SELECT COUNT(borrowID) FROM borrowing_details WHERE userID = :userID AND bookID = :bookID AND (borrow_request_status = 'Approved' OR borrow_status = 'Borrowed')";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->bindParam(":bookID", $bookID);
        if ($query->execute()) {
            $record = $query->fetchColumn();
            if (!empty($record) && $record > 0)
                return true;
        }
        return false;
    }

    public function deleteBorrowDetail($borrowID)
    {
        $sql = "DELETE FROM borrowing_details WHERE borrowID = :borrowID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":borrowID", $borrowID);
        return $query->execute();
    }

    public function calculateMaxCopiesAllowed($userTypeID, $borrow_limit, $current_borrowed_count, $max_available, $is_borrowed)
    {
        $available_slots = $borrow_limit - $current_borrowed_count;

        if ($available_slots <= 0) {
            return 0;
        }

        if ($userTypeID == 1 || $userTypeID == 3) {
            if ($is_borrowed) {
                return 0;
            }
            return min(1, $available_slots, $max_available);
        }

        if ($userTypeID == 2) {
            if ($is_borrowed) {
                return 0;
            }
            return min($max_available, $available_slots);
        }

        return 0;
    }

    public function updateAllUsersFines()
    {
        // Get all users who have active borrows or unpaid fines
        $sql = "SELECT DISTINCT bd.userID 
            FROM borrowing_details bd
            LEFT JOIN fines f ON bd.borrowID = f.borrowID
            WHERE bd.borrow_status = 'Borrowed' 
            OR f.fine_status = 'Unpaid'";

        $query = $this->connect()->prepare($sql);
        $query->execute();
        $users = $query->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $uid) {
            $this->checkAndApplyFines($uid);
        }
    }
}
?>