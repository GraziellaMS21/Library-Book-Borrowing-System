<?php
require_once "database.php";

class Login extends Database {
    public $email = "";
    public $password = "";

    protected $db;

    public function logIn($email, $password) {
        $sql = "SELECT email, password FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":email", $email);

        $record = NULL;
        
        if($query->execute()){
            $record = $query->fetch();

            if($record){
                if($password === $record["password"]){
                    return true;
                }else {
                    return "Email/Password is Invalid";
                }
            } else return "Email/Password is Invalid";
        }
    }
}