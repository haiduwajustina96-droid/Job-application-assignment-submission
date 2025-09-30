<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST requests allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['Unit Name'], $input['Arrival'], $input['Departure'], $input['Occupants'], $input['Ages'])) {
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$unitName = $input['Unit Name'];
$arrival = $input['Arrival'];
$departure = $input['Departure'];
$occupants = (int)$input['Occupants'];
$ages = $input['Ages'];

if (count($ages) !== $occupants) {
    echo json_encode(['error' => 'Ages array length must match Occupants']);
    exit;
}

$unitMap = [
    'Kalahari Farmhouse' => -2147483637,
    'Etosha Safari Lodge' => -2147483456
];
if (!isset($unitMap[$unitName])) {
    echo json_encode(['error' => 'Unknown Unit Name']);
    exit;
}
$unitTypeId = $unitMap[$unitName];

try {
    $arrivalDate = DateTime::createFromFormat('d/m/Y', $arrival)->format('Y-m-d');
    $departureDate = DateTime::createFromFormat('d/m/Y', $departure)->format('Y-m-d');
} catch (Exception $e) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

$guests = [];
foreach ($ages as $age) {
    $ageGroup = ($age >= 14) ? 'Adult' : 'Child';
    $guests[] = ['Age Group' => $ageGroup];
}

$payload = [
    'Unit Type ID' => $unitTypeId,
    'Arrival' => $arrivalDate,
    'Departure' => $departureDate,
    'Guests' => $guests
];

$ch = curl_init('https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['error' => 'Remote API error', 'details' => $response]);
    exit;
}

$remoteData = json_decode($response, true);
$remoteData['Unit Name'] = $unitName;
$remoteData['Date Range'] = $arrival . ' to ' . $departure;

echo json_encode($remoteData);