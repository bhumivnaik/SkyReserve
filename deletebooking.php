<<<<<<< HEAD
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "airport";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$booking = null;
$passenger_id = null;

// Step 1: Load booking using booking_id from viewbooking.php
if (isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];

$sql = "SELECT b.booking_id, b.flight_id, b.date,
               p.passenger_ID,
               CONCAT(p.fname, ' ', p.lname) AS name,
               p.email
        FROM booking b
        LEFT JOIN makes m ON b.booking_id = m.booking_id
        LEFT JOIN passenger p ON m.passenger_ID = p.passenger_ID
        WHERE b.booking_id = ?
        LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $booking_id); // VARCHAR
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $booking = $result->fetch_assoc();
        $passenger_id = $booking['passenger_ID'] ?? null;
    } else {
        $message = "Booking not found.";
    }
    $stmt->close();
}

// Step 2: If user confirms cancellation
if (isset($_POST['confirm_cancel']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $passenger_id = $_POST['passenger_id'] ?? null;

    // Delete from makes
    $stmt1 = $conn->prepare("DELETE FROM makes WHERE booking_id = ?");
    $stmt1->bind_param("s", $booking_id);
    $stmt1->execute();
    $stmt1->close();

    // Delete from booking
    $stmt2 = $conn->prepare("DELETE FROM booking WHERE booking_id = ?");
    $stmt2->bind_param("s", $booking_id);
    $stmt2->execute();
    $stmt2->close();

    // Delete passenger if we know it
    if ($passenger_id) {
        $stmt3 = $conn->prepare("DELETE FROM passenger WHERE passenger_ID = ?");
        $stmt3->bind_param("s", $passenger_id);
        $stmt3->execute();
        $stmt3->close();
        $message = "Your booking has been cancelled successfully!";
    } else {
        $message = "Your booking has been cancelled. (No passenger record linked or already removed.)";
    }

    $booking = null;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cancel Booking</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Libertinus+Serif:wght@400;600;700&family=Merriweather:wght@300;400;700&display=swap');

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
    margin: 0;
}

.video-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: -2;
}
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(0, 0, 40, 0.6), rgba(0, 0, 0, 0.8));
    z-index: -1;
}

.cancel-container {
    max-width: 600px;
    margin: auto;
    background: var(--white-color-light);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(15, 76, 117, 0.3);
    text-align: center;
}

h1, h2 {
    font-family: "Libertinus Serif";
    color: var(--dark-blue);
    margin-bottom: 15px;
}

p {
    color: var(--gray-colour);
    font-size: 16px;
}

.detail-box {
    background: #ffffff;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    border: 1px solid var(--third-blue);
    text-align: left;
}

.detail-box p {
    margin: 5px 0;
}

button, .btn {
    display: inline-block;
    padding: 10px 20px;
    margin: 10px 5px 0 5px;
    border-radius: 8px;
    border: none;
    font-size: 15px;
    cursor: pointer;
    text-decoration: none;
}

.btn-cancel {
    background: #b91c1c;
    color: white;
}

.btn-cancel:hover {
    background: #dc2626;
}

.btn-back {
    background: var(--dark-blue);
    color: white;
}

.btn-back:hover {
    background: var(--second-blue);
}

.message {
    margin-bottom: 20px;
    font-size: 18px;
    color: var(--gray-colour);
}
</style>
</head>
<body>

<video autoplay muted loop class="video-bg">
    <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
</video>
<div class="overlay"></div>

<div class="cancel-container">
    <h1>Cancel Booking</h1>

    <?php if($message): ?>
        <p class="message"><?= $message ?></p>
        <a href="managebooking.php" class="btn btn-back">Back to Manage Booking</a>

    <?php elseif(!$booking): ?>
        <p class="message">Booking not found.</p>
        <a href="managebooking.php" class="btn btn-back">Back to Manage Booking</a>

    <?php else: ?>
        <h2>Are you sure you want to cancel this booking?</h2>

        <div class="detail-box">
            <p><strong>Booking ID:</strong> <?= htmlspecialchars($booking['booking_id']) ?></p>
            <p><strong>Passenger Name:</strong> <?= htmlspecialchars($booking['name'] ?? 'N/A') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($booking['email'] ?? 'N/A') ?></p>
            <p><strong>Flight ID:</strong> <?= htmlspecialchars($booking['flight_id']) ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($booking['date']) ?></p>
        </div>

        <form method="post">
            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['booking_id']) ?>">
            <input type="hidden" name="passenger_id" value="<?= htmlspecialchars($booking['passenger_ID'] ?? '') ?>">

            <button type="submit" name="confirm_cancel" class="btn btn-cancel">
                Yes, Cancel My Booking
            </button>

            <a href="viewbooking.php?id=<?= urlencode($booking['booking_id']) ?>&email=<?= urlencode($booking['email'] ?? '') ?>" class="btn btn-back">
                No, Go Back
            </a>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
=======
<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "airport";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message      = "";
$booking_rows = [];
$passenger    = null;

