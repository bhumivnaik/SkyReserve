<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$booking = null;
$error = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM booking WHERE booking_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id); // booking_id is VARCHAR, so use "s"
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    } else {
        $error = "Booking not found.";
    }
}

// --- Fetch class options from the 'class' table ---
$class_options = [];
$class_sql = "SELECT class_id, class_name FROM class";
$class_result = $conn->query($class_sql);
if ($class_result->num_rows > 0) {
    while ($row = $class_result->fetch_assoc()) {
        $class_options[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update Booking</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libertinus+Serif:wght@400;600;700&family=Merriweather:wght@300;400;700&display=swap');

        :root {
            --dark-blue: #0d4b75ff;
            --second-blue: #2c81baff;
            --third-blue: #35acfcff;
            --gray-colour: #465f6dff;
            --white-color-light: #F4F4F4;
        }

        body {
            font-family: 'Cambria';
            background: linear-gradient(to right, var(--dark-blue), var(--second-blue));
            padding: 50px;
            margin-top: 60px;
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

        h2 {
            text-align: center;
            font-family: "Libertinus Serif";
            color: var(--dark-blue);
            font-size: 35px;
            margin-bottom: 45px;
        }

        .manage-container {
            max-width: 500px;
            background: var(--white-color-light);
            padding: 30px;
            background: var(--white-color-light);
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(16, 91, 141, 0.4);
            color: var(--dark-blue);
        }

        label {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            display: block;
            font-size: 16px;
            color: var(--second-blue);
        }

        input[type="text"],
        input[type="submit"],
        input[type="date"],
        input[type="number"] {
            width: 97%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid var(--second-blue);
            color: var(--gray-colour);
            font-family: 'Cambria';
        }

        input[type="submit"] {
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: var(--white-color-light);
            cursor: pointer;
            padding: 10px 15px;
            margin-top: 35px;
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

        input[type="text"],
        input[type="date"],
        input[type="number"] {
            width: 96.5%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid var(--second-blue);
            margin-top: 8px;
            background: #fff;
        }

        select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid var(--second-blue);
            margin-top: 8px;
            background: #fff;
            font-family: 'Cambria';
            color: var(--gray-colour);
        }

        .no-result {
            text-align: center;
            font-size: 18px;
            color: white;
        }
    </style>
</head>

<body>

    <video autoplay muted loop class="video-bg">
        <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <?php if ($booking): ?>

        <div class="manage-container">

            <h2>Update Your Booking</h2>

            <form action="confirm_update.php" method="get">
                <!-- SEND BOOKING ID via GET -->
                <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">

                <label>Change Date</label>
                <input type="date" name="date_value">

                <label>Change Class</label>
                <select name="class_value">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($class_options as $class): ?>
                        <option value="<?= $class['class_id'] ?>"
                            <?= ($booking['class_id'] == $class['class_id']) ? 'selected' : '' ?>>
                            <?= $class['class_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>



                <input type="submit" value="Continue">
            </form>
        </div>


    <?php else: ?>
        <p class="no-result"><?= $error ?></p>
    <?php endif; ?>

</body>

</html>