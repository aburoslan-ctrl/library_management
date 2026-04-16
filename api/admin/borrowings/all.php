<?php

$method = "GET";
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

if (!$stmt->execute()) {
    respondInternalError("DB execute failed: " . $stmt->error);
}
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
