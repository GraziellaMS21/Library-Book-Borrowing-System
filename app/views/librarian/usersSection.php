<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageUsers.php");
$userObj = new User();
$userTypes = $userObj->fetchUserTypes();

// Retrieve temporary session data for PRG pattern
$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];

// Clear the session variables for old data and errors after retrieval
unset($_SESSION["old"], $_SESSION["errors"]);

// Get state from query parameters
$query_modal = $_GET['modal'] ?? '';
$query_id = (int) ($_GET['id'] ?? 0);

// Determine which modal to open
$open_modal = '';
$edit_user_id = null;
$view_user_id = null;
$delete_user_id = null;

if ($query_modal === 'edit' && $query_id) {
    $open_modal = 'editUserModal';
    $edit_user_id = $query_id;
} elseif ($query_modal === 'view' && $query_id) {
    $open_modal = 'viewDetailsUserModal';
    $view_user_id = $query_id;
} elseif ($query_modal === 'delete' && $query_id) {
    $open_modal = 'deleteConfirmUserModal';
    $delete_user_id = $query_id;
}


// Load User Data for Modals
$modal_user_data = []; // For Edit, View, Delete

if ($open_modal == 'editUserModal' && $edit_user_id) {
    if (empty($old)) {
        // Load fresh data for an initial edit click
        $modal_user_data = $userObj->fetchUser($edit_user_id);
    } else {
        // Use failed submission data if validation failed (data is in $old)
        $modal_user_data = $old;
        $modal_user_data['userID'] = $edit_user_id; // Preserve the user ID
    }
    // Set image data safely
    $modal_user_data['imageID_name'] = $modal_user_data['imageID_name'] ?? ($modal_user_data['existing_image_name'] ?? '');
    $modal_user_data['imageID_dir'] = $modal_user_data['imageID_dir'] ?? ($modal_user_data['existing_image_dir'] ?? '');

} elseif ($open_modal == 'viewDetailsUserModal' && $view_user_id) {
    $modal_user_data = $userObj->fetchUser($view_user_id);
} elseif ($open_modal == 'deleteConfirmUserModal' && $delete_user_id) {
    $modal_user_data = $userObj->fetchUser($delete_user_id);
    $modal_user_data['userID'] = $delete_user_id;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$userTypeID = isset($_GET['userType']) ? trim($_GET['userType']) : "";

$users = $userObj->viewUser($search, $userTypeID);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Manage Users</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/admin.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="section manage_users h-full">
                <div class="title flex w-full items-center justify-between mb-4">
                    <h1 class="text-red-800 font-bold text-4xl">MANAGE USERS</h1>
                </div>

                <form method="GET" class="mb-4">
                    <input type="text" name="search" placeholder="Search by name or email"
                        class="border border-red-800 rounded-lg p-2 w-1/3">
                    <select name="userType" class=" border border-gray-400 mx-2 rounded-lg p-2">
                        <option value="">All Types</option>
                        <?php foreach ($userTypes as $type) { ?>
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
                            <th>ID Image</th>
                            <th>Contact No.</th>
                            <th>User Type</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>

                        <?php
                        $no = 1;
                        foreach ($users as $user) {
                            // FIX: Initialize variables used in the loop's context
                            $image_url = !empty($user["imageID_dir"]) ? "../../../" . $user["imageID_dir"] : null;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $user["lName"] ?></td>
                                <td><?= $user["fName"] ?></td>
                                <td><?= $user["email"] ?></td>
                                <td class="text-center">
                                    <?php
                                    // SIMPLIFIED DISPLAY: Rely on the URL path. 
                                    // The browser will show the icon if the image file is missing.
                                    if ($image_url) { ?>
                                        <img src="<?= $image_url ?>" alt="ID"
                                            class="w-16 h-16 object-cover rounded mx-auto border border-gray-300"
                                            title="<?= $user["imageID_name"] ?>">
                                    <?php } else { ?>
                                        <span class="text-gray-500 text-xs">N/A</span>
                                    <?php } ?>
                                </td>
                                <td><?= $user["contact_no"] ?></td>
                                <td><?= $user["type_name"] ?></td>
                                <td><?= $user["role"] ?></td>
                                <td class="action">
                                    <a class="editBtn px-2 py-1 rounded text-white bg-blue-600 hover:bg-blue-700"
                                        href="usersSection.php?modal=edit&id=<?= $user['userID'] ?>">Edit</a>

                                    <a class="deleteBtn px-2 py-1 rounded text-white bg-red-600 hover:bg-red-700"
                                        href="usersSection.php?modal=delete&id=<?= $user['userID'] ?>">
                                        Delete
                                    </a>

                                    <a class="viewBtn px-2 py-1 rounded text-white bg-gray-600 hover:bg-gray-700"
                                        href="usersSection.php?modal=view&id=<?= $user['userID'] ?>">
                                        View
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

    <div id="editUserModal" class="modal <?= $open_modal == 'editUserModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="editUserModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Edit User</h2>
            <form id="editUserForm"
                action="../../../app/controllers/userController.php?action=edit&id=<?= $edit_user_id ?>" method="POST"
                enctype="multipart/form-data">
                <input type="hidden" name="userID" value="<?= $edit_user_id ?>">
                <input type="hidden" name="existing_image_name" value="<?= $modal_user_data["imageID_name"] ?? "" ?>">
                <input type="hidden" name="existing_image_dir" value="<?= $modal_user_data["imageID_dir"] ?? "" ?>">


                <div class="grid grid-cols-2 gap-4">
                    <div class="input">
                        <label for="lName">Last Name<span>*</span> : </label>
                        <input type="text" class="input-field" name="lName" id="edit_lName"
                            value="<?= $modal_user_data["lName"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["lName"] ?? "" ?></p>
                    </div>
                    <div class="input">
                        <label for="fName">First Name<span>*</span> : </label>
                        <input type="text" class="input-field" name="fName" id="edit_fName"
                            value="<?= $modal_user_data["fName"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["fName"] ?? "" ?></p>
                    </div>
                    <div class="input col-span-2">
                        <label for="middleIn">Middle Initial : </label>
                        <input type="text" class="input-field" name="middleIn" id="edit_middleIn"
                            value="<?= $modal_user_data["middleIn"] ?? "" ?>">
                    </div>

                    <div class="input">
                        <label for="contact_no">Contact No. : </label>
                        <input type="text" class="input-field" name="contact_no" id="edit_contact_no"
                            value="<?= $modal_user_data["contact_no"] ?? "" ?>">
                    </div>
                    <div class="input">
                        <label for="email">Email<span>*</span> : </label>
                        <input type="email" class="input-field" name="email" id="edit_email"
                            value="<?= $modal_user_data["email"] ?? "" ?>">
                        <p class="errors text-red-500 text-sm"><?= $errors["email"] ?? "" ?></p>
                    </div>

                    <div class="input col-span-2">
                        <label for="college_department">College/Department : </label>
                        <input type="text" class="input-field" name="college_department" id="edit_college_department"
                            value="<?= $modal_user_data["college_department"] ?? ($modal_user_data['college'] ?? '') . ' ' . ($modal_user_data['department'] ?? '') ?>">
                    </div>

                    <div class="input">
                        <label for="userTypeID">User Type<span>*</span> : </label>
                        <select name="userTypeID" id="edit_userTypeID" class="input-field">
                            <option value="">---Select Type---</option>
                            <?php foreach ($userTypes as $type) {
                                $selected = (($modal_user_data['userTypeID'] ?? '') == $type['userTypeID']) ? 'selected' : '';
                                ?>
                                <option value="<?= $type['userTypeID'] ?>" <?= $selected ?>>
                                    <?= $type['type_name'] ?>
                                </option>
                            <?php } ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["userTypeID"] ?? "" ?></p>
                    </div>
                    <div class="input">
                        <label for="role">Role<span>*</span> : </label>
                        <select name="role" id="edit_role" class="input-field">
                            <option value="">---Select Role---</option>
                            <?php
                            $roles = ["Librarian", "Borrower", "Admin"];
                            foreach ($roles as $role) {
                                $selected = (($modal_user_data['role'] ?? '') == $role) ? 'selected' : '';
                                echo "<option value='{$role}' {$selected}>{$role}</option>";
                            }
                            ?>
                        </select>
                        <p class="errors text-red-500 text-sm"><?= $errors["role"] ?? "" ?></p>
                    </div>

                    <div class="input col-span-2">
                        <label for="new_imageID">Upload New ID Image (Optional): </label>
                        <input type="file" class="input-field" name="new_imageID" id="edit_new_imageID"
                            accept="image/*">
                        <p class="text-xs text-gray-500 mt-1">Current File:
                            <?= $modal_user_data["imageID_name"] ?? "None" ?>
                        </p>
                        <p class="errors text-red-500 text-sm"><?= $errors["new_imageID"] ?? "" ?></p>
                    </div>
                </div>

                <p class="errors text-red-500 text-sm col-span-2 mt-2"><?= $errors["db_error"] ?? "" ?></p>

                <br>
                <input type="submit" value="Save Changes"
                    class="font-bold cursor-pointer mt-4 border-none rounded-lg bg-red-800 text-white p-2 w-full hover:bg-red-700">
            </form>
        </div>
    </div>


    <div id="viewDetailsUserModal" class="modal <?= $open_modal == 'viewDetailsUserModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="viewDetailsUserModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4">User Details</h2>
            <div class="user-details grid grid-cols-2 gap-y-2 gap-x-4 text-base">

                <div class="col-span-2 mb-4">
                    <p class="font-semibold mb-2">ID Image:</p>
                    <?php
                    $modal_image_url = !empty($modal_user_data['imageID_dir']) ? "../../../" . $modal_user_data['imageID_dir'] : null;

                    if ($modal_image_url) { ?>
                        <img src="<?= $modal_image_url ?>" alt="User ID Image"
                            class="max-w-xs max-h-40 border rounded shadow-md">
                    <?php } else { ?>
                        <p class="text-gray-500">No ID Image Uploaded</p>
                    <?php } ?>
                </div>

                <p class="col-span-2">Name:
                    <?= ($modal_user_data['fName'] ?? 'N/A') . ' ' . ($modal_user_data['middleIn'] ?? '') . ' ' . ($modal_user_data['lName'] ?? '') ?>
                </p>

                <p>Email: <?= $modal_user_data['email'] ?? 'N/A' ?></p>
                <p>Contact No.: <?= $modal_user_data['contact_no'] ?? 'N/A' ?></p>

                <p>User Type: <?= $modal_user_data['type_name'] ?? 'N/A' ?></p>
                <p>Role: <?= $modal_user_data['role'] ?? 'N/A' ?></p>

                <p class="col-span-2">College/Department: <?= $modal_user_data['college_department'] ?? 'N/A' ?></p>
                <p class="col-span-2">Date Registered:
                    <?= $modal_user_data['date_registered'] ?? 'N/A' ?>
                </p>

            </div>
            <div class="mt-6 text-right">
                <button type="button"
                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    onclick="document.getElementById('viewDetailsUserModal').style.display='none';">Close</button>
            </div>
        </div>
    </div>


    <div id="deleteConfirmUserModal"
        class="modal delete-modal <?= $open_modal == 'deleteConfirmUserModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close" data-modal="deleteConfirmUserModal">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to delete the user:
                <span
                    class="font-semibold italic"><?= ($modal_user_data['fName'] ?? '') . ' ' . ($modal_user_data['lName'] ?? 'this user') ?></span>?
                This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    onclick="document.getElementById('deleteConfirmUserModal').style.display='none';">
                    Cancel
                </button>
                <a href="../../../app/controllers/userController.php?action=delete&id=<?= $modal_user_data['userID'] ?? $delete_user_id ?>"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-700 cursor-pointer">
                    Confirm Delete
                </a>
            </div>
        </div>
    </div>


</body>
<script src="../../../public/assets/js/librarian/admin.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const closeBtns = document.querySelectorAll('.close');

        // Function to open a modal
        const openModal = (modal) => {
            modal.style.display = 'flex';
            modal.classList.add('open');
        };

        // Function to close a modal
        const closeModal = (modal) => {
            modal.style.display = 'none';
            modal.classList.remove('open');
        };

        // Close Modal using Buttons
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-modal');
                closeModal(document.getElementById(modalId));
            });
        });

        // Close Modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
            }
        });

        // If the page was loaded with a modal parameter (from controller failure or direct link), open it
        const currentModal = document.querySelector('.modal.open');
        if (currentModal) {
            openModal(currentModal);
        }
    });
</script>

</html>