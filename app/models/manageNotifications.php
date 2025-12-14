<?php
require_once(__DIR__ . "/../../config/database.php");

class Notification extends Database
{
    public $userID;
    public $title;
    public $message;
    public $link;

    protected $db;

    public function addNotification()
    {
        $sql = "INSERT INTO notifications (userID, title, message, link) VALUES (:userID, :title, :message, :link)";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":userID", $this->userID);
        $query->bindParam(":title", $this->title);
        $query->bindParam(":message", $this->message);
        $query->bindParam(":link", $this->link);

        return $query->execute();
    }

    // Fetches all notifications for a user, optionally filtered by status
    public function getUserNotifications($userID, $filter = 'all')
    {
        $sql = "SELECT * FROM notifications WHERE userID = :userID";
        
        if ($filter === 'unread') {
            $sql .= " AND is_read = 0";
        } elseif ($filter === 'read') {
            $sql .= " AND is_read = 1";
        }

        $sql .= " ORDER BY created_at DESC";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        return $query->fetchAll();
    }

    public function getUnreadNotifications($userID)
    {
        // Reusing the general method
        return $this->getUserNotifications($userID, 'unread');
    }

    public function getUnreadNotificationCount($userID)
    {
        $sql = "SELECT COUNT(*) FROM notifications WHERE userID = :userID AND is_read = 0";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        return $query->fetchColumn();
    }

    public function markAllAsRead($userID)
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE userID = :userID AND is_read = 0";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        return $query->execute();
    }

    public function markAsRead($notifID)
    {
        $sql = "UPDATE notifications SET is_read = 1 WHERE notifID = :notifID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":notifID", $notifID);
        return $query->execute();
    }

    // New method to delete a notification
    public function deleteNotification($notifID)
    {
        $sql = "DELETE FROM notifications WHERE notifID = :notifID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":notifID", $notifID);
        return $query->execute();
    }

    public function fetchNotif($notifID)
    {
        $sql = "SELECT * FROM notifications WHERE notifID = :notifID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":notifID", $notifID);
        return $query->fetch();
    }
}
?>