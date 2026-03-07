<?php
$method = "POST";
$cache  = "no-cache";
include "../../head.php";

/* Validate token */
$datasentin = ValidateAPITokenSentIN();
$user_id = $datasentin->usertoken;

if (!isset($user_id) || input_is_invalid($user_id) || !is_numeric($user_id)) {
    respondUnauthorized();
}

$user_id = (int)$user_id;

/* Validate request fields */
if (isset($_POST['full_name']) && isset($_POST['phone'])) {

    $full_name = cleanme($_POST['full_name']);
    $phone     = cleanme($_POST['phone']);

    if (input_is_invalid($full_name)) {
        respondBadRequest("Member full name is required.");
    } 
    else if (input_is_invalid($phone)) {
        respondBadRequest("Phone number is required.");
    } 
    else {

        /* Check if member already exists */
        $check = $connect->prepare("SELECT id FROM members WHERE phone = ?");
        $check->bind_param("s", $phone);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            respondBadRequest("Member with this phone number already exists.");
        } 
        else {

            $insert = $connect->prepare("INSERT INTO members (full_name, phone, created_at) VALUES (?, ?, NOW())");
            $insert->bind_param("ss", $full_name, $phone);

            if ($insert->execute()) {

                respondOK([], "Member added successfully.");

            } 
            else {

                respondBadRequest("Failed to add member. Please try again.");

            }
        }
    }

} 
else {

    respondBadRequest("Invalid request. Full name and phone are required.");

}
?>