<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/managePenalty.php");
$penaltyObj = new Penalty();

$old = $_SESSION["old"] ?? [];
$errors = $_SESSION["errors"] ?? [];
$open_modal = $_SESSION['open_modal'] ?? ''; // Stores the modal to open on load
$edit_penalty_id = $_SESSION['edit_penalty_id'] ?? null; // Stores the ID of penalty being edited
$delete_penalty_id = $_SESSION['delete_penalty_id'] ?? null; // Stores the ID of penalty being deleted 

$initial_edit_id = isset($_GET['id']) && $_GET['action'] == 'edit-initial' ? (int) $_GET['id'] : null;
$initial_delete_id = isset($_GET['id']) && $_GET['action'] == 'delete-initial' ? (int) $_GET['id'] : null;

if ($initial_edit_id) {
    $_SESSION['open_modal'] = 'editPenaltyModal';
    $_SESSION['edit_penalty_id'] = $initial_edit_id;
    header("Location: penaltySection.php");
    exit;
}
if ($initial_delete_id) {
    $_SESSION['open_modal'] = 'deleteConfirmModal';
    $_SESSION['delete_penalty_id'] = $initial_delete_id;
    header("Location: penaltySection.php");
    exit;
}

//  Load Penalty Data for Modals
$current_penalty_data = [];
$load_id = $edit_penalty_id ?? $delete_penalty_id;

if ($open_modal == 'editPenaltyModal' && $edit_penalty_id) {
    if (empty($old)) {
        // Initial load for edit (fetches existing data)
        $current_penalty_data = $penaltyObj->fetchPenalty($edit_penalty_id);
    } else {
        // Failed submission, use old data
        $current_penalty_data = $old;
        $current_penalty_data['PenaltyID'] = $edit_penalty_id; // Keep the book ID
    }
} elseif ($open_modal == 'deleteConfirmModal' && $delete_penalty_id) {
    // Load for delete confirmation
    $current_penalty_data = $penaltyObj->fetchPenalty($delete_penalty_id);
    if (!$current_penalty_data) {
        $current_penalty_data = ['borrowID' => 'Error', 'type' => 'Penalty not found.'];
    }
    $current_penalty_data['PenaltyID'] = $delete_penalty_id;
} elseif ($open_modal == 'addPenaltyModal' && !empty($old)) {
    // Failed submission for add, use old data
    $current_penalty_data = $old;
}


// Clear the session variables after getting them for the current request
unset($_SESSION["old"], $_SESSION["errors"], $_SESSION['open_modal'], $_SESSION['edit_penalty_id'], $_SESSION['delete_penalty_id']);


$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$penalties = $penaltyObj->viewPenalties($search);
$penalty_types = ['Late', 'Damaged', 'Lost'];
$penalty_statuses = ['Unresolved', 'Resolved'];
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - Manage Penalties</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/librarian/admin.css" />
</head>

