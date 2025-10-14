<?php
require_once(__DIR__ . "/../../config/database.php");

class Details extends Database
{
    public $userID = "";
    public $bookID = "";
    public $borrow_date = "";
    public $pickup_date = "";
    public $return_date = "";
    public $returned_condition = "";
    public $request = "";

    public $penaltyID = "";

    public $status = "";

    protected $db;

    public function viewDetails($search = "")
    {
        if ($search != "") {
            $sql = "SELECT * FROM borrowing_details 
                    WHERE userID LIKE CONCAT('%', :search, '%') 
                       OR bookID LIKE CONCAT('%', :search, '%')
                    ORDER BY borrow_date DESC";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":search", $search);
        } else {
            $sql = "SELECT * FROM borrowing_details ORDER BY borrow_date DESC";
            $query = $this->connect()->prepare($sql);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }


    public function fetchDetail($detailID)
    {
        $sql = "SELECT * FROM borrowing_details WHERE borrowID = :detailID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":detailID", $detailID);
        $query->execute();
        return $query->fetch();
    }

    public function editDetail($detailID)
{
    $sql = "UPDATE borrowing_details
            SET pickup_date = :pickup_date,
                return_date = :return_date,
                request = :request,
                returned_condition = :returned_condition,
                status = :status
            WHERE borrowID = :borrowID";

    $query = $this->connect()->prepare($sql);
    $query->bindParam(":pickup_date", $this->pickup_date);
    $query->bindParam(":return_date", $this->return_date);
    $query->bindParam(":request", $this->request);
    $query->bindParam(":returned_condition", $this->returned_condition);
    $query->bindParam(":status", $this->status);
    $query->bindParam(":borrowID", $detailID);

    return $query->execute();
}


    public function deleteDetail($detailID)
    {
        $sql = "DELETE FROM borrowing_details WHERE detailID = :detailID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":detailID", $detailID);
        return $query->execute();
    }

    public function addDetail()
    {
        $sql = "INSERT INTO borrowing_details 
            (userID, bookID, borrow_date, pickup_date, return_date, returned_condition, request, status)
            VALUES (:userID, :bookID, :borrow_date, :pickup_date, :return_date, :returned_condition, :request, :status)";


        $query = $this->connect()->prepare($sql);

        $query->bindParam(":userID", $this->userID);
        $query->bindParam(":bookID", $this->bookID);
        $query->bindParam(":borrow_date", $this->borrow_date);
        $query->bindParam(":pickup_date", $this->pickup_date);
        $query->bindParam(":return_date", $this->return_date);
        $query->bindParam(":returned_condition", $this->returned_condition);
        $query->bindParam(":request", $this->request);
        $query->bindParam(":status", $this->status);

        $success = $query->execute();

        if ($success) {
            $updateSql = "UPDATE books SET book_condition = :returned_condition WHERE bookID = :bookID";
            $updateQuery = $this->connect()->prepare($updateSql);
            $updateQuery->bindParam(":returned_condition", $this->returned_condition);
            $updateQuery->bindParam(":bookID", $this->bookID);
            $updateQuery->execute();
        }

        return $success;

    }

    

}
?>