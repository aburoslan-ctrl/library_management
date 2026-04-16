<?php

$method = "GET";
$cache  = "no-cache";
include "../../../head.php";

/* Validate token */
$datasentin = ValidateAPITokenSentIN();
$user_id = $datasentin->usertoken;

if (!isset($user_id) || input_is_invalid($user_id) || !is_numeric($user_id)) {
    respondUnauthorized();
}

$user_id = (int)$user_id;

/* Fetch books */
$stmt = $connect->prepare("
    SELECT
        id,
        title,
        author,
        total_copies,
        available_copies,
        created_at
    FROM books
    ORDER BY created_at DESC
");

$stmt->execute();
$result = $stmt->get_result();

$books = [];

while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

respondOK(
    [
        "books" => $books,
        "total" => count($books)
    ],
    "Books fetched successfully."
);

$stmt->close();
?>
