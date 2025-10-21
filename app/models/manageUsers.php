<?php
require_once(__DIR__ . "/../../config/database.php");

class User extends Database
{
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contact_no = "";
    public $college_department = ""; // Combined property
    public $imageID_name = ""; // Image filename
    public $imageID_dir = ""; // Image path/directory
    public $email = "";
    public $password = "";
    public $userTypeID = "";
    public $date_registered = "";
    public $role = "";
    public $user_status = ""; // NEW PROPERTY for status management

    protected $db;

    public function viewUser($search = "", $userType = "", $statusFilter = "")
    {
        $whereConditions = [];

        // Base SQL
        $sql = "SELECT u.*, ut.type_name
                FROM users u
                JOIN user_type ut ON u.userTypeID = ut.userTypeID";

        // Handle Status Filter
        if ($statusFilter != "") {
            $dbStatus = ucfirst(strtolower($statusFilter));

            // MODIFIED LOGIC: Map tabs to database status values
            if ($statusFilter == 'blocked') {
                $dbStatus = 'Blocked'; // 'blocked' tab shows 'Blocked' accounts
            } elseif ($statusFilter == 'approved') {
                $dbStatus = 'Approved';
            } elseif ($statusFilter == 'rejected') {
                $dbStatus = 'Rejected';
            } else {
                $dbStatus = 'Pending';
            }

            $whereConditions[] = "u.user_status = :statusFilter";
        }

        // Build WHERE clause for search and userType
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

        // Bind parameters
        if ($search != "") {
            $query->bindParam(":search", $search);
        }
        if ($userType != "") {
            $query->bindParam(":userType", $userType);
        }
        if ($statusFilter != "") {
            // Re-map $dbStatus for binding
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

    public function fetchUser($userID)
    {
        // Aliasing u.user_status as 'status' for easier access in the view.
        $sql = "SELECT u.*, ut.type_name, u.user_status AS status FROM users u JOIN user_type ut ON u.userTypeID = ut.userTypeID WHERE u.userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':userID', $userID);
        $query->execute();
        return $query->fetch();
    }

    public function fetchUserTypes()
    {
        $sql = "SELECT * FROM user_type";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll();
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
        // $newStatus can be 'Approved', 'Rejected', or 'Blocked'
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
}