<?php
header("Content-Type: application/json");

echo json_encode([
    "status" => "ok",
    "message" => "PHP API running without framework"
]);
