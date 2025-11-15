<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// RECEIVE DATA VIA GET
$booking_id = $_GET['booking_id'] ?? null;
$new_date = $_GET['date_value'] ?? "";
$new_class = $_GET['class_value'] ?? "";

if (!$booking_id) {
    die("Invalid request. Booking ID missing.");
}

// --- UPDATE DATE ---
if (!empty($new_date)) {
    $sql = "UPDATE booking SET date = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_date, $booking_id);
    $stmt->execute();
}

// --- UPDATE CLASS ---
if (!empty($new_class)) {
    $sql = "UPDATE booking SET class_id = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_class, $booking_id);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Update Successful</title>
<style>
body {
    font-family: Cambria, serif;
    background: #f7f7f7;
    padding: 40px;
    text-align: center;
}

.box {
    background: white;
    padding: 30px;
    max-width: 400px;
    margin: auto;
    border-radius: 10px;
    box-shadow: 0 0 8px rgba(0,0,0,0.2);
}

h2 {
    color: #0F4C75;
}

a {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 18px;
    background: #0F4C75;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

a:hover {
    background: #3282B8;
}
</style>
</head>
<body>

<div class="box">
    <h2>Changes Applied Successfully!</h2>
    <p><strong>Booking ID:</strong> <?= $booking_id ?></p>
    <p>
        <?php if(!empty($new_date)) echo "Date updated to: $new_date<br>"; ?>
        <?php if(!empty($new_class)) echo "Class updated to: $new_class"; ?>
    </p>
    <a href="managebooking.php">Go Back</a>
</div>

</body>
</html>
