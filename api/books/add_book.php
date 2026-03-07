<?php
$method = "POST";
$cache  = "no-cache";
include "../../head.php";

/* Validate token */
$user = ValidateAPITokenSentIN();

if (isset($_POST['title']) && isset($_POST['author']) && isset($_POST['total_copies'])) {

    $title  = cleanme($_POST['title']);
    $author = cleanme($_POST['author']);
    $copies = cleanme($_POST['total_copies']);

    $datasentin = ValidateAPITokenSentIN();
    $user_id    = $datasentin->usertoken;

    /* Input validation */

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

        $copies = (int)$copies;

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