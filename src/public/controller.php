<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/triangulationcontroller.php';

$controller = new TriangulationController();
$response = $controller->calculateAction();

echo json_encode($response);
