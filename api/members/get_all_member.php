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

$user_id = (int)$user_id;

/* Fetch members */
$stmt = $connect->prepare("
    SELECT
        id,
        full_name,
        phone,
        created_at
    FROM members
    ORDER BY created_at DESC
");

$stmt->execute();
$result = $stmt->get_result();

$members = [];

while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

respondOK(
    [
        "members" => $members,
        "total" => count($members)
    ],
    "Members fetched successfully."
);

$stmt->close();
?>