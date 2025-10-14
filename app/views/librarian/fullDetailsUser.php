<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageUsers.php");

$userObj = new User();

if (isset($_GET['id'])) {
    $userID = $_GET['id'];
    $user = $userObj->fetchUser($userID);

    if (!$user) {
        echo "<p>No User found with that ID.</p>";
        exit;
    }
} else {
    echo "<p>No User ID provided.</p>";
    exit;
}
$userTypes = $userObj->fetchUserTypes();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/manage_book.css" />
</head>

<body class="w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="booksSection" id="bookSection">
                <div class="section manage_books h-full">
                    <div class="addBook rounded-xl p-4 bg-red-800 inline-block text-white my-2">
                        <button><a href="../../../app/views/librarian/usersSection.php"">Return</a></button>
                    </div>
                    <div class=" user-details">
                                <p><strong>Last Name:</strong> <?= htmlspecialchars($user['lName']) ?></p>
                                <p><strong>First Name:</strong> <?= htmlspecialchars($user['fName']) ?></p>
                                <p><strong>Middle Initial:</strong> <?= htmlspecialchars($user['middleIn']) ?></p>
                                <p><strong>College:</strong> <?= htmlspecialchars($user['college']) ?></p>
                                <p><strong>Department:</strong> <?= htmlspecialchars($user['department']) ?></p>
                                <p><strong>Position:</strong> <?= htmlspecialchars($user['position']) ?></p>
                                <p><strong>Contact No.:</strong> <?= htmlspecialchars($user['contact_no']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                <p><strong>User Type:</strong> <?= htmlspecialchars($user['type_name'] ?? 'N/A') ?></p>
                                <p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>
                                <p><strong>Date Registered:</strong> <?= htmlspecialchars($user['date_registered']) ?>
                                </p>
                    </div>


                    <div class="section manage_categories grid grid-cols-2 md:grid-cols-4 gap-4">

                    </div>
                </div>
            </div>
    </main>
    </div>


    <!-- <form action="../../controllers/logout.php" method="POST">

    
  <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
    Logout
  </button> -->
</body>
<script src="../../../public/assets/js/librarian/dashboard.js"></script>

</html>