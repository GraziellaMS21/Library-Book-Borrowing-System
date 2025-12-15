<?php
require_once(__DIR__ . "/../../config/database.php");

class User extends Database
{
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contact_no = "";
    public $departmentID = "";
    public $imageID_name = "";
    public $imageID_dir = "";
    public $email = "";
    public $password = "";
    public $borrowerTypeID = "";
    public $date_registered = "";
    public $role = "";
    public $registration_status = "";
    protected $db;

    public function changePassword($userID, $new_password)
    {
        $sql = "UPDATE users SET password = :password WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':password', $new_password);
        $query->bindParam(':userID', $userID);
        return $query->execute();
    }

    public function editUser($userID)
    {
        $sql = "UPDATE users SET 
                    lName = :lName, 
                    fName = :fName, 
                    middleIn = :middleIn, 
                    contact_no = :contact_no, 
                    departmentID = :departmentID, 
                    id_number = :id_number,  
                    email = :email, 
                    borrowerTypeID = :borrowerTypeID, 
                    imageID_name = :imageID_name, 
                    imageID_dir = :imageID_dir
                WHERE userID = :userID";

        $query = $this->connect()->prepare($sql);

        $deptID = empty($this->departmentID) ? null : $this->departmentID;
        $query->bindParam(":lName", $this->lName);
        $query->bindParam(":fName", $this->fName);
        $query->bindParam(":middleIn", $this->middleIn);
        $query->bindParam(":contact_no", $this->contact_no);
        $query->bindParam(":departmentID", $deptID);
        $query->bindParam(":id_number", $idNum);
        $query->bindParam(":imageID_name", $this->imageID_name);
        $query->bindParam(":imageID_dir", $this->imageID_dir);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":borrowerTypeID", $this->borrowerTypeID);
        $query->bindParam(":userID", $userID);

