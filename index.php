<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch all airports
$airport_query = $conn->query("SELECT acode, city, aport_name FROM airport ORDER BY city ASC");
$airports = [];
while ($row = $airport_query->fetch_assoc()) {
    $airports[] = $row;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Flight Search</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libertinus+Serif:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Merriweather:ital,opsz,wght@0,18..144,300..900;1,18..144,300..900&family=Momo+Signature&display=swap');

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
            color: var(--white-color-light);
        }

        h2 {
            font-family: "Libertinus Serif";
            font-size: 35px;
            color: var(--dark-blue);
            margin: 37px auto;
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
            background: var(--white-color-light);
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(16, 91, 141, 0.4);
            color: var(--dark-blue);
        }

        label {
            display: block;
            margin-top: 30px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="date"],
        input[type="submit"] {
            width: 93%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid var(--second-blue);
            font-family: 'Cambria';
            color: var(--gray-colour);
        }

        /* Submit Button */
        input[type="submit"] {
            background: linear-gradient(135deg, var(--second-blue), var(--dark-blue));
            color: var(--white-color-light);
            cursor: pointer;
            padding: 10px 15px;
            margin-top: 15px;
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

        /* Custom dropdown styling */
        .custom-select {
            position: relative;
            width: 100%;
        }

        .select-selected {
            background-color: var(--white-color-light);
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            color: var(--gray-colour);
            border: 1px solid var(--second-blue);

        }

        .select-items {
            position: absolute;
            background-color: var(--white-color-light);
            border: 1px solid #ccc;
            border-radius: 5px;
            z-index: 99;
            width: 100%;
            max-height: 150px;
            overflow-y: auto;
            display: none;
        }

        .select-items div {
            padding: 8px;
            cursor: pointer;
        }

        .select-items div:hover {
            background-color: var(--second-blue);
            color: var(--white-color-light);
        }

        .city {
            font-weight: bold;
        }

        .airport {
            font-size: 12px;
            color: #b5b5b5ff;
        }

        .trip-type {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .date-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 35px;
            margin-bottom: 25px;
        }
    </style>

</head>

<body>
    <video autoplay muted loop class="video-bg">
        <source src="From KlickPin CF [Video] timelapse of white clouds and blue sky di 2025 _ Desainsetyawandeddy050 (online-video-cutter.com).mp4" type="video/mp4">
        Your browser does not support HTML5 video.
    </video>
    <div class="overlay"></div>



    <form action="flight_search.php" method="POST">
        <h2 style="text-align:center; ">Find your Flights</h2>

        <!-- Source Dropdown -->
        <label>Source:</label>
        <div class="custom-select" id="sourceSelect">
            <div class="select-selected">Select Source</div>
            <div class="select-items">
                <?php foreach ($airports as $airport): ?>
                    <div data-value="<?= $airport['city'] ?>">
                        <span class="city"><?= $airport['city'] ?></span><br>
                        <span class="airport"><?= $airport['aport_name'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="source">
        </div>

        <!-- Destination Dropdown -->
        <label>Destination:</label>
        <div class="custom-select" id="destinationSelect">
            <div class="select-selected">Select Destination</div>
            <div class="select-items">
                <?php foreach ($airports as $airport): ?>
                    <div data-value="<?= $airport['city'] ?>">
                        <span class="city"><?= $airport['city'] ?></span><br>
                        <span class="airport"><?= $airport['aport_name'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="destination">
        </div>

        <!-- Trip Type -->
        <div class="trip-type">
            <label><input type="radio" name="trip" value="oneway" checked> One Way</label>
            <label><input type="radio" name="trip" value="twoway"> Two Way</label>
        </div>

        <!-- Dates -->
        <div class="date-grid">
            <div>
                <label>Outbound Date</label>
                <input type="date" name="date">

            </div>
            <div id="returnDiv">
                <label>Return Date</label>
                <input type="date" name="return_date">
            </div>
        </div>


        <input type="submit" value="Search Flights">
    </form>

    <script>
        // Hide/show return date dynamically
        const radios = document.querySelectorAll('input[name="trip"]');
        const returnDiv = document.getElementById('returnDiv');
        radios.forEach(r => {
            r.addEventListener('change', () => {
                returnDiv.style.display = (r.value === 'twoway' && r.checked) ? 'block' : 'none';
            });
        });
        returnDiv.style.display = 'none';

        function setupCustomSelect(selectId) {
            const select = document.getElementById(selectId);
            const selected = select.querySelector('.select-selected');
            const items = select.querySelector('.select-items');
            const input = select.querySelector('input');

            selected.addEventListener('click', () => {
                items.style.display = items.style.display === 'block' ? 'none' : 'block';
            });

            items.querySelectorAll('div').forEach(div => {
                div.addEventListener('click', () => {
                    selected.innerHTML = div.innerHTML;
                    input.value = div.dataset.value;
                    items.style.display = 'none';
                });
            });

            document.addEventListener('click', e => {
                if (!select.contains(e.target)) items.style.display = 'none';
            });
        }

        setupCustomSelect('sourceSelect');
        setupCustomSelect('destinationSelect');
    </script>

</body>

</html>
