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
    public $registration_status = "";

    protected $db;

    public function viewUser($search = "", $userType = "", $statusFilter = "")
    {
        $whereConditions = [];
        $dbStatus = ucfirst($statusFilter);

        $sql = "SELECT u.*, ut.type_name
            FROM users u
            JOIN user_type ut ON u.userTypeID = ut.userTypeID";

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

    public function countTotalBorrowers()
    {
        $sql = "SELECT COUNT(userID) AS total_borrowers FROM users WHERE role = 'Borrower' AND registration_status = 'Approved'";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_borrowers'] ?? 0;
    }

    // NEW FUNCTION: Count pending user registrations
    public function countPendingUsers()
    {
        $sql = "SELECT COUNT(userID) AS total_pending FROM users WHERE registration_status = 'Pending' AND role = 'Borrower'";
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
        $sql = "SELECT fName, lName FROM users WHERE userID = :userID";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(':userID', $userID);
        $query->execute();
        $user = $query->fetch();

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

    public function updateUserStatus($userID, $newRegStatus, $newAccStatus)
    {
        if ($newRegStatus != "" && $newAccStatus != "") {
            $sql = "UPDATE users SET registration_status = :newRegStatus,  account_status = :newAccStatus WHERE userID = :userID";
        } else if ($newRegStatus != "") {
            $sql = "UPDATE users SET registration_status = :newRegStatus WHERE userID = :userID";
        } else {
            $sql = "UPDATE users SET account_status = :newAccStatus WHERE userID = :userID";
        }

        $query = $this->connect()->prepare($sql);

        if ($newRegStatus != "" && $newAccStatus != "") {
            $query->bindParam(":newRegStatus", $newRegStatus);
            $query->bindParam(":newAccStatus", $newAccStatus);
        } else if ($newRegStatus != "") {
            $query->bindParam(":newRegStatus", $newRegStatus);
        } else {
            $query->bindParam(":newAccStatus", $newAccStatus);
        }

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
    public function getTopBorrowerName()
    {
        $sql = "SELECT u.fName, u.lName
                FROM borrowing_details bd
                JOIN users u ON bd.userID = u.userID
                GROUP BY bd.userID, u.fName, u.lName
                ORDER BY COUNT(bd.userID) DESC
                LIMIT 1";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['fName'] . ' ' . $result['lName'] : 'N/A';
    }

}