// Step 1: Load booking using booking_id from viewbooking.php
if (isset($_GET['booking_id'])) {
    $booking_id = $_GET['booking_id'];

    // Get ONE passenger (any) + all flight segments for this booking
    $sql = "
        SELECT 
            b.booking_id,
            b.flight_id,
            b.date,
            p.passenger_ID,
            CONCAT(p.fname, ' ', p.lname) AS name,
            p.email
        FROM booking b
        LEFT JOIN makes m ON b.booking_id = m.booking_id
        LEFT JOIN passenger p ON m.passenger_ID = p.passenger_ID
        WHERE b.booking_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $booking_rows[] = $row;
        }
        // Use first row just to show passenger info
        $passenger = $booking_rows[0];
    } else {
        $message = "Booking not found.";
    }
    $stmt->close();
}

// Step 2: If user confirms cancellation
if (isset($_POST['confirm_cancel']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];

    // Delete from makes (all passengers linked to this booking_id)
    $stmt1 = $conn->prepare("DELETE FROM makes WHERE booking_id = ?");
    $stmt1->bind_param("s", $booking_id);
    $stmt1->execute();
    $stmt1->close();

    // Delete from booking (one-way: 1 row, two-way: 2 rows, etc.)
    $stmt2 = $conn->prepare("DELETE FROM booking WHERE booking_id = ?");
    $stmt2->bind_param("s", $booking_id);
    $stmt2->execute();
    $stmt2->close();

    // We DO NOT delete passenger records here because:
    // - same passenger may have other bookings
    // - multiple passengers are linked to this booking

    $message      = "Your booking (all trip segments) has been cancelled successfully!";
    $booking_rows = [];
    $passenger    = null;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Cancel Booking</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libertinus+Serif:wght@400;600;700&family=Merriweather:wght@300;400;700&display=swap');

        :root {
            --dark-blue: #0d4b75ff;
            --second-blue: #2c81baff;
            --third-blue: #35acfcff;
            --gray-colour: #465f6dff;
            --white-light: #F4F4F4;
        }

        body {
            font-family: Cambria, serif;
            background: linear-gradient(to right, var(--dark-blue), var(--second-blue));
            padding: 50px;
            margin: 0;
        }

        .video-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0, 0, 40, 0.6), rgba(0, 0, 0, 0.8));
            z-index: -1;
        }

        .cancel-container {
            max-width: 500px;
            margin: auto;
            background: var(--white-light);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(15, 76, 117, 0.3);
            text-align: center;
            margin-top: 80px;
        }

        h1,
        h2 {
            font-family: "Libertinus Serif";
            color: var(--dark-blue);
            margin-bottom: 15px;
        }

        p {
            color: var(--gray-colour);
            font-size: 16px;
        }

        .detail-box {
            background: #ffffff;
            border-radius: 8px;
            padding: 15px;
            margin: 35px 0;
            border: 1px solid var(--third-blue);
            text-align: left;
        }

        .warning-box {
            background: rgba(255, 180, 40, 0.2);
            border-left: 5px solid #fbbf24;
            padding: 12px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 16px;
        }

        .detail-box p {
            margin: 5px 0;
        }

        button,
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px 0 5px;
            border-radius: 8px;
            border: none;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-cancel {
            background: #b91c1c;
            color: white;
        }

        .btn-cancel:hover {
            background: #dc2626;
        }

        .btn-back {
            background: var(--dark-blue);
            color: white;
        }

        .btn-back:hover {
            background: var(--second-blue);
        }

        .message {
            margin-bottom: 20px;
            font-size: 18px;
            color: var(--gray-colour);
        }

        .segment-title {
            font-weight: bold;
            margin-top: 10px;
            color: var(--second-blue);
        }
    </style>
</head>

<body>

    <video autoplay muted loop class="video-bg">
        <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <div class="cancel-container">
        <h1>Cancel Booking</h1>

        <?php if ($message): ?>
            <div class="but">
                <p class="message"><?= htmlspecialchars($message) ?></p>
                <a href="managebooking.php" class="btn btn-back">Back to Manage Booking</a>
            </div>

        <?php elseif (!$booking_rows): ?>
            <div class="but">
                <p class="message">Booking not found.</p>
                <a href="managebooking.php" class="btn btn-back">Back to Manage Booking</a>
            </div>

        <?php else: ?>
            <h2 style=" color: var(--second-blue);">Are you sure you want to cancel this booking?</h2>
            <div class="warning-box">
                ⚠ You are about to cancel your booking (all trip segments). This action cannot be undone.
            </div>

            <div class="detail-box">
                <p><strong>Booking ID:</strong> <?= htmlspecialchars($booking_rows[0]['booking_id']) ?></p>
                <p><strong>Passenger Name:</strong> <?= htmlspecialchars($passenger['name'] ?? 'N/A') ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($passenger['email'] ?? 'N/A') ?></p>

                <?php
                $segNo = 1;
                foreach ($booking_rows as $row) {
                    echo '<p class="segment-title">Segment ' . $segNo++ . ':</p>';
                    echo '<p><strong>Flight ID:</strong> ' . htmlspecialchars($row['flight_id']) . '</p>';
                    echo '<p><strong>Date:</strong> ' . htmlspecialchars($row['date']) . '</p>';
                }
                ?>
            </div>

            <form method="post">
                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking_rows[0]['booking_id']) ?>">

                <button type="submit" name="confirm_cancel" class="btn btn-cancel">
                    Yes, Cancel My Booking
                </button>

                <a href="managebooking.php" class="btn btn-back">
                    No, Go Back
                </a>
            </form>
        <?php endif; ?>
    </div>

</body>

</html>
>>>>>>> main
