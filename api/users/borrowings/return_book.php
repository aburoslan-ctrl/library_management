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

if (isset($_POST['id'])) {

    $borrow_id = cleanme($_POST['id']);

    if (input_is_invalid($borrow_id) || !is_numeric($borrow_id)) {
        respondBadRequest("A valid borrow ID is required.");
    }

    $borrow_id = (int)$borrow_id;
    if ($borrow_id < 1) {
        respondBadRequest("A valid borrow ID is required.");
    }

    /* Check borrow record */
    $check = $connect->prepare("SELECT book_id, status FROM borrowings WHERE id = ?");
    $check->bind_param("i", $borrow_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        respondBadRequest("Borrow record not found.");
    }

    $borrow = $result->fetch_assoc();

    if ($borrow['status'] === 'returned') {
        respondBadRequest("Book already returned.");
    }

    /* Update borrow record */
    $update = $connect->prepare("
        UPDATE borrowings
        SET status = 'returned', return_date = CURDATE()
        WHERE id = ?
    ");

    $update->bind_param("i", $borrow_id);

    if ($update->execute()) {

        /* Increase book copies */
        $bookUpdate = $connect->prepare("
            UPDATE books
            SET available_copies = available_copies + 1
            WHERE id = ?
        ");

        $bookUpdate->bind_param("i", $borrow['book_id']);
        $bookUpdate->execute();

        respondOK([], "Book returned successfully.");

    } else {
        respondBadRequest("Failed to return book.");
    }

} else {
    respondBadRequest("Borrow ID is required.");
}
?>
