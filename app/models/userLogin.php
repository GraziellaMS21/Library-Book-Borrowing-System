<?php
require_once(__DIR__ . "/../../config/database.php");

class Login extends Database
{
    public $email = "";
    public $password = "";
    public $account_status = "Active";
    // We remove $registration_status = "Approved" from here to fetch all user statuses

    protected $db;
    public function logIn($email, $password)
    {
        // MODIFIED: Removed the registration_status = :registration_status check from the query.
        $sql = "SELECT * FROM users WHERE email = :email AND account_status = :account_status";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        $query->bindParam(":account_status", $this->account_status);
        // Removed the bindParam for registration_status

        if ($query->execute()) {
            $record = $query->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                // Check password regardless of user status, we handle status in controller
                if (password_verify($password, $record["password"])) {
                    return $record;
                } elseif ($password === $record["password"]) {
                    // Assuming this is for non-hashed legacy passwords
                    return $record;
                } else {
                    return "Password is invalid.";
                }
            } else {
                // Return generic error for security, implies email not found or account inactive
                return "Invalid Email or Password.";
            }
        } else {
            return "Database error.";
        }
    }

    // New method to fetch user status for the controller to use (optional, but good practice)
    public function getUserStatus($email)
    {
        $sql = "SELECT registration_status FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        if ($query->execute()) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result['registration_status'] ?? null;
        }
        return null;
    }
}
?>
