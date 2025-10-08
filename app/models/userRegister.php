<?php
    require_once(__DIR__ . "/../config/database.php");

class Register extends Database {
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contactNo = "";
    public $email = "";
    public $password = "";
    public $borrowerTypeID = "";
    public $dateRegistered = "";
    
    protected $db;

    public function addUser(){
        $sql = "INSERT INTO users(borrowerTypeID, lName, fName, middleIn, contactNo, email, password,dateRegistered) VALUES (:borrowerTypeID, :lName, :fName, :middleIn, :contactNo, :email, :password, :dateRegistered)";

        $query = $this->connect()->prepare($sql);

        $query->bindParam(":borrowerTypeID", $this->borrowerTypeID);
        $query->bindParam(":lName", $this->lName);
        $query->bindParam(":fName", $this->fName);
        $query->bindParam(":middleIn", $this->middleIn);
        $query->bindParam(":contactNo", $this->contactNo);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":password", $this->password);
        $query->bindParam(":dateRegistered", $this->dateRegistered);

        return $query->execute();
    }

    public function fetchBorrowerType() {
        $sql = "SELECT * FROM borrowerType";
        $query = $this->connect()->prepare($sql);

        if($query->execute()){
            return $query->fetchAll();
        } else return null;
    }
    public function viewShelf() {
        $sql = "SELECT * FROM shelf";
            
        // $query = $this->db->connect()->prepare($sql);
        $query = $this->connect()->prepare($sql);
        
        if($query->execute()){
            return $query->fetchAll();
        }else{
            return null;
        }
    }

    public function isEmailExist($email) {
        $sql = "SELECT COUNT(userID) as total_users FROM users WHERE email = :email ";
        $query = $this->connect()->prepare($sql);
        $result = NULL;

        $query->bindParam(":email", $email);
        if($query->execute()){
            $record = $query->fetch();
        }

        if($record["total_users"] > 0){
            return true;
        } else return false;
    }
}

// $obj = new Register();
// $obj->fetchBorrowerType();