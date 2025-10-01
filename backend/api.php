<?php
// Set headers with restricted CORS (adjust origin in production)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://your-allowed-origin.com'); // Replace with specific origin
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// Read and decode input with validation
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$requiredFields = ['Unit Name', 'Arrival', 'Departure', 'Occupants', 'Ages'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing or empty field: $field"]);
        exit;
    }
}

$unitName = htmlspecialchars(trim($input['Unit Name']), ENT_QUOTES, 'UTF-8');
$arrival = trim($input['Arrival']);
$departure = trim($input['Departure']);
$occupants = filter_var($input['Occupants'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$agesInput = $input['Ages'];

// Handle Ages input as an array of integers
$ages = [];
if (is_array($agesInput)) {
    $ages = array_map(function($age) {
        return is_numeric($age) ? (int)$age : null;
    }, $agesInput);
    $ages = array_filter($ages); // Remove nulls (invalid ages)
} elseif (is_string($agesInput)) {
    $ages = array_map('trim', explode(',', $agesInput));
    $ages = array_map(function($age) {
        return is_numeric($age) ? (int)$age : null;
    }, $ages);
    $ages = array_filter($ages);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Ages must be a string or array of numbers']);
    exit;
}

if ($occupants === false || count($ages) !== $occupants) {
    http_response_code(400);
    echo json_encode(['error' => 'Ages array length must match Occupants and contain valid numbers']);
    exit;
}

$unitMap = [
    'Kalahari Farmhouse' => -2147483637,
    'Etosha Safari Lodge' => -2147483456
];
if (!isset($unitMap[$unitName])) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown Unit Name']);
    exit;
}
$unitTypeId = $unitMap[$unitName];

// Validate and sanitize dates
$arrivalDate = DateTime::createFromFormat('d/m/Y', $arrival);
$departureDate = DateTime::createFromFormat('d/m/Y', $departure);
if ($arrivalDate === false || $departureDate === false || $departureDate <= $arrivalDate) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format or invalid date range']);
    exit;
}
$arrivalDate = $arrivalDate->format('Y-m-d');
$departureDate = $departureDate->format('Y-m-d');

// Prepare guests with sanitized age groups
$guests = [];
foreach ($ages as $age) {
    $ageGroup = ($age >= 14) ? 'Adult' : 'Child';
    $guests[] = ['Age Group' => htmlspecialchars($ageGroup, ENT_QUOTES, 'UTF-8')];
}

$payload = [
    'Unit Type ID' => $unitTypeId,
    'Arrival' => $arrivalDate,
    'Departure' => $departureDate,
    'Guests' => $guests
];

// Secure CURL request
$ch = curl_init('https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || $error) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to fetch from remote API', 'code' => $httpCode]);
    exit;
}

$remoteData = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid response from remote API']);
    exit;
}

$remoteData['Unit Name'] = $unitName;
$remoteData['Date Range'] = htmlspecialchars($arrival . ' to ' . $departure, ENT_QUOTES, 'UTF-8');

echo json_encode($remoteData);