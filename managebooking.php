<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['search'])) {
    $id = $_POST['search_value'];
    $email = $_POST['search_email'];

    header("Location: viewbooking.php?id=$id&email=$email");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Your Booking</title>

    <link href="https://fonts.googleapis.com/css2?family=Libertinus+Serif&display=swap" rel="stylesheet">

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
        input[type="submit"] {
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
    </style>
</head>

<body>

    <video autoplay muted loop class="video-bg">
        <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>


    <div class="manage-container">
        <form method="POST">
            <h2>Manage Your Booking</h2>
            <label>Booking ID</label>
            <input type="text" name="search_value" placeholder="Enter your booking ID" required>

            <label>Email</label>
            <input type="text" name="search_email" placeholder="Enter your email" required>

            <input type="submit" name="search" value="Search Booking">
        </form>
    </div>

</body>

</html>