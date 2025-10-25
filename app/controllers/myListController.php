<?php
session_start();

// Initialize the list if it doesn't exist
if (!isset($_SESSION['my_borrow_list'])) {
    $_SESSION['my_borrow_list'] = [];
}

$action = $_GET['action'] ?? null;
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && $action === 'add') {
    $bookID = (int) ($_POST['bookID'] ?? 0);
    $title = trim(htmlspecialchars($_POST['title'] ?? ''));
    $author = trim(htmlspecialchars($_POST['author'] ?? ''));
    $condition = trim(htmlspecialchars($_POST['condition'] ?? ''));
    $cover_dir = trim(htmlspecialchars($_POST['cover'] ?? ''));
    $copies_available = (int) ($_POST['copies'] ?? 0);
    
    // Default requested copies is 1 for students/guests, or what's available for staff (will be clamped in confirmation)
    $copies_requested = 1; 

    if ($bookID > 0 && $copies_available > 0) {
        if (!isset($_SESSION['my_borrow_list'][$bookID])) {
            $_SESSION['my_borrow_list'][$bookID] = [
                'bookID' => $bookID,
                'title' => $title,
                'author' => $author,
                'condition' => $condition,
                'cover_dir' => $cover_dir,
                'copies_available' => $copies_available,
                'copies_requested' => $copies_requested,
            ];
            echo json_encode([
                'success' => true, 
                'message' => 'Book added to list.',
                'total_items' => count($_SESSION['my_borrow_list'])
            ]);
            exit;
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Book is already in your list.'
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid book data or not available for loan.'
        ]);
        exit;
    }
}

// NOTE: The 'remove' action is handled directly in myList.php via GET request for simplicity.

echo json_encode([
    'success' => false, 
    'message' => 'Invalid request or action.'
]);