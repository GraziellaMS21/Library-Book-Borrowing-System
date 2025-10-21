<?php
require_once(__DIR__ . "/../../config/database.php");

class Penalty extends Database
{
    public $borrowID = ""; // Only using borrowID for core DB interaction
    public $penalty_type = "";
    public $cost = "";
    public $penalty_status = "";

    public function viewPenalties($search = "")
    {
        // UPDATED: Joins penalty (p) -> borrowing_details (bd) -> users (u) and books (b)
        $sql = "SELECT 
                    p.*, 
                    u.fName, 
                    u.lName, 
                    b.book_title
                FROM penalty p
                JOIN borrowing_details bd ON p.borrowID = bd.borrowID
                JOIN users u ON bd.userID = u.userID
                JOIN books b ON bd.bookID = b.bookID";

        $where_clauses = [];
        $params = [];

        if ($search != "") {
            // Search criteria updated to check fields available in the joined result set
            $where_clauses[] = " (p.PenaltyID LIKE CONCAT('%', :search, '%') 
                                OR p.borrowID LIKE CONCAT('%', :search, '%')
                                OR p.penalty_type LIKE CONCAT('%', :search, '%')
                                OR u.fName LIKE CONCAT('%', :search, '%')
                                OR u.lName LIKE CONCAT('%', :search, '%')
                                OR b.book_title LIKE CONCAT('%', :search, '%'))";
            $params[":search"] = $search;
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        $sql .= " ORDER BY p.PenaltyID DESC";

        $query = $this->connect()->prepare($sql);

        foreach ($params as $key => $value) {
            $query->bindParam($key, $value);
        }

        if ($query->execute()) {
            return $query->fetchAll();
        } else {
            return null;
        }
    }

    // fetchPenalty remains simple, fetching raw penalty data
    public function fetchPenalty($penaltyID)
    {
        $sql = "SELECT * FROM penalty WHERE PenaltyID = :penaltyID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":penaltyID", $penaltyID);
        $query->execute();
        return $query->fetch();
    }

    public function addPenalty()
    {
        // UPDATED: Removed userID and bookID from INSERT query
        $sql = "INSERT INTO penalty (borrowID, penalty_type, cost, penalty_status)
                VALUES (:borrowID, :penalty_type, :cost, :penalty_status)";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":borrowID", $this->borrowID);
        $query->bindParam(":penalty_type", $this->penalty_type);
        $query->bindParam(":cost", $this->cost);
        $query->bindParam(":penalty_status", $this->penalty_status);

        return $query->execute();
    }

    public function editPenalty($penaltyID)
    {
        // UPDATED: Removed userID and bookID from UPDATE query
        $sql = "UPDATE penalty
                SET borrowID = :borrowID,
                    penalty_type = :penalty_type,
                    cost = :cost,
                    penalty_status = :penalty_status
                WHERE PenaltyID = :penaltyID";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":borrowID", $this->borrowID);
        $query->bindParam(":penalty_type", $this->penalty_type);
        $query->bindParam(":cost", $this->cost);
        $query->bindParam(":penalty_status", $this->penalty_status);
        $query->bindParam(":penaltyID", $penaltyID);

        return $query->execute();
    }
    public function deletePenalty($penaltyID)
    {
        $sql = "DELETE FROM penalty WHERE PenaltyID = :penaltyID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":penaltyID", $penaltyID);
        return $query->execute();
    }
}
?>