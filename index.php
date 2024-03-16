<?php
session_start();

// Initialize tasks array if not already set
if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = [];
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    $task_name = $_POST["task_name"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $dependency = $_POST["dependency"];

    // Perform basic validation
    if (empty($task_name) || empty($start_date) || empty($end_date)) {
        // Handle validation errors
        echo "<p>Please fill in all required fields.</p>";
    } elseif (!isUniqueTaskName($task_name, $_SESSION['tasks'])) {
        // Check for unique task name
        echo "<p>Task name must be unique.</p>";
    } elseif (!isValidDateRange($start_date, $end_date)) {
        // Check for valid date range
        echo "<p>End date must be greater than start date.</p>";
    } elseif (!isValidDependency($dependency, $start_date, $_SESSION['tasks'])) {
        // Check for valid dependency
        echo "<p>Dependent task's start date must be greater than dependable task's end date.</p>";
    } else {
        // Assign random color to the task
        $color = generateRandomColor();

        // Store task data in the array
        $_SESSION['tasks'][] = array(
            'task_name' => $task_name,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'dependency' => $dependency,
            'color' => $color
        );
    }
}

// Function to check if task name is unique
function isUniqueTaskName($task_name, $tasks)
{
    foreach ($tasks as $task) {
        if ($task['task_name'] == $task_name) {
            return false;
        }
    }
    return true;
}

// Function to check if the date range is valid
function isValidDateRange($start_date, $end_date)
{
    return strtotime($end_date) > strtotime($start_date);
}

// Function to check if dependency is valid
function isValidDependency($dependency, $start_date, $tasks)
{
    if (!empty($dependency)) {
        foreach ($tasks as $task) {
            if ($task['task_name'] == $dependency) {
                return strtotime($start_date) > strtotime($task['end_date']);
            }
        }
        // If dependency not found, it's considered valid
        return true;
    }
    return true; // If no dependency is set, it's considered valid
}

// Function to generate a random color
function generateRandomColor()
{
    // Generate random RGB values
    $red = mt_rand(0, 255);
    $green = mt_rand(0, 255);
    $blue = mt_rand(0, 255);

    // Convert RGB to hexadecimal format
    $color = sprintf('#%02x%02x%02x', $red, $green, $blue);

    return $color;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gantt Chart Generator</title>
    <style>
        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 80%;
            margin: 20px auto;
        }

        .form h2 {
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }


        .form label {
            display: block;
            margin-bottom: 5px;
        }

        .form input[type="text"],
        .form input[type="date"],
        .form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: block;
            width: 100%;
        }

        .form input[type="submit"]:hover {
            background-color: #45a049;

        }

        .chart {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .chart h2 {
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }


        .chart svg {
            width: 90%;
            height: auto;
            display: table;
            margin: 0 auto;
            border: 2px solid #eaeaea;
            padding: 20px;
        }

        .chart rect {
            stroke: none;
        }

        .chart text {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <form class="form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <h2>Task Form</h2>

        <label for="task_name">Task Name:</label>
        <input type="text" id="task_name" name="task_name" required><br><br>

        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" required> <br><br>
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" required>


        <label for="dependency">Dependency:</label>
        <select id="dependency" name="dependency">
            <option value="">None</option>
            <?php
            foreach ($_SESSION['tasks'] as $task) {
                echo '<option value="' . $task['task_name'] . '">' . $task['task_name'] . '</option>';
            }
            ?>
        </select><br><br>

        <input type="submit" value="Add Task">
    </form>

    <div class="chart">
        <h2>Gantt chart</h2>
        <svg width="800" height="900">
            <?php

            $y = 10;

            foreach ($_SESSION['tasks'] as $task) {
                $start = strtotime($task['start_date']);
                $end = strtotime($task['end_date']);
                $duration = $end - $start;
                $x = (($start - strtotime(min(array_column($_SESSION['tasks'], 'start_date')))) / (strtotime(max(array_column($_SESSION['tasks'], 'end_date'))) - strtotime(min(array_column($_SESSION['tasks'], 'start_date'))))) * 780;

                // Draw task bar
                echo '<rect x="' . $x . '" y="' . $y . '" width="' . $duration / (strtotime(max(array_column($_SESSION['tasks'], 'end_date'))) - strtotime(min(array_column($_SESSION['tasks'], 'start_date')))) * 780 . '" height="30" fill="' . $task['color']  . '"></rect>';

                // Draw task name
                echo '<text x="' . ($x + 5) . '" y="' . ($y + 20) . '" fill="black">' . $task['task_name'] . ' (' . date('d', strtotime($task['start_date'])) . ' - ' . date('d', strtotime($task['end_date'])) . ') ' . '</text>';


                // Draw dependency arrow
                if (!empty($task['dependency'])) {
                    $dependencyY = findDependencyY($_SESSION['tasks'], $task['dependency']);
                    $dependencyX = findDependencyX($_SESSION['tasks'], $task['dependency']);
                    if ($dependencyY !== false && $dependencyX !== false) {
                        $arrowX1 = $dependencyX;
                        $arrowY1 = $dependencyY + 15; // Center of the dependable task
                        $arrowX2 = $x; // Start of the dependent task
                        $arrowY2 = $y + 15; // Center of the dependent task

                        // Draw horizontal line from end of dependable task to start of dependent task
                        echo '<line x1="' . $arrowX1 . '" y1="' . $arrowY1 . '" x2="' . ($arrowX2 - 10) . '" y2="' . $arrowY1 . '" style="stroke:#00008b;stroke-width:2" />';

                        // Draw vertical line to connect to dependent task
                        echo '<line x1="' . ($arrowX2 - 10) . '" y1="' . $arrowY1 . '" x2="' . ($arrowX2 - 10) . '" y2="' . $arrowY2 . '" style="stroke:#00008b;stroke-width:2" />';

                        // Draw horizontal line to connect to dependent task
                        echo '<line x1="' . ($arrowX2 - 10) . '" y1="' . $arrowY2 . '" x2="' . $arrowX2 . '" y2="' . $arrowY2 . '" style="stroke:#00008b;stroke-width:2" />';

                        // Draw arrowhead
                        echo '<polygon points="' . ($arrowX2 - 5) . ',' . ($arrowY2 - 5) . ' ' . ($arrowX2 + 5) . ',' . ($arrowY2) . ' ' . ($arrowX2 - 5) . ',' . ($arrowY2 + 5) . '" style="fill:#00008b" />';
                    }
                }

                // Update y position for next task
                $y += 40;
            }

            // Function to find dependency Y position
            function findDependencyY($tasks, $dependency)
            {
                foreach ($tasks as $task) {
                    if ($task['task_name'] == $dependency) {
                        return 10 + array_search($task, $tasks) * 40; // Calculate Y position based on task index
                    }
                }
                return false;
            }

            // Function to find dependency X position
            function findDependencyX($tasks, $dependency)
            {
                foreach ($tasks as $task) {
                    if ($task['task_name'] == $dependency) {
                        $start = strtotime($task['start_date']);
                        $end = strtotime($task['end_date']);
                        $taskWidth = (($end - $start) / (strtotime(max(array_column($tasks, 'end_date'))) - strtotime(min(array_column($tasks, 'start_date'))))) * 780;
                        return (($start - strtotime(min(array_column($tasks, 'start_date')))) / (strtotime(max(array_column($tasks, 'end_date'))) - strtotime(min(array_column($tasks, 'start_date'))))) * 780 + $taskWidth;
                    }
                }
                return false;
            }

            ?>
        </svg>
    </div>

</body>

</html>