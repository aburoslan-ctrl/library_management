<?php
$method = "POST";
$cache  = "no-cache";
include "../../head.php";

/* Validate token */
$user = ValidateAPITokenSentIN();


if (isset($_POST['id']) && isset($_POST['title']) && isset($_POST['author']) && isset($_POST['total_copies'])) {

    $book_id = cleanme($_POST['id']);
    $title   = cleanme($_POST['title']);
    $author  = cleanme($_POST['author']);
    $copies  = cleanme($_POST['total_copies']);

    if (input_is_invalid($book_id) || !is_numeric($book_id)) {
        respondBadRequest("A valid book ID is required.");
    } 
    else if (input_is_invalid($title)) {
        respondBadRequest("Book title cannot be empty.");
    } 
    else if (input_is_invalid($author)) {
        respondBadRequest("Author name cannot be empty.");
    } 
    else if (input_is_invalid($copies) || !is_numeric($copies)) {
        respondBadRequest("Total copies must be a valid number.");
    } 
    else {

        $book_id = (int)$book_id;
        if ($book_id < 1) {
            respondBadRequest("A valid book ID is required.");
        }

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

        $copies  = (int)$copies;
        if ($copies < 1 || $copies > 10000) {
            respondBadRequest("Total copies must be between 1 and 10000.");
        }

        /* Check if book exists */
        $check = $connect->prepare("SELECT id, total_copies, available_copies FROM books WHERE id = ?");
        $check->bind_param("i", $book_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            respondBadRequest("Book not found.");
            exit;
        } 
        else {
            $book = $result->fetch_assoc();
            $borrowed = (int)$book['total_copies'] - (int)$book['available_copies'];
            if ($copies < $borrowed) {
                respondBadRequest("Total copies cannot be less than borrowed copies.");
            }

            $update = $connect->prepare("UPDATE books SET title = ?, author = ?, total_copies = ? WHERE id = ?");
            $update->bind_param("ssii", $title, $author, $copies, $book_id);

            if ($update->execute()) {

                respondOK([], "Book updated successfully.");

            } else {

                respondBadRequest("Failed to update book. Please try again.");

            }
        }
    }

} 
else {

    respondBadRequest("Invalid request. Book ID, title, author and total copies are required.");

}
?>
