<?php
require_once(__DIR__ . "/../../config/database.php");

class Login extends Database
{
    public function logIn($email, $password)
    {
        $sql = "SELECT userID, lName, fName, email, password, userTypeID FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);

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
                return "Email not found.";
            }
        } else {
            return "Database error.";
        }
    }
}
?>