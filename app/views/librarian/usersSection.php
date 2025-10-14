<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageUsers.php");
$userObj = new User();
$userTypes = $userObj->fetchUserTypes();

$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
unset($_SESSION["old"], $_SESSION["errors"]);
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$userTypeID = isset($_GET['userType']) ? trim($_GET['userType']) : "";

$users = $userObj->viewUser($search, $userTypeID);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/admin1.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="section manage_users h-full">
                <form method="GET">
                    <input type="text" name="search" placeholder="Search by name or email" class="border border-red-800 rounded-lg p-2 w-1/3">
                    <select name="userType" class=" border border-gray-400 mx-2 rounded-lg p-2">
                        <option value=""  >All Types</option>
                        <?php foreach ($userTypes as $type) {?>
                            <option value="<?= $type['userTypeID'] ?>"><?= $type['type_name'] ?></option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="bg-red-800 text-white rounded-lg px-4 py-2">Search</button>
                </form>


                        

                <div class="viewUsers">
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Email</th>
                            <th>Contact No.</th>
                            <th>User Type</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>

                        <?php
                        $no = 1;
                        foreach ($users as $user) {
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= ($user["lName"]) ?></td>
                                <td><?= ($user["fName"]) ?></td>
                                <td><?= ($user["email"]) ?></td>
                                <td><?= ($user["contact_no"]) ?></td>
                                <td><?= ($user["type_name"]) ?></td>
                                <td><?= ($user["role"]) ?></td>
                                <td class="action text-center">
                                    <a class="editBtn"
                                        href="../../../app/views/librarian/editUser.php?id=<?= $user['userID'] ?>">Edit</a>

                                    <a class="deleteBtn"
                                        href="../../../app/controllers/deleteUserController.php?id=<?= $user['userID'] ?>"
                                        onclick="return confirm('Are you sure you want to delete this user?');">
                                        Delete
                                    </a>

                                    <a class="viewBtn"
                                        href="../../../app/views/librarian/fullDetailsUser.php?id=<?= $user['userID'] ?>">
                                        View Full Details
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
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
<script src="../../../public/assets/js/librarian/admin.js"></script>

</html>