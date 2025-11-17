<?php
session_start();
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$flight_id = $_POST['flight_id'] ?? '';
$qty       = $_POST['seat_qty'] ?? 0;
$total     = $_POST['total'] ?? 0;
$date      = $_POST['date'] ?? '';
$out_flight_id = $_POST['out_flight_id'];
$ret_flight_id = $_POST['ret_flight_id'];
$out_date = $_POST['out_date'] ?? null;
$ret_date = $_POST['ret_date'] ?? null;
$out_class_id = intval($_POST['out_class_id'] ?? 0);
$ret_class_id = intval($_POST['ret_class_id'] ?? 0);

$fnames  = $_POST['fname'] ?? [];
$mnames  = $_POST['mname'] ?? [];
$lnames  = $_POST['lname'] ?? [];
$emails  = $_POST['email'] ?? [];
$phones  = $_POST['phno'] ?? [];
$genders = $_POST['gender'] ?? [];
$ages    = $_POST['age'] ?? [];

$booking_id = $_POST['booking_id'] ?? '';

$check = $conn->query("SELECT p.pay_id FROM makes m
    JOIN payment p ON m.pay_id = p.pay_id
    WHERE m.booking_id='$booking_id' AND p.status='Paid'");


if ($check->num_rows > 0) {
    header("Location: confirm.php?booking_id=" . $booking_id);
    exit;
}

/*
// --- Generate next Pay ID ---
$lastPay = $conn->query("SELECT pay_id FROM payment ORDER BY pay_id DESC LIMIT 1")->fetch_assoc();
if($lastPay){
    $lastNum = intval(substr($lastPay['pay_id'], 3));
    $nextPayId = 'PID'.str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
} else {
    $nextPayId = 'PID001';
}*/

$lastPay = $conn->query("SELECT pay_id FROM payment ORDER BY pay_id DESC LIMIT 1")->fetch_assoc();
if ($lastPay) {
    $lastNum = intval(substr($lastPay['pay_id'], 3));
    $nextPayId = 'PID' . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
} else {
    $nextPayId = 'PID001';
}


?>

<!DOCTYPE html>
<html>

<head>
    <title>Payment</title>
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
            background: linear-gradient(to right, #0161a5ff, #a5cfffff);
            ;
            padding: 40px;
        }

        h2 {
            font-family: "Libertinus Serif";
            font-size: 35px;
            color: var(--dark-blue);
            margin: 37px auto;
            text-align: center;
            margin-bottom: 35px;
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

        .card {
            background: var(--white-color-light);
            margin: 50px auto;
            padding: 30px;
            width: 500px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        label {
            color: var(--second-blue);
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        select,
        input {
            width: 97%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 25px;
            border-radius: 5px;
            border: 1px solid var(--second-blue);
            font-family: 'Cambria';
            font-size: 16px;
            color: var(--gray-colour);
        }

        select {
            width: 100%;
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

    <div class="card">
        <h2>Payment</h2>
        <form action="2wayconfirm.php" method="POST">
            <!-- Hidden inputs -->
            <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <input type="hidden" name="date" value="<?php echo $date; ?>">
            <input type="hidden" name="seat_qty" value="<?php echo $qty; ?>">
            <input type="hidden" name="total" value="<?php echo $total; ?>">
            <input type="hidden" name="out_flight_id" value="<?php echo $_POST['out_flight_id'] ?? ''; ?>">
            <input type="hidden" name="ret_flight_id" value="<?php echo $_POST['ret_flight_id'] ?? ''; ?>">
            <input type="hidden" name="out_date" value="<?= $out_date ?>">
            <input type="hidden" name="ret_date" value="<?= $ret_date ?>">

            <input type="hidden" name="out_class_id" value="<?php echo $_POST['out_class_id'] ?? $class_id; ?>">
            <input type="hidden" name="ret_class_id" value="<?php echo $_POST['ret_class_id'] ?? ''; ?>">

            <input type="hidden" name="trip_type" value="<?php echo $_POST['trip_type'] ?? 'one-way'; ?>">

            <!-- Passengers info -->
            <?php
            for ($i = 0; $i < $qty; $i++) {
                echo '<input type="hidden" name="fname[]" value="' . $fnames[$i] . '">';
                echo '<input type="hidden" name="mname[]" value="' . $mnames[$i] . '">';
                echo '<input type="hidden" name="lname[]" value="' . $lnames[$i] . '">';
                echo '<input type="hidden" name="email[]" value="' . $emails[$i] . '">';
                echo '<input type="hidden" name="phno[]" value="' . $phones[$i] . '">';
                echo '<input type="hidden" name="gender[]" value="' . $genders[$i] . '">';
                echo '<input type="hidden" name="age[]" value="' . $ages[$i] . '">';
            }
            ?>

            <label>Payment ID:</label>
            <input type="text" value="<?php echo $nextPayId; ?>" readonly>

            <label>Payment Mode:</label>
            <select name="mode" required>
                <option value="">Select Mode</option>
                <option value="UPI">UPI</option>
                <option value="Debit Card">Debit Card</option>
                <option value="Net Banking">Net Banking</option>
            </select>

            <label>Total Amount (₹):</label>
            <input type="text" value="<?php echo $total; ?>" readonly>

            <input type="submit" value="Pay">
        </form>
    </div>

</body>

</html>
