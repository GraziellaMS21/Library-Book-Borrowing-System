<?php
require_once(__DIR__ . "/../../config/database.php");

class Notification extends Database
{
    // Public properties for the addNotification method
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

    public function getUnreadNotifications($userID)
    {
        $sql = "SELECT * FROM notifications WHERE userID = :userID AND is_read = 0 ORDER BY created_at DESC";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        return $query->fetchAll();
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
        // Table name 'notifications' is manually put here
        $sql = "UPDATE notifications SET is_read = 1 WHERE userID = :userID AND is_read = 0";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        return $query->execute();
    }

    public function markAsRead($notifID)
    {
        // Table name 'notifications' is manually put here
        $sql = "UPDATE notifications SET is_read = 1 WHERE notifID = :notifID";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":notifID", $notifID);
        return $query->execute();
    }

    public function fetchNotif($notifID)
    {
        // Table name 'notifications' is manually put here
        $sql = "SELECT * FROM notifications WHERE notifID = :notifID AND is_read = 0";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":notifID", $notifID);
        return $query->fetch();
    }
}
?>