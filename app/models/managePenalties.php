<?php
require_once(__DIR__ . "/../../config/database.php");

class Penalty extends Database
{
    public $borrowID = "";
    public $bookID = "";
    public $type = "";   
    public $cost = "";
    public $status = ""; 

    public function viewPenalties($search = "")
    {
        if ($search != "") {
            $sql = "SELECT * FROM penalties
                    WHERE borrowID LIKE CONCAT('%', :search, '%')
                       OR bookID LIKE CONCAT('%', :search, '%')
                       OR type LIKE CONCAT('%', :search, '%')
                    ORDER BY PenaltyID DESC";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":search", $search);
        } else {
            $sql = "SELECT * FROM penalties ORDER BY PenaltyID DESC";
            $query = $this->connect()->prepare($sql);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }
    public function fetchPenalty($penaltyID)
    {
        $sql = "SELECT * FROM penalties WHERE PenaltyID = :penaltyID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":penaltyID", $penaltyID);
        $query->execute();
        return $query->fetch();
    }
    public function addPenalty()
    {
        $sql = "INSERT INTO penalties (BorrowID, BookID, type, cost, status)
                VALUES (:borrowID, :bookID, :type, :cost, :status)";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":borrowID", $this->borrowID);
        $query->bindParam(":bookID", $this->bookID);
        $query->bindParam(":type", $this->type);
        $query->bindParam(":cost", $this->cost);
        $query->bindParam(":status", $this->status);

        return $query->execute();
    }

    public function editPenalty($penaltyID)
    {
        $sql = "UPDATE penalties
                SET BorrowID = :borrowID,
                    BookID = :bookID,
                    type = :type,
                    cost = :cost,
                    status = :status
                WHERE PenaltyID = :penaltyID";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":borrowID", $this->borrowID);
        $query->bindParam(":bookID", $this->bookID);
        $query->bindParam(":type", $this->type);
        $query->bindParam(":cost", $this->cost);
        $query->bindParam(":status", $this->status);
        $query->bindParam(":penaltyID", $penaltyID);

        return $query->execute();
    }
    public function deletePenalty($penaltyID)
    {
        $sql = "DELETE FROM penalties WHERE PenaltyID = :penaltyID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":penaltyID", $penaltyID);
        return $query->execute();
    }
}
?>
