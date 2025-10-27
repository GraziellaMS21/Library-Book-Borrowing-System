<?php
// This is the page users are redirected to if their account status is 'Blocked'
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Blocked</title>
    <script src="../../../public/assets/js/tailwind.3.4.17.js"></script>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f8f8;
            font-family: sans-serif;
        }
    </style>
</head>
<body>
    <div class="bg-white p-10 rounded-xl shadow-2xl text-center max-w-lg mx-4">
        <svg class="mx-auto h-16 w-16 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M18.364 18.364L5.636 5.636" />
        </svg>
        <h1 class="text-3xl font-extrabold text-red-700 mt-4 mb-2">Account Blocked</h1>
        <p class="text-gray-600 mb-6">
            Your access to the system has been temporarily restricted by the administrator. 
            This is likely due to a violation of the library's terms and conditions.
        </p>
        <p class="text-gray-600 font-semibold">
            Please contact the library administrator for more information regarding your account status.
        </p>
        <div class="mt-8">
            <a href="login.php" class="bg-red-800 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors shadow-lg">
                Go to Login Page
            </a>
        </div>
    </div>
</body>
</html>
