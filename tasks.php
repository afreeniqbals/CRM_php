<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "crm_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$client_id = $_GET['client_id'] ?? null;
if (!$client_id) {
    echo "Client ID is missing!";
    exit();
}

// Add Task
if (isset($_POST['add_task'])) {
    $task_description = $_POST['task_description'];
    $due_date = $_POST['due_date'] ?? null;
    if (!empty($task_description)) {
        $stmt = $conn->prepare("INSERT INTO tasks (client_id, task_description, due_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $client_id, $task_description, $due_date);
        $stmt->execute();
        $stmt->close();
        header("Location: tasks.php?client_id=$client_id");
        exit();
    }
}

// Mark Task as Completed
if (isset($_GET['complete_task'])) {
    $task_id = $_GET['complete_task'];
    $conn->query("UPDATE tasks SET status = 'Completed' WHERE id = $task_id");
    header("Location: tasks.php?client_id=$client_id");
    exit();
}

// Delete Task
if (isset($_GET['delete_task'])) {
    $task_id = $_GET['delete_task'];
    $conn->query("DELETE FROM tasks WHERE id = $task_id");
    header("Location: tasks.php?client_id=$client_id");
    exit();
}

// Fetch tasks
$stmt = $conn->prepare("SELECT * FROM tasks WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$tasks_result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Tasks</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <style>
        #calendar {
            max-width: 900px;
            margin: 40px auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Tasks for Client ID: <?= htmlspecialchars($client_id) ?></h1>

        <!-- Back button -->
        <p><a href="client_management.php"?id=<?= $client_id ?>">&larr; Back to Client Page</a></p>

        <!-- Task Form -->
        <form method="POST" action="tasks.php?client_id=<?= $client_id ?>">
            <div class="input-group">
                <label for="task_description">Task Description</label>
                <textarea name="task_description" id="task_description" required></textarea>
            </div>
            <div class="input-group">
                <label for="due_date">Due Date</label>
                <input type="date" name="due_date" id="due_date">
            </div>
            <button type="submit" name="add_task">Add Task</button>
        </form>

        <!-- Task List -->
        <h2>Current Tasks</h2>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tasks_result->num_rows > 0): ?>
                    <?php while ($task = $tasks_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['task_description']) ?></td>
                            <td><?= htmlspecialchars($task['status']) ?></td>
                            <td><?= htmlspecialchars($task['due_date']) ?></td>
                            <td>
                                <a href="?client_id=<?= $client_id ?>&complete_task=<?= $task['id'] ?>">Complete</a> |
                                <a href="?client_id=<?= $client_id ?>&delete_task=<?= $task['id'] ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">No tasks found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Calendar View -->
        <h2>Calendar View</h2>
        <div id="calendar"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    <?php
                    $calendar_stmt = $conn->prepare("SELECT task_description, due_date FROM tasks WHERE client_id = ? AND due_date IS NOT NULL");
                    $calendar_stmt->bind_param("i", $client_id);
                    $calendar_stmt->execute();
                    $calendar_result = $calendar_stmt->get_result();
                    while ($row = $calendar_result->fetch_assoc()) {
                        echo "{ title: " . json_encode($row['task_description']) . ", date: '" . $row['due_date'] . "' },";
                    }
                    $calendar_stmt->close();
                    ?>
                ]
            });
            calendar.render();
        });
    </script>
</body>
</html>
