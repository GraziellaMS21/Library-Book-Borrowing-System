<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageNotifications.php");
require_once(__DIR__ . "/../../models/manageUsers.php");

$userObj = new User();
$notifObj = new Notification();

$userID = $_SESSION["user_id"];
$user = $userObj->fetchUser($userID);

// Determine active tab (All, Unread, Read)
$active_tab = $_GET['tab'] ?? 'all';

// Fetch notifications based on tab
$notifications = [];
if ($active_tab === 'unread') {
    $notifications = $notifObj->getUserNotifications($userID, 'unread');
} elseif ($active_tab === 'read') {
    $notifications = $notifObj->getUserNotifications($userID, 'read');
} else {
    $notifications = $notifObj->getUserNotifications($userID, 'all');
}

// FIXED: Helper to format time relative without modifying DateInterval object
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Map standard DateInterval properties to values
    $data = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => 0, // Initialize weeks
        'd' => $diff->d,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    ];

    // Calculate weeks from the days component
    if ($data['d'] >= 7) {
        $data['w'] = floor($data['d'] / 7);
        $data['d'] = $data['d'] % 7;
    }

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    foreach ($string as $k => &$v) {
        if ($data[$k]) {
            $v = $data[$k] . ' ' . $v . ($data[$k] > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - WMSU Library</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/borrower.css" />
    <link rel="stylesheet" href="../../../public/assets/css/header_footer.css" />
    <style>
        .notif-card:hover { transform: translateY(-2px); }
    </style>
</head>

<body class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php require_once(__DIR__ . '/../shared/headerBorrower.php'); ?>

        <header class="text-center my-10">
            <h1 class="title text-4xl sm:text-5xl font-extrabold text-white">Notifications</h1>
            <p class="text-xl mt-2 text-white">Stay updated with your library activities</p>
        </header>

        <div class="bg-white p-6 rounded-xl shadow-lg min-h-[500px]">
            
            <div class="flex flex-col md:flex-row justify-between items-center border-b border-gray-200 pb-4 mb-6">
                <nav class="flex space-x-2 mb-4 md:mb-0" aria-label="Tabs">
                    <a href="?tab=all" 
                       class="px-4 py-2 rounded-lg font-bold transition-colors <?= $active_tab == 'all' ? 'bg-red-800 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                       All
                    </a>
                    <a href="?tab=unread" 
                       class="px-4 py-2 rounded-lg font-bold transition-colors <?= $active_tab == 'unread' ? 'bg-red-800 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                       Unread
                    </a>
                    <a href="?tab=read" 
                       class="px-4 py-2 rounded-lg font-bold transition-colors <?= $active_tab == 'read' ? 'bg-red-800 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                       Read
                    </a>
                </nav>

                <?php if (!empty($notifications) && ($active_tab == 'all' || $active_tab == 'unread')): ?>
                    <a href="../../../app/controllers/notificationController.php?action=markAllRead&page=notifications.php" 
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                        <i class="fa-solid fa-check-double mr-2"></i> Mark all as read
                    </a>
                <?php endif; ?>
            </div>

            <div class="space-y-4">
                <?php if (empty($notifications)): ?>
                    <div class="py-20 text-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                        <i class="fa-regular fa-bell text-4xl text-gray-400 mb-3"></i>
                        <p class="text-lg text-gray-500 font-medium">No notifications found in this section.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notif-card flex flex-col sm:flex-row bg-white border <?= $notif['is_read'] == 0 ? 'border-l-4 border-l-red-600 border-gray-200 bg-red-50' : 'border-gray-200' ?> rounded-lg p-5 shadow-sm transition-all duration-200 relative group">
                            
                            <div class="flex-shrink-0 mr-4 mb-3 sm:mb-0 hidden sm:block">
                                <div class="h-10 w-10 rounded-full <?= $notif['is_read'] == 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500' ?> flex items-center justify-center">
                                    <i class="fa-solid <?= $notif['is_read'] == 0 ? 'fa-envelope' : 'fa-envelope-open' ?>"></i>
                                </div>
                            </div>

                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                                        <?= htmlspecialchars($notif['title']) ?>
                                        <?php if($notif['is_read'] == 0): ?>
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                New
                                            </span>
                                        <?php endif; ?>
                                    </h3>
                                    
                                    <span class="text-xs text-gray-400 whitespace-nowrap hidden sm:block">
                                        <?= time_elapsed_string($notif['created_at']) ?>
                                    </span>
                                </div>
                                
                                <p class="text-gray-600 text-sm leading-relaxed mb-3">
                                    <?= nl2br(htmlspecialchars($notif['message'])) ?>
                                </p>
                                
                                <p class="text-xs text-gray-400 mb-3 sm:hidden">
                                    <?= time_elapsed_string($notif['created_at']) ?>
                                </p>

                                <div class="flex items-center space-x-4 border-t border-gray-100 pt-3 mt-2">
                                    <?php if (!empty($notif['link'])): ?>
                                        <a href="<?= htmlspecialchars($notif['link']) ?>" class="text-sm font-semibold text-blue-600 hover:text-blue-800 transition">
                                            View Details
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($notif['is_read'] == 0): ?>
                                        <a href="../../../app/controllers/notificationController.php?action=markRead&id=<?= $notif['notifID'] ?>&page=notifications.php" 
                                           class="text-sm font-medium text-gray-500 hover:text-red-700 transition">
                                            Mark as Read
                                        </a>
                                    <?php endif; ?>

                                    <div class="flex-grow"></div>
                                    
                                    <a href="../../../app/controllers/notificationController.php?action=delete&id=<?= $notif['notifID'] ?>&page=notifications.php" 
                                       class="text-gray-400 hover:text-red-600 transition"
                                       title="Delete Notification"
                                       onclick="return confirm('Are you sure you want to delete this notification?');">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once(__DIR__ . '/../shared/footer.php'); ?>
</body>
</html>