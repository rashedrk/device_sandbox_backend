<?php

require_once __DIR__ . '/../config/database.php';

class DeviceAPI
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function handleRequest($method)
    {
        // Read-only API - only allow GET requests
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode([
                'error' => 'Method not allowed',
            ]);
            return;
        }

        $this->getAllDevices();
    }

    private function getAllDevices()
    {
        try {
            $query = "SELECT id, type, name, settings, created_at, updated_at 
                      FROM devices 
                      ORDER BY created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $devices = $stmt->fetchAll();

            // Parse JSON settings for each device
            foreach ($devices as &$device) {
                $device['settings'] = json_decode($device['settings'], true);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Devices fetch successfully',
                'data' => $devices
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    }
}

// Handle the request
if (isset($_GET['method'])) {
    $method = $_GET['method'];

    $api = new DeviceAPI();
    $api->handleRequest($method);
}
