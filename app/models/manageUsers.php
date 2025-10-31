<?php
require_once(__DIR__ . "/../../config/database.php");

class User extends Database
{
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contact_no = "";
    public $college_department = "";
    public $imageID_name = "";
    public $imageID_dir = "";
    public $email = "";
    public $password = "";
    public $userTypeID = "";
    public $date_registered = "";
    public $role = "";
    public $user_status = "";

    protected $db;

    public function viewUser($search = "", $userType = "", $statusFilter = "")
    {
        $whereConditions = [];

        $sql = "SELECT u.*, ut.type_name
                FROM users u
                JOIN user_type ut ON u.userTypeID = ut.userTypeID";

        if ($statusFilter != "") {
            $dbStatus = strtolower($statusFilter);

            if ($statusFilter == 'blocked') {
                $dbStatus = 'Blocked';
            } elseif ($statusFilter == 'approved') {
                $dbStatus = 'Approved';
            } elseif ($statusFilter == 'rejected') {
                $dbStatus = 'Rejected';
            } else {
                $dbStatus = 'Pending';
            }

            $whereConditions[] = "u.user_status = :statusFilter";
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
        if ($statusFilter != "") {
            if ($statusFilter == 'blocked') {
                $dbStatus = 'Blocked';
            } elseif ($statusFilter == 'approved') {
                $dbStatus = 'Approved';
            } elseif ($statusFilter == 'rejected') {
                $dbStatus = 'Rejected';
            } else {
                $dbStatus = 'Pending';
            }
            $query->bindParam(":statusFilter", $dbStatus);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }
    
    // NEW FUNCTION: Count total borrowers (users with role 'Borrower' and status 'Approved')
    public function countTotalBorrowers()
    {
        $sql = "SELECT COUNT(userID) AS total_borrowers FROM users WHERE role = 'Borrower' AND user_status = 'Approved'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_borrowers'] ?? 0;
    }

    // NEW FUNCTION: Count pending user registrations
    public function countPendingUsers()
    {
        $sql = "SELECT COUNT(userID) AS total_pending FROM users WHERE user_status = 'Pending' AND role = 'Borrower'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_pending'] ?? 0;
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
        // Select only the name columns you need (e.g., fName and lName)
        $sql = "SELECT fName, lName FROM users WHERE userID = :userID";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(':userID', $userID);
        $query->execute();
        $user = $query->fetch();

        // Ensure we always return an array, not false
        if ($user === false) {
            return null;
        }

        return $user;
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
            $sql = "SELECT COUNT(userID) as total_users 
                    FROM users 
                    WHERE email = :email AND userID <> :userID";
        } else {
            $sql = "SELECT COUNT(userID) as total_users 
                    FROM users 
                    WHERE email = :email";
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

    public function editUser($userID)
    {
        $sql = "UPDATE users SET 
                    lName = :lName, fName = :fName, middleIn = :middleIn, 
                    contact_no = :contact_no, college_department = :college_department, 
                    email = :email, userTypeID = :userTypeID, role = :role,
                    imageID_name = :imageID_name, imageID_dir = :imageID_dir
                WHERE userID = :userID";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(":lName", $this->lName);
        $query->bindParam(":fName", $this->fName);
        $query->bindParam(":middleIn", $this->middleIn);
        $query->bindParam(":contact_no", $this->contact_no);
        $query->bindParam(":college_department", $this->college_department);
        $query->bindParam(":imageID_name", $this->imageID_name);
        $query->bindParam(":imageID_dir", $this->imageID_dir);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":userTypeID", $this->userTypeID);
        $query->bindParam(":role", $this->role);
        $query->bindParam(":userID", $userID);

        return $query->execute();
    }

    public function approveRejectUser($userID, $newStatus)
    {
        $sql = "UPDATE users SET user_status = :newStatus WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":newStatus", $newStatus);
        $query->bindParam(":userID", $userID);
        return $query->execute();
    }

    public function deleteUser($userID)
    {
        $sql = "DELETE FROM users WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        return $query->execute();
    }

    public function updateUserStatus($userID, $newStatus)
    {
        $sql = "UPDATE users SET user_status = :newStatus WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":newStatus", $newStatus);
        $query->bindParam(":userID", $userID);

        return $query->execute();
    }
}
