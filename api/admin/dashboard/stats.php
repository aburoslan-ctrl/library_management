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

$countFromQuery = function ($sql) use ($connect) {
    $res = $connect->query($sql);
    if (!$res) {
        respondInternalError("Query failed: " . $connect->error);
    }
    return (int)($res->fetch_assoc()['count'] ?? 0);
};

$users       = $countFromQuery("SELECT COUNT(*) as count FROM users");
$books       = $countFromQuery("SELECT COUNT(*) as count FROM books");
$members     = $countFromQuery("SELECT COUNT(*) as count FROM members");
$borrowings  = $countFromQuery("SELECT COUNT(*) as count FROM borrowings");
$borrowed    = $countFromQuery("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'");
$returned    = $countFromQuery("SELECT COUNT(*) as count FROM borrowings WHERE status = 'returned'");
$available   = $countFromQuery("SELECT SUM(available_copies) as count FROM books");

respondOK([
    "total_users"       => (int)$users,
    "total_books"       => (int)$books,
    "total_members"     => (int)$members,
    "total_borrowings"  => (int)$borrowings,
    "currently_borrowed" => (int)$borrowed,
    "total_returned"    => (int)$returned,
    "available_copies"  => (int)$available
], "Dashboard stats fetched successfully.");

?>
