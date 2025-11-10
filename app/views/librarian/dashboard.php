<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

require_once(__DIR__ . "/../../models/manageUsers.php");
require_once(__DIR__ . '/../../models/manageBook.php');
require_once(__DIR__ . '/../../models/manageBorrowDetails.php');

// Initialize models
$bookObj = new Book();
$userObj = new User();
$borrowObj = new BorrowDetails();
$user_id = $_SESSION['user_id'];
$userDashboard = $userObj->fetchUserName($user_id);

// --- 1. DATA FOR CARDS ---
$total_book_copies = $bookObj->countTotalBookCopies();
$total_borrowed_books = $borrowObj->countTotalBorrowedBooks(); // New
$pending_borrow_requests_count = $borrowObj->countPendingRequests();
$overdue_book_count = $borrowObj->countOverdueBooks();
$total_borrowers = $userObj->countTotalBorrowers();
$monthly_collected_fines = $borrowObj->sumMonthlyCollectedFines();
$monthly_uncollected_fines = $borrowObj->sumMonthlyUncollectedFines(); // New

// --- 2. DATA FOR TOP 5 LISTS ---
$top_5_books = $borrowObj->getTopBorrowedBooks(5);
$top_5_categories = $bookObj->getTopPopularCategories(5);
$top_5_borrowers = $borrowObj->getTopActiveBorrowers(5);

// --- 3. DATA FOR CHARTS ---
// We'll json_encode these later in the script block
$monthly_borrow_trend_data = $borrowObj->getMonthlyBorrowingTrend();
$category_popularity_data = $bookObj->getTopPopularCategories(5); // Re-using this data
$borrow_status_breakdown_data = $borrowObj->getBorrowStatusBreakdown();
$monthly_fines_trend_data = $borrowObj->getMonthlyFinesTrend();

