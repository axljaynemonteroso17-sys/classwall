<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_SESSION["visits"])) {
        $_SESSION["visits"] = 0;
    }

    $_SESSION["visits"]++;
}

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $message = trim($_POST["message"]);

    if (!empty($name) && !empty($message)) {

        $sql = "INSERT INTO messages (author, body)
                VALUES (:author, :body)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":author" => $name,
            ":body" => $message
        ]);

        if (isset($_POST["remember"])) {
            setcookie("saved_name", $name, [
                "expires" => time() + (86400 * 30),
                "path" => "/",
                "httponly" => true,
                "samesite" => "Lax"
            ]);
        } else {
            setcookie("saved_name", "", time() - 3600, "/");
        }

        header("Location: index.php");
        exit;
    }
}

$savedName = $_COOKIE["saved_name"] ?? "";

$search = $_GET["search"] ?? "";

if (!empty($search)) {

    $sql = "SELECT * FROM messages
            WHERE author ILIKE :search
            OR body ILIKE :search
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":search" => "%" . $search . "%"
    ]);

} else {

    $sql = "SELECT * FROM messages
            ORDER BY created_at DESC";

    $stmt = $pdo->query($sql);
}

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Class Wall</title>
</head>

<body>

    <h1>Class Wall</h1>

    <p>
        You've visited <?php echo $_SESSION["visits"]; ?> times.
    </p>

    <hr>

    <h2>Post a Message</h2>

    <form method="POST" action="index.php">

        <label>Your Name:</label><br>

        <input
            type="text"
            name="name"
            value="<?php echo htmlspecialchars($savedName); ?>"
            required
        >

        <br><br>

        <label>Your Message:</label><br>

        <textarea name="message" required></textarea>

        <br><br>

        <input
            type="checkbox"
            name="remember"
            value="1"
        >

        <label>Remember my name</label>

        <br><br>

        <button type="submit">Post Message</button>

    </form>

    <hr>

    <h2>Search Messages</h2>

    <form method="GET" action="index.php">

        <input
            type="text"
            name="search"
            placeholder="Search messages..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit">Search</button>

        <a href="index.php">Show All</a>

    </form>

    <hr>

    <h2>Messages</h2>

    <?php if (count($messages) > 0): ?>

        <?php foreach ($messages as $row): ?>

            <div>

                <h3>
                    <?php echo htmlspecialchars($row["author"]); ?>
                </h3>

                <p>
                    <?php echo nl2br(htmlspecialchars($row["body"])); ?>
                </p>

                <small>
                    <?php echo htmlspecialchars($row["created_at"]); ?>
                </small>

            </div>

            <hr>

        <?php endforeach; ?>

    <?php else: ?>

        <p>No messages found.</p>

    <?php endif; ?>

</body>
</html>
