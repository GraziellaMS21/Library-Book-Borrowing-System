<?php
require_once(__DIR__ . "/../../config/database.php");

class Register extends Database
{
    public $lName = "";
    public $fName = "";
    public $middleIn = "";
    public $contact_no = "";
    public $id_number = "";
    public $departmentID = "";
    public $imageID_name = "";
    public $imageID_dir = "";
    public $email = "";
    public $password = "";
    public $userTypeID = ""; // Renaming this might break frontend forms if they use name="userTypeID", keeping property name but changing SQL binding OR updating frontend too. Let's update frontend too.
    public $borrowerTypeID = "";
    public $date_registered = "";
    public $role = "";
    public $registration_status = "Pending";

    // Added for Email Verification
    public $verification_code = "";
    public $verification_expiry = "";

    protected $db;

    public function addUser()
    {
        // CHANGED: Added verification_code, verification_expiry and set status to 'Unverified'
        $sql = "INSERT INTO users (lName, fName, middleIn, id_number, departmentID, imageID_name, imageID_dir, contact_no, email, password, borrowerTypeID, roleID, date_registered, registration_status, verification_code, verification_expiry) 
                VALUES (:lName, :fName, :middleIn, :id_number, :departmentID, :imageID_name, :imageID_dir, :contact_no, :email, :password, :borrowerTypeID, :roleID, :date_registered, :registration_status, :verification_code, :verification_expiry)";

        $query = $this->connect()->prepare($sql);

        $query->bindParam(":lName", $this->lName);
        $query->bindParam(":fName", $this->fName);
        $query->bindParam(":middleIn", $this->middleIn);
        $query->bindParam(":id_number", $this->id_number);
        $query->bindParam(":departmentID", $this->departmentID);
        $query->bindParam(":imageID_name", $this->imageID_name);
        $query->bindParam(":imageID_dir", $this->imageID_dir);
        $query->bindParam(":contact_no", $this->contact_no);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":password", $this->password);

        // $role = 'Borrower'; // Removed in 3NF
        // Set initial status to Unverified so they can't login yet
        $this->registration_status = "Unverified";

        // $query->bindParam(":role", $role); // Removed in 3NF
        $query->bindParam(":borrowerTypeID", $this->borrowerTypeID);
        $query->bindParam(":date_registered", $this->date_registered);
        $query->bindParam(":registration_status", $this->registration_status);

        // Bind Verification Params
        $query->bindParam(":verification_code", $this->verification_code);
        $query->bindParam(":verification_expiry", $this->verification_expiry);

        // Explicitly set roleID to 1 (Borrower) for new public registrations
        $roleID = 1;
        $query->bindParam(":roleID", $roleID);

        return $query->execute();
    }

    public function fetchUserType()
    {
        $sql = "SELECT * FROM borrower_types";
        $query = $this->connect()->prepare($sql);

        if ($query->execute()) {
            return $query->fetchAll();
        } else
            return null;
    }

    public function fetchDepartments()
    {
        $sql = "SELECT * FROM departments ORDER BY department_name ASC";
        $query = $this->connect()->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }

    public function isEmailExist($email)
    {
        $sql = "SELECT COUNT(userID) as total_users FROM users WHERE email = :email ";
        $query = $this->connect()->prepare($sql);
        $record = NULL;

        $query->bindParam(":email", $email);
        if ($query->execute()) {
            $record = $query->fetch();
        }

        if ($record["total_users"] > 0) {
            return true;
        } else
            return false;
    }

    // New Function: Verify OTP
    public function verifyEmailOtp($email, $otp)
    {
        $sql = "SELECT * FROM users WHERE email = :email AND verification_code = :otp AND verification_expiry > NOW()";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        $query->bindParam(":otp", $otp);
        $query->execute();

        return $query->fetch(); // Returns user data if match, false otherwise
    }

    // New Function: Mark Email as Verified (Set to Pending for Admin)
    public function markEmailVerified($email)
    {
        $sql = "UPDATE users SET registration_status = 'Pending', verification_code = NULL, verification_expiry = NULL WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        return $query->execute();
    }
}
?>