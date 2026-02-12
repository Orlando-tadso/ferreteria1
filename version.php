<?php
// Archivo de versión - devuelve el timestamp actual
// Se usa para detectar cuando hay nuevos cambios en el servidor
header('Content-Type: application/json');
echo json_encode(['timestamp' => time()]);
?>
