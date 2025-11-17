<?php
session_start();
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

require('../fpdf.php');
require('../PHPMailer-master/src/PHPMailer.php');
require('../PHPMailer-master/src/SMTP.php');
require('../PHPMailer-master/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ---------------------------------------------------
    READ DATA FROM PAYMENT PAGE
-----------------------------------------------------*/

$trip_type      = $_POST['trip_type'] ?? 'oneway';

$out_flight_id  = $_POST['out_flight_id'] ?? '';
$ret_flight_id  = $_POST['ret_flight_id'] ?? '';

$out_class_id   = intval($_POST['out_class_id'] ?? 0);
$ret_class_id   = intval($_POST['ret_class_id'] ?? 0);

$out_date       = $_POST['out_date'] ?? '';
$ret_date       = $_POST['ret_date'] ?? '';

$qty            = intval($_POST['seat_qty']);
$total          = intval($_POST['total']);

$fnames = $_POST['fname'];
$mnames = $_POST['mname'];
$lnames = $_POST['lname'];
$emails = $_POST['email'];
$phones = $_POST['phno'];
$genders = $_POST['gender'];
$ages = $_POST['age'];

$mode = $_POST['mode'];

/* ---------------------------------------------------
    1) GENERATE PAYMENT ID
-----------------------------------------------------*/
$row = $conn->query("SELECT pay_id FROM payment ORDER BY pay_id DESC LIMIT 1")->fetch_assoc();
if ($row) {
    $num = intval(substr($row['pay_id'], 3)) + 1;
    $pay_id = "PID" . str_pad($num, 3, "0", STR_PAD_LEFT);
} else {
    $pay_id = "PID001";
}

/* ---------------------------------------------------
    2) INSERT PAYMENT
-----------------------------------------------------*/
$stmt = $conn->prepare("INSERT INTO payment(pay_id, mode, amount, status) VALUES (?, ?, ?, 'Paid')");
$stmt->bind_param("ssi", $pay_id, $mode, $total);
$stmt->execute();

/* ---------------------------------------------------
    3) GENERATE NEXT BOOKING ID
-----------------------------------------------------*/
function nextBookingID($conn)
{
    $row = $conn->query("SELECT booking_id FROM booking ORDER BY booking_id DESC LIMIT 1")->fetch_assoc();
    if ($row) {
        $num = intval(substr($row['booking_id'], 3)) + 1;
        return "BID" . str_pad($num, 3, "0", STR_PAD_LEFT);
    } else {
        return "BID001";
    }
}

/* ---------------------------------------------------
    4) INSERT PASSENGERS
-----------------------------------------------------*/
$passenger_ids = [];

for ($i = 0; $i < $qty; $i++) {

    $stmt = $conn->prepare("
        INSERT INTO passenger(fname, mname, lname, gender, age, email, phno)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssiss",
        $fnames[$i],
        $mnames[$i],
        $lnames[$i],
        $genders[$i],
        $ages[$i],
        $emails[$i],
        $phones[$i]
    );
    $stmt->execute();

    $passenger_ids[] = $stmt->insert_id;
}

/* ---------------------------------------------------
    5) INSERT OUTBOUND BOOKING
-----------------------------------------------------*/
$booking_id = nextBookingID($conn);

$status = "Confirmed";
// Insert outbound booking
$stmt = $conn->prepare("
    INSERT INTO booking(booking_id, flight_id, class_id, date, seatsbooked, status)
    VALUES (?, ?, ?, ?, ?,?)
");
$stmt->bind_param("ssisis", $booking_id, $out_flight_id, $out_class_id, $out_date, $qty, $status);
$stmt->execute();

// Insert return booking (same booking id)
if ($trip_type == "twoway") {
    $stmt = $conn->prepare("
        INSERT INTO booking(booking_id, flight_id, class_id, date, seatsbooked,status)
        VALUES (?, ?, ?, ?, ?,?)
    ");
    $stmt->bind_param("ssisis", $booking_id, $ret_flight_id, $ret_class_id, $ret_date, $qty, $status);
    $stmt->execute();
}


/* ---------------------------------------------------
    7) LINK PASSENGERS → BOOKINGS → PAYMENT
-----------------------------------------------------*/

foreach ($passenger_ids as $pid) {

    if ($trip_type == "twoway") {
        $stmt = $conn->prepare("INSERT INTO makes(passenger_id, booking_id, pay_id) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $pid, $booking_id, $pay_id);
        $stmt->execute();
    }
}

//seatavailable update
$updateSeats = $conn->prepare("UPDATE flightinstance SET available_seats = available_seats - ? WHERE flight_id = ?");
$updateSeats->bind_param("is", $qty, $out_flight_id);
$updateSeats->execute();

if ($trip_type == "twoway") {
    $updateSeats = $conn->prepare("UPDATE flightinstance SET available_seats = available_seats - ? WHERE flight_id = ?");
    $updateSeats->bind_param("is", $qty, $ret_flight_id);
    $updateSeats->execute();
}

// Fetch outbound flight details
$outQuery = $conn->query(" SELECT f.flight_id, f.flight_name,
                   a1.city AS source,
                   a2.city AS destination,
                   fi.date, fi.departure_time as dep_time, fi.arrival_time as arr_time
            FROM flight f
            JOIN airport a1 ON f.sourceAcode = a1.acode
            JOIN airport a2 ON f.destAcode = a2.acode
            JOIN flightinstance fi ON f.flight_id = fi.flight_id
            WHERE f.flight_id='$out_flight_id'
            AND fi.date='$out_date'
            LIMIT 1
");
$outData = $outQuery->fetch_assoc();

// Fetch return flight details (only for two-way)
$retData = null;
if ($trip_type == "twoway") {
    $retQuery = $conn->query(" SELECT f.flight_id, f.flight_name,
                   a1.city AS source,
                   a2.city AS destination,
                   fi.date, fi.departure_time as dep_time, fi.arrival_time as arr_time
            FROM flight f
            JOIN airport a1 ON f.sourceAcode = a1.acode
            JOIN airport a2 ON f.destAcode = a2.acode
            JOIN flightinstance fi ON f.flight_id = fi.flight_id
            WHERE f.flight_id='$ret_flight_id'
            AND fi.date='$ret_date'
            LIMIT 1");
    $retData = $retQuery->fetch_assoc();
}

/* ---------------------------------------------------
    7) GENERATE PDF TICKET (FULL DETAILED)
-----------------------------------------------------*/

/* ---------------------------------------------------
    8) SEND EMAIL (With Ticket Attached)
-----------------------------------------------------*/
foreach ($passenger_ids as $index => $pid) {
    $pdf = new FPDF();
    $pdf->AddPage();

    // Header background
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(230, 240, 255);
    $pdf->Rect(10, 10, 190, 277, 'F');

    // Top blue bar
    $pdf->SetFillColor(40, 80, 250);
    $pdf->Rect(10, 5, 190, 28, 'F');

    // Header Title
    $pdf->SetXY(14, 12);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(50, 10, 'E-Ticket', 0, 1);

    $pdf->SetXY(14, 20);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 10, "Booking ID: $booking_id", 0, 1);

    // Branding
    $pdf->SetFont('Arial', 'B', 22);
    $pdf->Image('../plane.png', $pdf->SetXY(182, 20), 15, 15);
    $pdf->SetXY(130, 14);
    $pdf->Cell(50, 10, 'SKYRESERVE', 0, 1);


    // ---------- PASSENGER DETAILS ----------
    $pdf->Ln(10);
    $pdf->SetFont('Times', 'BI', 13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetX(14);
    $pdf->Cell(50, 10, 'Passenger Details', 0, 1);

    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(16, 45, 178, 40, 'F');

    $pdf->SetFont('Times', '', 11);
    $pdf->SetX(16);
    $pdf->Cell(45, 8, "Passenger Name : " . $fnames[$index] . " " . $mnames[$index] . " " . $lnames[$index], 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(45, 8, "Passenger Email : $emails[$index]", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(45, 8, "Passenger Ph.No : $phones[$index]", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(45, 8, "Passenger Age : $ages[$index]", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(45, 8, "Trip Type :  $trip_type", 0, 1);


    // ---------- OUTBOUND FLIGHT DETAILS ----------
    $pdf->Ln(10);
    $pdf->SetX(14);
    $pdf->SetFont('Times', 'BI', 13);
    $pdf->Cell(50, 10, 'Outbound Flight Details', 0, 1);

    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(16, 105, 178, 35, 'F');

    $pdf->SetFont('Times', '', 11);

    $pdf->SetX(16);
    $pdf->Cell(40, 10, "Flight Name : $out_flight_id", 0, 0);
    $pdf->Cell(49, 10, "", 0, 0);
    $pdf->Cell(40, 10, "Date : {$outData['date']}", 0, 1);

    $pdf->SetX(16);
    $pdf->Cell(40, 8, "From : {$outData['source']}", 0, 0);
    $pdf->Cell(49, 12, "", 0, 0);
    $pdf->Cell(40, 8, "To : {$outData['destination']}", 0, 1);

    $pdf->SetX(16);
    $pdf->Cell(40, 8, "Departure Time : {$outData['dep_time']}", 0, 0);
    $pdf->Cell(49, 12, "", 0, 0);
    $pdf->Cell(40, 8, "Arrival Time : {$outData['arr_time']}", 0, 1);

    $pdf->SetX(16);
    $pdf->Cell(40, 8, "Class : $out_class_id", 0, 1);


    // ---------- RETURN FLIGHT (IF TWO-WAY) ----------
    if ($trip_type == "twoway") {

        $pdf->Ln(10);
        $pdf->SetX(14);
        $pdf->SetFont('Times', 'BI', 13);
        $pdf->Cell(50, 10, 'Return Flight Details', 0, 1);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(16, 160, 178, 35, 'F');

        $pdf->SetFont('Times', '', 11);

        $pdf->SetX(16);
        $pdf->Cell(40, 10, "Flight Name : $ret_flight_id", 0, 0);
        $pdf->Cell(49, 10, "", 0, 0);
        $pdf->Cell(40, 10, "Date : {$retData['date']}", 0, 1);

        $pdf->SetX(16);
        $pdf->Cell(40, 8, "From : {$retData['source']}", 0, 0);
        $pdf->Cell(49, 8, "", 0, 0);
        $pdf->Cell(40, 8, "To : {$retData['destination']}", 0, 1);

        $pdf->SetX(16);
        $pdf->Cell(40, 8, "Departure Time : {$retData['dep_time']}", 0, 0);
        $pdf->Cell(49, 8, "", 0, 0);
        $pdf->Cell(40, 8, "Arrival Time : {$retData['arr_time']}", 0, 1);

        $pdf->SetX(16);
        $pdf->Cell(40, 8, "Class : $ret_class_id", 0, 1);
    }


    // ---------- PAYMENT DETAILS ----------
    $pdf->Ln(10);
    $pdf->SetX(14);
    $pdf->SetFont('Times', 'BI', 13);
    $pdf->Cell(50, 10, 'Payment Details', 0, 1);

    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(16, 210, 178, 25, 'F');

    $pdf->SetFont('Times', '', 11);
    $pdf->SetX(16);
    $pdf->Cell(40, 8, "Payment ID : $pay_id", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(40, 8, "Mode : $mode", 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(40, 8, "Amount : ₹$total", 0, 1);
    $pdf->Ln(5);
    $pdf->SetX(16);
    $pdf->SetFont('Times', '', 10);
    $pdf->Cell(100, 6, 'Instructions:', 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(100, 5, '- Please carry a valid ID proof (Passport/Aadhaar/PAN).', 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(100, 5, '- Arrive at least 2 hours before departure.', 0, 1);
    $pdf->SetX(16);
    $pdf->Cell(100, 5, '- For any queries, contact skyreserve@airline.com', 0, 1);


    // Footer
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetXY(15, 266);
    $pdf->Cell(0, 10, 'Thank you for choosing SkyReserve! Have a Safe Journey.', 0, 0, 'C');

    $pdfFile = "../tickets/$booking_id$pid.pdf";
    $pdf->Output("F", $pdfFile);


    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bhumivnaik@gmail.com';
        $mail->Password = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('bhumivnaik@gmail.com', 'Airport Reservation');
        $mail->addAddress($emails[$index]); // first passenger

        $mail->Subject = 'Your Airline Ticket - ' . $booking_id;
        $mail->Body = "<h3>Dear " . $fnames[$index] . ",</h3>
                       <p>Your booking (<b>$booking_id</b>) is confirmed!</p>
                       <p><b>Thank you for flying with us!</b></p>";
        $mail->addAttachment($pdfFile);

        $mail->send();
    } catch (Exception $e) {
        // Email sending errors ignored
    }
}

/* ---------------------------------------------------
    9) SHOW CONFIRMATION PAGE ON SCREEN (NO REDIRECT)
-----------------------------------------------------*/


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
            background: linear-gradient(to right, var(--dark-blue), var(--second-blue));
            padding: 60px;
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
            background: linear-gradient(to bottom, rgba(0, 0, 59, 0.6), rgba(0, 0, 0, 0.8));
            z-index: -1;
            backdrop-filter: blur(2px);
        }

        .confirm-card {
            max-width: 550px;
            margin: auto;
            background: var(--white-color-light);
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            animation: fadeIn 0.7s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            text-align: center;
            font-family: "Libertinus Serif";
            color: var(--dark-blue);
            margin-bottom: 25px;
        }

        .summary-box,
        .flight-box {
            background: #efefef;
            padding: 18px 22px;
            border-radius: 12px;
            border: 1px solid var(--second-blue);
            border-left: 6px solid var(--second-blue);
            margin-bottom: 22px;
        }

        .box-title {
            font-size: 14px;
            text-transform: uppercase;
            color: var(--second-blue);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .detail-row {
            font-size: 16px;
            color: var(--gray-colour);
            margin: 5px 0;
        }

        .btn-download {
            display: block;
            text-align: center;
            padding: 14px;
            margin-top: 20px;
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: white;
            font-size: 18px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s ease;
        }

        .btn-download:hover {
            transform: scale(1.05);
            box-shadow: 0 0 22px rgba(53, 172, 252, 0.45);
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
    </style>
</head>

<body>
    <video autoplay muted loop class="video-bg">
        <source src="../From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>
    <div class="overlay"></div>

    <div class="confirm-card">
        <div class="confirm-header">
            <h2>Booking Confirmed <span>✔</span></h2>
            <p>Your journey begins now!</p>
        </div>
        <!-- TOP SUMMARY BOX -->
        <div class="summary-box">
            <div class="box-title">Booking Summary</div>
            <div class="detail-row"><b>Booking ID:</b> <?= $booking_id ?></div>
            <div class="detail-row"><b>Payment ID:</b> <?= $pay_id ?></div>
            <div class="detail-row"><b>Trip Type:</b> <?= strtoupper($trip_type) ?></div>
            <div class="detail-row"><b>Total Passengers:</b> <?= $qty ?></div>
            <div class="detail-row"><b>Total Amount Paid:</b> ₹<?= $total ?></div>
        </div>

        <!-- OUTBOUND FLIGHT DETAILS -->
        <div class="flight-box">
            <div class="box-title">Outbound Flight Details</div>

            <div class="detail-row"><b>Flight ID:</b> <?= $out_flight_id ?></div>
            <div class="detail-row"><b>Source:</b> <?= $outData['source'] ?></div>
            <div class="detail-row"><b>Destination:</b> <?= $outData['destination'] ?></div>
            <div class="detail-row"><b>Departure Time:</b> <?= $outData['dep_time'] ?></div>
            <div class="detail-row"><b>Arrival Time:</b> <?= $outData['arr_time'] ?></div>
            <div class="detail-row"><b>Date:</b> <?= $outData['date'] ?></div>
        </div>

        <!-- RETURN FLIGHT DETAILS (only if two-way) -->
        <?php if ($trip_type == "twoway") { ?>
            <div class="flight-box">
                <div class="box-title">Return Flight Details</div>
                <div class="detail-row"><b>Flight ID:</b> <?= $ret_flight_id ?></div>
                <div class="detail-row"><b>Source:</b> <?= $retData['source'] ?></div>
                <div class="detail-row"><b>Destination:</b> <?= $retData['destination'] ?></div>
                <div class="detail-row"><b>Departure Time:</b> <?= $retData['dep_time'] ?></div>
                <div class="detail-row"><b>Arrival Time:</b> <?= $retData['arr_time'] ?></div>
                <div class="detail-row"><b>Date:</b> <?= $retData['date'] ?></div>
            </div>
        <?php } ?>

        <!-- DOWNLOAD BUTTON -->
        <a class="btn-download" href="<?= $pdfFile ?>" download>
            📄 Download Ticket (PDF)
        </a>

        <div class="confirm-footer">
            <h3>Tickets sent to your registered email.</h3>
        </div>

        <div class="confirm-back">
            <a href="http://localhost/airportrs/index.html">⟵ Back to Home</a>
        </div>
    </div>

</body>

</html>
