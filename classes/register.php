<?php
require_once "database.php";

class Register extends Database {
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contactNo = "";
    public $email = "";
    public $password = "";
    
    protected $db;

    public function addUser(){
        $sql = "INSERT INTO users(lName, fName, middleIn, contactNo, email, password) VALUES (:lName, :fName, :middleIn, :contactNo, :email, :password)";

        $query = $this->connect()->prepare($sql);

        $query->bindParam("lName", $this->lName);
        $query->bindParam("fName", $this->fName);
        $query->bindParam("middleIn", $this->middleIn);
        $query->bindParam("contactNo", $this->contactNo);
        $query->bindParam("email", $this->email);
        $query->bindParam("password", $this->password);

        return $query->execute();
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
}