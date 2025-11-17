<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$booking = null;
$error = "";
$available_flights = [];
$step = $_GET['step'] ?? 'segment'; // NEW: first step is choose segment
$segment = $_GET['segment'] ?? 'depart'; // 'depart' or 'return'

// 1️⃣ Fetch booking
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM booking WHERE booking_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id); // booking_id is VARCHAR, so use "s"
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    } else {
        $error = "Booking not found.";
    }
    $stmt->close();
} else {
    $error = "No booking selected.";
}

// 2️⃣ Fetch class options
$class_options = [];
$class_sql = "SELECT class_id, class_name FROM class";
$class_result = $conn->query($class_sql);
if ($class_result && $class_result->num_rows > 0) {
    while ($row = $class_result->fetch_assoc()) {
        $class_options[] = $row;
    }
}

// Determine current values depending on segment
$current_date = "";
$current_class_id = "";

if ($booking) {
    if ($segment === 'return') {
        // adjust these column names to your schema
        $current_date = $booking['return_date'] ?? $booking['date']; 
        $current_class_id = $booking['return_class_id'] ?? $booking['class_id'];
    } else { // depart
        $current_date = $booking['depart_date'] ?? $booking['date']; 
        $current_class_id = $booking['depart_class_id'] ?? $booking['class_id'];
    }
}

