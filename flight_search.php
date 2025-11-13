<?php
$server ="localhost";
$user ="root";
$password ="";
$dbname = "airport";

$conn = new mysqli($server, $user, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$source = $_POST['source'] ?? '';
$destination = $_POST['destination'] ?? '';
$trip = $_POST['trip'] ?? 'oneway';
$date = $_POST['date'] ?? '';
$return_date = $_POST['return_date'] ?? '';

// --- Function to build date condition dynamically ---
function buildDateCondition($dateField, $dateValue) {
    return !empty($dateValue) ? "AND $dateField = '$dateValue'" : "";
}

// --- Going flights query ---
$goingDateCondition = buildDateCondition("fi.date", $date);

$sql = "
SELECT 
    f.flight_id, f.flight_name,
    a1.city AS source_city,
    a2.city AS destination_city,
    fi.departure_time, fi.arrival_time, fi.available_seats, fi.date
FROM flight f
JOIN airport a1 ON f.sourceAcode = a1.acode
JOIN airport a2 ON f.destAcode = a2.acode
JOIN flightinstance fi ON f.flight_id = fi.flight_id
WHERE (
        a1.city = '$source'
     OR a1.state = '$source'
     OR a1.country = '$source'
     )
  AND (
        a2.city = '$destination'
     OR a2.state = '$destination'
     OR a2.country = '$destination'
     )
  $goingDateCondition
ORDER BY fi.date, fi.departure_time;
";

$result = $conn->query($sql);

echo "
<!DOCTYPE html>
<html>
<head>
<style>

@import url('https://fonts.googleapis.com/css2?family=Libertinus+Serif:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Merriweather:ital,opsz,wght@0,18..144,300..900;1,18..144,300..900&family=Momo+Signature&display=swap');

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #44a6ecff, #bedcffff);
    margin: 0;
    padding: 40px;
}
.container {
      width: 95%;
      max-width: 1200px;
      background: #fff;
      margin: 40px auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
   
    h3 {
      color: #0074e4;
      font-family: 'Merriweather';

      border-left: 5px solid #0074e4;
      padding-left: 10px;
      margin-top: 40px;
      margin-bottom: 15px;
    }
h2 {
font-family: 'Libertinus Serif';
font-size:35px;
color: #0161a5ff;
margin: 37px auto;
    text-align: center;
    margin-bottom: 30px;
}
table {
    width: 93%;
    margin: 20px auto;
    border-collapse: collapse;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
}
th {
    background-color: #0078D7;
    color: white;
    padding: 15px;
    text-align: center;
}
td {
    padding: 18px;
    text-align: center;
    border-bottom: 1px solid #ddd;
    border: 1px solid #e5e5e5ff;
    font-family: Cambria;

}
tr:hover {
    background-color: #cfe0fbff;
    transition: 0.3s;
}
.no-data {
    text-align: center;
    color: red;
    font-size: 18px;
    margin-top: 20px;
}
.book-btn {
    background-color: #0078D7;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}
.book-btn:hover { background-color: #005fa3; }
</style>
</head>
<body>
<div class='container'>
<h2>Available Flights</h2>
";



// --- Going flights table ---
echo "<h3 style='text-align:left;'>Going Flights</h3>";

if ($result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>Flight</th><th>Source</th><th>Destination</th>
                <th>Date</th><th>Departure</th><th>Arrival</th>
                <th>Available Seats</th><th>Book</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        $flight_id = urlencode($row['flight_id']);
        $flight_name = htmlspecialchars($row['flight_name']);
        $from = htmlspecialchars($row['source_city']);
        $to = htmlspecialchars($row['destination_city']);
        $dateVal = htmlspecialchars($row['date']);
        echo "<tr>
                <td>$flight_name</td>
                <td>$from</td>
                <td>$to</td>
                <td>$dateVal</td>
                <td>{$row['departure_time']}</td>
                <td>{$row['arrival_time']}</td>
                <td>{$row['available_seats']}</td>
                <td><a href='booking.php?flight_id=$flight_id&source=$from&flight_name=$flight_name&destination=$to&date=$dateVal' class='book-btn'>Book Now</a></td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p class='no-data'>No flights available for this route.</p>";
}

// --- Return flights (only for two-way trip) ---
if ($trip === 'twoway') {
    echo "<h3 style='text-align:center;'>Return Flights</h3>";

    $returnDateCondition = buildDateCondition("fi.date", $return_date);

    $sql2 = "
    SELECT 
        f.flight_id, f.flight_name,
        a1.city AS source_city,
        a2.city AS destination_city,
        fi.departure_time, fi.arrival_time, fi.available_seats, fi.date
    FROM flight f
    JOIN airport a1 ON f.sourceAcode = a1.acode
    JOIN airport a2 ON f.destAcode = a2.acode
    JOIN flightinstance fi ON f.flight_id = fi.flight_id
    WHERE (
            a1.city = '$destination'
         OR a1.state = '$destination'
         OR a1.country = '$destination'
         )
      AND (
            a2.city = '$source'
         OR a2.state = '$source'
         OR a2.country = '$source'
         )
      $returnDateCondition
    ORDER BY fi.date, fi.departure_time;
    ";

    $res2 = $conn->query($sql2);

    if ($res2->num_rows > 0) {
        echo "<table>
                <tr>
                    <th>Flight</th><th>Source</th><th>Destination</th>
                    <th>Date</th><th>Departure</th><th>Arrival</th>
                    <th>Available Seats</th><th>Book</th>
                </tr>";
        while ($r2 = $res2->fetch_assoc()) {
            $fid = urlencode($r2['flight_id']);
            $fname = htmlspecialchars($r2['flight_name']);
            $src = htmlspecialchars($r2['source_city']);
            $dst = htmlspecialchars($r2['destination_city']);
            $dte = htmlspecialchars($r2['date']);
            echo "<tr>
                    <td>$fname</td>
                    <td>$src</td>
                    <td>$dst</td>
                    <td>$dte</td>
                    <td>{$r2['departure_time']}</td>
                    <td>{$r2['arrival_time']}</td>
                    <td>{$r2['available_seats']}</td>
                    <td><a href='booking.php?flight_id=$fid&flight_name=$fname&source=$src&destination=$dst&date=$dte' class='book-btn'>Book Now</a></td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='no-data'>No return flights found.</p>";
    }
}
echo "</div></body></html>";
?>
