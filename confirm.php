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
if($lastBooking){
    $num = intval(substr($lastBooking['booking_id'],3));
    $booking_id = 'BID'.str_pad($num+1,3,'0',STR_PAD_LEFT);
}else{
    $booking_id = 'BID001';
}

if (!$booking_id) {
    die("Booking ID not found. Please start from passenger page.");
}

// --- Generate next Payment ID ---
$lastPay = $conn->query("SELECT pay_id FROM payment ORDER BY pay_id DESC LIMIT 1")->fetch_assoc();
if($lastPay){
    $num = intval(substr($lastPay['pay_id'],3));
    $pay_id = 'PID'.str_pad($num+1,3,'0',STR_PAD_LEFT);
}else{
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

for($i=0; $i<$seats; $i++){
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

// --- Insert into makes table ---
$insertMakes = $conn->prepare("INSERT INTO makes (passenger_ID, booking_id, pay_id) VALUES (?, ?, ?)");
foreach($passenger_ids as $pid){
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


$classQuery = $conn->query("SELECT class_name FROM class WHERE class_id='$class_id'");
if($classQuery->num_rows > 0){
    $classRow = $classQuery->fetch_assoc();
    $class_name = $classRow['class_name'];
} else {
    $class_name = "Unknown";
}

// --- Generate and Send Ticket PDF for each passenger ---
foreach($passenger_ids as $index => $pid){
    $pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 12);

$pdf->SetFillColor(20, 40, 220);   // Deep royal blue border box
$pdf->SetFillColor(230, 240, 255); // Background (light blue)220, 230, 255
$pdf->Rect(10, 10, 190, 277, 'DF'); // x=10, y=20, width=190, height=60
$pdf->SetFillColor(40, 80, 250);
$pdf->Rect(10, 10, 190, 20, 'DF');
$pdf->SetXY(14,12);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 20);
$pdf->Cell(50, 10, 'E-Ticket', 0, 1);
$pdf->SetXY(14,20);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 10, "Booking ID: $booking_id", 0, 1);

$pdf->SetFont('Arial', 'B', 22);
$pdf->Image('plane.png', $pdf->SetXY(182,20), 15, 15); 
$pdf->SetXY(130,15);
$pdf->Cell(50, 10, 'SKYRESERVE', 0, 1);

$pdf->Ln(10);//line break
$pdf->SetFont('Times', 'BI', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetX(14);
$pdf->Cell(50, 10, 'Passenger Details', 0, 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(16, 45, 178, 40, 'DF'); 
$pdf->SetFont('Times', '', 11);
$pdf->SetX(16);
$pdf->Cell(60, 10, 'Passenger Name', 1, 0);
$pdf->Cell(118, 10, $fnames[$index]." ".$mnames[$index]." ".$lnames[$index], 1, 1);
$pdf->SetX(16);
$pdf->Cell(60, 10, 'Passenger Email', 1, 0);
$pdf->Cell(118, 10, "$emails[$index]", 1, 1);
$pdf->SetX(16);
$pdf->Cell(60, 10, 'Passenger Phone.No', 1, 0);
$pdf->Cell(118, 10, "$phones[$index]", 1, 1);
$pdf->SetX(16);
$pdf->Cell(60, 10, 'Passenger Age', 1, 0);
$pdf->Cell(118, 10, "$ages[$index]", 1, 1);

$pdf->Ln(10);
$pdf->SetX(14);
$pdf->SetFont('Times', 'BI', 12);
$pdf->Cell(50, 10, 'Flight Details', 0, 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(16, 105, 178, 65, 'DF'); 
$pdf->SetFont('Times', '', 11);
$pdf->SetX(16);
$pdf->Cell(40, 10, 'Flight Name', 1, 0);
$pdf->Cell(49, 10, "$flight_name", 1, 0);
$pdf->Cell(40, 10, 'Date', 1, 0);
$pdf->Cell(49, 10, "$date", 1, 1);

$pdf->SetX(16);
$pdf->Cell(89, 10, 'From', 1, 0, 'C');
$pdf->Cell(89, 10, 'To', 1, 1, 'C');
$pdf->SetX(16);
$pdf->Cell(89, 10, "$source_full", 1, 0,'C');
$pdf->Cell(89, 10, "$dest_full", 1, 1,'C');
$pdf->Ln(5);
$pdf->SetX(16);
$pdf->Cell(40, 10, 'Departure Time', 1, 0);
$pdf->Cell(138, 10, "$dep_time", 1, 1);
$pdf->SetX(16);
$pdf->Cell(40, 10, 'Arrival Time', 1, 0);
$pdf->Cell(138, 10, "$arr_time", 1, 1);
$pdf->SetX(16);
$pdf->Cell(40, 10, 'Class', 1, 0);
$pdf->Cell(138, 10, "$class_name", 1, 1);


$pdf->SetX(16);

$pdf->Ln(10);
$pdf->SetX(14);
$pdf->SetFont('Times', 'BI', 12);
$pdf->Cell(50, 10, 'Payment Details', 0, 1);
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(16, 190, 178, 40, 'DF'); 
$pdf->SetFont('Times', '', 11);
$pdf->SetX(16);
$pdf->Cell(40, 10, 'Booking ID', 1, 0);
$pdf->Cell(138, 10, "$booking_id", 1, 1);
$pdf->SetX(16);
$pdf->Cell(40, 10, 'Payment ID', 1, 0);
$pdf->Cell(138, 10,"$pay_id" , 1, 1);
$pdf->SetX(16);
$pdf->Cell(40, 10, 'Mode', 1, 0);
$pdf->Cell(138, 10, "$mode", 1, 1);
$pdf->SetX(16);
$pdf->Cell(40, 10, 'Amount', 1, 0);
$pdf->Cell(138, 10, "$total", 1, 1);

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

        $mail->setFrom('bhumiv@gmail.com', 'Airline Reservation');
        $mail->addAddress($emails[$index]); // Passenger email
        $mail->isHTML(true);
        $mail->Subject = 'Your Airline Ticket - '.$flight_name;
        $mail->Body = "<h3>Dear ".$fnames[$index].",</h3>
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
        body {font-family:Cambria, serif; text-align:center; background:#e3f2fd; padding:40px;}
        .card {background:white; padding:30px; margin:auto; width:400px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.3);}
        .card p {margin: 8px 0;}
    </style>
</head>
<body>
<div class="card">
    <h2>Booking Confirmed ✅</h2>
    <p><b>Flight:</b> <?php echo $flight_name; ?> (<?php echo $flight_id; ?>)</p>
    <p><b>Departure:</b> <?php echo $dep_time; ?></p>
    <p><b>Arrival:</b> <?php echo $arr_time; ?></p>
    <p><b>Date:</b> <?php echo $date; ?></p>
    <p><b>Class:</b> <?php echo $class_id; ?></p>
    <p><b>Total Passengers:</b> <?php echo $seats; ?></p>
    <p><b>Payment ID:</b> <?php echo $pay_id; ?></p>
    <h3>Tickets sent to your registered emails 📧</h3>
</div>
</body>
</html>