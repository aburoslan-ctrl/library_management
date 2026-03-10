<?php
$method = "POST";
$cache  = "no-cache";
include "../../head.php";

/* Optional: Protect route if only admin can create new users */
$datasentin = ValidateAPITokenSentIN();
$user_id    = $datasentin->usertoken;



if (isset($_POST['full_name'], $_POST['email'], $_POST['password'])) {

    $full_name = cleanme($_POST['full_name']);
    $email    = strtolower(cleanme($_POST['email']));
    $password = cleanme($_POST['password']);

  

    /* Input validation */
    if (input_is_invalid($full_name) || input_is_invalid($email) || input_is_invalid($password)) {
        respondBadRequest("full_name, email, and password are required.");
    } 
    else if (strlen($full_name) < 2 || strlen($full_name) > 100) {
        respondBadRequest("Full name must be between 2 and 100 characters.");
    }
    else if (!preg_match("/^[A-Za-z .'-]+$/", $full_name)) {
        respondBadRequest("Full name contains invalid characters.");
    }
    else if (isStringHasEmojis($full_name) || isStringHasEmojis($email)) {
        respondBadRequest("Invalid characters in full_name or email.");
    }
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respondBadRequest("Invalid email format.");
    } 
    
    else if (strlen($password) < 6 || strlen($password) > 128) {
        respondBadRequest("Password must be between 6 and 128 characters.");
    }
    else if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        respondBadRequest("Password must contain at least one letter and one number.");
    } 
    else {

        /* Check if email or full_name already exists */
        $check = $connect->prepare("SELECT id FROM users WHERE email = ? OR full_name = ?");
        $check->bind_param("ss", $email, $full_name);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            respondBadRequest("Email or full_name already taken.");
        } 
        else {

            /* Hash password before storing */
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $role   = "admin"; // default role
            $insert = $connect->prepare("INSERT INTO users (full_name, email, password,  created_at) VALUES (?, ?, ?, NOW())");
            $insert->bind_param("sss", $full_name, $email, $hashed_password);

            if ($insert->execute()) {
                respondOK([], "Registration successful.");
            } 
            else {
                respondBadRequest("Registration failed. Please try again.");
            }
        }
    }

} 
else {
    respondBadRequest("Invalid request. full_name, email, and password are required.");
}
?>
