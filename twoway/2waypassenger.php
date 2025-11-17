<?php
session_start();
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Detect trip type
$oneway = isset($_POST['oneway']);
$twoway = isset($_POST['twoway']);

// Common values
$class_id  = $_POST['class_id'] ?? '';
$qty       = $_POST['seat_qty'] ?? 1;
$total     = $_POST['total'] ?? 0;

// -----------------------------
// ONE-WAY BOOKING
// -----------------------------
if ($oneway) {
    $flight_id   = $_POST['flight_id'] ?? '';
    $flight_name = $_POST['flight_name'] ?? '';
    $source      = $_POST['source'] ?? '';
    $destination = $_POST['destination'] ?? '';
    $date        = $_POST['date'] ?? '';
}

// -----------------------------
// TWO-WAY BOOKING
// -----------------------------
if ($twoway) {
    $outgoing_id   = $_POST['outgoing_id'] ?? '';
    $return_id     = $_POST['return_id'] ?? '';
    $out_date      = $_POST['out_date'] ?? '';
    $ret_date      = $_POST['ret_date'] ?? '';
    $source        = $_POST['source'] ?? '';
    $destination   = $_POST['destination'] ?? '';
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Passenger Details</title>
    <style>
        :root {
            --dark-blue: #0d4b75ff;
            --second-blue: #2c81baff;
            --third-blue: #35acfcff;
            --gray-colour: #465f6dff;
            --white-color-light: #F4F4F4;
        }

        body {
            font-family: Cambria, serif;
            background: linear-gradient(to right, #a0d8ff, #b0d3fa);
            padding: 30px;
            color: var(--gray-colour);
        }

        .container {
            max-width: 900px;
            background: #fff;
            margin: 40px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        h2 {
            font-family: "Libertinus Serif";
            font-size: 35px;
            color: var(--dark-blue);
            margin: 37px auto;
            margin-bottom: 30px;
        }

        h3 {
            color: var(--second-blue);
            font-family: 'Merriweather';
            border-left: 5px solid var(--dark-blue);
            padding-left: 10px;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        .passenger-box {
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;

        }

        .passenger-box h3 {
            margin-bottom: 15px;
            color: var(--second-blue);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }



        label {
            color: var(--second-blue);
            font-size: 14px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        input,
        select {
            width: 94%;
            padding: 8px;
            border: 1px solid var(--second-blue);
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Cambria';
            text-transform: capitalize;
            color: var(--gray-colour);
        }

        select {
            width: 100%;
        }

        input[type="email"] {
            text-transform: none;
        }

        input[type="submit"] {
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: var(--white-color-light);
            cursor: pointer;
            padding: 10px 15px;
            margin-top: 15px;
            width: 100%;
            border: none;
            font-size: medium;
            box-shadow: 0 0 15px rgba(53, 172, 252, 0.5);
        }

        input[type="submit"]:hover {
            transform: scale(1.05);
            box-shadow: 0 0 25px rgba(53, 172, 252, 0.3);
            background: linear-gradient(135deg, var(--dark-blue), var(--second-blue));
        }
    </style>
</head>

<body>
    <video autoplay muted loop class="video-bg">
        <source src="../From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>
    <div class="overlay"></div>
    <div class="container">
        <h2 style="text-align:center;">Passenger Details </h2>

        <form action="2waypayment.php" method="POST">

            <!-- Hidden Inputs -->
            <input type="hidden" name="seat_qty" value="<?= $qty ?>">
            <input type="hidden" name="total" value="<?= $total ?>">

            <!-- ONEWAY Hidden -->
            <?php if ($oneway): ?>
                <input type="hidden" name="trip_type" value="oneway">
                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                <input type="hidden" name="flight_id" value="<?= $flight_id ?>">
                <input type="hidden" name="flight_name" value="<?= $flight_name ?>">
                <input type="hidden" name="source" value="<?= $source ?>">
                <input type="hidden" name="destination" value="<?= $destination ?>">
                <input type="hidden" name="date" value="<?= $date ?>">
            <?php endif; ?>

            <!-- TWOWAY Hidden -->
            <?php if ($twoway): ?>
                <input type="hidden" name="trip_type" value="twoway">
                <input type="hidden" name="out_flight_id" value="<?= $outgoing_id ?>">
                <input type="hidden" name="ret_flight_id" value="<?= $return_id ?>">
                <input type="hidden" name="out_class_id" value="<?= $_POST['out_class_id'] ?>">
                <input type="hidden" name="ret_class_id" value="<?= $_POST['ret_class_id'] ?>">
                <input type="hidden" name="out_date" value="<?= $out_date ?>">
                <input type="hidden" name="ret_date" value="<?= $ret_date ?>">
                <input type="hidden" name="source" value="<?= $source ?>">
                <input type="hidden" name="destination" value="<?= $destination ?>">
            <?php endif; ?>

            <?php
            for ($i = 1; $i <= $qty; $i++):
            ?>
                <div class='passenger-box'>
                    <h3>Passenger <?= $i ?></h3>
                    <fieldset style='margin-top:15px;border:1px solid #ccc;border-radius:8px;padding:15px;'>
                        <input type='hidden' name='passenger_id[]' value='$passenger_id'>
                        <div class='form-grid'>
                            <div>
                                <label>First Name:</label>
                                <input type='text' name='fname[]' style="text-transform: 'capitalize';" required>
                            </div>
                            <div>
                                <label>Middle Name:</label>
                                <input type='text' name='mname[]' style="text-transform: 'capitalize';">
                            </div>
                            <div>
                                <label>Last Name:</label>
                                <input type='text' name='lname[]' style="text-transform: 'capitalize';" required>
                            </div>
                            <div>
                                <label>Email:</label>
                                <input type='email' name='email[]' required>
                            </div>
                            <div>
                                <label>Phone:</label>
                                <input type='text' name='phno[]' required>
                            </div>
                            <div>
                                <label>Gender:</label>
                                <select name='gender[]' required>
                                    <option value='Male'>Male</option>
                                    <option value='Female'>Female</option>
                                    <option value='Other'>Other</option>
                                </select>
                            </div>
                            <div>
                                <label>Age:</label>
                                <input type='number' name='age[]' min='1' required>
                            </div>
                        </div>
                    </fieldset>
                </div>
            <?php endfor; ?>

            <input type="submit" value="Confirm & Proceed to Pay ">
        </form>
    </div>
</body>

</html>