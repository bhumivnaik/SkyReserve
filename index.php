<?php
$conn = new mysqli("localhost", "root", "", "airport");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch all airports
$airport_query = $conn->query("SELECT acode, city, aport_name FROM airport ORDER BY city ASC");
$airports = [];
while($row = $airport_query->fetch_assoc()) {
    $airports[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Flight Search</title>
    <style>
        
@import url('https://fonts.googleapis.com/css2?family=Libertinus+Serif:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Merriweather:ital,opsz,wght@0,18..144,300..900;1,18..144,300..900&family=Momo+Signature&display=swap');

        body {
            font-family: Cambria, serif;
            background: linear-gradient(to right, #44a6ecff, #bedcffff);
            padding: 50px;
        }

        h2{
  font-family: "Libertinus Serif";
font-size:35px;
color: #0161a5ff;
margin: 37px auto;
        }

        form {
            background:white;
            max-width:500px;
            margin:50px auto;
            padding:30px;
            border-radius:10px;
            box-shadow:0 0 10px rgba(1, 56, 86, 0.4);
        }
        label {
            display:block;
            margin-top:30px;
            margin-bottom:8px;
            font-weight:bold;
        }
        input[type="date"], input[type="submit"] {
            width:93%;
            padding:8px;
            margin-top:5px;
            margin-bottom:15px;
            border-radius:5px;
            border:1px solid #ccc;
        }
        input[type="submit"] {
            background: #0161a5ff;
            color:white;
            border:none;
            cursor:pointer;
            padding: 10px 15px;
            margin-top:15px;
            width:100%;
        }
        input[type="submit"]:hover { background: #004475ff; }

        /* Custom dropdown styling */
        .custom-select {
            position: relative;
            width: 100%;
        }
        .select-selected {
            background-color: white;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius:5px;
            cursor: pointer;
        }
        .select-items {
            position: absolute;
            background-color: white;
            border: 1px solid #ccc;
            border-radius:5px;
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
        .select-items div:hover{
            background-color: #0161a5ff;
            color:white;
        }
        .city { font-weight: bold; }
        .airport { font-size: 12px; color: #b5b5b5ff; }

        .trip-type {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
      font-size: 16px;
      color: #374151;
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


<form action="flight_search.php" method="POST">
<h2 style="text-align:center; ">Find your Flights</h2>

    <!-- Source Dropdown -->
    <label>Source:</label>
    <div class="custom-select" id="sourceSelect">
        <div class="select-selected">Select Source</div>
        <div class="select-items">
            <?php foreach($airports as $airport): ?>
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
            <?php foreach($airports as $airport): ?>
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
        if(!select.contains(e.target)) items.style.display = 'none';
    });
}

setupCustomSelect('sourceSelect');
setupCustomSelect('destinationSelect');
</script>

</body>
</html>
