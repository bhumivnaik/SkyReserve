<?php
session_start();
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$flight_id = $_POST['flight_id'];
$flight_name = $_POST['flight_name'];
$source = $_POST['source'];
$destination = $_POST['destination'];
$date = $_POST['date'];
$class_id = intval($_POST['class_id'] ?? 0);
$qty = $_POST['seat_qty'];
$price = $_POST['price'];
$total = $_POST['total'];

$_SESSION['class_id'] = $class_id;
$_SESSION['flight_id'] = $flight_id;

  // --- Generate next Booking ID ---
$lastBooking = $conn->query("SELECT booking_id FROM booking ORDER BY booking_id DESC LIMIT 1")->fetch_assoc();
if($lastBooking){
    $num = intval(substr($lastBooking['booking_id'],3));
    $booking_id = 'BID'.str_pad($num+1,3,'0',STR_PAD_LEFT);
}else{
    $booking_id = 'BID001';
}
$_SESSION['booking_id'] = $booking_id;

   
?>

<!DOCTYPE html>
<html>
<head>
    <title>Passenger Details</title>
    <style>
        body {
            font-family: Cambria, serif;
            background: linear-gradient(to right, #a0d8ff, #b0d3fa);
            padding: 30px;
        }
        .container {
      max-width: 900px;
      background: #fff;
      margin: 40px auto;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
        form {
           display: flex; flex-direction: column; gap: 20px;
        }
        
        h2{
            font-family: "Libertinus Serif";
            font-size:35px;
            color: #0161a5ff;
            margin: 37px auto;
            margin-bottom:30px;
        }
        h3 {
      color: #0074e4;
      font-family: 'Merriweather';

      border-left: 5px solid #0074e4;
      padding-left: 10px;
      margin-top: 20px;
      margin-bottom: 15px;
    }
    .passenger-box {
      border: 1px solid #ddd;
      box-shadow: 0 0 10px rgba(0,0,0,0.2);
      padding: 15px;
      border-radius: 8px;
    margin-bottom: 15px;

    }
    .passenger-box h3 {
      margin-bottom: 15px;
      color: #333;
    }
    .form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}



    label {
      font-size: 14px;
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
    }
    input, select {
      width: 94%;
      padding: 8px;
      border: 1px solid #bbb;
      border-radius: 5px;
      font-size: 14px;
      font-family:'Cambria';
      text-transform: capitalize;
    }
        input[type="email"] {
            text-transform:none;
        }
        input[type="submit"] {
            background:#0078D7; color:white; border:none;
            border-radius:5px; cursor:pointer; margin-top:15px;
            padding:10px;
        }
        input[type="submit"]:hover { background:#005fa3; }
    </style>
</head>
<body>
<div class="container">
 <h2 style="text-align:center;">Passenger Details </h2>
    <p>Price per seat: <strong>₹<?=number_format($price,2)?></strong> |
       Total: <strong>₹<?=number_format($total,2)?></strong></p>

 <form action="payment.php" method="POST">
    <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
    <input type="hidden" name="seat_qty" value="<?php echo $qty; ?>">
    <input type="hidden" name="total" value="<?php echo $total; ?>">
    <input type="hidden" name="date" value="<?php echo $date; ?>">
    <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">


    <?php
  
 for ($i = 1; $i <= $qty; $i++) 
 {
    $passenger_id = "P" . str_pad($i, 3, "0", STR_PAD_LEFT);
    echo"
    <div class='passenger-box'>
        <h3>Passenger $i</h3>
        <fieldset style='margin-top:15px;border:1px solid #ccc;border-radius:8px;padding:15px;'>
            <input type='hidden' name='passenger_id[]' value='$passenger_id'>
            <div class='form-grid'>
                <div>
                    <label>First Name:</label>
                    <input type='text' name='fname[]' required>
                </div>
                <div>
                    <label>Middle Name:</label>
                    <input type='text' name='mname[]'>
                </div>
                <div>
                    <label>Last Name:</label>
                    <input type='text' name='lname[]' required>
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
    </div>";
}
    ?>
<input type="submit" value="Confirm Booking ">
 </form>
</div>
</body>
</html>
