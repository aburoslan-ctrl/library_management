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

    $datasentin = ValidateAPITokenSentIN();
    $user_id    = $datasentin->usertoken;

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
        $copies  = (int)$copies;

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