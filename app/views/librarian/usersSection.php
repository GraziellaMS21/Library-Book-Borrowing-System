<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageUsers.php");
$userObj = new User();
$userTypes = $userObj->fetchUserTypes();

$departments = $userObj->fetchDepartments(); 

$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
$success_modal = $_GET['success'] ?? false;
$success_message = "";
if ($success_modal) {
    if ($success_modal === 'delete') $success_message = "User deleted successfully.";
    elseif ($success_modal === 'block') $success_message = "User blocked successfully.";
    elseif ($success_modal === 'reject') $success_message = "Registration rejected successfully.";
    elseif ($success_modal === 'approve') $success_message = "User approved successfully.";
    elseif ($success_modal === 'unblock') $success_message = "User unblocked successfully.";
    elseif ($success_modal === 'edit') $success_message = "User details updated successfully.";
}

unset($_SESSION["old"], $_SESSION["errors"]);

$current_modal = $_GET['modal'] ?? '';
$user_id = (int) ($_GET['id'] ?? 0);
$open_modal = '';

$current_tab = $_GET['tab'] ?? 'pending';

$modal_user = [];
if ($current_modal === 'edit') {
    $open_modal = 'editUserModal';
} elseif ($current_modal === 'view') {
    $open_modal = 'viewDetailsUserModal';
} elseif ($current_modal === 'delete') {
    $open_modal = 'deleteConfirmModal';
}

