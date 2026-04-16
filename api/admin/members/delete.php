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

if (isset($_POST['id'])) {

    $member_id = cleanme($_POST['id']);

    if (input_is_invalid($member_id) || !is_numeric($member_id)) {
        respondBadRequest("A valid member ID is required.");
        exit;
    }

    $member_id = (int)$member_id;

    // Check if member exists
    $check = $connect->prepare("SELECT id FROM members WHERE id = ?");
    $check->bind_param("i", $member_id);
    $check->execute();

    if ($check->get_result()->num_rows === 0) {
        respondBadRequest("Member not found.");
        exit;
    }

    // Delete related borrowings first
    $deleteBorrowings = $connect->prepare("DELETE FROM borrowings WHERE member_id = ?");
    $deleteBorrowings->bind_param("i", $member_id);
    $deleteBorrowings->execute();

    // Delete member
    $delete = $connect->prepare("DELETE FROM members WHERE id = ?");
    $delete->bind_param("i", $member_id);
    $delete->execute();

    if ($delete->affected_rows > 0) {
        respondOK([], "Member deleted successfully.");
    } else {
        respondBadRequest("No changes made or delete failed.");
    }

} else {
    respondBadRequest("Invalid request. Member ID is required.");
}

?>
