<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Izinkan CORS
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$dataFile = 'data.json';
$logFile = 'api.log';

// Fungsi untuk logging
function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] ERROR: $message\n", FILE_APPEND);
}

// Fungsi untuk membaca data
function readData() {
    global $dataFile;
    try {
        if (!file_exists($dataFile)) {
            // Buat file baru dengan array kosong
            $initialData = [];
            file_put_contents($dataFile, json_encode($initialData, JSON_PRETTY_PRINT));
            return $initialData;
        }

        $content = file_get_contents($dataFile);
        if ($content === false) {
            throw new Exception("Gagal membaca file data.json");
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decoding JSON: " . json_last_error_msg());
        }

        return $data;
    } catch (Exception $e) {
        logError($e->getMessage());
        return [];
    }
}

// Fungsi untuk menulis data
function writeData($data) {
    global $dataFile;
    try {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error encoding JSON: " . json_last_error_msg());
        }

        if (file_put_contents($dataFile, $json) === false) {
            throw new Exception("Gagal menulis ke file data.json");
        }
        return true;
    } catch (Exception $e) {
        logError($e->getMessage());
        return false;
    }
}

// Fungsi untuk validasi serial number
function validateSerialNumbers($data, $newItem = null) {
    $allSerialNumbers = [];
    
    // Kumpulkan semua serial number yang ada
    foreach ($data as $item) {
        if (isset($item['serialNumbers']) && is_array($item['serialNumbers'])) {
            foreach ($item['serialNumbers'] as $sn) {
                if ($newItem && $item['kode'] === $newItem['kode']) {
                    continue; // Skip serial number dari item yang sedang diupdate
                }
                $allSerialNumbers[] = $sn;
            }
        }
    }
    
    // Validasi serial number baru
    if ($newItem && isset($newItem['serialNumbers']) && is_array($newItem['serialNumbers'])) {
        foreach ($newItem['serialNumbers'] as $sn) {
            if (in_array($sn, $allSerialNumbers)) {
                return false; // Serial number duplikat ditemukan
            }
        }
    }
    
    return true;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $data = readData();
            echo json_encode($data);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON input");
            }
            
            $data = readData();
            
            // Validasi serial number
            if (!validateSerialNumbers($data, $input)) {
                throw new Exception('Serial number duplikat ditemukan');
            }
            
            $data[] = $input;
            if (!writeData($data)) {
                throw new Exception('Gagal menyimpan data');
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON input");
            }
            
            $data = readData();
            $index = array_search($input['kode'], array_column($data, 'kode'));
            
            if ($index === false) {
                throw new Exception('Barang tidak ditemukan');
            }
            
            // Validasi serial number
            if (!validateSerialNumbers($data, $input)) {
                throw new Exception('Serial number duplikat ditemukan');
            }
            
            $data[$index] = $input;
            if (!writeData($data)) {
                throw new Exception('Gagal menyimpan data');
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON input");
            }
            
            $data = readData();
            $index = array_search($input['kode'], array_column($data, 'kode'));
            
            if ($index === false) {
                throw new Exception('Barang tidak ditemukan');
            }
            
            array_splice($data, $index, 1);
            if (!writeData($data)) {
                throw new Exception('Gagal menyimpan data');
            }
            
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Metode tidak didukung');
    }
} catch (Exception $e) {
    logError($e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>