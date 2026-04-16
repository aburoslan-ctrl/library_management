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

if (!isset($_POST['id'])) {
    respondBadRequest("Member ID is required.");
    exit;
}

$member_id = cleanme($_POST['id']);

if (input_is_invalid($member_id) || !is_numeric($member_id)) {
    respondBadRequest("A valid member ID is required.");
    exit;
}

$member_id = (int)$member_id;

// Fetch current member data
$curr = $connect->prepare("SELECT full_name, phone FROM members WHERE id = ?");
$curr->bind_param("i", $member_id);
$curr->execute();
$current = $curr->get_result()->fetch_assoc();

if (!$current) {
    respondBadRequest("Member not found.");
    exit;
}

if (!isset($_POST['full_name']) && !isset($_POST['phone'])) {
    respondBadRequest("Nothing to update. Provide full_name or phone.");
    exit;
}

$full_name = isset($_POST['full_name']) ? cleanme($_POST['full_name']) : $current['full_name'];
$phone     = isset($_POST['phone'])     ? cleanme($_POST['phone'])     : $current['phone'];

$full_name = preg_replace('/\s+/', ' ', trim($full_name));
$phone = preg_replace('/[()\s-]+/', '', trim($phone));

if (input_is_invalid($full_name) || input_is_invalid($phone)) {
    respondBadRequest("Fields cannot be empty.");
    exit;
}

if (strlen($full_name) < 2 || strlen($full_name) > 100) {
    respondBadRequest("Member full name must be between 2 and 100 characters.");
    exit;
} else if (!preg_match("/^[A-Za-z .'-]+$/", $full_name) || isStringHasEmojis($full_name)) {
    respondBadRequest("Member full name contains invalid characters.");
    exit;
} else if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
    respondBadRequest("Phone number must be 7 to 15 digits.");
    exit;
}

// Check for phone conflict with other members
$check = $connect->prepare("SELECT id FROM members WHERE phone = ? AND id != ?");
$check->bind_param("si", $phone, $member_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    respondBadRequest("Phone number is already taken by another member.");
    exit;
}

$stmt = $connect->prepare("UPDATE members SET full_name = ?, phone = ? WHERE id = ?");
$stmt->bind_param("ssi", $full_name, $phone, $member_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    respondOK([
        "member_id" => $member_id,
        "full_name" => $full_name,
        "phone"     => $phone
    ], "Member updated successfully.");
} else {
    respondBadRequest("No changes made or update failed.");
}

?>
