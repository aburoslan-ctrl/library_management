<?php
//Database Connection to 
$server= 'localhost';
$username= 'root';
$password= '';
$dbname= 'library_management'; 


$connect= mysqli_connect($server,$username,$password,$dbname);
if(!$connect){
    http_response_code(500);
    die(json_encode(["status"=>"error","message"=>"Database connection failed"]));
}
mysqli_set_charset($connect,'utf8mb4');

?>