// --- 4. DATA FOR ACTIVITY TABLES ---
$pending_requests = $borrowObj->viewBorrowDetails("", "Pending");
$pending_users = $userObj->viewUser("", "", "pending");
$pending_users_count = count($pending_users);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/admin.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="w-full h-screen flex flex-col">
        <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>
        <main class="overflow-y-auto">
            <div class="container">

                <section id="dashboardSection" class="section dashboardSection grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="info">
                        <span><?= htmlspecialchars($total_book_copies) ?></span>
                        <h2 class="title">Total Book Copies</h2>
                    </div>

                    <div class="info">
                        <span><?= htmlspecialchars($total_borrowed_books) ?></span>
                        <h2 class="title">Total Borrowed Books</h2>
                    </div>

                    <div class="info">
                        <span><?= htmlspecialchars($pending_borrow_requests_count) ?></span>
                        <h2 class="title">Pending Borrow Requests</h2>
                    </div>

                    <div class="info">
                        <span><?= htmlspecialchars($overdue_book_count) ?></span>
                        <h2 class="title">Overdue Books</h2>
                    </div>

                    <div class="info">
                        <span><?= htmlspecialchars(string: $total_borrowers) ?></span>
                        <h2 class="title">Total Borrowers</h2>
                    </div>

                    <div class="info">
                        <span>₱<?= number_format($monthly_collected_fines, 2) ?></span>
                        <h2 class="title">Monthly Collected Fines</h2>
                    </div>

                    <div class="info">
                        <span>₱<?= number_format($monthly_uncollected_fines, 2)?></span>
                        <h2 class="title">Monthly Uncollected Fines</h2>
                    </div>

                    <div class="info">
                        <span><?= htmlspecialchars($pending_users_count) ?></span>
                        <h2 class="title">Pending User Requests </h2>
                    </div>

                </section>

                <section class="mx-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="top-info">
                        <h2 class="text-xl font-bold text-red-800 mb-3">Top 5 Most Borrowed Books</h2>
                        <ol class="list-decimal list-inside space-y-2">
                            <?php foreach ($top_5_books as $book): ?>
                                <li class="truncate break-words whitespace-normal">
                                    <span class="font-semibold"><?= htmlspecialchars($book['book_title']) ?></span>
                                    <span class="text-gray-600">(<?= $book['borrow_count'] ?> borrows)</span>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($top_5_books)): ?>
                                <li class="text-gray-500">No borrowing data available.</li>
                            <?php endif; ?>
                        </ol>
                    </div>

                    <div class="top-info">
                        <h2 class="text-xl font-bold text-red-800 mb-3">Top 5 Most Popular Categories</h2>
                        <ol class="list-decimal list-inside space-y-2">
                            <?php foreach ($top_5_categories as $category): ?>
                                <li class="truncate break-words whitespace-normal">
                                    <span class="font-semibold"><?= htmlspecialchars($category['category_name']) ?></span>
                                    <span class="text-gray-600">(<?= $category['borrow_count'] ?> borrows)</span>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($top_5_categories)): ?>
                                <li class="text-gray-500">No category data available.</li>
                            <?php endif; ?>
                        </ol>
                    </div>

                    <div class="top-info">
                        <h2 class="text-xl font-bold text-red-800 mb-3">Top 5 Most Active Borrowers</h2>
                        <ol class="list-decimal list-inside space-y-2">
                            <?php foreach ($top_5_borrowers as $borrower): ?>
                                <li class="truncate break-words whitespace-normal">
                                    <span
                                        class="font-semibold"><?= htmlspecialchars($borrower['fName'] . ' ' . $borrower['lName']) ?></span>
                                    <span class="text-gray-600">(<?= $borrower['borrow_count'] ?> borrows)</span>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($top_5_borrowers)): ?>
                                <li class="text-gray-500">No borrower data available.</li>
                            <?php endif; ?>
                        </ol>
                    </div>
                </section>


                <section class="section grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold text-red-800 mb-3">Monthly Borrowing Trend (Last 12 Mo.)</h2>
                        <canvas id="monthlyBorrowChart"></canvas>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold text-red-800 mb-3">Category Popularity</h2>
                        <div class="max-w-xs mx-auto">
                            <canvas id="categoryPopularityChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold text-red-800 mb-3">Borrow Status Breakdown</h2>
                        <div class="max-w-xs mx-auto">
                            <canvas id="borrowStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h2 class="text-xl font-bold text-red-800 mb-3">Fine Collection Over Time (Last 12 Mo.)</h2>
                        <canvas id="fineCollectionChart"></canvas>
                    </div>
                </section>


                <section class="section mt-8">
                    <h2 class="text-2xl font-bold text-red-800 mb-4">Pending Borrow Requests
                        (<?= htmlspecialchars($pending_borrow_requests_count) ?>)</h2>
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

                <section class="section mt-8">
                    <h2 class="text-2xl font-bold text-red-800 mb-4">Pending User Approvals
                        (<?= htmlspecialchars($pending_users_count) ?>)</h2>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Helper function to generate random colors for charts
            const getChartColors = (num) => {
                const colors = [
                    '#931C19', '#BD322F', '#610101', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6'
                ];
                let result = [];
                for (let i = 0; i < num; i++) {
                    result.push(colors[i % colors.length]);
                }
                return result;
            };

            // 1. Monthly Borrowing Trend (Bar Chart)
            const monthlyBorrowData = <?= json_encode($monthly_borrow_trend_data) ?>;
            const borrowCtx = document.getElementById('monthlyBorrowChart').getContext('2d');
            if (monthlyBorrowData.length > 0) {
                new Chart(borrowCtx, {
                    type: 'bar',
                    data: {
                        labels: monthlyBorrowData.map(d => d.month),
                        datasets: [{
                            label: 'Total Borrows',
                            data: monthlyBorrowData.map(d => d.total_borrows),
                            backgroundColor: '#931C19',
                            borderColor: '#610101',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }

            // 2. Category Popularity (Pie Chart)
            const categoryData = <?= json_encode($category_popularity_data) ?>;
            const categoryCtx = document.getElementById('categoryPopularityChart').getContext('2d');
            if (categoryData.length > 0) {
                new Chart(categoryCtx, {
                    type: 'pie',
                    data: {
                        labels: categoryData.map(d => d.category_name),
                        datasets: [{
                            label: 'Borrows',
                            data: categoryData.map(d => d.borrow_count),
                            backgroundColor: getChartColors(categoryData.length),
                        }]
                    },
                    options: { responsive: true, }
                });
            }

            // 3. Borrow Status Breakdown (Donut Chart)
            const statusData = <?= json_encode($borrow_status_breakdown_data) ?>;
            const statusCtx = document.getElementById('borrowStatusChart').getContext('2d');
            if (statusData.length > 0) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusData.map(d => d.status_label),
                        datasets: [{
                            label: 'Count',
                            data: statusData.map(d => d.status_count),
                            backgroundColor: getChartColors(statusData.length),
                        }]
                    },
                    options: { responsive: true, }
                });
            }

            // 4. Fine Collection Over Time (Line Chart)
            const finesData = <?= json_encode($monthly_fines_trend_data) ?>;
            const finesCtx = document.getElementById('fineCollectionChart').getContext('2d');
            if (finesData.length > 0) {
                new Chart(finesCtx, {
                    type: 'line',
                    data: {
                        labels: finesData.map(d => d.month),
                        datasets: [{
                            label: 'Fines Collected (₱)',
                            data: finesData.map(d => d.total_fines),
                            backgroundColor: 'rgba(245, 158, 11, 0.2)',
                            borderColor: 'rgba(245, 158, 11, 1)',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>