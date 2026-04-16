<?php
$method = "POST";
$cache  = "no-cache";
include "../../../head.php";

if (isset($_POST['email']) && isset($_POST['password'])) {

    $email    = strtolower(cleanme($_POST['email']));
    $password = cleanme($_POST['password']);

    if (input_is_invalid($email) || input_is_invalid($password)) {
        respondBadRequest("Email and password are required.");
    }if (!validateEmail($email)) {
        respondBadRequest("Invalid email format.");
    } else if (!str_ends_with($email, "@admin.com")) {
        respondBadRequest("Email must end with @admin.com for admin login.");
    }  else {

        $stmt = $connect->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $isValidPassword = false;

            if (password_verify($password, $user['password'])) {
                $isValidPassword = true;
            }

            if (!$isValidPassword) {
                respondBadRequest("Invalid email or password.");
            }

            if ($user['role'] !== 'admin') {
                respondForbiddenAuthorized("Access denied. Admin privileges required.");
            }

            $accessToken = getTokenToSendAPI($user['id']);

            respondOK([
                "access_token" => $accessToken,
                "user" => [
                    "full_name" => $user['full_name'],
                    "email"     => $user['email'],
                    "role"      => $user['role']
                ]
            ], "Login successful.");
        } else {
            respondBadRequest("Invalid email or password.");
        }
    }

} else {
    respondBadRequest("Invalid request. Email and password are required.");
}
?>
