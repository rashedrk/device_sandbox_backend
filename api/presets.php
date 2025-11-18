<?php

require_once __DIR__ . '/../config/database.php';

class PresetAPI
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function handleRequest($method, $params)
    {
        switch ($method) {
            case 'GET':
                // Check if requesting a specific preset by ID
                if (!empty($params[0])) {
                    $this->getPresetById($params[0]);
                } else {
                    $this->getAllPresets();
                }
                break;

            case 'POST':
                $this->createPreset();
                break;

            case 'PUT':
            case 'PATCH':
                if (!empty($params[0])) {
                    $this->updatePreset($params[0]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'Bad request',
                        'message' => 'Preset ID is required for update'
                    ]);
                }
                break;

            case 'DELETE':
                if (!empty($params[0])) {
                    $this->deletePreset($params[0]);
                } else {
                    http_response_code(400);
                    echo json_encode([
                        'error' => 'Bad request',
                        'message' => 'Preset ID is required for delete'
                    ]);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode([
                    'error' => 'Method not allowed',
                    'message' => 'Supported methods: GET, POST, PUT, PATCH, DELETE'
                ]);
                break;
        }
    }

    private function getAllPresets()
    {
        try {
            $query = "SELECT *
                      FROM presets 
                      ORDER BY created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $presets = $stmt->fetchAll();

            // Parse JSON settings for each device
            foreach ($presets as &$preset) {
                $preset['devices'] = json_decode($preset['devices'], true);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'count' => count($presets),
                'data' => $presets
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function getPresetById($id)
    {
        try {
            $query = "SELECT id, name, devices, created_at, updated_at 
                      FROM presets 
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $preset = $stmt->fetch();

            if ($preset) {
                // Parse JSON devices
                $preset['devices'] = json_decode($preset['devices'], true);

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $preset
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Not found',
                    'message' => 'Preset not found'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function createPreset()
    {
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (empty($input['name'])) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Validation error',
                    'message' => 'Name is required'
                ]);
                return;
            }

            if (empty($input['devices'])) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Validation error',
                    'message' => 'Devices data is required'
                ]);
                return;
            }

            // Encode devices as JSON string
            $devicesJson = json_encode($input['devices']);

            $query = "INSERT INTO presets (name, devices) VALUES ( :name, :devices)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $input['name']);
            $stmt->bindParam(':devices', $devicesJson);

            if ($stmt->execute()) {
                // Fetch the created preset
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Preset created successfully',
                    'data' => [
                        'name' => $input['name'],
                        'devices' => $input['devices']
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Database error',
                    'message' => 'Failed to create preset'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function updatePreset($id)
    {
        try {
            // Check if preset exists
            $checkQuery = "SELECT id FROM presets WHERE id = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();

            if ($checkStmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Not found',
                    'message' => 'Preset not found'
                ]);
                return;
            }

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            // Validate JSON parsing
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Invalid JSON',
                    'message' => 'Invalid JSON format: ' . json_last_error_msg()
                ]);
                return;
            }

            // Build dynamic update query
            $updates = [];
            $params = [':id' => $id];

            if (isset($input['name'])) {
                $updates[] = "name = :name";
                $params[':name'] = $input['name'];
            }

            if (isset($input['devices'])) {
                // Fetch current devices data for merging
                $currentQuery = "SELECT devices FROM presets WHERE id = :id";
                $currentStmt = $this->conn->prepare($currentQuery);
                $currentStmt->bindParam(':id', $id);
                $currentStmt->execute();
                $currentData = $currentStmt->fetch();

                $currentDevices = json_decode($currentData['devices'], true);

                // Deep merge settings if they exist
                if (isset($input['devices']['settings']) && isset($currentDevices['settings'])) {
                    $input['devices']['settings'] = array_merge(
                        $currentDevices['settings'],
                        $input['devices']['settings']
                    );
                }

                // Merge other device fields
                $mergedDevices = array_merge($currentDevices, $input['devices']);

                $updates[] = "devices = :devices";
                $params[':devices'] = json_encode($mergedDevices);
            }

            if (empty($updates)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Validation error',
                    'message' => 'No fields to update'
                ]);
                return;
            }

            $query = "UPDATE presets SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($stmt->execute()) {
                // Fetch the updated preset
                $this->getPresetById($id);
            } else {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Database error',
                    'message' => 'Failed to update preset'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function deletePreset($id)
    {
        try {
            // Check if preset exists
            $checkQuery = "SELECT id FROM presets WHERE id = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();

            if ($checkStmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Not found',
                    'message' => 'Preset not found'
                ]);
                return;
            }

            $query = "DELETE FROM presets WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Preset deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Database error',
                    'message' => 'Failed to delete preset'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