<body class="h-screen w-screen flex">
    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <main>
        <div class="container">
            <div class="section h-full">
                <div class="title flex w-full items-center justify-between">
                    <h1 class="text-red-800 font-bold text-4xl">MANAGE PENALTIES</h1>
                    <a id="openAddPenaltyModalBtn" class="addBtn" href="#">+ Add Penalty</a>
                </div>

                <form method="GET" class="search mb-4">
                    <!-- UPDATED PLACEHOLDER -->
                    <input type="text" name="search" placeholder="Search by ID, Name, Title, or Type"
                        class="border border-red-800 rounded-lg p-2 w-1/3">
                    <button type="submit" class="bg-red-800 text-white rounded-lg px-4 py-2">Search</button>
                </form>

                <?php if (!empty($errors['general'])): ?>
                    <p class="text-red-600 font-semibold mb-4 bg-red-100 p-2 rounded-lg"><?= $errors['general'] ?></p>
                <?php endif; ?>

                <div class="view">
                    <table>
                        <tr>
                            <th>No</th>
                            <th>Penalty ID</th>
                            <th>Borrow ID</th>
                            <th>User Name</th>
                            <th>Book Title</th>
                            <th>Type</th>
                            <th>Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                        <?php
                        $no = 1;
                        foreach ($penalties as $penalty) {
                            $status_class = $penalty['status'] == 'Resolved' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                            // Format Full Name and Title (retrieved via JOIN in the model)
                            $fullName = $penalty["fName"] ?? 'N/A' . ' ' . $penalty["lName"] ?? '';
                            $bookTitle = $penalty["book_title"] ?? 'N/A';
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $penalty["PenaltyID"] ?></td>
                                <td><?= $penalty["borrowID"] ?></td>
                                <td><?= $fullName ?></td>
                                <td><?= $bookTitle ?></td>
                                <td><?= $penalty["type"] ?></td>
                                <td>₱<?= number_format($penalty["cost"], 2) ?></td>
                                <td class="<?= $status_class ?>"><?= $penalty["status"] ?></td>

                                <td class="action text-center space-x-2 flex justify-center items-center">
                                    <a class="editBtn px-2 py-1 rounded text-white bg-blue-600 hover:bg-blue-700"
                                        href="penaltySection.php?action=edit-initial&id=<?= $penalty['PenaltyID'] ?>">Edit</a>
                                    <a class="deleteBtn px-2 py-1 rounded text-white bg-red-600 hover:bg-red-700"
                                        href="penaltySection.php?action=delete-initial&id=<?= $penalty['PenaltyID'] ?>">
                                        Delete
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

    <!-- ADD PENALTY MODAL -->
    <div id="addPenaltyModal" class="modal <?= $open_modal == 'addPenaltyModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="addPenaltyModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Add New Penalty</h2>
            <form action="../../../app/controllers/penaltyController.php?action=add" method="POST">

                <div class="input">
                    <label for="borrowID">Borrow ID<span>*</span> : </label>
                    <input type="number" class="input-field" name="borrowID" id="add_borrowID"
                        value="<?= $current_penalty_data["borrowID"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["borrowID"] ?? "" ?></p>
                </div>

                <!-- REMOVED USERID and BOOKID INPUT FIELDS -->

                <div class="input">
                    <label for="type">Penalty Type<span>*</span> : </label>
                    <select name="type" id="add_type" class="input-field">
                        <option value="">---Select Type---</option>
                        <?php foreach ($penalty_types as $type) {
                            $selected = (($current_penalty_data['type'] ?? '') == $type) ? 'selected' : '';
                            echo "<option value='{$type}' {$selected}>{$type}</option>";
                        } ?>
                    </select>
                    <p class="errors text-red-500 text-sm"><?= $errors["type"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="cost">Cost (₱)<span>*</span> : </label>
                    <input type="number" step="0.01" class="input-field" name="cost" id="add_cost"
                        value="<?= $current_penalty_data["cost"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["cost"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="status">Status<span>*</span> : </label>
                    <select name="status" id="add_status" class="input-field">
                        <option value="">---Select Status---</option>
                        <?php foreach ($penalty_statuses as $status) {
                            $selected = (($current_penalty_data['status'] ?? '') == $status) ? 'selected' : '';
                            echo "<option value='{$status}' {$selected}>{$status}</option>";
                        } ?>
                    </select>
                    <p class="errors text-red-500 text-sm"><?= $errors["status"] ?? "" ?></p>
                </div>

                <br>
                <input type="submit" value="Add Penalty"
                    class="font-bold cursor-pointer mt-4 border-none rounded-lg bg-red-800 text-white p-2 w-full hover:bg-red-700">
                <p class="errors text-red-500 text-sm mt-2"><?= $errors["general"] ?? "" ?></p>
            </form>
        </div>
    </div>


    <!-- EDIT PENALTY MODAL -->
    <div id="editPenaltyModal" class="modal <?= $open_modal == 'editPenaltyModal' ? 'open' : '' ?>">
        <div class="modal-content">
            <span class="close" data-modal="editPenaltyModal">&times;</span>
            <h2 class="text-2xl font-bold mb-4">Edit Penalty (ID: <?= $edit_penalty_id ?>)</h2>
            <form id="editPenaltyForm"
                action="../../../app/controllers/penaltyController.php?action=edit&id=<?= $edit_penalty_id ?>"
                method="POST">
                <input type="hidden" name="penaltyID" value="<?= $edit_penalty_id ?>">

                <div class="input">
                    <label for="borrowID">Borrow ID<span>*</span> : </label>
                    <input type="number" class="input-field" name="borrowID" id="edit_borrowID"
                        value="<?= $current_penalty_data["borrowID"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["borrowID"] ?? "" ?></p>
                </div>

                <!-- REMOVED USERID and BOOKID INPUT FIELDS -->

                <div class="input">
                    <label for="type">Penalty Type<span>*</span> : </label>
                    <select name="type" id="edit_type" class="input-field">
                        <option value="">---Select Type---</option>
                        <?php foreach ($penalty_types as $type) {
                            $selected = (($current_penalty_data['type'] ?? '') == $type) ? 'selected' : '';
                            echo "<option value='{$type}' {$selected}>{$type}</option>";
                        } ?>
                    </select>
                    <p class="errors text-red-500 text-sm"><?= $errors["type"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="cost">Cost (₱)<span>*</span> : </label>
                    <input type="number" step="0.01" class="input-field" name="cost" id="edit_cost"
                        value="<?= $current_penalty_data["cost"] ?? "" ?>">
                    <p class="errors text-red-500 text-sm"><?= $errors["cost"] ?? "" ?></p>
                </div>

                <div class="input">
                    <label for="status">Status<span>*</span> : </label>
                    <select name="status" id="edit_status" class="input-field">
                        <option value="">---Select Status---</option>
                        <?php foreach ($penalty_statuses as $status) {
                            $selected = (($current_penalty_data['status'] ?? '') == $status) ? 'selected' : '';
                            echo "<option value='{$status}' {$selected}>{$status}</option>";
                        } ?>
                    </select>
                    <p class="errors text-red-500 text-sm"><?= $errors["status"] ?? "" ?></p>
                </div>

                <br>
                <input type="submit" value="Save Changes"
                    class="font-bold cursor-pointer mt-4 border-none rounded-lg bg-red-800 text-white p-2 w-full hover:bg-red-700">
                <p class="errors text-red-500 text-sm mt-2"><?= $errors["general"] ?? "" ?></p>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="deleteConfirmModal" class="modal <?= $open_modal == 'deleteConfirmModal' ? 'open' : '' ?>">
        <div class="modal-content max-w-sm">
            <span class="close" data-modal="deleteConfirmModal">&times;</span>
            <h2 class="text-xl font-bold mb-4 text-red-700">Confirm Deletion</h2>
            <p class="mb-6 text-gray-700">
                Are you sure you want to delete the **<?= $current_penalty_data['type'] ?? 'this' ?>** penalty (Borrow
                ID: <span class="font-semibold"><?= $current_penalty_data['borrowID'] ?? 'N/A' ?></span>)?
                This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-4">
                <button type="button"
                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400"
                    onclick="document.getElementById('deleteConfirmModal').style.display='none';">
                    Cancel
                </button>
                <a href="../../../app/controllers/penaltyController.php?action=delete&id=<?= $current_penalty_data['PenaltyID'] ?? $delete_penalty_id ?>"
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
        const addPenaltyModal = document.getElementById('addPenaltyModal');
        const openAddPenaltyBtn = document.getElementById('openAddPenaltyModalBtn');
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
            //clear error
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
        };

        // Open Modal using addBtn
        openAddPenaltyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelector('#addPenaltyModal form').reset();
            document.querySelectorAll('#addPenaltyModal .errors').forEach(p => p.textContent = '');

            openModal(addPenaltyModal);
        });

        // Close Modal when clicking close Buttons
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
    });
</script>

</html>