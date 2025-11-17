<?php
$server = "localhost";
$user = "root";
$password = "";
$dbname = "airport";

$conn = new mysqli($server, $user, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$source = $_POST['source'] ?? '';
$destination = $_POST['destination'] ?? '';
$trip = $_POST['trip'] ?? 'oneway';
$date = $_POST['date'] ?? '';
$return_date = $_POST['return_date'] ?? '';

// --- Function to build date condition dynamically ---
function buildDateCondition($dateField, $dateValue)
{
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

:root {
            --dark-blue: #0d4b75ff;
            --second-blue: #2c81baff;
            --third-blue: #35acfcff;
            --gray-colour: #1B262C;
            --white-color-light: #F4F4F4;
        }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, var(--dark-blue), var(--second-blue));
    margin: 0;
    padding: 40px;
}
.container {
      width: 95%;
      max-width: 1200px;
      background: #F4F4F4;
      margin: 40px auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
   
    h3 {
      color: var(--second-blue);
      font-family: 'Merriweather';

      border-left: 5px solid var(--dark-blue);
      padding-left: 10px;
      margin-top: 40px;
      margin-bottom: 15px;
    }
h2 {
font-family: 'Libertinus Serif';
font-size:35px;
color:var(--dark-blue);
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
    background-color:var(--second-blue);
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
    background-color: #e9edf5ff;
    transition: 0.3s;
}
.no-data {
    text-align: center;
    color: red;
    font-size: 18px;
    margin-top: 20px;
}
.book-btn 
{
    background-color:var(--second-blue);
    color: var(--white-color-light);
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
    box-shadow: 0 0 15px rgba(53, 172, 252, 0.5);
}

.book-btn:hover { 
background-color: var(--dark-blue);
transform: scale(1.07);
            box-shadow: 0 0 25px rgba(53, 172, 252, 0.3);
</style>
</head>
<body>
<video autoplay muted loop class='video-bg'>
    <source src='From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4' type='video/mp4'>
    Your browser does not support HTML5 video.
    </video>
    <div class='overlay'></div>
<div class='container'>
<h2>Available Flights</h2>
";


if ($trip === 'oneway') {
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
}
// --- Return flights (only for two-way trip) ---
if ($trip === 'twoway') {

    echo "<form action='twoway/2waybooking.php' method='POST'>";

    echo "<h3>Going Flights</h3>";

    if ($result->num_rows > 0) {
        echo "<table>
            <tr>
                <th>Select</th>
                <th>Flight</th><th>Source</th><th>Destination</th>
                <th>Date</th><th>Departure</th><th>Arrival</th>
                <th>Available Seats</th>
            </tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td><input type='radio' name='outgoing' value='{$row['flight_id']}' required></td>
                <td>{$row['flight_name']}</td>
                <td>{$row['source_city']}</td>
                <td>{$row['destination_city']}</td>
                <td>{$row['date']}</td>
                <td>{$row['departure_time']}</td>
                <td>{$row['arrival_time']}</td>
                <td>{$row['available_seats']}</td>
            </tr>";
        }

        echo "</table>";
    } else {
        echo "<p class='no-data'>No outbound flights found.</p>";
    }

    echo "<h3>Return Flights</h3>";

    $returnDateCondition = buildDateCondition("fi.date", $return_date);

    $sql2 = "SELECT f.flight_id, f.flight_name, a1.city AS source_city, a2.city AS destination_city, fi.departure_time, fi.arrival_time, fi.available_seats, fi.date FROM flight f JOIN airport a1 ON f.sourceAcode = a1.acode JOIN airport a2 ON f.destAcode = a2.acode JOIN flightinstance fi ON f.flight_id = fi.flight_id WHERE ( a1.city = '$destination' OR a1.state = '$destination' OR a1.country = '$destination' ) AND ( a2.city = '$source' OR a2.state = '$source' OR a2.country = '$source' ) $returnDateCondition ORDER BY fi.date, fi.departure_time;";
    $res2 = $conn->query($sql2);

    if ($res2->num_rows > 0) {
        echo "<table>
            <tr>
                <th>Select</th>
                <th>Flight</th><th>Source</th><th>Destination</th>
                <th>Date</th><th>Departure</th><th>Arrival</th>
                <th>Available Seats</th>
            </tr>";

        while ($r2 = $res2->fetch_assoc()) {
            echo "<tr>
                <td><input type='radio' name='returning' value='{$r2['flight_id']}' required></td>
                <td>{$r2['flight_name']}</td>
                <td>{$r2['source_city']}</td>
                <td>{$r2['destination_city']}</td>
                <td>{$r2['date']}</td>
                <td>{$r2['departure_time']}</td>
                <td>{$r2['arrival_time']}</td>
                <td>{$r2['available_seats']}</td>
            </tr>";
        }

        echo "</table>";
    } else {
        echo "<p class='no-data'>No return flights found.</p>";
    }

    // Hidden values
    echo "
        <input type='hidden' name='trip' value='twoway'>
        <input type='hidden' name='source' value='$source'>
        <input type='hidden' name='destination' value='$destination'>
        <input type='hidden' name='date' value='$date'>
        <input type='hidden' name='return_date' value='$return_date'>
    ";

    echo "<br><center>
        <button type='submit' class='book-btn'>Book Selected Flights</button>
    </center></form>";
}

echo "</div></body></html>";
