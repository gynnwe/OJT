<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Set the timezone to Manila (Philippine Time)
date_default_timezone_set('Asia/Manila');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modified Layout</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
		body {
			background-color: transparent !important;
		}
        .mt-5 {
            margin-top: 0 rem !important;
        }
        .placeholder {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 10px;
            height: 100px;
        }
        .analytics {
            height: 350px;
        }

        .performance {
            height: 250px;
        }

        .calendar {
            height: 150px;
            background: #ffffff;
            padding: 8px;
            color: #333333;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .assignments {
            height: 450px; /* Larger box height */
        }

        .gap {
            margin-bottom: 15px; /* Space between rows */
        }

        /* Use flexbox to control widths of the columns */
        .row {
            display: flex;
            justify-content: space-between;
        }

        .first-column,
        .second-column {
            max-width: 60%;
            padding: 10px;
        }

        .first-column {
            flex: 0 0 60%; /* First column takes 60% width */
        }

        .second-column {
            flex: 1; /* Second column will take up remaining space */
        }

        /* Calendar Styles */
        .calendar-header {
            text-align: left;
            font-size: 1.4em; /* Reduced font size */
            font-weight: bold;
            margin-bottom: 8px; /* Reduced margin */
        }

        .week {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px; /* Reduced gap */
            margin-top: 4px; /* Added to reduce space above the grid */
        }

        .day-block {
            text-align: center;
            padding: 4px 0;
            font-size: 0.8em;
            font-weight: 500;
        }

        .today {
            background-color: #991B1E;
            color: #ffffff;
            font-weight: bold;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row gap">
            <!-- First Column -->
            <div class="first-column">
                <!-- Large Box in the First Row -->
                <div class="placeholder analytics mb-3"></div>
                <!-- Medium Box in the Second Row -->
                <div class="placeholder performance"></div>
            </div>

            <!-- Second Column -->
            <div class="second-column d-flex flex-column">
                <!-- Calendar Box in the First Row -->
                <div class="placeholder calendar mb-3">
                    <?php
                    function renderCurrentWeek() {
                        // Get current date info
                        $currentYear = date('Y');
                        $currentMonth = date('n');
                        $currentDay = date('j');
                        $currentWeekDay = date('w'); // Day of the week (0 = Sunday, 6 = Saturday)

                        // Start and end of the current week
                        $weekStart = $currentDay - $currentWeekDay; // Subtract weekday offset
                        $weekEnd = $weekStart + 6; // Add 6 to get the week's end

                        // Get month name
                        $monthName = date('F Y');

                        // Days of the week header
                        $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

                        echo '<div class="calendar">';
                        echo '<div class="calendar-header">' . $monthName . '</div>';
                        echo '<div class="week">';

                        // Render the current week
                        for ($day = $weekStart; $day <= $weekEnd; $day++) {
                            $date = mktime(0, 0, 0, $currentMonth, $day, $currentYear); // Build the date
                            $dayOfMonth = date('j', $date); // Get day of the month
                            $weekDayIndex = date('w', $date); // Get the weekday index for this date

                            // Apply "today" class to the current day
                            $class = ($dayOfMonth == $currentDay) ? 'day-block today' : 'day-block';
                            echo '<div class="' . $class . '">';
                            echo '<div>' . $daysOfWeek[$weekDayIndex] . '</div>'; // Day name
                            echo '<div>' . $dayOfMonth . '</div>'; // Date
                            echo '</div>';
                        }

                        echo '</div>';
                        echo '</div>';
                    }

                    // Call the function to render the current week
                    renderCurrentWeek();
                    ?>
                </div>

                <!-- Large Box in the Second Row -->
                <div class="placeholder assignments"></div>
            </div>
        </div>
    </div>
</body>
</html>
