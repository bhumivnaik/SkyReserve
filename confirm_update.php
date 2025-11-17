<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// RECEIVE DATA VIA POST
$booking_id    = $_POST['booking_id']   ?? null;
$new_date      = $_POST['new_date']     ?? "";
$new_class     = $_POST['class_value']  ?? "";
$f_instance_id = $_POST['f_instance_id'] ?? null;
$segment       = $_POST['segment']      ?? 'depart';   // 'depart' or 'return' (used only for display text)

if (!$booking_id) {
    die("Invalid request. Booking ID missing.");
}

// Get existing booking
$sql = "SELECT * FROM booking WHERE booking_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$booking_result = $stmt->get_result();
if ($booking_result->num_rows === 0) {
    die("Booking not found.");
}
$booking = $booking_result->fetch_assoc();
$stmt->close();

// Get flight + instance details if chosen
$flight_info = null;
if ($f_instance_id) {
    $sql = "SELECT fi.f_instance_id, fi.flight_id, fi.departure_time, fi.arrival_time, fi.date, fi.available_seats,
                   f.flight_name, f.sourceAcode, f.destAcode, f.duration
            FROM flightinstance fi
            JOIN flight f ON fi.flight_id = f.flight_id
            WHERE fi.f_instance_id = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $f_instance_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $flight_info = $result->fetch_assoc();
    }
    $stmt->close();
}

/*
   If you want to adjust available_seats in flightinstance for old vs new instance,
   you can do it here using $booking['seatsbooked'] and $f_instance_id.
*/

// --- UPDATE booking TABLE ---
// 1) Update date (single 'date' column in your current schema)
if (!empty($new_date)) {
    $sql = "UPDATE booking SET date = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_date, $booking_id);
    $stmt->execute();
    $stmt->close();
}

// 2) Update class_id
if (!empty($new_class)) {
    $sql = "UPDATE booking SET class_id = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_class, $booking_id);
    $stmt->execute();
    $stmt->close();
}

// 3) Update flight_id if we selected a flight instance
if ($flight_info) {
    $sql = "UPDATE booking SET flight_id = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $flight_info['flight_id'], $booking_id);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Update Successful</title>
    <style>
        :root {
            --dark-blue: #0d4b75ff;
            --second-blue: #2c81baff;
            --third-blue: #35acfcff;
            --gray-colour: #465f6dff;
            --white-color-light: #F4F4F4;
        }

        body {
            font-family: Cambria;
            background: linear-gradient(to right, var(--dark-blue), var(--second-blue));
            padding: 30px;
            margin-top: 30px;
            text-align: center;
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
            background: linear-gradient(to bottom, rgba(0, 0, 59, 0.6), rgba(0, 0, 0, 0.8));
            z-index: -1;
            backdrop-filter: blur(2px);
        }

        .success-icon {
            font-size: 55px;
            color: var(--second-blue);
            margin-bottom: 10px;
            animation: pop 0.4s ease-in-out;
        }

        @keyframes pop {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        h2 {
            text-align: center;
            font-family: "Libertinus Serif";
            color: var(--dark-blue);
            font-size: 32px;
            margin-bottom: 20px;
        }

        .manage-container {
            max-width: 500px;
            margin: auto;
            background: var(--white-color-light);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(15, 76, 117, 0.3);
            text-align: center;
            position: relative;
        }

        .subtext {
            color: var(--gray-colour);
            margin-bottom: 20px;
        }

        .summary-grid {
            margin-top: 20px;
            text-align: left;
        }

        .summary-row {
            background: #ffffff;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-left: 4px solid var(--second-blue);
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.08);
        }

        .summary-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dark-blue);
        }

        .summary-value {
            font-size: 17px;
            color: var(--gray-colour);
            margin-top: 4px;
        }

        a {
            display: inline-block;
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: white;
            margin-top: 20px;
            padding: 12px 28px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: 0.25s ease;
        }

        a:hover {
            transform: scale(1.07);
            background: linear-gradient(135deg, var(--dark-blue), var(--second-blue));
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>

<body>

    <video autoplay muted loop class="video-bg">
        <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <div class="manage-container">
        <div class="success-icon">✔</div>
        <h2>Booking Updated Successfully</h2>
        <p class="subtext">
            Your changes have been applied 
            <?php if ($segment === 'return'): ?>
                to your <strong>Return</strong> preferences.
            <?php else: ?>
                to your <strong>Departure</strong> preferences.
            <?php endif; ?>
        </p>

        <div class="summary-grid">

            <div class="summary-row">
                <div class="summary-label">Booking ID</div>
                <div class="summary-value"><?= htmlspecialchars($booking_id) ?></div>
            </div>

            <?php if (!empty($new_date) || ($flight_info && !empty($flight_info['date']))): ?>
                <div class="summary-row">
                    <div class="summary-label">Travel Date</div>
                    <div class="summary-value">
                        <?= htmlspecialchars(!empty($new_date) ? $new_date : $flight_info['date']) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($new_class)): ?>
                <div class="summary-row">
                    <div class="summary-label">Class</div>
                    <div class="summary-value"><?= htmlspecialchars($new_class) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($flight_info): ?>
                <div class="summary-row">
                    <div class="summary-label">Flight</div>
                    <div class="summary-value">
                        <?= htmlspecialchars($flight_info['flight_name']) ?> (<?= htmlspecialchars($flight_info['flight_id']) ?>)
                    </div>
                </div>

                <div class="summary-row">
                    <div class="summary-label">Route</div>
                    <div class="summary-value">
                        <?= htmlspecialchars($flight_info['sourceAcode']) ?> → <?= htmlspecialchars($flight_info['destAcode']) ?>
                    </div>
                </div>

                <div class="summary-row">
                    <div class="summary-label">Timing</div>
                    <div class="summary-value">
                        Departure: <?= htmlspecialchars($flight_info['departure_time']) ?> &nbsp; | &nbsp;
                        Arrival: <?= htmlspecialchars($flight_info['arrival_time']) ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="summary-row">
                <div class="summary-label">Segment Edited</div>
                <div class="summary-value">
                    <?= ($segment === 'return') ? 'Return Flight' : 'Departure Flight' ?>
                </div>
            </div>

        </div>

        <a href="managebooking.php">Back to Manage Booking</a>
    </div>

</body>
</html>
