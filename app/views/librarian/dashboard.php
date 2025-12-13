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
$total_borrowed_books = $borrowObj->countTotalBorrowedBooks();
$total_pick_up = $borrowObj->countTotalBooksForPickUp();
$pending_borrow_requests_count = $borrowObj->countPendingRequests();
$overdue_book_count = $borrowObj->countOverdueBooks();
$total_borrowers = $userObj->countTotalActiveBorrowers();
$monthly_collected_fines = $borrowObj->sumMonthlyCollectedFines();
$collected_fines_7_days = $borrowObj->getCollectedFinesLast7Days();


$books_due_today = $borrowObj->getBooksDueToday();
$books_due_today_count = count($books_due_today);

// --- 2. DATA FOR TOP 5 LISTS ---
$top_5_books = $borrowObj->getTopBorrowedBooks(5);
$top_5_categories = $bookObj->getTopPopularCategories(5);
$top_5_borrowers = $borrowObj->getTopActiveBorrowers(5);
// --- 3. DATA FOR NEW CHARTS ---
$daily_activity_data = $borrowObj->getDailyBorrowingActivity();
$borrow_return_data = $borrowObj->getMonthlyBorrowReturnStats();
$user_reg_trend_data = $userObj->getUserRegistrationTrend();
$month = date('F');
// --- 4. DATA FOR ACTIVITY TABLES ---
$pending_users = $userObj->viewUser("", "", "pending");
$pending_users_count = count($pending_users);


$borrow_details = $borrowObj->viewBorrowDetails('', 'pending');

$userDashboard = $userObj->fetchUserName($_SESSION["user_id"]);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/admin.css" />
    <script src="../../../public/assets/js/chart.js"></script>
</head>

