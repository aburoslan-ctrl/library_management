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

if (isset($_POST['id'])) {

    $member_id = cleanme($_POST['id']);

    if (input_is_invalid($member_id) || !is_numeric($member_id)) {
        respondBadRequest("A valid member ID is required.");
    } 
    else {

        $member_id = (int)$member_id;
        if ($member_id < 1) {
            respondBadRequest("A valid member ID is required.");
        }

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

            $delete = $connect->prepare("DELETE FROM members WHERE id = ?");
            $delete->bind_param("i", $member_id);

            if ($delete->execute()) {
                respondOK([], "Member deleted successfully.");
            } else {
                respondBadRequest("Failed to delete member. Please try again.");
            }
        }
    }

} 
else {

    respondBadRequest("Invalid request. Member ID is required.");

}
?>
