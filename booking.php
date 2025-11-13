<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$flight_id = $_GET['flight_id'] ?? '';
$flight_name = $_GET['flight_name'] ?? '';
$source = $_GET['source'] ?? '';
$destination = $_GET['destination'] ?? '';
$date = $_GET['date'] ?? '';

// Fetch available classes for this flight
$sql = "SELECT * FROM price WHERE flight_id='$flight_id'";
$result = $conn->query($sql);


?>

<!DOCTYPE html>
<html>
<head>
    <title>Flight Booking</title>
    <style>
        body {
            font-family: Cambria, serif;
            background: linear-gradient(to right, #b0d3fa, #a0d8ff);
            padding: 40px;
        }
        h2{
            font-family: "Libertinus Serif";
            font-size:35px;
            color: #0161a5ff;
            margin: 37px auto;
            margin-bottom:30px;
        }
        p {
      text-align: center;
      color: #555;
      margin-bottom: 35px;
      font-size: 16px;
    }

        form {
            background: white;
            max-width: 500px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
        label { display:block; margin-top:10px; font-weight:bold; }
display:block; margin-top:10px; font-weight:bold;
        select, input {
            width: 99%; padding: 8px; margin-top:5px;margin-bottom:15px;
            border-radius:5px;
            border:1px solid #ccc;
            font-family: 'Cambria';
            font-size: 16px;
        }
        
        input[type="number"], input[type="text"], input[type="submit"] {
            width:96%;
            padding:8px;
            margin-top:5px;
            margin-bottom:15px;
            border-radius:5px;
            font-family: 'Cambria';
            border:1px solid #ccc;
        }
        input[type="submit"] {
            width:99%;
            background:#0078D7; color:white; border:none;
            border-radius:5px; cursor:pointer; margin-top:15px;
            padding:10px;
        }
        
        input[type="submit"]:hover { background: #005fa3; }
    </style>
</head>
<body>


<form action="passenger.php" method="POST">
    <h2 style="text-align:center;">Book a Flight</h2>
    <input type="hidden" name="flight_id" value="<?php echo $flight_id; ?>">
    <input type="hidden" name="flight_name" value="<?php echo $flight_name; ?>">
    <input type="hidden" name="source" value="<?php echo $source; ?>">
    <input type="hidden" name="destination" value="<?php echo $destination; ?>">
    <input type="hidden" name="date" value="<?php echo $date; ?>">

    <p>
    <strong><?php echo $flight_name; ?></strong><br>
    <?php echo $source; ?> → <?php echo $destination; ?>
  </p>

    <label>Class:</label>
    <select name="class_id" id="class_id" required onchange="updatePrice()">
        <option value="">Select Class</option>
        <?php
        $prices = [];
        while ($row = $result->fetch_assoc()) {
            $prices[$row['class_id']] = $row['price'];
            if ($row['class_id'] == '1001') $class_name = "Economy";
            elseif ($row['class_id'] == '1002') $class_name = "Business";
            elseif ($row['class_id'] == '1003')$class_name = "First";
            else $class_name = "Unknown";
            echo "<option value='{$row['class_id']}'>$class_name</option>";
        }
        ?>
    </select>
    

    <label>Quantity of Seats:</label>
    <input type="number" id="seat_qty" name="seat_qty" min="1" value="1" required oninput="updatePrice()">

    <label>Price (₹):</label>
    <input type="text" id="price" name="price" readonly>

    <label>Total (₹):</label>
    <input type="text" id="total" name="total" readonly>

    <input type="submit" value="Next ➜">
</form>

<script>
const priceData = <?php echo json_encode($prices); ?>;

function updatePrice() {
    const classSelect = document.getElementById('class_id');
    const qtyInput = document.getElementById('seat_qty');
    const priceBox = document.getElementById('price');
    const totalBox = document.getElementById('total');

    const classId = classSelect.value;
    const qty = parseInt(qtyInput.value) || 0;
    const price = priceData[classId] || 0;
    const total = price * qty;

    priceBox.value = price;
    totalBox.value = total;
}
</script>

</body>
</html>
