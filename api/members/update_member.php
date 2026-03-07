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

if (isset($_POST['id']) && isset($_POST['full_name']) && isset($_POST['phone'])) {

    $member_id = cleanme($_POST['id']);
    $full_name = cleanme($_POST['full_name']);
    $phone     = cleanme($_POST['phone']);

    if (input_is_invalid($member_id) || !is_numeric($member_id)) {
        respondBadRequest("A valid member ID is required.");
    } 
    else if (input_is_invalid($full_name)) {
        respondBadRequest("Member full name cannot be empty.");
    } 
    else if (input_is_invalid($phone)) {
        respondBadRequest("Phone number cannot be empty.");
    } 
    else {

        $member_id = (int)$member_id;

        /* Check if member exists */
        $check = $connect->prepare("SELECT id FROM members WHERE id = ?");
        $check->bind_param("i", $member_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows === 0) {
            respondBadRequest("Member not found.");
            exit;
        } 
        else {

            /* Optional: check duplicate phone */
            $dup = $connect->prepare("SELECT id FROM members WHERE phone = ? AND id != ?");
            $dup->bind_param("si", $phone, $member_id);
            $dup->execute();

            if ($dup->get_result()->num_rows > 0) {
                respondBadRequest("Another member already uses this phone number.");
            } 
            else {

                $update = $connect->prepare("UPDATE members SET full_name = ?, phone = ? WHERE id = ?");
                $update->bind_param("ssi", $full_name, $phone, $member_id);

                if ($update->execute()) {
                    respondOK([], "Member updated successfully.");
                } else {
                    respondBadRequest("Failed to update member. Please try again.");
                }

            }
        }
    }

} 
else {
    respondBadRequest("Invalid request. Member ID, full name and phone are required.");
}
?>