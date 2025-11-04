<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: ../../app/views/borrower/login.php");
  exit;
}

// Check if user is an Admin/Librarian (optional, but good practice)
// if (isset($_SESSION['user_role']) && $_SESSION['user_role'] != 'Admin') {
//     // Redirect non-admins if necessary
//     // header("Location: user_dashboard.php");
//     // exit;
// }

// Include necessary models
require_once(__DIR__ . '/../../models/manageBook.php');
require_once(__DIR__ . '/../../models/manageUsers.php');
require_once(__DIR__ . '/../../models/manageBorrowDetails.php');

// Initialize models and fetch data
$bookModel = new Book();
$userModel = new User();
$borrowModel = new BorrowDetails();

$total_books = $bookModel->countTotalBooks();
$total_borrowers = $userModel->countTotalBorrowers(); 
$overdue_book_count = $borrowModel->countOverdueBooks(); 
$pending_borrow_requests_count = $borrowModel->countPendingRequests(); 


$pending_requests = $borrowModel->viewBorrowDetails("", "Pending");
$pending_users = $userModel->viewUser("", "", "pending"); // Fetch all pending user registrations
$pending_users_count = count($pending_users); // Count the fetched array
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Librarian Dashboard</title>
  <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
  <link rel="stylesheet" href="../../../public/assets/css/adminFinal.css" />
  <style>
    .info {
      padding: 1.5rem;
      border-radius: 0.5rem;
      color: white;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      transition: transform 0.2s;
      min-height: 120px;
    }
    .info:hover {
        transform: translateY(-2px);
    }
    .count {
      font-size: 2.5rem;
      font-weight: bold;
      line-height: 1;
    }
    
  </style>
</head>

<body class="h-screen w-screen flex">

  <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>
  
  <div class="flex flex-col w-10/12"> 
    <nav class="mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
    </nav>
    <main>
      <div class="container">
        <div id="dashboardSection" class="section dashboardSection grid grid-cols-2 md:grid-cols-4 gap-4">
          
          <!-- Total Books -->
          <div class="info total-books bg-blue-400">
            <span class="count"><?= htmlspecialchars($total_books) ?></span>
            <h2 class="title">Total Book Copies</h2>
          </div>
          
          <!-- Total Borrowers (Approved Users) -->
          <div class="info total-borrowers bg-green-400">
            <span class="count"><?= htmlspecialchars($total_borrowers) ?></span>
            <h2 class="title">Total Approved Borrowers</h2>
          </div>
          
          <!-- Overdue Books -->
          <div class="info overdue-book-count bg-yellow-400">
            <span class="count"><?= htmlspecialchars($overdue_book_count) ?></span>
            <h2 class="title">Overdue Books</h2>
          </div>
          
          <!-- Pending Borrow Requests -->
          <div class="info pending-request bg-red-400">
            <span class="count"><?= htmlspecialchars($pending_borrow_requests_count) ?></span>
            <h2 class="title">Pending Borrow Requests</h2>
          </div>
          
        </div>

        <!-- Pending Requests Table -->
        <section class="section mt-8">
            <h2 class="text-2xl font-bold text-red-800 mb-4">Pending Borrow Requests (<?= htmlspecialchars($pending_borrow_requests_count) ?>)</h2>
            <div class="view bg-white p-4 rounded-lg shadow-md">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>User Name</th>
                            <th>Book Title</th>
                            <th>Copies</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_requests)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-gray-500">
                                    No pending borrow requests found.
                                </td>
                            </tr>
                        <?php else:
                            $no = 1;
                            foreach ($pending_requests as $detail) {
                                $fullName = htmlspecialchars($detail["lName"] . ", " . $detail["fName"]);
                                $bookTitle = htmlspecialchars($detail["book_title"]);
                                $borrowID = $detail["borrowID"];
                                $request_date = $detail["request_date"];
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= $fullName ?></td>
                                    <td><?= $bookTitle ?></td>
                                    <td><?= $detail["no_of_copies"] ?></td>
                                    <td><?= $request_date ?></td>
                                    <td class="action text-center">
                                        <a class="actionBtn bg-green-500 hover:bg-green-600 text-white inline-block mb-1"
                                            href="../../../app/controllers/dashboardController.php?action=accept&id=<?= $borrowID ?>">Accept</a>
                                        <a class="actionBtn bg-red-500 hover:bg-red-600 text-white inline-block mb-1"
                                            href="../../../app/controllers/dashboardController.php?action=reject&id=<?= $borrowID ?>">Reject</a>
                                        <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-white inline-block mb-1"
                                            href="borrowDetailsSection.php?modal=view&id=<?= $borrowID ?>">View</a>
                                    </td>
                                </tr>
                            <?php }
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
            <!-- --- PENDING USER APPROVALS TABLE--- -->
        <section class="section mt-8">
            <h2 class="text-2xl font-bold text-red-800 mb-4">Pending User Approvals (<?= htmlspecialchars($pending_users_count) ?>)</h2>
            <div class="view bg-white p-4 rounded-lg shadow-md">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>User Type</th>
                            <th>Date Reg.</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-gray-500">
                                    No pending user registrations found.
                                </td>
                            </tr>
                        <?php else:
                            $no = 1;
                            foreach ($pending_users as $user) {
                                $fullName = htmlspecialchars($user["fName"] . " " . $user["lName"]);
                                $userID = $user["userID"];
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= $fullName ?></td>
                                    <td><?= htmlspecialchars($user["email"]) ?></td>
                                    <td><?= htmlspecialchars($user["type_name"]) ?></td>
                                    <td><?= $user["date_registered"] ?></td>
                                    <td class="action text-center">
                                        <a class="actionBtn bg-green-500 hover:bg-green-600 text-white inline-block mb-1"
                                            href="../../../app/controllers/userController.php?action=approveReject&id=<?= $userID ?>&status=Approved">Approve</a>
                                        <a class="actionBtn bg-red-500 hover:bg-red-600 text-white inline-block mb-1"
                                            href="../../../app/controllers/userController.php?action=approveReject&id=<?= $userID ?>&status=Rejected">Reject</a>
                                        <a class="actionBtn bg-gray-500 hover:bg-gray-600 text-white inline-block mb-1"
                                            href="usersSection.php?modal=view&id=<?= $userID ?>">View</a>
                                    </td>
                                </tr>
                            <?php }
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

      </div>
    </main>
  </div>
  
</body>

</html>
