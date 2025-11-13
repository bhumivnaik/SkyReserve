<?php
session_start();
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$flight_id = $_POST['flight_id'] ?? '';
$class_id  = $_POST['class_id'];
$qty       = $_POST['seat_qty'] ?? 0;
$total     = $_POST['total'] ?? 0;
$date      = $_POST['date'] ?? '';

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


if($check->num_rows > 0){
    header("Location: confirm.php?booking_id=".$booking_id);
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
if (!isset($_SESSION['nextPayId'])) {
    $lastPay = $conn->query("SELECT pay_id FROM payment ORDER BY pay_id DESC LIMIT 1")->fetch_assoc();
    if($lastPay){
        $lastNum = intval(substr($lastPay['pay_id'], 3));
        $_SESSION['nextPayId'] = 'PID'.str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $_SESSION['nextPayId'] = 'PID001';
    }
}
$nextPayId = $_SESSION['nextPayId'];

?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment</title>
    <style>
        body {font-family: Cambria, serif;  background:linear-gradient(to right, #0161a5ff, #a5cfffff);; padding:40px;}
        h2{
            font-family: "Libertinus Serif";
            font-size:35px;
            color: #0161a5ff;
            margin: 37px auto;
            text-align: center;
            margin-bottom:30px;
        }
        .card {background:white; padding:30px; margin:auto; width:500px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.3);}
        label{display:block; margin-top:10px; font-weight:bold;}
        select, input {width: 99%; padding: 8px; margin-top:5px;margin-bottom:15px;
            border-radius:5px;
            border:1px solid #ccc;
            font-family: 'Cambria';
            font-size: 16px;}
        input[type="submit"] {background:#0078D7;color:white;border:none;padding:10px;margin-top:15px;border-radius:5px; cursor:pointer;}
        input[type="submit"]:hover {background:#005fa3;}
    </style>
</head>
<body>

<div class="card">
    <h2>Payment</h2>
    <form action="confirm.php" method="POST">
        <!-- Hidden inputs -->
        <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
        <input type="hidden" name="date" value="<?php echo $date; ?>">
        <input type="hidden" name="total" value="<?php echo $total; ?>">
        <!-- Passengers info -->
        <?php
        for($i=0; $i<$qty; $i++){
            echo '<input type="hidden" name="fname[]" value="'.$fnames[$i].'">';
            echo '<input type="hidden" name="mname[]" value="'.$mnames[$i].'">';
            echo '<input type="hidden" name="lname[]" value="'.$lnames[$i].'">';
            echo '<input type="hidden" name="email[]" value="'.$emails[$i].'">';
            echo '<input type="hidden" name="phno[]" value="'.$phones[$i].'">';
            echo '<input type="hidden" name="gender[]" value="'.$genders[$i].'">';
            echo '<input type="hidden" name="age[]" value="'.$ages[$i].'">';
        }
        ?>

        <label>Payment ID:</label>
        <input type="text" value="<?php echo $nextPayId; ?>" readonly>

        <label>Payment Mode:</label>
        <select name="mode" required>
            <option value="">--Select Mode--</option>
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
