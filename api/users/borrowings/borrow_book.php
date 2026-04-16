<?php
$method = "POST";
$cache  = "no-cache";
include "../../../head.php";

/* Validate token */
$datasentin = ValidateAPITokenSentIN();
$user_id = $datasentin->usertoken;

if (!isset($user_id) || input_is_invalid($user_id) || !is_numeric($user_id)) {
    respondUnauthorized();
}

$user_id = (int)$user_id;

if (isset($_POST['member_id']) && isset($_POST['book_id'])) {

    $member_id = cleanme($_POST['member_id']);
    $book_id   = cleanme($_POST['book_id']);

    if (input_is_invalid($member_id) || !is_numeric($member_id)) {
        respondBadRequest("A valid member ID is required.");
    }
    else if (input_is_invalid($book_id) || !is_numeric($book_id)) {
        respondBadRequest("A valid book ID is required.");
    }
    else {

        $member_id = (int)$member_id;
        $book_id   = (int)$book_id;
        if ($member_id < 1) {
            respondBadRequest("A valid member ID is required.");
        }
        if ($book_id < 1) {
            respondBadRequest("A valid book ID is required.");
        }

        /* Check member */
        $memberCheck = $connect->prepare("SELECT id FROM members WHERE id = ?");
        $memberCheck->bind_param("i", $member_id);
        $memberCheck->execute();

        if ($memberCheck->get_result()->num_rows === 0) {
            respondBadRequest("Member not found.");
        }

        /* Check book */
        $bookCheck = $connect->prepare("SELECT available_copies FROM books WHERE id = ?");
        $bookCheck->bind_param("i", $book_id);
        $bookCheck->execute();
        $bookResult = $bookCheck->get_result();

        if ($bookResult->num_rows === 0) {
            respondBadRequest("Book not found.");
        }

        $book = $bookResult->fetch_assoc();

        if ($book['available_copies'] <= 0) {
            respondBadRequest("Book is not available.");
        }

        /* Insert borrowing record */
        $insert = $connect->prepare("
            INSERT INTO borrowings (member_id, book_id, borrow_date, status, created_at)
            VALUES (?, ?, CURDATE(), 'borrowed', NOW())
        ");

        $insert->bind_param("ii", $member_id, $book_id);

        if ($insert->execute()) {

            /* Reduce available copies */
            $update = $connect->prepare("
                UPDATE books
                SET available_copies = available_copies - 1
                WHERE id = ?
            ");

            $update->bind_param("i", $book_id);
            $update->execute();

            respondOK([], "Book borrowed successfully.");

        } else {
            respondBadRequest("Failed to borrow book.");
        }

    }

} else {
    respondBadRequest("Invalid request. Member ID and Book ID are required.");
}
?>
