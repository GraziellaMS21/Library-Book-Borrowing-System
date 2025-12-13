<?php
require_once(__DIR__ . "/../../config/database.php");

class User extends Database
{
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contact_no = "";
    // CHANGED: Use departmentID
    public $departmentID = "";
    public $imageID_name = "";
    public $imageID_dir = "";
    public $email = "";
    public $password = "";
    public $userTypeID = "";
    public $date_registered = "";
    public $role = "";
    public $registration_status = "";
    public $status_reason = "";
    protected $db;

    public function editUser($userID)
    {
        $sql = "UPDATE users SET 
                    lName = :lName, fName = :fName, middleIn = :middleIn, 
                    contact_no = :contact_no, departmentID = :departmentID, 
                    email = :email, userTypeID = :userTypeID, role = :role,
                    imageID_name = :imageID_name, imageID_dir = :imageID_dir
                WHERE userID = :userID";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(":lName", $this->lName);
        $query->bindParam(":fName", $this->fName);
        $query->bindParam(":middleIn", $this->middleIn);
        $query->bindParam(":contact_no", $this->contact_no);
        // CHANGED: Bind departmentID
        $query->bindParam(":departmentID", $this->departmentID);
        $query->bindParam(":imageID_name", $this->imageID_name);
        $query->bindParam(":imageID_dir", $this->imageID_dir);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":userTypeID", $this->userTypeID);
        $query->bindParam(":role", $this->role);
        $query->bindParam(":userID", $userID);

        return $query->execute();
    }

    // ... (Other methods from manageUsers.php preserved) ...
    // viewUser, countTotalActiveBorrowers, fetchUser, fetchUserName, etc.

    public function viewUser($search = "", $userType = "", $statusFilter = "")
    {
        $whereConditions = [];
        $dbStatus = ucfirst($statusFilter);

        $sql = "SELECT u.*, ut.type_name, d.department_name
        FROM users u
        JOIN user_type ut ON u.userTypeID = ut.userTypeID
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
        $sql = "SELECT u.*, ut.*, u.userTypeID AS status FROM users u JOIN user_type ut ON u.userTypeID = ut.userTypeID WHERE u.userID = :userID";
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
        $sql = "SELECT * FROM user_type";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }
    public function fetchUserLimit($userTypeID)
    {
        $sql = "SELECT borrower_limit FROM user_type WHERE userTypeID = :userTypeID LIMIT 1";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':userTypeID', $userTypeID);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['borrower_limit'] ?? 0;
    }
    public function isEmailExist($email, $userID = "")
    {
        if ($userID) {
            $sql = "SELECT COUNT(userID) as total_users FROM users WHERE email = :email AND userID <> :userID";
        } else {
            $sql = "SELECT COUNT(userID) as total_users FROM users WHERE email = :email";
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
    public function updateUserStatus($userID, $newRegStatus, $newAccStatus, $status_reason = null)
    {
        $sql = "UPDATE users SET ";
        $params = [];
        if ($newRegStatus != "" && $newAccStatus != "") {
            $sql .= "registration_status = :newRegStatus, account_status = :newAccStatus";
            $params[':newRegStatus'] = $newRegStatus;
            $params[':newAccStatus'] = $newAccStatus;
        } else if ($newRegStatus != "") {
            $sql .= "registration_status = :newRegStatus";
            $params[':newRegStatus'] = $newRegStatus;
        } else {
            $sql .= "account_status = :newAccStatus";
            $params[':newAccStatus'] = $newAccStatus;
        }
        if ($status_reason !== null) {
            $sql .= ", status_reason = :status_reason";
            $params[':status_reason'] = $status_reason;
        }
        $sql .= " WHERE userID = :userID";
        $params[':userID'] = $userID;
        $query = $this->connect()->prepare($sql);
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }
        return $query->execute();
    }
    // In app/models/manageUsers.php

    public function deleteUser($userID)
    {
        $checkSql = "SELECT role FROM users WHERE userID = :userID";
        $checkQuery = $this->connect()->prepare($checkSql);
        $checkQuery->bindParam(":userID", $userID);
        $checkQuery->execute();
        $targetUser = $checkQuery->fetch(PDO::FETCH_ASSOC);
        // Prevent deletion of Super Admin accounts
        if ($targetUser && $targetUser['role'] === 'Super Admin') {
            return false;
        }
        $sql = "DELETE FROM users WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        return $query->execute();
    }
    public function countTotalActiveBorrowers()
    {
        $sql = "SELECT COUNT(userID) AS total_borrowers FROM users WHERE role = 'Borrower' AND account_status = 'Active'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_borrowers'] ?? 0;
    }
    public function countPendingUsers()
    {
        $sql = "SELECT COUNT(userID) AS total_pending FROM users WHERE registration_status = 'Pending' AND role = 'Borrower'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_pending'] ?? 0;
    }
    public function getUserRegistrationTrend()
    {
        $sql = "SELECT DATE(date_registered) AS reg_date, COUNT(userID) AS new_users FROM users WHERE date_registered >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(date_registered) ORDER BY reg_date ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
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