<?php
require_once(__DIR__ . "/../../config/database.php");

class Login extends Database
{
    public $email = "";
    public $password = "";
    protected $db;

    public function userLogIn($email, $password)
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);

        if ($query->execute()) {
            $record = $query->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                if (password_verify($password, $record["password"])) {
                    return $record;
                } elseif ($password === $record["password"]) {
                    return $record;
                } else {
                    return "Password is invalid.";
                }
            } else {
                return "Invalid Email or Password.";
            }
        } else {
            return "Database error.";
        }
    }
}
?>