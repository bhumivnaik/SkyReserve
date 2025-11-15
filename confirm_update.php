<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// RECEIVE DATA VIA GET
$booking_id = $_GET['booking_id'] ?? null;
$new_date = $_GET['date_value'] ?? "";
$new_class = $_GET['class_value'] ?? "";

if (!$booking_id) {
    die("Invalid request. Booking ID missing.");
}

// --- UPDATE DATE ---
if (!empty($new_date)) {
    $sql = "UPDATE booking SET date = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_date, $booking_id);
    $stmt->execute();
}

// --- UPDATE CLASS ---
if (!empty($new_class)) {
    $sql = "UPDATE booking SET class_id = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $new_class, $booking_id);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Update Successful</title>
    <style>
        :root {
            --dark-blue: #0d4b75ff;
            --second-blue: #2c81baff;
            --third-blue: #35acfcff;
            --gray-colour: #465f6dff;
            --white-color-light: #F4F4F4;
        }

        body {
            font-family: Cambria;
            background: linear-gradient(to right, var(--dark-blue), var(--second-blue));
            padding: 50px;
            margin-top: 90px;
            text-align: center;
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

        .box {
            max-width: 500px;
            margin: auto;
            background: var(--white-color-light);
            padding: 35px;
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            position: relative;
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

        .success-icon {
            font-size: 55px;
            color: var(--second-blue);
            margin-bottom: 15px;
            animation: pop 0.4s ease-in-out;
        }

        @keyframes pop {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        h2 {
            text-align: center;
            font-family: "Libertinus Serif";
            color: var(--dark-blue);
            font-size: 32px;
            margin-bottom: 20px;
        }

        .details {
            background: #e7e2e2ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--second-blue);
        }

        .details p {
            margin: 5px 0;
            color: var(--gray-colour);
        }

        .info p {
            font-size: 17px;
            color: var(--gray-colour);
            margin: 10px 0;
        }

        .divider {
            border: 0;
            height: 1px;
            background: #d1d1d1;
            margin: 25px 0;
        }

        a {
            display: inline-block;
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: white;
            margin-top: 10px;
            padding: 12px 28px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: 0.25s ease;
        }

        a:hover {
            transform: scale(1.07);
            background: linear-gradient(135deg, var(--dark-blue), var(--second-blue));
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>

<body>

    <video autoplay muted loop class="video-bg">
        <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>


    <div class="box">

        <div class="success-icon">✔</div>

        <h2>Changes Applied Successfully!</h2>

        <div class="details">
            <p><b>Booking Updated</b></p>
            <p>Your changes were saved without any issues.</p>
        </div>

        <p><strong>Booking ID:</strong> <?= $booking_id ?></p>

        <div class="info">
            <?php if (!empty($new_date)) { ?>
                <p>📅 <strong>New Date:</strong> <?= $new_date ?></p>
            <?php } ?>

            <?php if (!empty($new_class)) { ?>
                <p>🎟 <strong>New Class:</strong> <?= $new_class ?></p>
            <?php } ?>
        </div>

        <hr class="divider">

        <a href="managebooking.php">Back to Home</a>
    </div>

</body>

</html>