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

// Determines the current Modal
$current_modal = $_GET['modal'] ?? '';
$user_id = (int) ($_GET['id'] ?? 0);
$open_modal = '';

// Determines the current tab
$current_tab = $_GET['tab'] ?? 'pending';

// Load User Data for Modals
$modal_user = [];
if ($current_modal === 'edit') {
    $open_modal = 'editUserModal';
} elseif ($current_modal === 'view') {
    $open_modal = 'viewDetailsUserModal';
} elseif ($current_modal === 'block') {
    $open_modal = 'blockConfirmUserModal';
} elseif ($current_modal === 'delete') {
    $open_modal = 'deleteConfirmModal';
}

if (!empty($open_modal)) {
    if ($open_modal == 'editUserModal' && !empty($old)) {
        $modal_user = $old;
    } else { //view
        $modal_user = $userObj->fetchUser($user_id) ?: [];
    }
    if ($open_modal != 'viewDetailsUserModal') { //delete 
        $modal_user['userID'] = $user_id;
    }
    if (empty($modal_user['status']) && !empty($user_id)) { //block
        $data = $userObj->fetchUser($user_id);
        $modal_user['status'] = $data['status'] ?? 'N/A';
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
    <link rel="stylesheet" href="../../../public/assets/css/admin1.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>
    <div class="flex flex-col w-10/12">
        <nav>
            <h1 class="text-xl font-semibold">Users</h1>
        </nav>
        <main>
            <div class="container">
                <div class="section manage_users h-full">
                    <div class="title flex w-full items-center justify-between mb-4">
                        <h1 class="text-red-800 font-bold text-4xl">MANAGE USERS</h1>
                    </div>

                    <div class="tabs flex border-b border-gray-200 mb-6">
                        <a href="?tab=pending" class="tab-btn <?= $current_tab == 'pending' ? 'active' : '' ?>">Pending
                            Registers</a>
                        <a href="?tab=approved"
                            class="tab-btn <?= $current_tab == 'approved' ? 'active' : '' ?>">Approved
                            Users</a>
                        <a href="?tab=rejected"
                            class="tab-btn <?= $current_tab == 'rejected' ? 'active' : '' ?>">Rejected
                            Users</a>
                        <a href="?tab=blocked" class="tab-btn <?= $current_tab == 'blocked' ? 'active' : '' ?>">Blocked
                            Accounts</a>
                    </div>

                    <form method="GET" class="search flex gap-2 items-center mb-6">
                        <input type="hidden" name="tab" value="<?= $current_tab ?>">
                        <input type="text" name="search" placeholder="Search by name or email" value="<?= $search ?>"
                            class="border rounded-lg p-2 flex-grow">
                        <select name="userType" class="border rounded-lg p-2">
                            <option value="">All Types</option>
                            <?php foreach ($userTypes as $type) { ?>
                                <option value="<?= $type['userTypeID'] ?>" <?= $userTypeID == $type['userTypeID'] ? 'selected' : '' ?>>
                                    <?= $type['type_name'] ?>
                                </option>
                            <?php } ?>
                        </select>
                        <button type="submit"
                            class="bg-red-800 text-white rounded-lg px-4 py-2 hover:bg-red-700">Search</button>
                    </form>

                    <h2 class="text-2xl font-semibold mb-4 text-gray-700">
                        <?php
                        switch ($current_tab) {
                            case 'pending':
                                echo 'Pending User Registrations';
                                break;
                            case 'approved':
                                echo 'Approved System Users';
                                break;
                            case 'rejected':
                                echo 'Rejected User Registrations';
                                break;
                            case 'blocked':
                                echo 'Blocked Accounts';
                                break;
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
                                <th>Date Reg.</th>
                                <th>Actions</th>
                            </tr>

                            <?php
                            $no = 1;
                            $colspan = 8;

                            if (empty($users)): ?>
                                <tr>
                                    <td colspan="<?= $colspan ?>" class="text-center py-4 text-gray-500">
                                        No <?= strtolower(str_replace('d', 'd ', $current_tab)) ?> users found.
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($users as $user) {
                                    $image_url = !empty($user["imageID_dir"]) ? "../../../" . $user["imageID_dir"] : null;
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= $user["lName"] ?></td>
                                        <td><?= $user["fName"] ?></td>
                                        <td><?= $user["email"] ?></td>
                                        <td class="text-center">
                                            <?php
                                            if ($image_url) { ?>
                                                <img src="<?= $image_url ?>" alt="ID"
                                                    class="w-16 h-16 object-cover rounded mx-auto border border-gray-300"
                                                    title="<?= $user["imageID_name"] ?>">
                                            <?php } else { ?>
                                                <span class="text-gray-500 text-xs">N/A</span>
                                            <?php } ?>
                                        </td>
                                        <td><?= $user["type_name"] ?></td>
                                        <td><?= $user['date_registered'] ?? 'N/A' ?></td>
                                        <td class="action text-center">

                                            <?php if ($current_tab == 'pending'): ?>
                                                <a href="../../../app/controllers/userController.php?tab=<?= $current_tab ?>&action=approveReject&id=<?= $user['userID'] ?>&status=Approved"
                                                    class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1">
                                                    Approve
                                                </a>
                                                <a href="../../../app/controllers/userController.php?tab=<?= $current_tab ?>&action=approveReject&id=<?= $user['userID'] ?>&status=Rejected"
                                                    class="actionBtn bg-red-500 hover:bg-red-600 text-sm inline-block mb-1">
                                                    Reject
                                                </a>
                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="usersSection.php?tab=<?= $current_tab ?>&modal=view&id=<?= $user['userID'] ?>">
                                                    View
                                                </a>

                                            <?php elseif ($current_tab == 'approved'): ?>
                                                <a class="actionBtn editBtn bg-blue-500 hover:bg-blue-600 text-sm inline-block mb-1"
                                                    href="usersSection.php?tab=<?= $current_tab ?>&modal=edit&id=<?= $user['userID'] ?>">Edit</a>

                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="usersSection.php?tab=<?= $current_tab ?>&modal=view&id=<?= $user['userID'] ?>">
                                                    View
                                                </a>

                                                <a class="actionBtn bg-amber-500 hover:bg-amber-600 text-sm inline-block mb-1"
                                                    href="usersSection.php?tab=<?= $current_tab ?>&modal=block&id=<?= $user['userID'] ?>">
                                                    Block
                                                </a>

                                                <a class="actionBtn  bg-red-500 hover:bg-red-600 text-sm inline-block mb-1"
                                                    href="usersSection.php?tab=<?= $current_tab ?>&modal=delete&id=<?= $user['userID'] ?>">
                                                    Delete
                                                </a>

                                            <?php elseif ($current_tab == 'blocked'): ?>
                                                <a class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1"
                                                    href="../../../app/controllers/userController.php?tab=<?= $current_tab ?>&action=unblock&id=<?= $user['userID'] ?>">
                                                    Unblock
                                                </a>

                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="usersSection.php?tab=<?= $current_tab ?>&modal=view&id=<?= $user['userID'] ?>">
                                                    View
                                                </a>

                                                <a class="actionBtn  bg-red-500 hover:bg-red-600 text-sm inline-block mb-1"
                                                    href="usersSection.php?tab=<?= $current_tab ?>&modal=delete&id=<?= $user['userID'] ?>">
                                                    Delete
                                                </a>

                                            <?php else: ?>
                                                <a href="../../../app/controllers/userController.php?tab=<?= $current_tab ?>&action=approveReject&id=<?= $user['userID'] ?>&status=Approved"
                                                    class="actionBtn bg-green-500 hover:bg-green-600 text-sm inline-block mb-1">
                                                    Approve
                                                </a>

                                                <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-sm inline-block mb-1"
                                                    href="usersSection.php?tab=<?= $current_tab ?>&modal=view&id=<?= $user['userID'] ?>">
                                                    View
                                                </a>

                                                <a class="actionBtn  bg-red-500 hover:bg-red-600 text-sm inline-block mb-1"
                                                    href="usersSection.php?tab=<?= $current_tab ?>&modal=delete&id=<?= $user['userID'] ?>">
                                                    Delete
                                                </a>
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
            </div>
        </main>
    </div>

    <div id="editUserModal" class="modal <?= $open_modal == 'editUserModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close close-times" data-modal="editUserModal" data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Edit User</h2>
            <form id="editUserForm"
                action="../../../app/controllers/userController.php?tab=<?= $current_tab ?>&action=edit&id=<?= $modal_user['userID'] ?? $user_id ?>"
                method="POST" enctype="multipart/form-data">
                <input type="hidden" name="userID" value="<?= $modal_user['userID'] ?? $user_id ?>">
                <input type="hidden" name="existing_image_name" value="<?= $modal_user["imageID_name"] ?? "" ?>">
                <input type="hidden" name="existing_image_dir" value="<?= $modal_user["imageID_dir"] ?? "" ?>">

                <div class="input">
                    <label for="lName">Last Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="lName" id="edit_lName"
                        value="<?= $modal_user["lName"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["lName"] ?? "" ?></p>
                </div>
                <div class="input">
                    <label for="fName">First Name<span>*</span> : </label>
                    <input type="text" class="input-field" name="fName" id="edit_fName"
                        value="<?= $modal_user["fName"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["fName"] ?? "" ?></p>
                </div>
                <div class="input col-span-2">
                    <label for="middleIn">Middle Initial : </label>
                    <input type="text" class="input-field" name="middleIn" id="edit_middleIn"
                        value="<?= $modal_user["middleIn"] ?? "" ?>">
                </div>

                <div class="input col-span-2">
                    <label for="new_imageID">Upload New ID Image (Optional): </label>
                    <input type="file" class="input-field" name="new_imageID" id="edit_new_imageID" accept="image/*">
                    <p class="text-xs text-gray-500 mt-1">Current File:
                        <?= $modal_user["imageID_name"] ?? "None" ?>
                    </p>
                    <p class="errors text-red-500 text-sm"><?= $errors["new_imageID"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="contact_no">Contact No. : </label>
                    <input type="text" class="input-field" name="contact_no" id="edit_contact_no"
                        value="<?= $modal_user["contact_no"] ?? "" ?>">
                </div>
                <div class="input">
                    <label for="email">Email<span>*</span> : </label>
                    <input type="email" class="input-field" name="email" id="edit_email"
                        value="<?= $modal_user["email"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["email"] ?? "" ?></p>
                </div>

                <div class="input col-span-2">
                    <label for="college_department">College/Department : </label>
                    <input type="text" class="input-field" name="college_department" id="edit_college_department"
                        value="<?= $modal_user["college_department"] ?? '' ?>">
                </div>

                <div class="input">
                    <label for="userTypeID">User Type<span>*</span> : </label>
                    <select name="userTypeID" id="edit_userTypeID" class="input-field">
                        <option value="">---Select Type---</option>
                        <?php foreach ($userTypes as $type) {
                            $selected = (($modal_user['userTypeID'] ?? '') == $type['userTypeID']) ? 'selected' : '';
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
                        $roles = ["Borrower", "Admin"];
                        foreach ($roles as $role) {
                            $selected = (($modal_user['role'] ?? '') == $role) ? 'selected' : '';
                            echo "<option value='{$role}' {$selected}>{$role}</option>";
                        }
                        ?>
                    </select>
                    <p class="errors text-red-500 text-sm"><?= $errors["role"] ?? "" ?></p>
                </div>

                <p class="errors text-red-500 text-sm col-span-2 mt-2"><?= $errors["db_error"] ?? "" ?></p>

                <br>
                <div class="cancelConfirmBtns">

                    <button type="button" data-modal="editBookModal" data-tab="<?= $current_tab ?>"
                        class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400 cursor-pointer">
                        Cancel
                    </button>

                    <input type="submit" value="Save Changes"
                        class="font-bold cursor-pointer mt-4 border-none rounded-lg bg-red-800 text-white p-2 w-full hover:bg-red-700">

                </div>
            </form>
        </div>
    </div>


    <div id="viewDetailsUserModal" class="modal <?= $open_modal == 'viewDetailsUserModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close close-times" data-modal="viewDetailsUserModal"
                data-tab="<?= $current_tab ?>">&times;</span>
            <h2 class="text-2xl font-bold mb-4">User Details</h2>
            <div class="user-details grid grid-cols-2 gap-y-2 gap-x-4 text-base">

                <div class="col-span-2 mb-4 relative justify-items-center">
                    <p class="font-semibold mb-2">ID Image:</p>
                    <?php
                    $modal_image_url = !empty($modal_user['imageID_dir']) ? "../../../" . $modal_user['imageID_dir'] : null;

                    if ($modal_image_url) { ?>
                        <img src="<?= $modal_image_url ?>" alt="User ID Image"
                            class="max-w-xs max-h-40 border rounded shadow-md">
                    <?php } else { ?>
                        <p class="text-gray-500">No ID Image Uploaded</p>
                    <?php } ?>
                    <button type="button" id="openImage" class="enlarge">
                        <i class="fa-solid fa-expand" style="color: #ffffff;"></i>
                    </button>
                </div>

                <p class="col-span-2"><strong>Last Name:</strong> <?= $modal_user['lName'] ?? 'N/A' ?></p>
                <p class="col-span-2"><strong>Middle Initial:</strong> <?= $modal_user['middleIn'] ?? 'N/A' ?></p>
                <p class="col-span-2"><strong>First Name:</strong> <?= $modal_user['fName'] ?? 'N/A' ?></p>
                <p><strong>Email:</strong> <?= $modal_user['email'] ?? 'N/A' ?></p>
                <p><strong>Contact No.:</strong> <?= $modal_user['contact_no'] ?? 'N/A' ?></p>

                <p><strong>User Type:</strong> <?= $modal_user['type_name'] ?? 'N/A' ?></p>
                <p><strong>Role:</strong> <?= $modal_user['role'] ?? 'N/A' ?></p>
                <p><strong>College/Department:</strong> <?= $modal_user['college_department'] ?? 'N/A' ?></p>

                <p><strong>Account Status:</strong> <span
                        class="font-bold text-red-800"><?= $modal_user['account_status'] ?? 'N/A' ?></span></p>

                <p><strong>Date Registered:</strong>
                    <?= $modal_user['date_registered'] ?? 'N/A' ?>
                </p>

            </div>
            <div class="mt-6 text-right">
                <button type="button" data-modal="viewDetailsUserModal" data-tab="<?= $current_tab ?>"
                    class="close viewBtn bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">Close</button>
            </div>
        </div>
    </div>

    <div id="blockConfirmUserModal"
        class="modal delete-modal <?= $open_modal == 'blockConfirmUserModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <h2 class="text-xl font-bold mb-4 text-yellow-700">Confirm Block</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to <strong>block</strong> the user:
                <span
                    class="font-semibold italic"><?= ($modal_user['fName'] ?? '') . ' ' . ($modal_user['lName'] ?? 'this user') ?></span>?
                This user will be unable to log in until their status is changed back to Approved.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button" data-modal="blockConfirmUserModal" data-tab="<?= $current_tab ?>"
                    class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">
                    Cancel
                </button>
                <a href="../../../app/controllers/userController.php?tab=<?= $current_tab ?>&action=block&id=<?= $modal_user['userID'] ?? $user_id ?>"
                    class="bg-yellow-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-yellow-700 cursor-pointer">
                    Confirm Block
                </a>
            </div>
        </div>
    </div>


    <div id="deleteConfirmModal" class="modal delete-modal <?= $open_modal == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to **delete** the user:
                <span
                    class="font-semibold italic"><?= ($modal_user['fName'] ?? '') . ' ' . ($modal_user['lName'] ?? 'this user') ?></span>?
                This action cannot be undone.
            </p>
            <div class="cancelConfirmBtns">
                <button type="button" data-modal="deleteConfirmModal" data-tab="<?= $current_tab ?>"
                    class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">
                    Cancel
                </button>
                <a href="../../../app/controllers/bookController.php?action=delete&id=<?= $modal_user['userID'] ?>"
                    class="text-white px-4 py-2 rounded-lg font-semibold cursor-pointer">
                    Confirm Delete
                </a>
            </div>
        </div>
    </div>

    <div id="success-modal" class="modal <?= $success_modal ? 'open' : '' ?>">
        <div class="modal-content max-w-sm text-center">
            <span class="close close-times" data-modal="success-modal">&times;</span>
            <h3 class="text-xl font-bold text-red-800 mb-2">Success!</h3>
            <p class="text-gray-700">
                <?= $success_message ?> Book Success!
            </p>
            <button type="button" data-modal="success-modal"
                class="close bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400">
                Close
            </button>
        </div>
    </div>

    <div id="imageEnlargeModal" class="modal hidden">
        <div class="modal-content !max-w-4xl text-center">
            <span class="close-times" id="closeImage">&times;</span>
            <p class="font-semibold mb-2">Image:</p>
            <?php
            $modal_image_url = !empty($modal_user['imageID_dir']) ? "../../../" . $modal_user['imageID_dir'] : null;


            if ($modal_image_url) { ?>
                <img src="<?= $modal_image_url ?>" alt="Book Cover Image"
                    class="w-full h-auto max-h-[80vh] object-contain mx-auto">
            <?php } else { ?>
                <p class="text-gray-500">No Book Cover Uploaded</p>
            <?php } ?>
            <!-- </div> -->
        </div>
    </div>

</body>
<script src="../../../public/assets/js/modal.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const closeImage = document.getElementById("closeImage");
        const imageEnlargeModal = document.getElementById("imageEnlargeModal");
        const openImage = document.getElementById("openImage");


        openImage.addEventListener("click", () => {
            imageEnlargeModal.style.display = 'flex';
        })

        closeImage.addEventListener("click", () => {
            imageEnlargeModal.style.display = 'none';
        })

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    })
</script>

</html>