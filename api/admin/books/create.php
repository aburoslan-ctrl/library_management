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

if (isset($_POST['title']) && isset($_POST['author']) && isset($_POST['total_copies'])) {

    $title  = cleanme($_POST['title']);
    $author = cleanme($_POST['author']);
    $copies = cleanme($_POST['total_copies']);

    if (input_is_invalid($title)) {
        respondBadRequest("Book title is required.");
    }
    else if (input_is_invalid($author)) {
        respondBadRequest("Author name is required.");
    }
    else if (input_is_invalid($copies) || !is_numeric($copies)) {
        respondBadRequest("Total copies must be a valid number.");
    }
    else {

        $title = preg_replace('/\s+/', ' ', trim($title));
        $author = preg_replace('/\s+/', ' ', trim($author));

        if (strlen($title) < 2 || strlen($title) > 150) {
            respondBadRequest("Book title must be between 2 and 150 characters.");
        } else if (strlen($author) < 2 || strlen($author) > 100) {
            respondBadRequest("Author name must be between 2 and 100 characters.");
        } else if (isStringHasEmojis($title) || isStringHasEmojis($author)) {
            respondBadRequest("Invalid characters in title or author.");
        } else if (!preg_match("/^[A-Za-z0-9 .,'\"-]+$/", $title)) {
            respondBadRequest("Book title contains invalid characters.");
        } else if (!preg_match("/^[A-Za-z0-9 .,'\"-]+$/", $author)) {
            respondBadRequest("Author name contains invalid characters.");
        }

        $copies = (int)$copies;
        if ($copies < 1 || $copies > 10000) {
            respondBadRequest("Total copies must be between 1 and 10000.");
        }

        /* Check if book already exists */
        $check = $connect->prepare("SELECT id FROM books WHERE title = ? AND author = ?");
        $check->bind_param("ss", $title, $author);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            respondBadRequest("This book already exists in the library.");
        }
        else {

            $insert = $connect->prepare("INSERT INTO books (title, author, total_copies, available_copies, created_at) VALUES (?, ?, ?, ?, NOW())");
            $insert->bind_param("ssii", $title, $author, $copies, $copies);

            if ($insert->execute()) {
                respondOK([], "Book added successfully.");
            } else {
                respondBadRequest("Failed to add book. Please try again.");
            }
        }
    }

} else {
    respondBadRequest("Invalid request. Title, author and total copies are required.");
}
?>
