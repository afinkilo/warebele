<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Izinkan CORS
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$dataFile = 'data.json';

// Fungsi untuk membaca data
function readData() {
    global $dataFile;
    if (!file_exists($dataFile)) {
        file_put_contents($dataFile, json_encode([]));
    }
    return json_decode(file_get_contents($dataFile), true);
}

// Fungsi untuk menulis data
function writeData($data) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        echo json_encode(readData());
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $data = readData();
        $data[] = $input;
        writeData($data);
        echo json_encode(['success' => true]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $data = readData();
        $index = array_search($input['kode'], array_column($data, 'kode'));
        if ($index !== false) {
            $data[$index] = $input;
            writeData($data);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $data = readData();
        $index = array_search($input['kode'], array_column($data, 'kode'));
        if ($index !== false) {
            array_splice($data, $index, 1);
            writeData($data);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Metode tidak didukung']);
        break;
}
?>