<?php
$method = "POST";
include "../../head.php";



if (!isset($_POST['email'], $_POST['password'])) {
    respondBadRequest("Email and password required.");
}


$email    = strtolower(cleanme($_POST['email']));
$password = cleanme($_POST['password']);


if (input_is_invalid($email) || input_is_invalid($password)) {
    respondBadRequest("All fields are required.");
}

if (isStringHasEmojis($email)) {
    respondBadRequest("Invalid characters in email.");
}

if (strlen($email) > 254) {
    respondBadRequest("Email is too long.");
}

if (strlen($password) > 128) {
    respondBadRequest("Password is too long.");
}

/* Validate Email Format */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondBadRequest("Invalid email format.");
}

/* Check user */
$stmt = $connect->prepare("SELECT id, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    respondBadRequest("Invalid login credentials.");
}

$user = $result->fetch_assoc();

/* Verify password */
if (password_verify($password, $user['password'])) {
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $connect->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_hash, $user['id']);
        $update->execute();
    }
}
/* Backward compatibility for old plain-text passwords */
else if (hash_equals((string)$user['password'], (string)$password)) {
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    $update = $connect->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param("si", $new_hash, $user['id']);
    $update->execute();
}
else {
    respondBadRequest("Invalid login credentials.");
}

/* Generate Token */
$token = getTokenToSendAPI($user['id']);

respondOK([
    "access_token" => $token
], "Login successful.");
?>
