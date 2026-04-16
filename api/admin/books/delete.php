<?php
$method = "POST";
$cache  = "no-cache";
include "../../../head.php";

// Validate token
$user = ValidateAPITokenSentIN();
$user_id = $user->usertoken;

if (!isset($user_id) || input_is_invalid($user_id) || !is_numeric($user_id)) {
    respondUnauthorized();
    exit;
}

// Admin only
$roleCheck = $connect->prepare("SELECT role FROM users WHERE id = ?");
$roleCheck->bind_param("i", $user_id);
$roleCheck->execute();
$roleResult = $roleCheck->get_result()->fetch_assoc();

if (!$roleResult || $roleResult['role'] !== 'admin') {
    respondForbiddenAuthorized("Admin access required.");
    exit;
}

if (isset($_POST['id'])) {

    $book_id = cleanme($_POST['id']);

    if (input_is_invalid($book_id) || !is_numeric($book_id)) {
        respondBadRequest("A valid book ID is required.");
    }
    else {

        $book_id = (int)$book_id;
        if ($book_id < 1) {
            respondBadRequest("A valid book ID is required.");
        }

        /* Check if book exists */
        $check = $connect->prepare("SELECT id FROM books WHERE id = ?");
        $check->bind_param("i", $book_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            respondBadRequest("Book not found.");
            exit;
        }
        else {

            /* Delete related borrowings first */
            $deleteBorrowings = $connect->prepare("DELETE FROM borrowings WHERE book_id = ?");
            $deleteBorrowings->bind_param("i", $book_id);
            $deleteBorrowings->execute();

            $delete = $connect->prepare("DELETE FROM books WHERE id = ?");
            $delete->bind_param("i", $book_id);

            if ($delete->execute()) {
                respondOK([], "Book deleted successfully.");
            } else {
                respondBadRequest("Failed to delete book. Please try again.");
            }
        }
    }

} else {
    respondBadRequest("Invalid request. Book ID is required.");
}
?>
