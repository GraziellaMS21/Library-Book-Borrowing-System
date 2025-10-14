<?php
require_once(__DIR__ . "/../../config/database.php");

class User extends Database
{
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contact_no = "";
    public $college = "";
    public $department = "";
    public $position = "";
    public $email = "";
    public $password = "";
    public $userTypeID = "";
    public $date_registered = "";
    public $role = "";

    protected $db;

    public function viewUser($search = "", $userType = "")
    {
        if ($search != "" && $userType != "") {
            $sql = "SELECT u.*, ut.type_name
                    FROM users u
                    JOIN user_type ut ON u.userTypeID = ut.userTypeID
                    WHERE (u.fName LIKE CONCAT('%', :search, '%') 
                        OR u.lName LIKE CONCAT('%', :search, '%')
                        OR u.email LIKE CONCAT('%', :search, '%'))
                      AND ut.userTypeID = :userType
                    ORDER BY u.lName ASC";
        } else if ($search != "") {
            $sql = "SELECT u.*, ut.type_name
                    FROM users u
                    JOIN user_type ut ON u.userTypeID = ut.userTypeID
                    WHERE (u.fName LIKE CONCAT('%', :search, '%') 
                        OR u.lName LIKE CONCAT('%', :search, '%')
                        OR u.email LIKE CONCAT('%', :search, '%'))
                    ORDER BY u.lName ASC";
        } else if ($userType != "") {
            $sql = "SELECT u.*, ut.type_name
                    FROM users u
                    JOIN user_type ut ON u.userTypeID = ut.userTypeID
                    WHERE ut.userTypeID = :userType
                    ORDER BY u.lName ASC";
        } else {
            $sql = "SELECT u.*, ut.type_name
                    FROM users u
                    JOIN user_type ut ON u.userTypeID = ut.userTypeID
                    ORDER BY u.lName ASC";
        }

        $query = $this->connect()->prepare($sql);

        if ($search != "") {
            $query->bindParam(":search", $search);
        }
        if ($userType != "") {
            $query->bindParam(":userType", $userType);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function fetchUser($userID)
    {
        $sql = "SELECT u.*, ut.type_name 
                FROM users u
                JOIN user_type ut ON u.userTypeID = ut.userTypeID
                WHERE u.userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':userID', $userID);
        $query->execute();
        return $query->fetch();
    }
    public function fetchUserTypes()
    {
        $sql = "SELECT * FROM user_type";
        $query = $this->connect()->prepare($sql);
        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    public function isEmailExist($email, $userID)
    {
        $sql = "SELECT COUNT(userID) as total_users FROM users WHERE email = :email AND userID <> :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        $query->bindParam(":userID", $userID);
        $query->execute();
        $record = $query->fetch();

        return ($record["total_users"] > 0);
    }

    public function editUser($userID)
    {
        $sql = "UPDATE users 
                SET lName = :lName, fName = :fName, middleIn = :middleIn, 
                    contact_no = :contact_no, college = :college, department = :department,
                    position = :position, email = :email, userTypeID = :userTypeID,
                    role = :role
                WHERE userID = :userID";

        $query = $this->connect()->prepare($sql);
        $query->bindParam(":lName", $this->lName);
        $query->bindParam(":fName", $this->fName);
        $query->bindParam(":middleIn", $this->middleIn);
        $query->bindParam(":contact_no", $this->contact_no);
        $query->bindParam(":college", $this->college);
        $query->bindParam(":department", $this->department);
        $query->bindParam(":position", $this->position);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":userTypeID", $this->userTypeID);
        $query->bindParam(":role", $this->role);
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
