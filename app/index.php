<?php
// ---------- CONFIGURATION ----------
$servername = getenv('DB_HOST');      // or your DB endpoint if remote
$username   = getenv('DB_USER');
$password   = getenv('DB_PASS');
$dbname     = getenv('DB_NAME');

// ---------- ENABLE ERROR DISPLAY ----------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------- CONNECT TO DATABASE ----------
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// ---------- CREATE TABLE IF NOT EXISTS ----------
$conn->query("
CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_name VARCHAR(255) NOT NULL,
  status ENUM('Pending', 'Done') DEFAULT 'Pending'
)
");

// ---------- ADD NEW TASK ----------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $task_name = trim($_POST['task_name']);
    if (!empty($task_name)) {
        $stmt = $conn->prepare("INSERT INTO tasks (task_name) VALUES (?)");
        $stmt->bind_param("s", $task_name);
        $stmt->execute();
        $stmt->close();
        echo "<p style='color:green;'>✅ Task added successfully!</p>";
    } else {
        echo "<p style='color:red;'>⚠️ Please enter a task name.</p>";
    }
}

// ---------- MARK TASK AS DONE ----------
if (isset($_GET['done'])) {
    $task_id = intval($_GET['done']);
    $conn->query("UPDATE tasks SET status='Done' WHERE id=$task_id");
}

// ---------- DELETE TASK ----------
if (isset($_GET['delete'])) {
    $task_id = intval($_GET['delete']);
    $conn->query("DELETE FROM tasks WHERE id=$task_id");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Simple Task Manager</title>
  <style>
    body { font-family: Arial; margin: 40px; background-color: #f8f9fa; }
    h1 { color: #333; }
    form { margin-bottom: 20px; }
    input { padding: 8px; }
    button { padding: 8px 12px; }
    table { border-collapse: collapse; width: 60%; background: #fff; }
    th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
    th { background: #f2f2f2; }
    a { text-decoration: none; color: #007BFF; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>

<h1>📝 Task Manager</h1>

<form method="POST">
  <input type="text" name="task_name" placeholder="Enter new task" required>
  <button type="submit" name="add">Add Task</button>
</form>

<h2>Current Tasks</h2>

<?php
$result = $conn->query("SELECT * FROM tasks ORDER BY id DESC");

if ($result->num_rows > 0) {
    echo "<table>
            <tr>
              <th>ID</th>
              <th>Task</th>
              <th>Status</th>
              <th>Action</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['task_name']}</td>
                <td>{$row['status']}</td>
                <td>";
        if ($row['status'] == 'Pending') {
            echo "<a href='?done={$row['id']}'>✅ Mark Done</a> | ";
        }
        echo "<a href='?delete={$row['id']}'>🗑 Delete</a></td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No tasks yet. Add one above!</p>";
}

$conn->close();
?>

</body>
</html>