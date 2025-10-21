<?php
require_once(__DIR__ . "/../../config/database.php");

class Category extends Database
{
    public $category_name;
    protected $db;

    public function addCategory()
    {
        $sql = "INSERT INTO category (category_name) VALUES (:category_name)";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":category_name", $this->category_name);
        return $query->execute();
    }

    public function viewCategory()
    {
        $sql = "SELECT * FROM category ORDER BY category_name ASC";
        $query = $this->connect()->prepare($sql);

        if ($query->execute()) {
            return $query->fetchAll();
        } else
            return null;
    }

    public function fetchCategory($categoryID)
    {
        $sql = "SELECT * FROM category WHERE categoryID = :categoryID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(':categoryID', $categoryID);
        $query->execute();
        return $query->fetch();
    }

    public function isCategoryExist($category_name, $categoryID = "")
    {
        if ($categoryID) {
            $sql = "SELECT COUNT(*) as total_categories FROM category WHERE category_name = :category_name AND categoryID <> :categoryID";
        } else {
            $sql = "SELECT COUNT(*) as total_categories FROM category WHERE category_name = :category_name";
        }

        $query = $this->connect()->prepare($sql);
        $record = NULL;
        $query->bindParam(":category_name", $category_name);

        if ($categoryID) {
            $query->bindParam(":categoryID", $categoryID);
        }

        if ($query->execute()) {
            $record = $query->fetch();
        }

        if ($record && $record["total_categories"] > 0) {
            return true;
        } else
            return false;
    }

    public function editCategory($cid)
    {
        $sql = "UPDATE category SET category_name = :category_name WHERE categoryID = :categoryID";

        $query = $this->connect()->prepare($sql);

        $query->bindParam(":category_name", $this->category_name);
        $query->bindParam(":categoryID", $cid);
        return $query->execute();
    }

    public function deleteCategory($cid)
    {
        $sql = "DELETE FROM category WHERE categoryID = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $cid);
        return $query->execute();
    }
}