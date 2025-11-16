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
    /**
     * Creates a new notification for a user.
     * Assumes properties (userID, title, message, link) have been set.
     */
    public function addNotification()
    {
        // Table name 'notifications' is manually put here
        $sql = "INSERT INTO notifications (userID, title, message, link) VALUES (:userID, :title, :message, :link)";
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":userID", $this->userID);
        $query->bindParam(":title", $this->title);
        $query->bindParam(":message", $this->message);
        $query->bindParam(":link", $this->link);

        return $query->execute();
    }

    /**
     * Fetches all unread notifications for a specific user.
     */
    public function getUnreadNotifications($userID)
    {
        // Table name 'notifications' is manually put here
        $sql = "SELECT * FROM notifications WHERE userID = :userID AND is_read = 0 ORDER BY created_at DESC";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        return $query->fetchAll();
    }

    /**
     * Fetches the count of unread notifications for a specific user.
     */
    public function getUnreadNotificationCount($userID)
    {
        // Table name 'notifications' is manually put here
        $sql = "SELECT COUNT(*) FROM notifications WHERE userID = :userID AND is_read = 0";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        $query->execute();
        return $query->fetchColumn();
    }

    /**
     * Marks all unread notifications for a user as read.
     */
    public function markAllAsRead($userID)
    {
        // Table name 'notifications' is manually put here
        $sql = "UPDATE notifications SET is_read = 1 WHERE userID = :userID AND is_read = 0";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":userID", $userID);
        return $query->execute();
    }
}
?>