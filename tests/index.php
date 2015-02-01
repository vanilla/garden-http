<?php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['hello' => 'world'], JSON_PRETTY_PRINT);