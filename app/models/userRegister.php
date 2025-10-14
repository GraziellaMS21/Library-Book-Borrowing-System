<?php
require_once(__DIR__ . "/../../config/database.php");

class Register extends Database
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

    public function addUser($position = '')
    {
        $role = (strpos(strtolower($position), 'librarian') !== false) ? 'Admin' : 'Borrower';


        $sql = "INSERT INTO users (
                    lName, fName, middleIn, college, department, position,
                    contact_no, email, password, role, userTypeID, date_registered
                ) VALUES (
                    :lName, :fName, :middleIn, :college, :department, :position,
                    :contact_no, :email, :password, :role, :userTypeID, :date_registered
                )";

        $query = $this->connect()->prepare($sql);

        $query->bindParam(":lName", $this->lName);
        $query->bindParam(":fName", $this->fName);
        $query->bindParam(":middleIn", $this->middleIn);
        $query->bindParam(":college", $this->college);
        $query->bindParam(":department", $this->department);
        $query->bindParam(":position", $this->position);
        $query->bindParam(":contact_no", $this->contact_no);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":password", $this->password);
        $query->bindParam(":role", $role);
        $query->bindParam(":userTypeID", $this->userTypeID);
        $query->bindParam(":date_registered", $this->date_registered);

        return $query->execute();
    }



    public function fetchUserType()
    {
        $sql = "SELECT * FROM user_type";
        $query = $this->connect()->prepare($sql);

        if ($query->execute()) {
            return $query->fetchAll();
        } else
            return null;
    }

    public function isEmailExist($email)
    {
        $sql = "SELECT COUNT(userID) as total_users FROM users WHERE email = :email ";
        $query = $this->connect()->prepare($sql);
        $result = NULL;

        $query->bindParam(":email", $email);
        if ($query->execute()) {
            $record = $query->fetch();
        }

        if ($record["total_users"] > 0) {
            return true;
        } else
            return false;
    }
}

// $obj = new Register();
// $obj->fetchBorrowerType();