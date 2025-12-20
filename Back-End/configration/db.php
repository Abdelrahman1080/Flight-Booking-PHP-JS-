<?php

$conn = mysqli_connect("localhost","root","","flight_booking");
if(!$conn){
    die("Connection failed: " . mysqli_connect_error());
}
else{
    print("connections works");}

?> 