if (!empty($open_modal)) {
    if ($open_modal == 'editUserModal' && !empty($old)) {
        $modal_user = $old;
    } else { 
        $modal_user = $userObj->fetchUser($user_id) ?: [];
    }
    if ($open_modal != 'viewDetailsUserModal') { 
        $modal_user['userID'] = $user_id;
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$userTypeID = isset($_GET['userType']) ? trim($_GET['userType']) : "";

$users = $userObj->viewUser($search, $userTypeID, $current_tab);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Manage Users</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/admin.css" />
    <style>
        .modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal.open { display: block; }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; border-radius: 8px; }
    </style>
</head>

<body>
    <div class="w-full h-screen flex flex-col">
        <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

        <main class="overflow-y-auto">
                <div class="section manage_users">
                    <div class="title flex w-full items-center mb-4">
                        <h1 class="text-red-800 font-bold text-4xl">MANAGE USERS</h1>
                    </div>

                    <div class="tabs flex border-b border-gray-200 mb-6">
                        <a href="?tab=pending" class="tab-btn <?= $current_tab == 'pending' ? 'active' : '' ?>">Pending Registers</a>
                        <a href="?tab=approved" class="tab-btn <?= $current_tab == 'approved' ? 'active' : '' ?>">Approved Users</a>
                        <a href="?tab=rejected" class="tab-btn <?= $current_tab == 'rejected' ? 'active' : '' ?>">Rejected Users</a>
                        <a href="?tab=blocked" class="tab-btn <?= $current_tab == 'blocked' ? 'active' : '' ?>">Blocked Accounts</a>
                    </div>

                    <form method="GET" class="search flex gap-2 items-center mb-6">
                        <input type="hidden" name="tab" value="<?= $current_tab ?>">
                        <input type="text" name="search" placeholder="Search by name or email" value="<?= $search ?>" class="border rounded-lg p-2 flex-grow">
                        <select name="userType" class="border rounded-lg p-2">
                            <option value="">All Types</option>
                            <?php foreach ($userTypes as $type) { ?>
                                <option value="<?= $type['userTypeID'] ?>" <?= $userTypeID == $type['userTypeID'] ? 'selected' : '' ?>>
                                    <?= $type['type_name'] ?>
                                </option>
                            <?php } ?>
                        </select>
                        <button type="submit" class="bg-red-800 text-white rounded-lg px-4 py-2 hover:bg-red-700">Search</button>
                    </form>

                    <h2 class="text-2xl font-semibold mb-4 text-gray-700">
                        <?php
                        switch ($current_tab) {
                            case 'pending': echo 'Pending User Registrations'; break;
                            case 'approved': echo 'Approved System Users'; break;
                            case 'rejected': echo 'Rejected User Registrations'; break;
                            case 'blocked': echo 'Blocked Accounts'; break;
                        }
                        ?>
                    </h2>

                    <div class="view">
                        <table>
                            <tr>
                                <th>No</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Email</th>
                                <th>ID Image</th>
                                <th>User Type</th>
                                <th>Department</th> <th>Date Reg.</th>
                                <th>Actions</th>
                            </tr>

                            <?php
                            $no = 1;
                            $colspan = 9;

                            if (empty($users)): ?>
                                <tr>
                                    <td colspan="<?= $colspan ?>" class="text-center py-4 text-gray-500">
                                        No <?= strtolower(str_replace('d', 'd ', $current_tab)) ?> users found.
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($users as $user) {
                                    $image_url = !empty($user["imageID_dir"]) ? "../../../" . $user["imageID_dir"] : null;
                                    $fullName = htmlspecialchars($user["fName"] . ' ' . $user["lName"]);

                                    $myRole = $_SESSION['role'] ?? 'Borrower';
                                    $targetRole = $user['role'] ?? 'Borrower';
                                    
                                    $canEdit = false;
                                    $canDelete = false;

                                    if ($myRole === 'Super Admin') {
                                        $canEdit = true;
                                        $canDelete = true;
                                    } elseif ($myRole === 'Admin') {
                                        if ($targetRole === 'Borrower') {
                                            $canEdit = true;
                                            $canDelete = true;
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= $user["lName"] ?></td>
                                        <td><?= $user["fName"] ?></td>
                                        <td><?= $user["email"] ?></td>
                                        <td class="text-center">
                                            <?php if ($image_url) { ?>
                                                <img src="<?= $image_url ?>" alt="ID" class="w-16 h-16 object-cover rounded mx-auto border border-gray-300" title="<?= $user["imageID_name"] ?>">
                                            <?php } else { ?>
                                                <span class="text-gray-500 text-xs">N/A</span>
                                            <?php } ?>
                                        </td>
                                        <td><?= $user["type_name"] ?></td>
                                        <td><?= htmlspecialchars($user['department_name'] ?? 'N/A') ?></td>
                                        <td><?= $user['date_registered'] ?? 'N/A' ?></td>
                                        <td class="action text-center">

                                            <?php if ($current_tab == 'pending'): ?>
                                                <a href="../../../app/controllers/userController.php?tab=<?= $current_tab ?>&action=approve&id=<?= $user['userID'] ?>" class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1">Approve</a>
                                                
                                                <button class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1 cursor-pointer open-modal-btn"
                                                        data-target="rejectUserModal"
                                                        data-id="<?= $user['userID'] ?>"
                                                        data-name="<?= $fullName ?>">
                                                    Reject
                                                </button>

                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="usersSection.php?tab=<?= $current_tab ?>&modal=view&id=<?= $user['userID'] ?>">View</a>

                                            <?php elseif ($current_tab == 'approved'): ?>
                                                
                                                <?php if ($canEdit): ?>
                                                    <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1" href="usersSection.php?tab=<?= $current_tab ?>&modal=edit&id=<?= $user['userID'] ?>">Edit</a>
                                                <?php endif; ?>

                                                <?php if ($canEdit): ?>
                                                    <button class="actionBtn bg-amber-500 hover:bg-amber-600 text-sm inline-block mb-1 cursor-pointer open-modal-btn"
                                                            data-target="blockUserModal"
                                                            data-id="<?= $user['userID'] ?>"
                                                            data-name="<?= $fullName ?>">
                                                        Block
                                                    </button>
                                                <?php endif; ?>

                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="usersSection.php?tab=<?= $current_tab ?>&modal=view&id=<?= $user['userID'] ?>">View</a>
                                                
                                                <?php if ($canDelete): ?>
                                                    <a class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1" href="usersSection.php?tab=<?= $current_tab ?>&modal=delete&id=<?= $user['userID'] ?>">Delete</a>
                                                <?php endif; ?>

                                            <?php elseif ($current_tab == 'blocked'): ?>
                                                
                                                <?php if ($canEdit): ?>
                                                    <button class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1 cursor-pointer open-modal-btn"
                                                            data-target="unblockUserModal"
                                                            data-id="<?= $user['userID'] ?>"
                                                            data-name="<?= $fullName ?>">
                                                        Unblock
                                                    </button>
                                                <?php endif; ?>

                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="usersSection.php?tab=<?= $current_tab ?>&modal=view&id=<?= $user['userID'] ?>">View</a>
                                                
                                                <?php if ($canDelete): ?>
                                                    <a class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1" href="usersSection.php?tab=<?= $current_tab ?>&modal=delete&id=<?= $user['userID'] ?>">Delete</a>
                                                <?php endif; ?>

                                            <?php else: ?>
                                                <a href="../../../app/controllers/userController.php?tab=<?= $current_tab ?>&action=approve&id=<?= $user['userID'] ?>" class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1">Approve</a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1" href="usersSection.php?tab=<?= $current_tab ?>&modal=view&id=<?= $user['userID'] ?>">View</a>
                                                
                                                <?php if ($canDelete): ?>
                                                    <a class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1" href="usersSection.php?tab=<?= $current_tab ?>&modal=delete&id=<?= $user['userID'] ?>">Delete</a>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                    <?php
                                }
                            endif;
                            ?>
                        </table>
                    </div>
                </div>
        </main>
    </div>

    <div id="rejectUserModal" class="modal">
        <div class="modal-content max-w-md">
            <span class="close close-modal text-3xl cursor-pointer float-right">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-red-700">Reject Registration</h2>
            <form action="../../../app/controllers/userController.php" method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="tab" value="<?= $current_tab ?>">
                <input type="hidden" name="userID" id="reject_userID">

                <p class="mb-4 text-gray-700">
                    Reason for rejecting <span class="font-bold user-name-span"></span>:
                </p>

                <div class="bg-gray-100 p-4 rounded mb-4 text-sm">
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Invalid ID Image / Unclear" class="mr-2"> Invalid / Unclear ID
                    </label>
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Incomplete Information" class="mr-2"> Incomplete Information
                    </label>
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Not a verified student" class="mr-2"> Not a verified student/faculty
                    </label>
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Duplicate Account" class="mr-2"> Duplicate Account
                    </label>
                </div>

                <label class="font-semibold block mb-1">Other Reason:</label>
                <textarea name="reason_custom" rows="3" class="w-full border rounded p-2" placeholder="Type specific details..."></textarea>

                <input type="submit" value="Confirm Reject" class="mt-4 bg-red-700 text-white font-bold py-2 px-4 rounded w-full cursor-pointer hover:bg-red-800">
            </form>
        </div>
    </div>

    <div id="blockUserModal" class="modal">
        <div class="modal-content max-w-md">
            <span class="close close-modal text-3xl cursor-pointer float-right">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-yellow-700">Block User Account</h2>
            <form action="../../../app/controllers/userController.php" method="POST">
                <input type="hidden" name="action" value="block">
                <input type="hidden" name="tab" value="<?= $current_tab ?>">
                <input type="hidden" name="userID" id="block_userID">

                <p class="mb-4 text-gray-700">
                    Reason for blocking <span class="font-bold user-name-span"></span>:
                </p>

                <div class="bg-gray-100 p-4 rounded mb-4 text-sm">
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Unpaid Overdue Fines" class="mr-2"> Unpaid Overdue Fines
                    </label>
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Lost Books" class="mr-2"> Lost Books
                    </label>
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Library Policy Violation" class="mr-2"> Policy Violation (Noise/Conduct)
                    </label>
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Identity Theft Suspicion" class="mr-2"> Identity Theft Suspicion
                    </label>
                </div>

                <label class="font-semibold block mb-1">Additional Details:</label>
                <textarea name="reason_custom" rows="3" class="w-full border rounded p-2" placeholder="Type specific details..."></textarea>

                <input type="submit" value="Confirm Block" class="mt-4 bg-yellow-600 text-white font-bold py-2 px-4 rounded w-full cursor-pointer hover:bg-yellow-700">
            </form>
        </div>
    </div>

    <div id="unblockUserModal" class="modal">
        <div class="modal-content max-w-md">
            <span class="close close-modal text-3xl cursor-pointer float-right">&times;</span>
            <h2 class="text-2xl font-bold mb-4 text-green-700">Unblock User Account</h2>
            <form action="../../../app/controllers/userController.php" method="POST">
                <input type="hidden" name="action" value="unblock">
                <input type="hidden" name="tab" value="<?= $current_tab ?>">
                <input type="hidden" name="userID" id="unblock_userID">

                <p class="mb-4 text-gray-700">
                    Reason for unblocking <span class="font-bold user-name-span"></span>:
                </p>

                <div class="bg-gray-100 p-4 rounded mb-4 text-sm">
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Appeal Granted" class="mr-2"> Appeal Granted
                    </label>
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Fines Paid" class="mr-2"> Fines Paid
                    </label>
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Books Returned" class="mr-2"> Books Returned
                    </label>
                    <label class="flex items-center mb-2 cursor-pointer">
                        <input type="checkbox" name="reason_presets[]" value="Identity Verified" class="mr-2"> Identity Verified
                    </label>
                </div>

                <label class="font-semibold block mb-1">Additional Details:</label>
                <textarea name="reason_custom" rows="3" class="w-full border rounded p-2" placeholder="Type specific details..."></textarea>

                <input type="submit" value="Confirm Unblock" class="mt-4 bg-green-600 text-white font-bold py-2 px-4 rounded w-full cursor-pointer hover:bg-green-700">
            </form>
        </div>
    </div>

    <div id="editUserModal" class="modal <?= $open_modal == 'editUserModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close close-times" data-modal="editUserModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Edit User</h2>
            <form id="editUserForm" action="../../../app/controllers/userController.php?tab=<?= $current_tab ?>&action=edit&id=<?= $modal_user['userID'] ?? $user_id ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="userID" value="<?= $modal_user['userID'] ?? $user_id ?>">
                <input type="hidden" name="existing_image_name" value="<?= $modal_user["imageID_name"] ?? "" ?>">
                <input type="hidden" name="existing_image_dir" value="<?= $modal_user["imageID_dir"] ?? "" ?>">

                <div class="input">
                    <label for="lName">Last Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="lName" id="edit_lName" value="<?= $modal_user["lName"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["lName"] ?? "" ?></p>
                </div>
                <div class="input">
                    <label for="fName">First Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="fName" id="edit_fName" value="<?= $modal_user["fName"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["fName"] ?? "" ?></p>
                </div>
                <div class="input col-span-2">
                    <label for="middleIn">Middle Initial : </label>
                    <input type="text" class="input-field" name="middleIn" id="edit_middleIn" value="<?= $modal_user["middleIn"] ?? "" ?>">
                </div>

                <div class="input col-span-2">
                    <label for="new_imageID">Upload New ID Image (Optional): </label>
                    <input type="file" class="input-field" name="new_imageID" id="edit_new_imageID" accept="image/*">
                    <p class="text-xs text-gray-500 mt-1">Current File: <?= $modal_user["imageID_name"] ?? "None" ?></p>
                    <p class="errors text-red-500 text-sm"><?= $errors["new_imageID"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="contact_no">Contact No. : </label>
                    <input type="text" class="input-field" name="contact_no" id="edit_contact_no" value="<?= $modal_user["contact_no"] ?? "" ?>">
                </div>
                <div class="input">
                    <label for="email">Email<span>*</span> : </label>
                    <input type="email" class="input-field" name="email" id="edit_email" value="<?= $modal_user["email"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["email"] ?? "" ?></p>
                </div>

                <div class="input col-span-2">
                    <label for="departmentID">College/Department : </label>
                    <select name="departmentID" id="edit_departmentID" class="input-field">
                        <option value="">---Select Department---</option>
                        <?php foreach ($departments as $dept) {
                            $selected = (($modal_user['departmentID'] ?? '') == $dept['departmentID']) ? 'selected' : '';
                            echo "<option value='{$dept['departmentID']}' {$selected}>{$dept['department_name']}</option>";
                        } ?>
                    </select>
                </div>

                <div class="input">
                    <label for="userTypeID">User Type<span>*</span> : </label>
                    <select name="userTypeID" id="edit_userTypeID" class="input-field">
                        <option value="">---Select Type---</option>
                        <?php foreach ($userTypes as $type) {
                            $selected = (($modal_user['userTypeID'] ?? '') == $type['userTypeID']) ? 'selected' : '';
                            echo "<option value='{$type['userTypeID']}' {$selected}>{$type['type_name']}</option>";
                        } ?>
                    </select>
                    <p class="errors text-red-500 text-sm"><?= $errors["userTypeID"] ?? "" ?></p>
                </div>
                <div class="input">
                    <label>User Role</label>
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Super Admin'): ?>
                        <select name="role" class="input-field">
                            <option value="Borrower">Borrower</option>
                            <option value="Admin">Admin (Librarian)</option>
                        </select>
                        <small class="text-gray-500">Only Super Admins can change this.</small>
                    
                    <?php else: ?>
                        <input type="text" value="<?= $modal_user['role'] ?? 'Borrower' ?>" class="input-field bg-gray-100" readonly>
                        <input type="hidden" name="role" value="<?= $modal_user['role'] ?? 'Borrower' ?>">
                        <small class="text-red-500">You do not have permission to promote users.</small>
                    <?php endif; ?>
                </div>

                <p class="errors text-red-500 text-sm col-span-2 mt-2"><?= $errors["db_error"] ?? "" ?></p>

                <br>
                <div class="cancelConfirmBtns">
                    <button type="button" data-modal="editUserModal" data-tab="<?= $current_tab ?>" class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400 cursor-pointer">Cancel</button>
                    <input type="submit" value="Save Changes" class="font-bold cursor-pointer mt-4 border-none rounded-lg bg-red-800 text-white p-2 w-full hover:bg-red-700">
                </div>
            </form>
        </div>
    </div>

    <div id="viewDetailsUserModal" class="modal <?= $open_modal == 'viewDetailsUserModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close close-times" data-modal="viewDetailsUserModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4">User Details</h2>
            <div class="user-details grid grid-cols-2 gap-y-2 gap-x-4 text-base">
                <div class="col-span-2 mb-4 relative justify-items-center">
                    <p class="font-semibold mb-2">ID Image:</p>
                    <?php
                    $modal_image_url = !empty($modal_user['imageID_dir']) ? "../../../" . $modal_user['imageID_dir'] : null;
                    if ($modal_image_url) { ?>
                        <img src="<?= $modal_image_url ?>" alt="User ID Image" class="max-w-xs max-h-40 border rounded shadow-md">
                    <?php } else { ?>
                        <p class="text-gray-500">No ID Image Uploaded</p>
                    <?php } ?>
                    <button type="button" id="openImage" class="enlarge"><i class="fa-solid fa-expand" style="color: #ffffff;"></i></button>
                </div>

                <p class="col-span-2"><strong>Last Name:</strong> <?= $modal_user['lName'] ?? 'N/A' ?></p>
                <p class="col-span-2"><strong>Middle Initial:</strong> <?= $modal_user['middleIn'] ?? 'N/A' ?></p>
                <p class="col-span-2"><strong>First Name:</strong> <?= $modal_user['fName'] ?? 'N/A' ?></p>
                <p><strong>Email:</strong> <?= $modal_user['email'] ?? 'N/A' ?></p>
                <p><strong>Contact No.:</strong> <?= $modal_user['contact_no'] ?? 'N/A' ?></p>
                <p><strong>User Type:</strong> <?= $modal_user['type_name'] ?? 'N/A' ?></p>
                <p><strong>Role:</strong> <?= $modal_user['role'] ?? 'N/A' ?></p>
                
                <p><strong>College/Department:</strong> <?= htmlspecialchars($modal_user['department_name'] ?? 'N/A') ?></p>
                
                <p><strong>Account Status:</strong> <span class="font-bold text-red-800"><?= $modal_user['account_status'] ?? 'N/A' ?></span></p>
                <p><strong>Date Registered:</strong> <?= $modal_user['date_registered'] ?? 'N/A' ?></p>
                <?php if(!empty($modal_user['status_reason'])): ?>
                    <p class="col-span-2"><strong>Status Reason:</strong> <span class="text-red-700"><?= htmlspecialchars($modal_user['status_reason']) ?></span></p>
                <?php endif; ?>
            </div>
            <div class="mt-6 text-right">
                <button type="button" data-modal="viewDetailsUserModal" data-tab="<?= $current_tab ?>" class="close viewBtn bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">Close</button>
            </div>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal delete-modal <?= $open_modal == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to **delete** the user:
                <span class="font-semibold italic"><?= ($modal_user['fName'] ?? '') . ' ' . ($modal_user['lName'] ?? 'this user') ?></span>?
                This action cannot be undone.
            </p>
            <div class="cancelConfirmBtns">
                <button type="button" data-modal="deleteConfirmModal" data-tab="<?= $current_tab ?>" class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">Cancel</button>
                <a href="../../../app/controllers/userController.php?action=delete&id=<?= $modal_user['userID'] ?? $user_id ?>&tab=<?= $current_tab ?>" class="text-white px-4 py-2 rounded-lg font-semibold cursor-pointer bg-red-600 hover:bg-red-700">Confirm Delete</a>
            </div>
        </div>
    </div>

    <div id="success-modal" class="modal <?= $success_modal ? 'open' : '' ?>">
        <div class="modal-content max-w-sm text-center">
            <span class="close close-times" data-modal="success-modal">&times;</span>
            <h3 class="text-xl font-bold text-red-800 mb-2">Success!</h3>
            <p class="text-gray-700"><?= $success_message ?></p>
            <button type="button" data-modal="success-modal" class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400 mt-4">Close</button>
        </div>
    </div>

    <div id="imageEnlargeModal" class="modal hidden">
        <div class="modal-content !max-w-4xl text-center">
            <span class="close-times cursor-pointer float-right text-2xl" id="closeImage">&times;</span>
            <p class="font-semibold mb-2">Image:</p>
            <?php
            $modal_image_url = !empty($modal_user['imageID_dir']) ? "../../../" . $modal_user['imageID_dir'] : null;
            if ($modal_image_url) { ?>
                <img src="<?= $modal_image_url ?>" alt="Book Cover Image" class="w-full h-auto max-h-[80vh] object-contain mx-auto">
            <?php } else { ?>
                <p class="text-gray-500">No ID Image Uploaded</p>
            <?php } ?>
        </div>
    </div>

</body>
<script src="../../../public/assets/js/modal.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const closeImage = document.getElementById("closeImage");
        const imageEnlargeModal = document.getElementById("imageEnlargeModal");
        const openImage = document.getElementById("openImage");

        if(openImage && imageEnlargeModal) {
            openImage.addEventListener("click", () => {
                imageEnlargeModal.style.display = 'block';
            });
            closeImage.addEventListener("click", () => {
                imageEnlargeModal.style.display = 'none';
            });
            window.addEventListener('click', (e) => {
                if (e.target === imageEnlargeModal) {
                    imageEnlargeModal.style.display = 'none';
                }
            });
        }

        document.querySelectorAll('.open-modal-btn').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const userId = this.getAttribute('data-id');
                const userName = this.getAttribute('data-name');
                const modal = document.getElementById(targetId);
                
                if (modal) {
                    modal.style.display = 'block';
                    modal.classList.add('open');

                    if (targetId === 'rejectUserModal') document.getElementById('reject_userID').value = userId;
                    if (targetId === 'blockUserModal') document.getElementById('block_userID').value = userId;
                    if (targetId === 'unblockUserModal') document.getElementById('unblock_userID').value = userId;

                    if (userName) {
                        const nameSpan = modal.querySelector('.user-name-span');
                        if (nameSpan) nameSpan.textContent = userName;
                    }
                }
            });
        });

        document.querySelectorAll('.close-modal').forEach(span => {
            span.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if(modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('open');
                }
            });
        });
    });
</script>
</html>