        return $query->execute();
    }

    public function viewUser($search = "", $userType = "", $statusFilter = "")
    {
        $whereConditions = [];
        $whereConditions[] = "u.is_removed = 0";

        $dbStatus = ucfirst($statusFilter);

        $sql = "SELECT u.*, bt.borrower_type, ur.role_name as role, d.department_name
        FROM users u
        JOIN user_roles ur ON u.roleID = ur.roleID
        JOIN borrower_types bt ON u.borrowerTypeID = bt.borrowerTypeID
        LEFT JOIN departments d ON u.departmentID = d.departmentID";

        if ($statusFilter != "") {
            if ($statusFilter == 'blocked') {
                $whereConditions[] = "u.account_status = 'Blocked'";
            } elseif ($statusFilter == 'approved') {
                $whereConditions[] = "u.registration_status = 'Approved'";
                $whereConditions[] = "u.account_status = 'Active'";
            } else {
                $whereConditions[] = "u.registration_status = :statusFilter";
            }
        }

        if ($search != "") {
            $whereConditions[] = "(u.fName LIKE CONCAT('%', :search, '%') 
                            OR u.lName LIKE CONCAT('%', :search, '%')
                            OR u.email LIKE CONCAT('%', :search, '%'))";
        }
        if ($userType != "") {
            $whereConditions[] = "ut.userTypeID = :userType";
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }

        $sql .= " ORDER BY u.lName ASC";

        $query = $this->connect()->prepare($sql);

        if ($search != "") {
            $query->bindParam(":search", $search);
        }
        if ($userType != "") {
            $query->bindParam(":userType", $userType);
        }
        if (in_array($statusFilter, ['pending', 'rejected'])) {
            $query->bindParam(":statusFilter", $dbStatus);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function fetchUser($userID)
    {
        $sql = "SELECT u.*, bt.borrower_type, ur.role_name as role, bt.borrower_limit, u.borrowerTypeID AS status FROM users u JOIN user_roles ur ON u.roleID = ur.roleID JOIN borrower_types bt ON u.borrowerTypeID = bt.borrowerTypeID WHERE u.userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':userID', $userID);
        $query->execute();
        return $query->fetch();
    }

    public function fetchUserName($userID)
    {
        $sql = "SELECT fName, lName FROM users WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':userID', $userID);
        $query->execute();
        $user = $query->fetch();
        return ($user === false) ? null : $user;
    }

    public function fetchUserTypes()
    {
        $sql = "SELECT * FROM borrower_types";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }

    public function fetchUserLimit($borrowerTypeID)
    {
        $sql = "SELECT borrower_limit FROM borrower_types WHERE borrowerTypeID = :borrowerTypeID LIMIT 1";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':borrowerTypeID', $borrowerTypeID);
        $query->execute();
        $result = $query->fetch();
        return $result['borrower_limit'] ?? 0;
    }

    public function isEmailExist($email, $userID = "")
    {
        if ($userID) {
            // Check is_removed
            $sql = "SELECT COUNT(userID) as total_users FROM users WHERE email = :email AND userID <> :userID AND is_removed = 0";
        } else {
            $sql = "SELECT COUNT(userID) as total_users FROM users WHERE email = :email AND is_removed = 0";
        }
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        if ($userID) {
            $query->bindParam(":userID", $userID);
        }
        $query->execute();
        $record = $query->fetch();
        return ($record["total_users"] > 0);
    }
    public function fetchReasonRefs($category = null)
    {
        $sql = "SELECT * FROM ref_status_reasons";
        if ($category) {
            $sql .= " WHERE category = :category";
        }
        $query = $this->connect()->prepare($sql);
        if ($category) {
            $query->bindParam(':category', $category);
        }
        $query->execute();
        return $query->fetchAll();
    }

    public function fetchLatestUserReasons($userID)
    {
        $sqlHistory = "SELECT h.userHistoryID, h.additional_remarks, h.created_at, 
                              CONCAT(admin.fName, ' ', admin.lName) as admin_name
                       FROM user_status_history h
                       LEFT JOIN users admin ON h.performed_by = admin.userID
                       WHERE h.userID = :userID 
                       ORDER BY h.created_at DESC LIMIT 1";

        $queryHistory = $this->connect()->prepare($sqlHistory);
        $queryHistory->bindParam(':userID', $userID);
        $queryHistory->execute();
        $history = $queryHistory->fetch();

        if (!$history)
            return ['remarks' => '', 'reasons' => [], 'admin_name' => 'System', 'date' => ''];

        // --- UPDATE THIS SECTION ---
        // Changed 'WHERE e.historyID' to 'WHERE e.userHistoryID' to match your database change
        $sqlReasons = "SELECT r.reason_text FROM user_status_event_reasons e 
                       JOIN ref_status_reasons r ON e.reasonID = r.reasonID 
                       WHERE e.userHistoryID = :historyID";

        $queryReasons = $this->connect()->prepare($sqlReasons);
        $queryReasons->bindParam(':historyID', $history['userHistoryID']);
        $queryReasons->execute();

        return [
            'remarks' => $history['additional_remarks'],
            'reasons' => $queryReasons->fetchAll(PDO::FETCH_COLUMN),
            'admin_name' => $history['admin_name'] ?? 'System/Unknown',
            'date' => $history['created_at']
        ];
    }
    public function updateUserStatus($userID, $newRegStatus, $newAccStatus, $actionType, $remarks = "", $reasonIDs = [], $adminID = null)
    {
        // ✅ ONE connection only
        $conn = $this->connect();

        /* 1. Update users table */
        if ($newRegStatus != "" && $newAccStatus != "") {
            $sql = "UPDATE users 
                SET registration_status = :newReg,
                    account_status = :newAcc
                WHERE userID = :userID";
        } elseif ($newRegStatus != "") {
            $sql = "UPDATE users 
                SET registration_status = :newReg
                WHERE userID = :userID";
        } else {
            $sql = "UPDATE users 
                SET account_status = :newAcc
                WHERE userID = :userID";
        }

        $query = $conn->prepare($sql);
        $query->bindParam(":userID", $userID);

        if ($newRegStatus != "")
            $query->bindParam(":newReg", $newRegStatus);

        if ($newAccStatus != "")
            $query->bindParam(":newAcc", $newAccStatus);

        $result = $query->execute();

        /* 2. Insert user status history */
        if ($result) {
            $sqlHist = "INSERT INTO user_status_history
                    (userID, action_type, additional_remarks, performed_by)
                    VALUES (:uid, :action, :remarks, :adminID)";

            $queryHist = $conn->prepare($sqlHist);
            $queryHist->bindParam(":uid", $userID);
            $queryHist->bindParam(":action", $actionType);
            $queryHist->bindParam(":remarks", $remarks);
            $queryHist->bindParam(":adminID", $adminID);
            $queryHist->execute();

            // ✅ NOW this is correct
            $historyID = $conn->lastInsertId();

            // 🚨 safety guard
            if (!$historyID) {
                return $result;
            }

            /* 3. Insert reasons */
            if (!empty($reasonIDs)) {
                $sqlEvent = "INSERT INTO user_status_event_reasons
                         (userHistoryID, reasonID)
                         VALUES (:hid, :rid)";

                $queryEvent = $conn->prepare($sqlEvent);

                foreach ($reasonIDs as $rid) {
                    $queryEvent->bindParam(":hid", $historyID);
                    $queryEvent->bindParam(":rid", $rid);
                    $queryEvent->execute();
                }
            }
        }

        return $result;
    }


    public function deleteUser($userID)
    {
        $checkSql = "SELECT ur.role_name as role FROM users u JOIN user_roles ur ON u.roleID = ur.roleID WHERE u.userID = :userID";
        $checkQuery = $this->connect()->prepare($checkSql);
        $checkQuery->bindParam(":userID", $userID);
        $checkQuery->execute();
        $targetUser = $checkQuery->fetch();

        if ($targetUser && $targetUser['role'] === 'Admin') {
            return false;
        }
        $sql = "UPDATE users SET is_removed = 1 WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        return $query->execute();
    }

    public function countTotalActiveBorrowers()
    {
        $sql = "SELECT COUNT(u.userID) AS total_borrowers FROM users u JOIN user_roles ur ON u.roleID = ur.roleID WHERE ur.role_name = 'Borrower' AND u.account_status = 'Active' AND u.is_removed = 0";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch();
        return $result['total_borrowers'] ?? 0;
    }

    public function countPendingUsers()
    {
        $sql = "SELECT COUNT(u.userID) AS total_pending FROM users u JOIN user_roles ur ON u.roleID = ur.roleID WHERE u.registration_status = 'Pending' AND ur.role_name = 'Borrower' AND u.is_removed = 0";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch();
        return $result['total_pending'] ?? 0;
    }

    public function getUserRegistrationTrend()
    {
        $sql = "SELECT DATE(date_registered) AS reg_date, COUNT(userID) AS new_users FROM users WHERE date_registered >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND is_removed = 0 GROUP BY DATE(date_registered) ORDER BY reg_date ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }

    public function fetchDepartments()
    {
        $sql = "SELECT * FROM departments ORDER BY department_name ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }
}
?>