<body>
    <div class="w-full h-screen flex flex-col">
        <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>
        <main class="overflow-y-auto">
            <h2 class="flex items-center gap-2 m-8 text-4xl font-bold text-gray-800">
                Welcome,
                <span class="text-red-900">
                    <?php
                    if (isset($userDashboard)) {
                        echo htmlspecialchars($userDashboard["fName"] . " " . $userDashboard["lName"]);
                    } else {
                        echo "Admin User";
                    }
                    ?>
                </span>
                !
            </h2>

            <section id="dashboardSection" class="section dashboardSection grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="info p-4 flex items-start rounded-2xl shadow-md">
                    <div class="text-5xl text-white mr-4">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="flex w-full flex-col items-end text-white">
                        <span class="text-2xl font-semibold"><?= htmlspecialchars($total_book_copies) ?></span>
                        <h2 class="title text-sm text-gray-300">Total Available Book Copies</h2>
                    </div>
                </div>

                <div class="info p-4 flex items-start rounded-2xl shadow-md">
                    <div class="text-5xl text-white mr-4">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="w-full flex flex-col items-end text-white">
                        <span class="text-2xl font-semibold"><?= htmlspecialchars($total_borrowers) ?></span>
                        <h2 class="title text-sm text-gray-300">Total Active Borrowers</h2>
                    </div>
                </div>

                <div class="info p-4 flex items-start rounded-2xl shadow-md">
                    <div class="text-5xl text-white mr-4">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="w-full flex flex-col items-end text-white">
                        <span class="text-2xl font-semibold"><?= htmlspecialchars($pending_users_count) ?></span>
                        <h2 class="title text-sm text-gray-300">Pending User Requests</h2>
                    </div>
                </div>

                <div class="info p-4 flex items-start rounded-2xl shadow-md">
                    <div class="text-5xl text-white mr-4">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="w-full flex flex-col items-end text-white">
                        <span
                            class="text-2xl font-semibold"><?= htmlspecialchars($pending_borrow_requests_count) ?></span>
                        <h2 class="title text-sm text-gray-300">Pending Borrow Requests</h2>
                    </div>
                </div>

                <div class="info p-4 flex items-start rounded-2xl shadow-md">
                    <div class="text-5xl text-white mr-4">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="w-full flex flex-col items-end text-white">
                        <span class="text-2xl font-semibold"><?= htmlspecialchars($total_pick_up) ?></span>
                        <h2 class="title text-sm text-gray-300">Total Books For Pickup</h2>
                    </div>
                </div>

                <div class="info p-4 flex items-start rounded-2xl shadow-md">
                    <div class="text-5xl text-white mr-4">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <div class="w-full flex flex-col items-end text-white">
                        <span class="text-2xl font-semibold"><?= htmlspecialchars($total_borrowed_books) ?></span>
                        <h2 class="title text-sm text-gray-300">Total Borrowed Books</h2>
                    </div>
                </div>

                <div class="info p-4 flex items-start rounded-2xl shadow-md">
                    <div class="text-5xl text-white mr-4">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="w-full flex flex-col items-end text-white">
                        <span class="text-2xl font-semibold"><?= htmlspecialchars($overdue_book_count) ?></span>
                        <h2 class="title text-sm text-gray-300">Overdue Books</h2>
                    </div>
                </div>

                <div class="info p-4 flex items-start rounded-2xl shadow-md">
                    <div class="text-5xl text-white mr-4">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="w-full flex flex-col items-end text-white">
                        <span class="text-2xl font-semibold">₱<?= number_format($monthly_collected_fines, 2) ?></span>
                        <h2 class="title text-sm text-gray-300">Monthly Collected Fines</h2>
                    </div>
                </div>
            </section>


            <h2 class="font-extrabold text-3xl pl-8 pt-4 text-red-900">POPULAR LIBRARY INSIGHTS</h2>
            <section class="grid grid-cols-1 p-8 lg:grid-cols-3 gap-6">
                <div class="info-section min-h-[250px]">
                    <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">Top 5 Most Active Borrowers
                    </h2>
                    <div class="flex justify-center items-center">
                        <?php if (empty($top_5_borrowers)): ?>
                            <p class="text-gray-500">No borrower data available.</p>
                        <?php else: ?>
                            <canvas id="topBorrowersChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-section min-h-[250px]">
                    <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">Top 5 Most Borrowed Books
                    </h2>
                    <div class="flex justify-center items-center">
                        <?php if (empty($top_5_books)): ?>
                            <p class="text-gray-500">No borrowing data available.</p>
                        <?php else: ?>
                            <canvas id="topBooksChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-section min-h-[250px]">
                    <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">Top 5 Most Popular Categories
                    </h2>
                    <div class="flex justify-center items-center">
                        <?php if (empty($top_5_categories)): ?>
                            <p class="text-gray-500">No category data available.</p>
                        <?php else: ?>
                            <canvas id="topCategoriesChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

            </section>


            <h2 class="font-extrabold text-3xl pl-8 pt-4 text-red-900">LIBRARY ACTIVITY OVERVIEW</h2>
            <section class="grid grid-cols-1 p-8 lg:grid-cols-2 gap-6">
                <div class="info-section min-h-[250px]">
                    <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">Daily Borrowing Activity
                        (Last 7 Days)
                    </h2>
                    <div class="flex justify-center items-center">
                        <?php if (empty($daily_activity_data)): ?>
                            <p class="text-gray-500">No activity in the last 7 days.</p>
                        <?php else: ?>
                            <canvas id="dailyActivityChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-section min-h-[250px]">
                    <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">Borrowed and Returned Books
                        (<?= $month ?>)
                    </h2>
                    <div class="flex justify-center items-center">
                        <?php if (empty($borrow_return_data) || ($borrow_return_data[0]['count'] == 0 && $borrow_return_data[1]['count'] == 0)): ?>
                            <p class="text-gray-500">No borrow/return data this month.</p>
                        <?php else: ?>
                            <div class="max-w-xs mx-auto w-full">
                                <canvas id="borrowReturnChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <div class="info-section min-h-[250px]">
                    <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">New User Registration (Last
                        30 Days)
                    </h2>
                    <div class="flex justify-center items-center">
                        <?php if (empty($user_reg_trend_data)): ?>
                            <p class="text-gray-500">No new users in the last 30 days.</p>
                        <?php else: ?>
                            <canvas id="userRegTrendChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="info-section min-h-[250px]">
                    <h2 class="text-xl font-bold text-red-900 text-center mb-3 w-full">Collected Fines (Last 7
                        Days)
                    </h2>
                    <div class="flex justify-center items-center">
                        <?php if (empty($collected_fines_7_days)): ?>
                            <p class="text-gray-500">No fines collected in the last 7 days.</p>
                        <?php else: ?>
                            <canvas id="collectedFineChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </section>


            <h2 class="font-extrabold text-3xl pl-8 pt-4 text-red-900">PENDING ACTIVITIES</h2>
            <section class="section mt-8">
                <h2 class="text-2xl font-bold mb-4">Pending Borrow Requests
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
                            <?php if (empty($borrow_details)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-gray-500">
                                        No pending borrow requests found.
                                    </td>
                                </tr>
                            <?php else:
                                $no = 1;
                                foreach ($borrow_details as $detail) {
                                    $fullName = htmlspecialchars($detail["lName"] . ', ' . $detail["fName"]);
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
                <h2 class="text-2xl font-bold mb-4">Pending User Approvals
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

            // --- Top 5 Books Chart (Horizontal Bar) ---
            const topBooksData = <?= json_encode($top_5_books) ?>;
            if (topBooksData.length > 0) {
                const topBooksCtx = document.getElementById('topBooksChart').getContext('2d');
                new Chart(topBooksCtx, {
                    type: 'bar',
                    data: {
                        // Reverse labels and data to show Top 1 at the top
                        labels: topBooksData.map(d => d.book_title).reverse(),
                        datasets: [{
                            label: 'Borrows',
                            data: topBooksData.map(d => d.borrow_count).reverse(),
                            backgroundColor: '#931C19',
                        }]
                    },
                    options: {
                        indexAxis: 'y', // This makes it a horizontal bar chart
                        responsive: true,
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 } }
                        },
                        plugins: {
                            legend: { display: false } // No legend needed for a single dataset
                        }
                    }
                });
            }

            // --- Top 5 Categories Chart (Horizontal Bar) ---
            const topCategoriesData = <?= json_encode($top_5_categories) ?>;
            if (topCategoriesData.length > 0) {
                const topCategoriesCtx = document.getElementById('topCategoriesChart').getContext('2d');
                new Chart(topCategoriesCtx, {
                    type: 'bar',
                    data: {
                        // Reverse labels and data
                        labels: topCategoriesData.map(d => d.category_name).reverse(),
                        datasets: [{
                            label: 'Borrows',
                            data: topCategoriesData.map(d => d.borrow_count).reverse(),
                            backgroundColor: '#BD322F',
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 } }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            // --- Top 5 Borrowers Chart (Horizontal Bar) ---
            const topBorrowersData = <?= json_encode($top_5_borrowers) ?>;
            if (topBorrowersData.length > 0) {
                const topBorrowersCtx = document.getElementById('topBorrowersChart').getContext('2d');
                new Chart(topBorrowersCtx, {
                    type: 'bar',
                    data: {
                        // Reverse labels and data
                        labels: topBorrowersData.map(d => d.fName + ' ' + d.lName).reverse(),
                        datasets: [{
                            label: 'Borrows',
                            data: topBorrowersData.map(d => d.borrow_count).reverse(),
                            backgroundColor: '#931C19',
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 } }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            // --- NEW: Daily Borrowing Activity (Bar Chart) ---
            const dailyActivityData = <?= json_encode($daily_activity_data) ?>;

            if (dailyActivityData.length > 0) {
                // Generate last 7 dates
                const today = new Date();
                const last7Days = [];
                for (let i = 6; i >= 0; i--) {
                    const d = new Date(today);
                    d.setDate(today.getDate() - i);
                    last7Days.push(d.toISOString().split('T')[0]);
                }

                // Convert your PHP data into an easy lookup
                const borrowMap = {};
                dailyActivityData.forEach(d => {
                    borrowMap[d.borrow_date] = parseInt(d.total_borrows);
                });

                // Create data including 0s for missing dates
                const filledData = last7Days.map(date => ({
                    date: date,
                    total: borrowMap[date] || 0
                }));

                // Draw the chart
                const dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
                new Chart(dailyCtx, {
                    type: 'bar',
                    data: {
                        labels: filledData.map(d => d.date),
                        datasets: [{
                            label: 'Total Borrows',
                            data: filledData.map(d => d.total),
                            backgroundColor: '#610101',
                            borderColor: '#931C19',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            // --- NEW: Books Borrowed vs. Returned (Doughnut Chart) ---
            const borrowReturnData = <?= json_encode($borrow_return_data) ?>;
            if (borrowReturnData.length > 0 && (borrowReturnData[0].count > 0 || borrowReturnData[1].count > 0)) {
                const borrowReturnCtx = document.getElementById('borrowReturnChart').getContext('2d');
                new Chart(borrowReturnCtx, {
                    type: 'doughnut',
                    data: {
                        labels: borrowReturnData.map(d => d.status),
                        datasets: [{
                            label: 'Count',
                            data: borrowReturnData.map(d => d.count),
                            backgroundColor: ['#931C19', '#610101'], // Red for Borrowed, Yellow for Returned
                        }]
                    },
                    options: { responsive: true }
                });
            }

            // --- Collected Fines (Last 7 Days) ---
            // **** CORRECTION 3: Variable name fixed from $collected_fines_last_7_days ****
            const fines7Data = <?= json_encode($collected_fines_7_days ?? []) ?>;

            // Make sure it's an array
            const finesDataArray = Array.isArray(fines7Data) ? fines7Data : [];

            // Generate last 7 days
            const finesToday = new Date(); // Use a different var name to avoid conflict in scope if any
            const finesLast7Days = [];
            for (let i = 6; i >= 0; i--) {
                const d = new Date(finesToday);
                d.setDate(finesToday.getDate() - i);
                finesLast7Days.push(d.toISOString().split('T')[0]);
            }

            // Map fines data by date
            const finesMap = {};
            finesDataArray.forEach(d => {
                if (d.fine_date && d.total_fines !== undefined) {
                    finesMap[d.fine_date] = parseFloat(d.total_fines);
                }
            });

            // Fill missing days with 0
            const filledFinesData = finesLast7Days.map(date => ({
                date: date,
                total: finesMap[date] || 0
            }));

            // Draw chart
            const finesChartEl = document.getElementById('collectedFineChart');
            // Only draw if there's data to show
            if (finesChartEl && finesDataArray.length > 0) {
                const fines7Ctx = finesChartEl.getContext('2d');
                new Chart(fines7Ctx, {
                    type: 'bar',
                    data: {
                        labels: filledFinesData.map(d => d.date),
                        datasets: [{
                            label: 'Collected Fines (₱)',
                            data: filledFinesData.map(d => d.total),
                            backgroundColor: '#931C19',
                            borderColor: '#600705',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }



            // --- NEW: User Registration Trend (Line Chart) ---
            const userRegData = <?= json_encode($user_reg_trend_data) ?>;
            if (userRegData.length > 0) {
                const userRegCtx = document.getElementById('userRegTrendChart').getContext('2d');
                new Chart(userRegCtx, {
                    type: 'line',
                    data: {
                        labels: userRegData.map(d => d.reg_date),
                        datasets: [{
                            label: 'New Users',
                            data: userRegData.map(d => d.new_users),
                            backgroundColor: '#931b19b4',
                            borderColor: '#7b0907ff',
                            borderWidth: 2,
                            tension: 0.1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>