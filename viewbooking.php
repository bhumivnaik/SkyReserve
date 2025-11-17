<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$booking = null;
$error = "";

if (isset($_GET['id']) && isset($_GET['email'])) {
    $id = $_GET['id'];
    $email = $_GET['email'];

    $sql = "SELECT b.*, p.email 
            FROM booking b
            JOIN makes m ON m.booking_id = b.booking_id
            JOIN passenger p ON p.passenger_ID = m.passenger_ID
            WHERE b.booking_id = ? AND p.email = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $id, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    } else {
        $error = "No booking found. Please check your details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Booking Details</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libertinus+Serif:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Merriweather:wght@300;400;700&display=swap');

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
            padding: 50px;
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

        .manage-container {
            max-width: 500px;
            margin: auto;
            background: var(--white-color-light);
            padding: 35px;
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            position: relative;
        }

        h2 {
            text-align: center;
            font-family: "Libertinus Serif";
            color: var(--dark-blue);
            font-size: 35px;
            margin-bottom: 45px;
        }

        /* Grid styling */
        .booking-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }

        /* Each detail box */
        .detail-box {
            background: #eae8e8ff;
            border-radius: 12px;
            padding: 18px;
            border-left: 6px solid var(--second-blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            transition: 0.3s ease;
        }

        .detail-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.20);
        }

        .detail-title {
            font-size: 13px;
            color: var(--second-blue);
            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        /* Values */
        .detail-value {
            font-size: 17px;
            font-weight: 700;
            margin-top: 6px;
            color: var(--gray-colour);
        }

        /* Update Booking Button */
        .btn-update,
        .btn-delete {
            display: block;
            margin-top: 25px;
            text-align: center;
            width: 94%;
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: white;
            padding: 14px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
            transition: 0.3s ease;
        }

        .btn-update:hover,
        .btn-delete:hover {
            transform: scale(1.07);
            box-shadow: 0 0 25px rgba(53, 172, 252, 0.3);
            background: linear-gradient(135deg, var(--dark-blue), var(--second-blue));
        }


        /* Back button */
        .back-home {
            display: block;
            margin: 10px auto 0 auto;
            text-align: center;
            width: 60%;
            color: var(--dark-blue);
            font-weight: 600;
            text-decoration: none;
            font-size: 17px;
            transition: 0.25s ease;
        }

        .back-home:hover {
            color: var(--third-blue);
            letter-spacing: 0.5px;
        }

        /* No result message */
        .no-result {
            text-align: center;
            font-size: 18px;
            color: white;
            margin-top: 20px;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 35px;
        }

        .action-buttons a {
            flex: 1;
            text-align: center;
        }
    </style>

</head>

<body>

    <video autoplay muted loop class="video-bg">
        <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>


    <div class="manage-container">

        <h2>Your Booking Overview</h2>

        <?php if ($booking): ?>

            <div class="booking-grid">

                <div class="detail-box">
                    <div class="detail-title">Booking ID</div>
                    <div class="detail-value"><?= $booking['booking_id'] ?></div>
                </div>

                <div class="detail-box">
                    <div class="detail-title">Date</div>
                    <div class="detail-value"><?= $booking['date'] ?></div>
                </div>

                <div class="detail-box">
                    <div class="detail-title">Status</div>
                    <div class="detail-value"><?= $booking['status'] ?></div>
                </div>

                <div class="detail-box">
                    <div class="detail-title">Flight ID</div>
                    <div class="detail-value"><?= $booking['flight_id'] ?></div>
                </div>

                <div class="detail-box">
                    <div class="detail-title">Seats Booked</div>
                    <div class="detail-value"><?= $booking['seatsbooked'] ?></div>
                </div>

                <div class="detail-box">
                    <div class="detail-title">Class</div>
                    <div class="detail-value"><?= $booking['class_id'] ?></div>
                </div>

                <div class="detail-box">
                    <div class="detail-title">Email</div>
                    <div class="detail-value"><?= $booking['email'] ?></div>
                </div>

            </div>

            <div class="action-buttons">
                <a class="btn-update" href="updatebooking.php?id=<?= $booking['booking_id'] ?>">Update Booking</a>
                <a class="btn-delete" href="deletebooking.php?booking_id=<?= urlencode($booking['booking_id']) ?>">Delete Booking</a>
            </div>

        <?php else: ?>
            <p class="no-result"><?= $error ?></p>
        <?php endif; ?>

    </div>

</body>

</html>