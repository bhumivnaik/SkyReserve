<?php
session_start();
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

require('fpdf.php');
require('PHPMailer-master/src/PHPMailer.php');
require('PHPMailer-master/src/SMTP.php');
require('PHPMailer-master/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Receive POST data ---
$flight_id = $_SESSION['flight_id'] ?? '';
$class_id = intval($_SESSION['class_id'] ?? 0);
$date      = $_POST['date'] ?? '';
$qty       = $_POST['seat_qty'] ?? 0;
$total     = $_POST['total'] ?? 0;
$mode      = $_POST['mode'] ?? '';

$fnames  = $_POST['fname'] ?? [];
$mnames  = $_POST['mname'] ?? [];
$lnames  = $_POST['lname'] ?? [];
$emails  = $_POST['email'] ?? [];
$phones  = $_POST['phno'] ?? [];
$genders = $_POST['gender'] ?? [];
$ages    = $_POST['age'] ?? [];

//$booking_id = $_SESSION['booking_id'] ?? '';
// --- Generate next Booking ID ---
$lastBooking = $conn->query("SELECT booking_id FROM booking ORDER BY booking_id DESC LIMIT 1")->fetch_assoc();
if ($lastBooking) {
    $num = intval(substr($lastBooking['booking_id'], 3));
    $booking_id = 'BID' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
} else {
    $booking_id = 'BID001';
}

if (!$booking_id) {
    die("Booking ID not found. Please start from passenger page.");
}

// --- Generate next Payment ID ---
$lastPay = $conn->query("SELECT pay_id FROM payment ORDER BY pay_id DESC LIMIT 1")->fetch_assoc();
if ($lastPay) {
    $num = intval(substr($lastPay['pay_id'], 3));
    $pay_id = 'PID' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
} else {
    $pay_id = 'PID001';
}

// --- Insert payment ---
$insertPay = $conn->prepare("INSERT INTO payment (pay_id, mode, amount, status) VALUES (?, ?, ?, ?)");
$status = "Paid";
$insertPay->bind_param("ssds", $pay_id, $mode, $total, $status);
$insertPay->execute();

// --- Insert booking ---
$stmt = $conn->prepare("INSERT INTO booking (booking_id, date, status, flight_id, seatsbooked, class_id)
                        VALUES (?, ?, ?, ?, ?, ?)");
$bookingStatus = "Confirmed";
$seats = count($fnames);
$stmt->bind_param("ssssii", $booking_id, $date, $bookingStatus, $flight_id, $seats, $class_id);
$stmt->execute();

$passenger_ids = [];
$stmtPass = $conn->prepare("INSERT INTO passenger (fname, mname, lname, email, phno, gender, age) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtPass->bind_param("ssssssi", $fname, $mname, $lname, $email, $phno, $gender, $age);

for ($i = 0; $i < $seats; $i++) {
    $fname  = $fnames[$i];
    $mname  = $mnames[$i];
    $lname  = $lnames[$i];
    $email  = $emails[$i];
    $phno   = $phones[$i];
    $gender = $genders[$i];
    $age    = $ages[$i];
    $stmtPass->execute();
    $passenger_ids[] = $conn->insert_id;
}

$updateSeats = $conn->prepare("UPDATE flightinstance SET available_seats = available_seats - ? WHERE flight_id = ?");
$updateSeats->bind_param("is", $qty, $flight_id);
$updateSeats->execute();

// --- Insert into makes table ---
$insertMakes = $conn->prepare("INSERT INTO makes (passenger_ID, booking_id, pay_id) VALUES (?, ?, ?)");
foreach ($passenger_ids as $pid) {
    $insertMakes->bind_param("iss", $pid, $booking_id, $pay_id);
    $insertMakes->execute();
}

// --- Fetch flight details ---
$flight = $conn->query("SELECT flight_name, departure_time, arrival_time FROM flight natural join flightinstance WHERE flight_id='$flight_id'")->fetch_assoc();
$flight_name = $flight['flight_name'];
$dep_time = $flight['departure_time'];
$arr_time = $flight['arrival_time'];

$sql = "SELECT 
            a1.city AS source_city, a1.aport_name AS source_airport,
            a2.city AS dest_city, a2.aport_name AS dest_airport
        FROM flight f
        JOIN airport a1 ON f.sourceAcode = a1.acode
        JOIN airport a2 ON f.destAcode = a2.acode
        WHERE f.flight_id = '$flight_id'";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

$source_full = $row['source_city'] . " (" . $row['source_airport'] . ")";
$dest_full   = $row['dest_city'] . " (" . $row['dest_airport'] . ")";
$source_city = $row['source_city'];
$dest_city  = $row['dest_city'];

$classQuery = $conn->query("SELECT class_name FROM class WHERE class_id='$class_id'");
if ($classQuery->num_rows > 0) {
    $classRow = $classQuery->fetch_assoc();
    $class_name = $classRow['class_name'];
} else {
    $class_name = "Unknown";
}

// --- Generate and Send Ticket PDF for each passenger ---
foreach ($passenger_ids as $index => $pid) {
    $pdf = new FPDF();
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 12);

    $pdf->SetFillColor(20, 40, 220);   // Deep royal blue border box
    $pdf->SetFillColor(230, 240, 255); // Background (light blue)220, 230, 255
    $pdf->Rect(10, 10, 190, 277, 'DF'); // x=10, y=20, width=190, height=60
    $pdf->SetFillColor(40, 80, 250);
    $pdf->Rect(10, 5, 190, 28, 'DF');
    $pdf->SetXY(14, 12);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(50, 10, 'E-Ticket', 0, 1);
    $pdf->SetXY(14, 20);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 10, "Booking ID: $booking_id", 0, 1);

    $pdf->SetFont('Arial', 'B', 22);
    $pdf->Image('plane.png', $pdf->SetXY(182, 20), 15, 15);
    $pdf->SetXY(130, 14);
    $pdf->Cell(50, 10, 'SKYRESERVE', 0, 1);

    $pdf->Ln(10); //line break
    $pdf->SetFont('Times', 'BI', 13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetX(14);
    $pdf->Cell(50, 10, 'Passenger Details', 0, 1);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(16, 45, 178, 40, 'F');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX(16);
    $pdf->Cell(45, 10, "Passenger Name     :  " . $fnames[$index] . " " . $mnames[$index] . " " . $lnames[$index], 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(45, 10, "Passenger Email     :  $emails[$index]", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(45, 10, "Passenger Ph.No    :  $phones[$index]", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(45, 10, "Passenger Age        :  $ages[$index]", 0, 1);

    $pdf->Ln(10);
    $pdf->SetX(14);
    $pdf->SetFont('Times', 'BI', 13);
    $pdf->Cell(50, 10, 'Flight Details', 0, 1);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(16, 105, 178, 65, 'F');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX(16);
    $pdf->Cell(40, 15, "Flight Name       : $flight_name", 0, 0);
    $pdf->Cell(49, 15, "", 0, 0);
    $pdf->Cell(40, 15, "Date   : $date", 0, 0);
    $pdf->Cell(49, 15, "", 0, 1);

    $pdf->SetX(16);
    $pdf->Cell(40, 10, "From                   : $source_full", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(40, 10, "To                       : $dest_full", 0, 1);

    $pdf->SetX(16);
    $pdf->Cell(40, 10, "Departure Time  : $dep_time", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(40, 10, "Arrival Time      : $arr_time", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(40, 10, "Class                  : $class_name", 0, 0);


    $pdf->SetX(16);

    $pdf->Ln(20);
    $pdf->SetX(14);
    $pdf->SetFont('Times', 'BI', 13);
    $pdf->Cell(50, 10, 'Payment Details', 0, 1);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(16, 190, 178, 40, 'F');
    $pdf->SetFont('Times', '', 11);
    $pdf->SetX(16);
    $pdf->Cell(40, 10, "Booking ID    : $booking_id", 0, 1);

    $pdf->SetX(16);
    $pdf->Cell(40, 10, "Payment ID    : $pay_id", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(40, 10, "Mode              : $mode", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(40, 10, "Amount          : $total", 0, 1);

    $pdf->Ln(5);
    $pdf->SetX(16);
    $pdf->Cell(100, 6, 'Instructions:', 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(100, 5, '- Please carry a valid ID proof (Passport/Aadhaar/PAN).', 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(100, 5, '- Arrive at least 2 hours before departure.', 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(100, 5, '- For any queries, contact skyreserve@airline.com', 0, 1);

    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetXY(15, 265);
    $pdf->Cell(0, 10, 'Thank you for choosing SkyReserve! Have a Safe Journey.', 0, 0, 'C');

    $pdfFile = "tickets/ticket_$booking_id$pid.pdf";
    $pdf->Output($pdfFile, 'F');

    // --- Send Email with attachment ---
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bhumivnaik@gmail.com'; // 🔹 Your Gmail
        $mail->Password = 'hsuf atjj tczi ncty';   // 🔹 App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('bhumivnaik@gmail.com', 'Airline Reservation');
        $mail->addAddress($emails[$index]); // Passenger email
        $mail->isHTML(true);
        $mail->Subject = 'Your Airline Ticket - ' . $flight_name;
        $mail->Body = "<h3>Dear " . $fnames[$index] . ",</h3>
                       <p>Your booking (<b>$booking_id</b>) is confirmed!</p>
                       <p>Flight: <b>$flight_name ($flight_id)</b><br>
                       Date: $date<br>
                       Departure: $dep_time<br>
                       Arrival: $arr_time</p>
                       <p><b>Thank you for flying with us!</b></p>";
        $mail->addAttachment($pdfFile);
        $mail->send();
    } catch (Exception $e) {
        error_log("Mail could not be sent. Error: {$mail->ErrorInfo}");
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Booking Confirmed</title>
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
            text-align: center;
            background: #e3f2fd;
            padding: 0px;
        }

        .confirm-card {
            max-width: 500px;
            margin: 40px auto;
            padding: 15px 30px;
            border-radius: 18px;
            background: linear-gradient(135deg,
                    var(--white-color-light),
                    #ffffffdd);
            backdrop-filter: blur(10px);
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.6);
            font-family: "Cambria";
        }

        .confirm-header h2 {
            font-size: 35px;
            margin-bottom: 5px;
            color: var(--dark-blue);
        }

        .confirm-header span {
            color: #2ecc71;
            font-size: 30px;
        }

        .confirm-header p {
            font-size: 16px;
            color: var(--second-blue);
            margin-bottom: 20px;
        }


        .confirm-details div {
            background: #ffffff;
            padding: 12px 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 16px;
            display: flex;
            justify-content: baseline;
            border-left: 4px solid var(--third-blue);
            transition: 0.25s;
            color: var(--gray-colour);
        }

        .confirm-details div:hover {
            transform: translateX(5px);
            background: var(--second-blue);
            color: white;
            border-left-color: var(--dark-blue);
        }

        .confirm-footer h3 {
            text-align: center;
            margin-top: 25px;
            color: var(--dark-blue);
            font-weight: bold;
        }

        .confirm-back {
            text-align: center;
            margin-top: 25px;
        }

        .confirm-back a {
            display: inline-block;
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: white;
            padding: 12px 28px;
            font-size: 17px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: 0.25s ease;
        }

        .confirm-back a:hover {
            transform: scale(1.07);
            background: linear-gradient(135deg, var(--dark-blue), var(--second-blue));
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
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
    </style>
</head>

<body>
    <video autoplay muted loop class="video-bg">
        <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>
    <div class="overlay"></div>


    <div class="confirm-card">
        <div class="confirm-header">
            <h2>Booking Confirmed <span>✔</span></h2>
            <p>Your journey begins now!</p>
        </div>

        <div class="confirm-details">
            <div><strong>Flight:</strong> <?php echo $flight_name; ?> (<?php echo $flight_id; ?>)</div>
            <div><strong>Source:</strong> <?php echo $source_city; ?></div>
            <div><strong>Destination:</strong> <?php echo $dest_city; ?></div>
            <div><strong>Departure:</strong> <?php echo $dep_time; ?></div>
            <div><strong>Arrival:</strong> <?php echo $arr_time; ?></div>
            <div><strong>Date:</strong> <?php echo $date; ?></div>
            <div><strong>Class:</strong> <?php echo $class_id; ?></div>
            <div><strong>Total Passengers:</strong> <?php echo $seats; ?></div>
            <div><strong>Payment ID:</strong> <?php echo $pay_id; ?></div>
        </div>

        <div class="confirm-footer">
            <h3>Tickets sent to your registered email.</h3>
        </div>

        <div class="confirm-back">
            <a href="http://localhost/airportrs/index.html">⟵ Back to Home</a>
        </div>
    </div>

</body>

</html>