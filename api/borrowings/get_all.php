<?php

$method = "GET";
$cache  = "no-cache";
include "../../head.php";

/* Validate token */
$datasentin = ValidateAPITokenSentIN();
$user_id = $datasentin->usertoken;

if (!isset($user_id) || input_is_invalid($user_id) || !is_numeric($user_id)) {
    respondUnauthorized();
}

/* Fetch borrow records */
$stmt = $connect->prepare("
    SELECT
        b.id,
        m.full_name AS member_name,
        bk.title AS book_title,
        b.borrow_date,
        b.return_date,
        b.status
    FROM borrowings b
    JOIN members m ON m.id = b.member_id
    JOIN books bk ON bk.id = b.book_id
    ORDER BY b.created_at DESC
");

$stmt->execute();
$result = $stmt->get_result();

$borrowings = [];

while ($row = $result->fetch_assoc()) {
    $borrowings[] = $row;
}

respondOK(
    [
        "borrowings" => $borrowings,
        "total" => count($borrowings)
    ],
    "Borrow records fetched successfully."
);

$stmt->close();
?>