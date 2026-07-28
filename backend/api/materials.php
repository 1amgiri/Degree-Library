<?php
require '../config.php';

$group = isset($_GET['group']) ? $_GET['group'] : '';
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$query = isset($_GET['query']) ? $_GET['query'] : '';

$sql = "SELECT id, name, subject, group_name AS `group`, semester, uploader, file_name, file_type, file_path, created_at FROM materials WHERE 1=1";
$params = [];
$types = '';

if (!empty($group)) {
    $sql .= " AND group_name = ?";
    $params[] = $group;
    $types .= 's';
}

if (!empty($semester)) {
    $sql .= " AND semester = ?";
    $params[] = $semester;
    $types .= 's';
}

if (!empty($query)) {
    $searchTerm = "%" . $query . "%";
    $sql .= " AND (name LIKE ? OR subject LIKE ? OR uploader LIKE ? OR group_name LIKE ? OR semester LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $types .= 'sssss';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$materials = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Adjust file path to be a URL-friendly path
        $row['file_path'] = str_replace('../', '', $row['file_path']);
        $materials[] = $row;
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($materials);
?>
