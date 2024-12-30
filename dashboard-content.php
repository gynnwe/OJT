<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: index.php");
    exit;
}
date_default_timezone_set('Asia/Manila');

include 'conn.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: transparent !important;
            font-family: 'Inter', sans-serif;
        }
        .content-wrapper {
            padding: 30px;
            background: transparent;
        }
        .stats-card {
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.6);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-weight: 600;
        }
        .number {
            font-size: 60px;
            line-height: 1;
            color: #632121;
        }
        .total-equipment {
            background-color: #632121 !important; 
            color: white;
        }
        .white-card {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .solid-card {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress-chart {
            min-height: 300px;
        }
        .activity-feed {
            min-height: 165px;
        }
		
		.inside-activity-feed {
            background: #white !important;
            border-radius: 8px;
			border: 3px solid #e3e3e3;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
			color: gray;
			height: 95px;
    		overflow-y: auto;
        }
		
		.date {
			color: #8c2020;
		}

		.equip-name, .remarks-name {
			color: #bd4444;
		}
        .maintenance-goal {
            min-height: 468px;
        }
		.maintenance-goal h6 {
			margin-bottom: 17px;
		}
		
		.inside-maintenance-goal {
			max-height: 391px;
    		overflow-y: auto;
			margin-right: -13px;
		}	
		.no-goals-message {
			font-size: 12px;
			color: #838383;
			padding-left: 5px;
		}
		.month {
			background-color: #ECECEC;
			border-radius: 8px; 
			cursor: pointer; 
			margin-bottom: 5px;
			padding-left: 15px;
		}

		.goal-item {
			margin-top: 2px;
			margin-bottom: 6px;
			font-size: 12px;
			padding-left: 25px;
			color: #838383;
		}
		
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            text-align: center;
            font-size: 12px;
        }
        .calendar-header {
            margin-bottom: 5px;
            font-size: 1em;
        }
        .week {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            font-size: 0.75em;
            background: #632121;
            border-radius: 8px;
            padding: 4px;
            margin-top: 8px;
            color: white;
        }
        .day-block {
            text-align: center;
            padding: 2px 0;
            font-weight: 500;
        }
        .today-column {
            background-color: white;
            color: black;
            font-weight: bold;
            border-radius: 6px;
        }
        .calendar-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 4px;
        }
        .maintenance-text {

        }
        h6 {
            font-weight: 600;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card total-equipment">
                    <div>Total Equipment</div>
                    <?php
                    $query = "SELECT COUNT(*) as total FROM equipment WHERE deleted_id = 0";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    ?>
                    <div class="number" style="color: white;"><?php echo $total; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div>Serviceable Equipment</div>
                    <?php
                    $query = "SELECT COUNT(*) as serviceable FROM equipment WHERE status = 'Serviceable' AND deleted_id = 0";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $serviceable = $stmt->fetch(PDO::FETCH_ASSOC)['serviceable'];
                    ?>
                    <div class="number"><?php echo $serviceable; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div>Non-Serviceable Equipment</div>
                    <?php
                    $query = "SELECT COUNT(*) as nonserviceable FROM equipment WHERE status = 'Non-serviceable' AND deleted_id = 0";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $nonserviceable = $stmt->fetch(PDO::FETCH_ASSOC)['nonserviceable'];
                    ?>
                    <div class="number"><?php echo $nonserviceable; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <?php
                    function renderCurrentWeek() {
                        $currentYear = date('Y');
                        $currentMonth = date('n');
                        $currentDay = date('j');
                        $currentWeekDay = date('w');

                        $weekStart = $currentDay - $currentWeekDay;
                        $weekEnd = $weekStart + 6;

                        $monthName = date('F Y');
                        $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

                        echo '<div class="calendar-header">' . $monthName . '</div>';
                        echo '<div class="week">';

                        for ($i = 0; $i < 7; $i++) {
                            $isToday = ($i == $currentWeekDay) ? 'today-column' : '';
                            echo '<div class="calendar-column ' . $isToday . '">';
                            echo '<div class="day-block">' . $daysOfWeek[$i] . '</div>';
                            
                            $day = $weekStart + $i;
                            $date = mktime(0, 0, 0, $currentMonth, $day, $currentYear);
                            $dayOfMonth = date('j', $date);
                            
                            echo '<div class="day-block">' . $dayOfMonth . '</div>';
                            echo '</div>';
                        }

                        echo '</div>';
                    }

                    renderCurrentWeek();
                    ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-9">
                <div class="white-card">
                    <h6>Progress This Year</h6>
                    <div id="progressChart" class="progress-chart"></div>
                </div>
                <div class="solid-card activity-feed">
                    <h6>Recent Maintenance Activity Feed</h6>
					<div class="inside-activity-feed">
						<?php
						$query = "SELECT m.maintenance_date, e.equip_name, r.remarks_name 
								  FROM `ict_maintenance_logs` m 
								  JOIN equipment e ON m.equipment_id = e.equipment_id 
								  JOIN remarks r ON m.remarks_id = r.remarks_id 
								  ORDER BY m.maintenance_date DESC LIMIT 10";
						$stmt = $conn->prepare($query);
						$stmt->execute();
						$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

						// Display recent maintenance activities
						if (count($result) > 0) {
							echo '<table>';
							foreach ($result as $row) {
								echo '<tr>';
								// Format the date
								$formattedDate = date('F j, Y', strtotime($row['maintenance_date']));
								echo '<td class="date">' . $formattedDate . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
								// Wrap equip_name and remarks_name in a sentence with bold text
								echo '<td><strong class="equip-name">' . $row['equip_name'] . '</strong> maintenance is <strong class="remarks-name">' . $row['remarks_name'] . '</strong>.</td>';
								echo '</tr>';
							}
							echo '</table>';
						} else {
							echo '<p>No recent maintenance activities found.</p>';
						}
						?>
					</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="solid-card maintenance-goal">
					<h6>Maintenance Goal This Year</h6>
					<div class="inside-maintenance-goal">
						<?php
						// Fetch maintenance goals from the database
						$query = "SELECT pd.month, et.equip_type_name, pd.target, mp.year 
								  FROM plan_details pd 
								  JOIN maintenance_plan mp ON pd.maintenance_plan_id = mp.id 
								  JOIN equipment_type et ON pd.equip_type_id = et.equip_type_id 
								  WHERE mp.status = 'submitted' 
								  AND mp.id = (SELECT MAX(id) FROM maintenance_plan WHERE status = 'submitted') 
								  ORDER BY FIELD(pd.month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')";
						$stmt = $conn->prepare($query);
						$stmt->execute();
						$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

						// Prepare data for displaying goals
						$goals = [];
						$year = '';
						foreach ($result as $row) {
							$goals[$row['month']][] = [
								'equipment_type' => $row['equip_type_name'],
								'planned_goal' => $row['target']
							];
							$year = $row['year']; // Store the year from the first row
						}

						// Display Maintenance Goals
						if (!empty($goals)) {
							echo '<div class="maintenance-goal">';
							foreach ($goals as $month => $equipment) {
								echo '<div class="month" onclick="toggleDropdown(this)">' . $month . ' &#9662;</div>';
								echo '<div class="dropdown-content" style="display:none;">';
								foreach ($equipment as $goal) {
									echo '<div class="goal-item">' . $goal['equipment_type'] . ': ' . $goal['planned_goal'] . '&nbsp;units</div>';
								}
								echo '</div>';
							}
							echo '</div>';
						} else {
							echo '<p class="no-goals-message">No maintenance goals found.</p>';
						}
						?>
					</div>
				</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        var options = {
            series: [{
                name: 'Progress',
                data: []
            }],
            chart: {
                type: 'bar',
                height: 200,
                toolbar: {
                    show: false
                },
                background: 'transparent'
            },
            colors: ['#632121', '#FFA500'],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '60%',
                }
            },
            grid: {
                borderColor: '#e7e7e7',
                strokeDashArray: 5
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                labels: {
                    style: {
                        colors: '#666'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#666'
                    }
                }
            }
        };

        var chart = new ApexCharts(document.querySelector("#progressChart"), options);
        chart.render();
    </script>
    <script>
        function toggleDropdown(element) {
            var dropdownContent = element.nextElementSibling;
            if (dropdownContent.style.display === "block") {
                dropdownContent.style.display = "none";
            } else {
                dropdownContent.style.display = "block";
            }
        }
    </script>
</body>
</html>
