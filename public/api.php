<?php
declare(strict_types=1);

use App\Sudoku;

require __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Origin: https://bernherre.github.io');
header('Vary: Origin');


$action = $_GET['action'] ?? 'new';
$input = json_decode(file_get_contents('php://input') ?: "{}", true) ?? [];

try {
    switch ($action) {
        case 'new':
            $size = (int)($input['size'] ?? 9);
            if (!in_array($size, [4,6,9], true)) $size = 9;
            $difficulty = (string)($input['difficulty'] ?? 'medium');
            $data = Sudoku::generate($size, $difficulty);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            break;

        case 'check':
            $current = $input['current'] ?? null;
            $solution = $input['solution'] ?? null;
            if (!is_array($current) || !is_array($solution)) throw new \InvalidArgumentException('Faltan current/solution.');
            $res = Sudoku::checkGrid($current, $solution);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no soportada']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}