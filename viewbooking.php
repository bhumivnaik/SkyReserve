<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$booking = null;
$error = "";

if (isset($_GET['id']) && isset($_GET['email'])) {
    $id = $_GET['id'];
    $email = $_GET['email'];

    $sql = "SELECT b.*, p.email 
            FROM booking b
            JOIN makes m ON m.booking_id = b.booking_id
            JOIN passenger p ON p.passenger_ID = m.passenger_ID
            WHERE b.booking_id = ? AND p.email = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    } else {
        $error = "No booking found. Please check your details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Details</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Libertinus+Serif:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Merriweather:wght@300;400;700&display=swap');

:root {
    --dark-blue: #0F4C75;
    --second-blue: #3282B8;
    --third-blue: #BBE1FA;
    --gray-colour: #1B262C;
    --white-color-light: #F4F4F4;
}

body {
    font-family: Cambria, serif;
    background: linear-gradient(to right, var(--dark-blue), var(--second-blue));
    padding: 50px;
}

/* VIDEO */
.video-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: -2;
}

/* OVERLAY */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(0,0,40,0.6), rgba(0,0,0,0.8));
    z-index: -1;
}

/* CARD */
.manage-container {
    max-width: 600px;
    margin: auto;
    background: var(--white-color-light);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(15, 76, 117, 0.3);
}

h2 {
    text-align: center;
    font-family: "Libertinus Serif";
    color: var(--dark-blue);
    font-size: 32px;
    margin-bottom: 25px;
}

/* NEW GRID LAYOUT */
.booking-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.detail-box {
    background: white;
    border-radius: 10px;
    padding: 15px;
    border-left: 5px solid var(--second-blue);
    box-shadow: 0 0 7px rgba(0,0,0,0.15);
}

.detail-title {
    font-size: 14px;
    color: var(--dark-blue);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 18px;
    color: var(--gray-colour);
    margin-top: 5px;
    font-weight: bold;
}

/* UPDATE BUTTON */
.btn-update {
    display: block;
    margin-top: 25px;
    text-align: center;
    background: var(--dark-blue);
    color: white;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 17px;
    transition: 0.3s;
}

.btn-update:hover {
    background: var(--second-blue);
}

.no-result {
    text-align: center;
    font-size: 18px;
    color: white;
    margin-top: 20px;
}
</style>

</head>

<body>

<video autoplay muted loop class="video-bg">
    <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
</video>
<div class="overlay"></div>

<div class="manage-container">

    <h2>Your Booking Overview</h2>

    <?php if ($booking): ?>

    <div class="booking-grid">

        <div class="detail-box">
            <div class="detail-title">Booking ID</div>
            <div class="detail-value"><?= $booking['booking_id'] ?></div>
        </div>

        <div class="detail-box">
            <div class="detail-title">Date</div>
            <div class="detail-value"><?= $booking['date'] ?></div>
        </div>

        <div class="detail-box">
            <div class="detail-title">Status</div>
            <div class="detail-value"><?= $booking['status'] ?></div>
        </div>

        <div class="detail-box">
            <div class="detail-title">Flight ID</div>
            <div class="detail-value"><?= $booking['flight_id'] ?></div>
        </div>

        <div class="detail-box">
            <div class="detail-title">Seats Booked</div>
            <div class="detail-value"><?= $booking['seatsbooked'] ?></div>
        </div>

        <div class="detail-box">
            <div class="detail-title">Class</div>
            <div class="detail-value"><?= $booking['class_id'] ?></div>
        </div>

        <div class="detail-box">
            <div class="detail-title">Email</div>
            <div class="detail-value"><?= $booking['email'] ?></div>
        </div>

    </div>

    <a class="btn-update" href="updatebooking.php?id=<?= $booking['booking_id'] ?>">Update Booking</a>

    <?php else: ?>
        <p class="no-result"><?= $error ?></p>
    <?php endif; ?>

</div>

</body>
</html>
