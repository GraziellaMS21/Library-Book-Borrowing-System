<?php
require_once(__DIR__ . "/../../config/database.php");

class Reports extends Database
{
    public function getMonthlyBorrowReturnTrend()
    {
        $sql = "SELECT 
                    Months.month,
                    COALESCE(SUM(b.total_borrows), 0) AS total_borrows,
                    COALESCE(SUM(r.total_returns), 0) AS total_returns
                FROM (
                    SELECT DATE_FORMAT(CURDATE() - INTERVAL (n.n - 1) MONTH, '%M') AS month
                    FROM (SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12) AS n
                ) AS Months
                LEFT JOIN (
                    SELECT DATE_FORMAT(request_date, '%M') AS month, SUM(no_of_copies) AS total_borrows
                    FROM borrowing_details
                    WHERE (borrow_request_status = 'Approved' OR borrow_status IN ('Borrowed', 'Returned'))
                    GROUP BY DATE_FORMAT(request_date, '%M')
                ) AS b ON Months.month = b.month
                LEFT JOIN (
                    SELECT DATE_FORMAT(return_date, '%M') AS month, SUM(no_of_copies) AS total_returns
                    FROM borrowing_details
                    WHERE borrow_status = 'Returned'
                    GROUP BY DATE_FORMAT(return_date, '%M')
                ) AS r ON Months.month = r.month
                GROUP BY Months.month
                ORDER BY STR_TO_DATE(CONCAT('01 ', Months.month, ' 2000'), '%d %M %Y') ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
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

    public function getSummaryMostBorrowedAuthor()
    {
        $sql = "SELECT a.author_name as author, COUNT(bd.bookID) AS borrow_count
                FROM borrowing_details bd
                JOIN books b ON bd.bookID = b.bookID
                JOIN book_authors ba ON b.bookID = ba.bookID
                JOIN authors a ON ba.authorID = a.authorID
                GROUP BY a.authorID, a.author_name
                ORDER BY borrow_count DESC
                LIMIT 1";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getBorrowingByDepartment()
    {
        $sql = "
        SELECT 
            d.department_name AS department,
            COUNT(bd.borrowID) AS total_borrowed
        FROM departments d
        LEFT JOIN users u 
            ON u.departmentID = d.departmentID
        LEFT JOIN borrowing_details bd 
            ON bd.userID = u.userID
            AND bd.borrow_request_status = 'Approved'
        GROUP BY d.department_name
        ORDER BY total_borrowed DESC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getMonthlyFineCollectionTrend()
    {
        $sql = "SELECT 
                    Months.month,
                    COALESCE(SUM(f_paid.total_fines), 0) AS total_collected,
                    COALESCE(SUM(f_unpaid.total_fines), 0) AS total_uncollected
                FROM (
                    SELECT DATE_FORMAT(CURDATE() - INTERVAL (n.n - 1) MONTH, '%M') AS month
                    FROM (SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12) AS n
                ) AS Months
                LEFT JOIN ( SELECT DATE_FORMAT(f.date_created, '%M') AS month, SUM(f.fine_amount) AS total_fines FROM fines f WHERE f.fine_status = 'Paid' GROUP BY DATE_FORMAT(f.date_created, '%M') ) AS f_paid ON Months.month = f_paid.month
                LEFT JOIN ( SELECT DATE_FORMAT(f.date_created, '%M') AS month, SUM(f.fine_amount) AS total_fines FROM fines f WHERE f.fine_status = 'Unpaid' GROUP BY DATE_FORMAT(f.date_created, '%M') ) AS f_unpaid ON Months.month = f_unpaid.month
                GROUP BY Months.month ORDER BY STR_TO_DATE(CONCAT('01 ', Months.month, ' 2000'), '%d %M %Y') ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopUnpaidFinesUsers($limit = 5)
    {
        $sql = "SELECT u.fName, u.lName, SUM(f.fine_amount) AS total_unpaid, COUNT(bd.borrowID) AS overdue_items 
                FROM borrowing_details bd 
                JOIN fines f ON bd.borrowID = f.borrowID
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

    public function getFineCollectionSummary()
    {
        $sql = "SELECT 
                    SUM(CASE WHEN fine_status = 'Paid' THEN fine_amount ELSE 0 END) AS total_collected, 
                    SUM(CASE WHEN fine_status = 'Unpaid' THEN fine_amount ELSE 0 END) AS total_outstanding, 
                    SUM(fine_amount) AS total_issued 
                FROM fines 
                WHERE fine_amount > 0";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getSummaryAverageBorrowDuration()
    {
        $sql = "SELECT AVG(DATEDIFF(return_date, pickup_date)) AS avg_duration_days 
                FROM borrowing_details 
                WHERE borrow_status = 'Returned' AND pickup_date IS NOT NULL AND return_date IS NOT NULL";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
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

    public function getSummaryTotalBorrows()
    {
        $sql = "SELECT SUM(no_of_copies) AS total_borrows 
                FROM borrowing_details 
                WHERE (borrow_request_status = 'Approved' OR borrow_status IN ('Borrowed', 'Returned'))";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_borrows'] ?? 0;
    }

    public function getOnTimeReturnRate()
    {
        $sql = "SELECT 
                    COUNT(CASE WHEN borrow_status = 'Returned' AND return_date <= expected_return_date THEN 1 END) AS on_time_returns, 
                    COUNT(CASE WHEN borrow_status = 'Returned' THEN 1 END) AS total_returns 
                FROM borrowing_details 
                WHERE return_date IS NOT NULL AND expected_return_date IS NOT NULL";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        $on_time = $result['on_time_returns'] ?? 0;
        $total = $result['total_returns'] ?? 0;

        if ($total == 0) {
            return ['rate' => 0];
        }

        $rate = ($on_time / $total) * 100;
        return ['rate' => $rate];
    }

    public function getSummaryTotalCategories()
    {
        $sql = "SELECT COUNT(categoryID) AS total_categories FROM category";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function countTotalActiveBorrowers()
    {
        $sql = "SELECT COUNT(userID) AS total_borrowers FROM users WHERE roleID = 1 AND account_status = 'Active'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_borrowers'] ?? 0;
    }

    public function getTopActiveBorrowers($limit = 5)
    {
        $sql = "SELECT u.fName, u.lName, 
                       COUNT(CASE WHEN bd.borrow_status IN ('Borrowed', 'Returned') THEN 1 END) AS borrow_count, 
                       COUNT(CASE WHEN bd.borrow_status = 'Returned' THEN 1 END) AS return_count 
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

    public function getMonthlyUserRegistrationTrend()
    {
        $sql = "SELECT DATE_FORMAT(date_registered, '%M') AS month, COUNT(userID) AS new_users 
                FROM users 
                WHERE date_registered >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                GROUP BY DATE_FORMAT(date_registered, '%M') 
                ORDER BY month ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBorrowerTypeBreakdown()
    {
        $sql = "SELECT ut.borrower_type, COUNT(bd.borrowID) AS borrow_count 
                FROM borrowing_details bd 
                JOIN users u ON bd.userID = u.userID 
                JOIN borrower_types ut ON u.borrowerTypeID = ut.borrowerTypeID 
                GROUP BY ut.borrower_type 
                ORDER BY borrow_count DESC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookStatusOverview()
    {
        $sql_available = "SELECT SUM(book_copies) FROM books";
        $query_available = $this->connect()->prepare($sql_available);
        $query_available->execute();
        $available = $query_available->fetchColumn() ?? 0;

        $sql_borrowed = "SELECT SUM(no_of_copies) FROM borrowing_details WHERE borrow_status = 'Borrowed'";
        $query_borrowed = $this->connect()->prepare($sql_borrowed);
        $query_borrowed->execute();
        $borrowed = $query_borrowed->fetchColumn() ?? 0;

        $sql_lost = "SELECT SUM(no_of_copies) FROM borrowing_details WHERE borrow_status = 'Lost'";
        $query_lost = $this->connect()->prepare($sql_lost);
        $query_lost->execute();
        $lost = $query_lost->fetchColumn() ?? 0;

        return [
            ['status' => 'Available', 'count' => $available],
            ['status' => 'Borrowed', 'count' => $borrowed],
            ['status' => 'Lost/Damaged', 'count' => $lost]
        ];
    }

    public function getLostBooksDetails()
    {
        $sql = "SELECT 
                    b.book_title,
                    b.book_copies,
                    u.fName,
                    u.lName,
                    bd.pickup_date AS borrow_date,
                    bd.borrow_status
                FROM borrowing_details bd
                JOIN books b ON bd.bookID = b.bookID
                LEFT JOIN users u ON bd.userID = u.userID
                WHERE bd.borrow_status = 'Lost'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBooksPerCategory()
    {
        $sql = "SELECT c.category_name, SUM(b.book_copies) AS total_copies 
                FROM books b 
                JOIN category c ON b.categoryID = c.categoryID 
                GROUP BY c.category_name 
                ORDER BY total_copies DESC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAverageBorrowDurationByCategory()
    {
        $sql = "SELECT c.category_name, AVG(DATEDIFF(bd.return_date, bd.pickup_date)) AS avg_duration_days 
                FROM borrowing_details bd 
                JOIN books b ON bd.bookID = b.bookID 
                JOIN category c ON b.categoryID = c.categoryID 
                WHERE bd.borrow_status = 'Returned' AND bd.pickup_date IS NOT NULL AND bd.return_date IS NOT NULL 
                GROUP BY c.category_name 
                HAVING avg_duration_days > 0 
                ORDER BY avg_duration_days DESC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOverdueBooksSummary()
    {
        $sql = "
        SELECT 
            b.book_title,
            u.fName,
            u.lName,
            bd.expected_return_date,
            bd.return_date,
            f.fine_status,
            DATEDIFF(CURDATE(), bd.expected_return_date) AS days_overdue,
            f.fine_amount,
            bd.borrow_status
        FROM borrowing_details bd
        INNER JOIN fines f ON bd.borrowID = f.borrowID
        INNER JOIN users u ON bd.userID = u.userID
        INNER JOIN books b ON bd.bookID = b.bookID
        WHERE 
            f.fine_status = 'Unpaid' OR f.fine_status = 'Paid'
        ORDER BY days_overdue DESC
    ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getLateReturnsTrend()
    {
        $sql = "SELECT DATE_FORMAT(return_date, '%M') AS month, COUNT(borrowID) AS late_returns 
                FROM borrowing_details 
                WHERE borrow_status = 'Returned' AND return_date > expected_return_date 
                  AND return_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                GROUP BY DATE_FORMAT(return_date, '%M') 
                ORDER BY month ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>