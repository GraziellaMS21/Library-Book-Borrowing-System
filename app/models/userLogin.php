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
                }
            }
        }
        return "Invalid Email or Password.";
    }

    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Save the 6-digit OTP
    public function saveResetToken($email, $token, $expiry)
    {
        $sql = "UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?";
        $query = $this->connect()->prepare($sql);
        return $query->execute([$token, $expiry, $email]);
    }

    // Verify if Email and OTP match and are valid
    public function verifyOtp($email, $otp)
    {
        $sql = "SELECT * FROM users WHERE email = ? AND reset_token = ?";
        $query = $this->connect()->prepare($sql);
        $query->execute([$email, $otp]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        if ($user && strtotime($user['reset_token_expires_at']) > time()) {
            return $user;
        }
        return false;
    }

    // Update password and clear OTP
    public function updateUserPassword($email, $plainPassword)
    {
        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE email = ?";
        $query = $this->connect()->prepare($sql);
        return $query->execute([$plainPassword, $email]);
    }
}
?>