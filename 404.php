<?
http_response_code(404);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'status' => 'error',
    'date' => null,
    'error' => [
        'massage' => 'Not found',
        'code' => 404,
        'customDate' => null
    ]
]);