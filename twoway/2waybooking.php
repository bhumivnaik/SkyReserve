<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// -------------------------------
//  DETECT ONE-WAY vs TWO-WAY
// -------------------------------
$oneway = false;
$twoway = false;

// ONE-WAY
if (!empty($_GET['flight_id'])) {
    $oneway = true;
    $flight_id = $_GET['flight_id'];
}

// TWO-WAY
if (!empty($_POST['outgoing']) && !empty($_POST['returning'])) {
    $twoway = true;
    $outgoing_id = $_POST['outgoing'];
    $return_id   = $_POST['returning'];
}

if (!$oneway && !$twoway) die("Invalid Access!");


// -------------------------------
//  FETCH FLIGHT DETAILS
// -------------------------------
function getFlight($conn, $flight_id, $date)
{
    $sql = "SELECT f.flight_id, f.flight_name,
                   a1.city AS source_city,
                   a2.city AS dest_city,
                   fi.date, fi.departure_time, fi.arrival_time
            FROM flight f
            JOIN airport a1 ON f.sourceAcode = a1.acode
            JOIN airport a2 ON f.destAcode = a2.acode
            JOIN flightinstance fi ON f.flight_id = fi.flight_id
            WHERE f.flight_id='$flight_id'
            AND fi.date='$date'
            LIMIT 1";

    return $conn->query($sql)->fetch_assoc();
}

if ($oneway) {
    $flight = getFlight($conn, $flight_id, $date);
} else {
    $outgoing = getFlight($conn, $outgoing_id, $_POST['date']);
    $returnf  = getFlight($conn, $return_id, $_POST['return_date']);
}


