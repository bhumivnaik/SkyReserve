<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$booking = null;
$error = "";
$available_flights = [];
$step = $_GET['step'] ?? 'date'; // step = 'date' or 'select_flight'

if (!isset($_GET['id'])) {
    $error = "No booking selected.";
} else {
    $id = $_GET['id'];

    // Fetch booking
    $sql = "SELECT * FROM booking WHERE booking_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id); // booking_id is VARCHAR
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    } else {
        $error = "Booking not found.";
    }
    $stmt->close();
}

// --- Fetch class options from the 'class' table ---
$class_options = [];
$class_sql = "SELECT class_id, class_name FROM class";
$class_result = $conn->query($class_sql);
if ($class_result && $class_result->num_rows > 0) {
    while ($row = $class_result->fetch_assoc()) {
        $class_options[] = $row;
    }
}

// If user has chosen a new date, move to step "select_flight"
$selected_date = null;
if ($booking && $step === 'select_flight' && isset($_GET['date_value']) && $_GET['date_value'] !== '') {
    $selected_date = $_GET['date_value'];

    // Fetch available flight instances on that date for the SAME route as original flight
    // First, get original flight's source/destination
    $orig_flight_id = $booking['flight_id'];

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
        // Now find all flights with same route that have instances on that date
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
    background: linear-gradient(to bottom, rgba(0,0,40,0.6), rgba(0,0,0,0.8));
    z-index: -1;
}

.manage-container {
    max-width: 800px;
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

label {
    font-weight: bold;
    color: var(--gray-colour);
    margin-top: 15px;
    display: block;
}

input[type="text"], input[type="date"], input[type="number"]{
    width: 96.5%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid var(--second-blue);
    margin-top: 8px;
    background: #fff;
}
select{
    width: 100%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid var(--second-blue);
    margin-top: 8px;
    background: #fff;
}

input[type="submit"], button {
    background: var(--dark-blue);
    color: white;
    border: none;
    padding: 12px 18px;
    border-radius: 8px;
    font-size: 16px;
    margin-top: 20px;
    cursor: pointer;
}

input[type="submit"]:hover, button:hover {
    background: var(--second-blue);
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

    <?php if ($step === 'date'): ?>
        <!-- STEP 1: Choose new date + (optionally) class -->
        <form action="updatebooking.php" method="get">
            <input type="hidden" name="id" value="<?= htmlspecialchars($booking['booking_id']) ?>">
            <input type="hidden" name="step" value="select_flight">

            <label>Current Date</label>
            <input type="text" value="<?= htmlspecialchars($booking['date']) ?>" disabled>

            <label>Choose New Travel Date</label>
            <input type="date" name="date_value" required>

            <label>Change Class (optional)</label>
            <select name="class_value">
                <option value="">-- Keep Same Class (<?= htmlspecialchars($booking['class_id']) ?>) --</option>
                <?php foreach ($class_options as $class): ?>
                    <option value="<?= htmlspecialchars($class['class_id']) ?>"
                        <?= ($booking['class_id'] == $class['class_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['class_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="submit" value="Find Flights on This Date">
        </form>

    <?php elseif ($step === 'select_flight'): ?>

        <h3 style="color: var(--dark-blue);">Flights available on <?= htmlspecialchars($selected_date) ?></h3>

        <?php if (empty($available_flights)): ?>
            <p>No flights available on this date for your route with enough seats.</p>
            <a href="updatebooking.php?id=<?= urlencode($booking['booking_id']) ?>" >
                <button type="button">Choose Another Date</button>
            </a>
        <?php else: ?>
            <!-- STEP 2: Choose specific flight instance and confirm -->
            <form action="confirm_update.php" method="post">
                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['booking_id']) ?>">
                <input type="hidden" name="new_date" value="<?= htmlspecialchars($selected_date) ?>">
                <input type="hidden" name="class_value" value="<?= htmlspecialchars($_GET['class_value'] ?? '') ?>">

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
