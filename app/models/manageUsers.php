<?php
require_once(__DIR__ . "/../../config/database.php");

class User extends Database
{
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contact_no = "";
    public $college_department = ""; // Combined property
    // REMOVED: public $college = "";
    // REMOVED: public $department = "";
    // REMOVED: public $position = "";
    public $imageID_name = ""; // Image filename
    public $imageID_dir = ""; // Image path/directory
    public $email = "";
    public $password = "";
    public $userTypeID = "";
    public $date_registered = "";
    public $role = "";

    protected $db;

    public function viewUser($search = "", $userType = "")
    {
        // SQL queries updated to select image fields
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
        $sql = "SELECT u.*, ut.type_name FROM users u JOIN user_type ut ON u.userTypeID = ut.userTypeID WHERE u.userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':userID', $userID);
        $query->execute();
        return $query->fetch();
    }

    // fetchUserTypes remains the same
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
        // UPDATED SQL: combine college/department, remove position, add image fields
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
        // REMOVED: $query->bindParam(":department", $this->department);
        // REMOVED: $query->bindParam(":position", $this->position);
        $query->bindParam(":imageID_name", $this->imageID_name);
        $query->bindParam(":imageID_dir", $this->imageID_dir);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":userTypeID", $this->userTypeID);
        $query->bindParam(":role", $this->role);
        $query->bindParam(":userID", $userID);

        return $query->execute();
    }

    // deleteUser remains the same
    public function deleteUser($userID)
    {
        $sql = "DELETE FROM users WHERE userID = :userID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        return $query->execute();
    }
}