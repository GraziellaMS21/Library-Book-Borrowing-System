<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../app/views/borrower/login.php");
    exit;
}

// ---
// NOTE: Add a link to this page (reportsSection.php) in your dashboardHeader.php file!
// e.g., <a href="reportsSection.php"><li>Reports</li></a>
// ---

require_once(__DIR__ . '/../../models/manageBook.php');
require_once(__DIR__ . '/../../models/manageUsers.php');
require_once(__DIR__ . '/../../models/manageBorrowDetails.php');
require_once(__DIR__ . '/../../models/manageCategory.php'); // Needed for filters

// Initialize models
$bookModel = new Book();
$userModel = new User();
$borrowModel = new BorrowDetails();
$categoryModel = new Category();

// --- 1. GET FILTER VALUES ---
$filters = [
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
    'category' => $_GET['category'] ?? null,
    'user_type' => $_GET['user_type'] ?? null,
    'status' => $_GET['status'] ?? null,
];

// --- 2. FETCH DATA FOR FILTERS AND TABLE ---
$categories = $categoryModel->viewCategory();
$user_types = $userModel->fetchUserTypes();

// Fetch the main table data using the new filterable function
$borrow_history = $borrowModel->getFilteredBorrowHistory($filters);

// --- 3. FETCH DATA FOR CHARTS (For this version, charts are overall stats) ---
$category_popularity_data = $bookModel->getTopPopularCategories(10); // Show more on reports
$monthly_fines_data = $borrowModel->getMonthlyFinesTrend();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Section</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <link rel="stylesheet" href="../../../public/assets/css/admin.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="h-screen w-screen flex">

    <?php require_once(__DIR__ . '/../shared/dashboardHeader.php'); ?>

    <div class="flex flex-col w-10/12">
        <nav class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Reports</h1>
            <div class="account flex items-center">
                <div class="bg-white rounded-full flex items-center justify-center h-8 w-8 px-4 mx-4">
                    <i class="fa-solid fa-user" style="color: #bd322f;"></i>
                </div>
                <h2 class="text-lg font-bold">
                    <?= $userDashboard["fName"] . " " . $userDashboard["lName"] ?>
                </h2>
            </div>
        </nav>
        <main>
            <div class="container">

                <!-- 1. FILTER SECTION -->
                <section class="section mb-8 bg-white p-4 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Generate Report</h2>
                    <form action="reportsSection.php" method="GET"
                        class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="start_date" id="start_date"
                                value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category" id="category"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['categoryID'] ?>" <?= ($filters['category'] == $cat['categoryID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="user_type" class="block text-sm font-medium text-gray-700">User Type</label>
                            <select name="user_type" id="user_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All</option>
                                <?php foreach ($user_types as $type): ?>
                                    <option value="<?= $type['userTypeID'] ?>"
                                        <?= ($filters['user_type'] == $type['userTypeID']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All</option>
                                <option value="Pending" <?= ($filters['status'] == 'Pending') ? 'selected' : '' ?>>Pending
                                </option>
                                <option value="Approved" <?= ($filters['status'] == 'Approved') ? 'selected' : '' ?>>
                                    Approved</option>
                                <option value="Borrowed" <?= ($filters['status'] == 'Borrowed') ? 'selected' : '' ?>>
                                    Borrowed</option>
                                <option value="Overdue" <?= ($filters['status'] == 'Overdue') ? 'selected' : '' ?>>Overdue
                                </option>
                                <option value="Returned" <?= ($filters['status'] == 'Returned') ? 'selected' : '' ?>>
                                    Returned</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent bg-red-800 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2">
                                Generate
                            </button>
                        </div>
                    </form>
                </section>

                <!-- 2. DETAILED CHARTS (Overall Stats) -->
                <section class="section mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Borrowing Frequency by Category</h3>
                        <canvas id="categoryReportChart"></canvas>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Monthly Fines Trend (Last 12 Months)</h3>
                        <canvas id="finesReportChart"></canvas>
                    </div>
                </section>

                <!-- 3. DATA TABLES -->
                <section class="section">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-gray-800">Borrowing History (<?= count($borrow_history) ?>
                            results)</h2>
                        <?php
                        // Build a query string (e.g., "start_date=...&category=...")
// This passes all your active filters to the export scripts.
                        $query_string = http_build_query($filters);
                        ?>
                        <div>
                            <a href="export_csv.php?<?= $query_string ?>"
                                class="actionBtn bg-green-700 hover:bg-green-800 text-white mr-2">
                                Export to Excel (CSV)
                            </a>

                            <a href="export_pdf.php?<?= $query_string ?>" target="_blank"
                                class="actionBtn bg-red-700 hover:bg-red-800 text-white">
                                Download PDF
                            </a>
                        </div>
                    </div>
                    <div class="view bg-white p-4 rounded-lg shadow-md">
                        <table>
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Book Title</th>
                                    <th>Category</th>
                                    <th>Request Date</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                    <th>Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($borrow_history)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-gray-500">
                                            No records found matching your criteria.
                                        </td>
                                    </tr>
                                <?php else:
                                    foreach ($borrow_history as $detail):
                                        $fullName = htmlspecialchars($detail["lName"] . ", " . $detail["fName"]);
                                        $status_label = '';
                                        $status_color = '';
                                        if ($detail['borrow_request_status'] == 'Pending') {
                                            $status_label = 'Pending';
                                            $status_color = 'bg-yellow-200 text-yellow-800';
                                        } elseif ($detail['borrow_status'] == 'Borrowed' && $detail['expected_return_date'] < date('Y-m-d')) {
                                            $status_label = 'Overdue';
                                            $status_color = 'bg-red-200 text-red-800';
                                        } elseif ($detail['borrow_status'] == 'Borrowed') {
                                            $status_label = 'Borrowed';
                                            $status_color = 'bg-blue-200 text-blue-800';
                                        } elseif ($detail['borrow_status'] == 'Returned') {
                                            $status_label = 'Returned';
                                            $status_color = 'bg-green-200 text-green-800';
                                        } elseif ($detail['borrow_request_status'] == 'Approved') {
                                            $status_label = 'Approved';
                                            $status_color = 'bg-indigo-200 text-indigo-800';
                                        } else {
                                            $status_label = ucfirst(strtolower($detail['borrow_request_status']));
                                            $status_color = 'bg-gray-200 text-gray-800';
                                        }
                                        ?>
                                        <tr>
                                            <td><?= $fullName ?></td>
                                            <td><?= htmlspecialchars($detail['book_title']) ?></td>
                                            <td><?= htmlspecialchars($detail['category_name']) ?></td>
                                            <td><?= htmlspecialchars($detail['request_date']) ?></td>
                                            <td><?= htmlspecialchars($detail['return_date'] ?? 'N/A') ?></td>
                                            <td><span
                                                    class="px-2 py-1 rounded-full text-xs font-medium <?= $status_color ?>"><?= $status_label ?></span>
                                            </td>
                                            <td>$<?= htmlspecialchars(number_format($detail['fine_amount'], 2)) ?></td>
                                        </tr>
                                    <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <script>
        // Helper function
        function prepareChartData(data, labelKey, dataKey) {
            const labels = [];
            const values = [];
            data.forEach(item => {
                labels.push(item[labelKey]);
                values.push(item[dataKey]);
            });
            return { labels, values };
        }

        // 1. Category Popularity (Bar)
        const categoryData = <?= json_encode($category_popularity_data) ?>;
        const categoryChart = prepareChartData(categoryData, 'category_name', 'borrow_count');

        new Chart(document.getElementById('categoryReportChart'), {
            type: 'bar',
            data: {
                labels: categoryChart.labels,
                datasets: [{
                    label: 'Total Borrows',
                    data: categoryChart.values,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // Horizontal bar chart
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } }
            }
        });

        // 2. Monthly Fines Trend (Line)
        const monthlyFinesData = <?= json_encode($monthly_fines_data) ?>;
        const monthlyFines = prepareChartData(monthlyFinesData, 'month', 'total_fines');

        new Chart(document.getElementById('finesReportChart'), {
            type: 'line',
            data: {
                labels: monthlyFines.labels,
                datasets: [{
                    label: 'Fines Collected ($)',
                    data: monthlyFines.values,
                    fill: false,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    tension: 0.1
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });
    </script>

</body>

</html>