// 3️⃣ Step: select_flight – fetch flights for chosen date+segment
$selected_date = null;
if ($booking && $step === 'select_flight' && isset($_GET['date_value']) && $_GET['date_value'] !== '') {
    $selected_date = $_GET['date_value'];

    // original flight id depends on segment
    if ($segment === 'return') {
        $orig_flight_id = $booking['return_flight_id'] ?? $booking['flight_id'];
    } else {
        $orig_flight_id = $booking['flight_id']; // depart
    }

    // Get route of that flight
    $route_sql = "SELECT sourceAcode, destAcode FROM flight WHERE flight_id = ?";
    $route_stmt = $conn->prepare($route_sql);
    $route_stmt->bind_param("s", $orig_flight_id);
    $route_stmt->execute();
    $route_result = $route_stmt->get_result();

    $sourceAcode = null;
    $destAcode = null;
    if ($route_result->num_rows > 0) {
        $route_row = $route_result->fetch_assoc();
        $sourceAcode = $route_row['sourceAcode'];
        $destAcode   = $route_row['destAcode'];
    }
    $route_stmt->close();

    if ($sourceAcode && $destAcode) {
        $flight_sql = "
            SELECT fi.f_instance_id, fi.flight_id, fi.departure_time, fi.arrival_time, fi.available_seats,
                   f.flight_name, f.sourceAcode, f.destAcode, f.duration
            FROM flightinstance fi
            JOIN flight f ON fi.flight_id = f.flight_id
            WHERE fi.date = ? 
              AND f.sourceAcode = ?
              AND f.destAcode = ?
              AND fi.available_seats >= ?
        ";
        $seats_needed = $booking['seatsbooked']; // only show flights with enough seats
        $flight_stmt = $conn->prepare($flight_sql);
        $flight_stmt->bind_param("sssi", $selected_date, $sourceAcode, $destAcode, $seats_needed);
        $flight_stmt->execute();
        $flight_result = $flight_stmt->get_result();

        if ($flight_result->num_rows > 0) {
            while ($row = $flight_result->fetch_assoc()) {
                $available_flights[] = $row;
            }
        }
        $flight_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update Booking</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libertinus+Serif:wght@400;600;700&family=Merriweather:wght@300;400;700&display=swap');

        :root {
            --dark-blue: #0d4b75ff;
            --second-blue: #2c81baff;
            --third-blue: #35acfcff;
            --gray-colour: #465f6dff;
            --white-color-light: #F4F4F4;
        }

        body {
            font-family: 'Cambria';
            background: linear-gradient(to right, var(--dark-blue), var(--second-blue));
            padding: 40px;
            margin-top: 60px;
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

        h2 {
            text-align: center;
            font-family: "Libertinus Serif";
            color: var(--dark-blue);
            font-size: 35px;
            margin-bottom: 45px;
        }

        .manage-container {
            max-width: 500px;
            background: var(--white-color-light);
            padding: 30px;
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(16, 91, 141, 0.4);
            color: var(--dark-blue);
        }

        label {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            display: block;
            font-size: 16px;
            color: var(--second-blue);
        }

        input[type="text"],
        input[type="submit"],
        input[type="date"],
        input[type="number"] {
            width: 97%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid var(--second-blue);
            color: var(--gray-colour);
            font-family: 'Cambria';
        }

        input[type="submit"],
        button {
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: var(--white-color-light);
            cursor: pointer;
            padding: 10px 15px;
            margin-top: 35px;
            width: 100%;
            border: none;
            font-size: medium;
            box-shadow: 0 0 15px rgba(53, 172, 252, 0.5);
            font-family: 'Cambria';
        }

        input[type="submit"]:hover,
        button:hover {
            transform: scale(1.07);
            box-shadow: 0 0 25px rgba(53, 172, 252, 0.3);
            background: linear-gradient(135deg, var(--dark-blue), var(--second-blue));
        }

        input[type="text"],
        input[type="date"],
        input[type="number"] {
            width: 96.5%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid var(--second-blue);
            margin-top: 8px;
            background: #fff;
        }

        select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid var(--second-blue);
            margin-top: 8px;
            background: #fff;
            font-family: 'Cambria';
            color: var(--gray-colour);
        }

        .no-result {
            text-align: center;
            font-size: 18px;
            color: white;
        }

        .flight-card {
            border: 1px solid var(--third-blue);
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            background: #fff;
        }

        .flight-title {
            font-weight: bold;
            color: var(--dark-blue);
        }

        .flight-sub {
            color: var(--gray-colour);
            font-size: 14px;
        }

        .radio-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .segment-options {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .segment-options label {
            margin-top: 0;
            font-weight: normal;
            color: var(--gray-colour);
        }
    </style>
</head>

<body>

<video autoplay muted loop class="video-bg">
    <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
</video>
<div class="overlay"></div>

<?php if ($booking): ?>

    <div class="manage-container">
        <h2>Update Your Booking</h2>

        <?php if ($step === 'segment'): ?>
            <!-- STEP 0: Choose which part of trip to edit -->
            <form action="updatebooking.php" method="get">
                <input type="hidden" name="id" value="<?= htmlspecialchars($booking['booking_id']) ?>">
                <input type="hidden" name="step" value="date">

                <label>What do you want to edit?</label>
                <div class="segment-options">
                    <input type="radio" id="seg_depart" name="segment" value="depart" checked>
                    <label for="seg_depart">Departure Flight (Going)</label>
                </div>
                <div class="segment-options">
                    <input type="radio" id="seg_return" name="segment" value="return">
                    <label for="seg_return">Return Flight</label>
                </div>

                <input type="submit" value="Continue">
            </form>

        <?php elseif ($step === 'date'): ?>
            <!-- STEP 1: Choose new date + class for selected segment -->
            <form action="updatebooking.php" method="get">
                <input type="hidden" name="id" value="<?= htmlspecialchars($booking['booking_id']) ?>">
                <input type="hidden" name="step" value="select_flight">
                <input type="hidden" name="segment" value="<?= htmlspecialchars($segment) ?>">

                <label>Editing</label>
                <input type="text" value="<?= ($segment === 'return') ? 'Return Flight' : 'Departure Flight' ?>" disabled>

                <label>Current Date</label>
                <input type="text" value="<?= htmlspecialchars($current_date) ?>" disabled>

                <label>Choose New Travel Date</label>
                <input type="date" name="date_value" required>

                <label>Change Class (optional)</label>
                <select name="class_value">
                    <option value="">-- Keep Same Class --</option>
                    <?php foreach ($class_options as $class): ?>
                        <option value="<?= htmlspecialchars($class['class_id']) ?>"
                            <?= ($current_class_id == $class['class_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['class_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="submit" value="Find Flights on This Date">
            </form>

        <?php elseif ($step === 'select_flight'): ?>

            <h3 style="color: var(--second-blue);">
                Flights available on <?= htmlspecialchars($selected_date) ?>
                (<?= ($segment === 'return') ? 'Return' : 'Departure' ?>)
            </h3>

            <?php if (empty($available_flights)): ?>
                <p>No flights available on this date for your route with enough seats.</p>
                <a href="updatebooking.php?id=<?= urlencode($booking['booking_id']) ?>&step=date&segment=<?= urlencode($segment) ?>">
                    <button type="button">Choose Another Date</button>
                </a>
            <?php else: ?>
                <!-- STEP 2: Choose specific flight instance and confirm -->
                <form action="confirm_update.php" method="post">
                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['booking_id']) ?>">
                    <input type="hidden" name="new_date" value="<?= htmlspecialchars($selected_date) ?>">
                    <input type="hidden" name="class_value" value="<?= htmlspecialchars($_GET['class_value'] ?? '') ?>">
                    <input type="hidden" name="segment" value="<?= htmlspecialchars($segment) ?>">

                    <?php foreach ($available_flights as $f): ?>
                        <div class="flight-card">
                            <div class="radio-wrap">
                                <input type="radio" name="f_instance_id" value="<?= htmlspecialchars($f['f_instance_id']) ?>" required>
                                <div>
                                    <div class="flight-title">
                                        <?= htmlspecialchars($f['flight_name']) ?> (<?= htmlspecialchars($f['flight_id']) ?>)
                                    </div>
                                    <div class="flight-sub">
                                        <?= htmlspecialchars($f['sourceAcode']) ?> → <?= htmlspecialchars($f['destAcode']) ?><br>
                                        Depart: <?= htmlspecialchars($f['departure_time']) ?> &nbsp;|&nbsp;
                                        Arrive: <?= htmlspecialchars($f['arrival_time']) ?><br>
                                        Duration: <?= htmlspecialchars($f['duration']) ?><br>
                                        Available Seats: <?= htmlspecialchars($f['available_seats']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <input type="submit" value="Confirm Change">
                </form>
            <?php endif; ?>

        <?php endif; ?>

    </div>

<?php else: ?>
    <p class="no-result"><?= $error ?></p>
<?php endif; ?>

</body>
</html>
