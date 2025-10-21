<?php
require_once(__DIR__ . "/../../config/database.php");

class Register extends Database
{
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contact_no = "";
    public $id_number = "";
    public $college_department = "";
    public $imageID_name = "";
    public $imageID_dir = "";
    public $email = "";
    public $password = "";
    public $userTypeID = "";
    public $date_registered = "";
    public $role = "";

    protected $db;

    public function addUser()
    {
        // FIX 3: Corrected SQL query to match the number of columns and placeholders
        // and matched the placeholder names (imageID_name/dir) to the class properties.
        $sql = "INSERT INTO users (lName, fName, middleIn, id_number, college_department, imageID_name, imageID_dir, contact_no, email, password, role, userTypeID, date_registered) VALUES (:lName, :fName, :middleIn, :id_number, :college_department, :imageID_name, :imageID_dir, :contact_no, :email, :password, :role, :userTypeID, :date_registered)";

        $query = $this->connect()->prepare($sql);

        $query->bindParam(":lName", $this->lName);
        $query->bindParam(":fName", $this->fName);
        $query->bindParam(":middleIn", $this->middleIn);
        $query->bindParam(":id_number", $this->id_number);
        $query->bindParam(":college_department", $this->college_department);
        // FIX 4: Corrected binding for image properties
        $query->bindParam(":imageID_name", $this->imageID_name);
        $query->bindParam(":imageID_dir", $this->imageID_dir);
        $query->bindParam(":contact_no", $this->contact_no);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":password", $this->password);
        // FIX 5: Defined default role before binding
        $role = 'Borrower'; 
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