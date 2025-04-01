<?php
$NODE_SERVER = 'http://localhost:3000';
$event = null;

require_once('navbar.php');
require_once('functions.php');
require_once('db.php'); // Include the database connection file

renderNavbar();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $eventId = $_GET['id'] ?? null;

    if (!$eventId) {
        die("No event ID provided");
    }

    // Fetch event data from API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $NODE_SERVER . "/api/getEventById/" . $eventId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Curl error: " . curl_error($ch));
        die('Error fetching event: ' . curl_error($ch));
    }

    curl_close($ch);

    $responseData = json_decode($response, true);

    if (!$responseData) {
        error_log("API response is empty or invalid: " . $response);
        die("Failed to fetch event data from the API.");
    }

    $event = $responseData[0] ?? null;

    if (!$event) {
        error_log("Event not found for ID: " . $eventId);
        die("Event not found. Please check if the event ID is correct.");
    }

    // Fetch wineCollection and activities from the database
    $conn = getDbConnection(); // Use the reusable connection function
    $event['wineCollection'] = fetchWineCollection($conn, $eventId);
    $event['activities'] = fetchActivities($conn, $eventId);

    // Log the fetched data for debugging
    error_log("Wine Collection: " . json_encode($event['wineCollection']));
    error_log("Activities: " . json_encode($event['activities']));

    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', 'root', 'wine');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Define target directories
    $event_target_dir = "/Users/lucaseduardo/wineServer/src/images/";
    $wine_target_dir = "/Users/lucaseduardo/wineServer/src/images/wineimages/";

    // Handle event image upload
    $imageUrl = handleEventImageUpload($event_target_dir, $_POST['currentImageUrl']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $location = $_POST['location'];
    $capacity = intval($_POST['capacity']);
    $price = floatval($_POST['price']);
    $isPrivate = isset($_POST['isPrivate']) ? 1 : 0;
    $id = intval($_POST['id']);

    // Update event details
    $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, imageUrl = ?, date = ?, startTime = ?, endTime = ?, location = ?, capacity = ?, price = ?, isPrivate = ? WHERE id = ?");
    $stmt->bind_param(
        "sssssssidii",
        $title,
        $description,
        $imageUrl,
        $date,
        $startTime,
        $endTime,
        $location,
        $capacity,
        $price,
        $isPrivate,
        $id
    );

    if (!$stmt->execute()) {
        die("Error updating event: " . $stmt->error);
    }

    // Update wine collection
    $wineCollection = json_decode($_POST['wineCollection'], true);
    updateWineCollection($conn, $id, $wineCollection, $wine_target_dir);

    // Update activities and materials
    $activities = json_decode($_POST['activities'], true);
    updateActivitiesAndMaterials($conn, $id, $activities);

    $conn->close();
    header("Location: /");
    exit();
}
?>

<?php if ($event): ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Event</title>
        <link rel="stylesheet" href="editEventStyle.css">
        <script src="/js/eventFunctions.js" defer></script>
    </head>

    <body>
        <div class="container">
            <h1>Edit Event</h1>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($event['id'] ?? ''); ?>">
                <input type="hidden" name="currentImageUrl"
                    value="<?php echo htmlspecialchars($event['imageUrl'] ?? ''); ?>">
                <input type="hidden" id="wineCollection" name="wineCollection"
                    value="<?php echo htmlspecialchars(json_encode($event['wineCollection'] ?? [])); ?>">
                <input type="hidden" id="activities" name="activities"
                    value="<?php echo htmlspecialchars(json_encode($event['activities'] ?? [])); ?>">
                <!-- Event Details -->
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title"
                        value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description"
                        required><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Current Image:</label>
                    <img class="current-image"
                        src="<?php echo htmlspecialchars($NODE_SERVER . ($event['imageUrl'] ?? '')); ?>"
                        alt="Current event image">
                    <label for="image">Update Image (optional):</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($event['date'] ?? ''); ?>"
                        required>
                </div>
                <div class="form-group">
                    <label for="startTime">Start Time:</label>
                    <input type="time" id="startTime" name="startTime"
                        value="<?php echo htmlspecialchars($event['startTime'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="endTime">End Time:</label>
                    <input type="time" id="endTime" name="endTime"
                        value="<?php echo htmlspecialchars($event['endTime'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location"
                        value="<?php echo htmlspecialchars($event['location'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="capacity">Capacity:</label>
                    <input type="number" id="capacity" name="capacity"
                        value="<?php echo htmlspecialchars($event['capacity'] ?? 0); ?>" required>
                </div>
                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" step="0.01"
                        value="<?php echo htmlspecialchars($event['price'] ?? 0.0); ?>" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="isPrivate" <?php echo $event['isPrivate'] ? 'checked' : ''; ?>>
                        Private Event
                    </label>
                </div>
                <!-- Wine Collection -->
                <div class="form-group">
                    <h3>Edit Wines</h3>
                    <label for="wineName">Name:</label>
                    <input type="text" id="wineName">
                    <label for="wineVariety">Variety:</label>
                    <input type="text" id="wineVariety">
                    <label for="wineYear">Year:</label>
                    <input type="number" id="wineYear">
                    <label for="wineRegion">Region:</label>
                    <input type="text" id="wineRegion">
                    <label for="winePrice">Price:</label>
                    <input type="number" id="winePrice" step="0.01">
                    <label for="wineDescription">Description:</label>
                    <textarea id="wineDescription"></textarea>
                    <label for="wineImages">Upload Wine Images:</label>
                    <input type="file" id="wineImages" name="wineImages[]" multiple accept="image/*">
                    <button type="button" onclick="addWine()">Add Wine</button>
                    <ul id="wineList"></ul>
                </div>
                <!-- Activities -->
                <div class="form-group">
                    <h3>Edit Activities</h3>
                    <label for="activityTitle">Title:</label>
                    <input type="text" id="activityTitle">
                    <label for="activityDuration">Duration (minutes):</label>
                    <input type="number" id="activityDuration">
                    <label for="activityDifficulty">Difficulty:</label>
                    <select id="activityDifficulty">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    <label for="activityMaterials">Materials (comma-separated):</label>
                    <input type="text" id="activityMaterials">
                    <button type="button" onclick="addActivity()">Add Activity</button>
                    <ul id="activityList"></ul>
                </div>
                <div class="button-group">
                    <button type="submit" class="save-button">Save Changes</button>
                    <a href="/"><button type="button" class="cancel-button">Cancel</button></a>
                    <button type="button" class="delete-button"
                        onclick="deleteEvent(<?php echo htmlspecialchars($event['id']); ?>)">Delete Event</button>
                </div>
            </form>
        </div>
    </body>

    </html>
<?php else: ?>
    <p>Error: Could not load event data. Please check the event ID or try again later.</p>
    <a href="/">Back to Events</a>
<?php endif; ?>