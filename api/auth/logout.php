<?php
$method = "POST";
$cache  = "no-cache";
include "../../head.php";

/* Validate token */
$datasentin = ValidateAPITokenSentIN();
$user_id    = $datasentin->usertoken;



if (input_is_invalid($user_id) || !is_numeric($user_id) || (int)$user_id < 1) {
    respondBadRequest("Invalid user session.");
} else {

    /* Delete token */
    $delete = $connect->prepare("DELETE FROM users WHERE id = ?");
    $delete->bind_param("i", $user_id);

    if ($delete->execute()) {

        respondOK([], "Logout successful.");

    } else {

        respondBadRequest("Logout failed. Please try again.");

    }
}
?>
