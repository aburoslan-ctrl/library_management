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
        $full_name = preg_replace('/\s+/', ' ', trim($full_name));
        $phone = preg_replace('/[()\s-]+/', '', trim($phone));

        if (strlen($full_name) < 2 || strlen($full_name) > 100) {
            respondBadRequest("Member full name must be between 2 and 100 characters.");
        } else if (!preg_match("/^[A-Za-z .'-]+$/", $full_name) || isStringHasEmojis($full_name)) {
            respondBadRequest("Member full name contains invalid characters.");
        } else if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
            respondBadRequest("Phone number must be 7 to 15 digits.");
        }

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