// -------------------------------
//  FETCH PRICE TABLE
// -------------------------------
function getPrices($conn, $flight_id)
{
    $prices = [];
    $res = $conn->query("SELECT class_id, price FROM price WHERE flight_id='$flight_id'");
    while ($row = $res->fetch_assoc()) {
        $prices[$row['class_id']] = $row['price'];
    }
    return $prices;
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Flight Booking</title>
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
            max-width: 500px;
            background: var(--white-color-light);
            margin: 40px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

        }

        h2 {
            font-family: "Libertinus Serif";
            font-size: 35px;
            color: var(--dark-blue);
            margin: 30px auto;
            margin-bottom: 30px;
            text-align: center;
        }

        p {
            text-align: center;
            color: #424242ff;
            margin-bottom: 35px;
            font-size: 16px;
        }

        h3 {
            color: var(--second-blue);
            font-family: 'Merriweather';
            padding-left: 10px;
            margin-top: 20px;
            margin-bottom: 15px;
            text-align: center;
        }

        label {
            color: var(--second-blue);
            font-size: 14px;
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
            margin-top: 10px;
        }

        input,
        select {
            width: 96%;
            padding: 8px;
            border: 1px solid var(--second-blue);
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Cambria';
            text-transform: capitalize;
            color: var(--gray-colour);
            margin-bottom: 12px;

        }

        select {
            width: 99%;
        }

        input[type="submit"] {
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: var(--white-color-light);
            cursor: pointer;
            padding: 10px 15px;
            margin-top: 30px;
            width: 100%;
            border: none;
            font-size: medium;
            box-shadow: 0 0 15px rgba(53, 172, 252, 0.5);
        }

        input[type="submit"]:hover {
            transform: scale(1.07);
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

    <form action="2waypassenger.php" method="POST">
        <h2>Flight Booking</h2>

        <?php if ($oneway): ?>
            <p>
            <h3><?= $flight['flight_name'] ?></h3>
            <p><?= $flight['source_city'] ?> → <?= $flight['dest_city'] ?> (<?= $flight['date'] ?>)</p>
            </p>
            <input type="hidden" name="oneway" value="1">
            <input type="hidden" name="flight_id" value="<?= $flight['flight_id'] ?>">
            <input type="hidden" name="date" value="<?= $flight['date'] ?>">


            <?php $prices = getPrices($conn, $flight['flight_id']); ?>

            <label>Select Class</label>
            <select name="class_id" required onchange="updatePrice()">
                <option value="">Select Class</option>
                <?php
                foreach ($prices as $cid => $prc) {
                    $cname = ($cid == 1001 ? "Economy" : ($cid == 1002 ? "Business" : "First"));
                    echo "<option value='$cid'>$cname</option>";
                }
                ?>
            </select>

            <label>Seats</label>
            <input type="number" name="seat_qty" id="seat_qty" min="1" value="1" oninput="updatePrice()">

            <label>Total</label>
            <input type="text" name="total" id="total" readonly>

            <script>
                let prices = <?= json_encode($prices) ?>;

                function updatePrice() {
                    let qty = document.getElementById("seat_qty").value;
                    let cid = document.querySelector("[name='class_id']").value;
                    let price = prices[cid] || 0;
                    document.getElementById("total").value = price * qty;
                }
            </script>

        <?php else: ?>

            <!------------------------------->
            <!-- TWO WAY BOOKING FORM -->
            <!------------------------------->
            <p>
            <h3 style="color:#0d4b75;">Round Trip</h3>
            <p><b>Outbound:</b> <?= $outgoing['source_city'] ?> → <?= $outgoing['dest_city'] ?> (<?= $outgoing['date'] ?>)<br>
                <b>Return:</b> <?= $returnf['source_city'] ?> → <?= $returnf['dest_city'] ?> (<?= $returnf['date'] ?>)
            </p>
            </p>
            <input type="hidden" name="twoway" value="1">
            <input type="hidden" name="outgoing_id" value="<?= $outgoing['flight_id'] ?>">
            <input type="hidden" name="return_id" value="<?= $returnf['flight_id'] ?>">
            <input type="hidden" name="out_date" value="<?= $outgoing['date'] ?>">
            <input type="hidden" name="ret_date" value="<?= $returnf['date'] ?>">


            <?php
            $out_prices = getPrices($conn, $outgoing['flight_id']);
            $ret_prices = getPrices($conn, $returnf['flight_id']);
            ?>

            <!-- OUTBOUND CLASS -->
            <label>Outbound Class</label>
            <select name="out_class_id" id="out_class" required onchange="updateTotal()">
                <option value="">Select Class</option>
                <?php
                foreach ($out_prices as $cid => $prc) {
                    $cname = ($cid == 1001 ? "Economy" : ($cid == 1002 ? "Business" : "First"));
                    echo "<option value='$cid'>$cname (₹$prc)</option>";
                }
                ?>
            </select>

            <!-- RETURN CLASS -->
            <label>Return Class</label>
            <select name="ret_class_id" id="ret_class" required onchange="updateTotal()">
                <option value="">Select Class</option>
                <?php
                foreach ($ret_prices as $cid => $prc) {
                    $cname = ($cid == 1001 ? "Economy" : ($cid == 1002 ? "Business" : "First"));
                    echo "<option value='$cid'>$cname (₹$prc)</option>";
                }
                ?>
            </select>

            <label>Seats</label>
            <input type="number" name="seat_qty" id="qty" value="1" min="1" required oninput="updateTotal()">

            <label>Total (₹)</label>
            <input type="text" name="total" id="total" readonly>

            <script>
                let outPrices = <?= json_encode($out_prices) ?>;
                let retPrices = <?= json_encode($ret_prices) ?>;

                function updateTotal() {
                    let qty = document.getElementById('qty').value;
                    let out = document.getElementById('out_class').value;
                    let ret = document.getElementById('ret_class').value;

                    let outPrice = outPrices[out] || 0;
                    let retPrice = retPrices[ret] || 0;

                    document.getElementById('total').value = (Number(outPrice) + Number(retPrice)) * Number(qty);
                }
            </script>

        <?php endif; ?>

        <input type="submit" value="Next →">

    </form>
</body>

</html>
