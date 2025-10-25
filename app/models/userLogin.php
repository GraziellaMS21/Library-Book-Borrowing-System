<?php
require_once(__DIR__ . "/../../config/database.php");

class Login extends Database
{
    public $email = "";
    public $password = "";
    public $account_status = "Active";
    public $user_status = "Approved";

    protected $db;
    public function logIn($email, $password)
    {
        $sql = "SELECT * FROM users WHERE email = :email AND account_status = :account_status AND user_status = :user_status";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        $query->bindParam(":account_status", $this->account_status);
        $query->bindParam(":user_status", $this->user_status);

        if ($query->execute()) {
            $record = $query->fetch();

            if ($record) {
                if (password_verify($password, $record["password"])) {
                    return $record;
                } elseif ($password === $record["password"]) {
                    return $record;
                } else {
                    return "Password is invalid.";
                }
            } else {
                return "Email not found or Account is Inactive.";
            }
        } else {
            return "Database error.";
        }
    }
}
?>