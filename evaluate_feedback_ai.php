<?php
require_once('../../config.php');

// Habilitar el reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_login();
if (!isloggedin()) {
    throw new moodle_exception('notloggedin', 'error');
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Log de datos recibidos
        error_log('POST recibido: ' . print_r($_POST, true));
        
        if (!isset($_POST['feedbackdata'])) {
            throw new Exception('No se recibieron datos en feedbackdata');
        }

        $feedbackdata = json_decode($_POST['feedbackdata'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar JSON: ' . json_last_error_msg() . ' - Datos recibidos: ' . $_POST['feedbackdata']);
        }

        if (empty($feedbackdata)) {
            throw new Exception('Los datos recibidos están vacíos después de decodificar');
        }

        global $DB;
        
        // Log de los datos a insertar
        error_log('Datos a insertar: ' . print_r($feedbackdata, true));
        
        foreach ($feedbackdata as $entry) {
            if (!isset($entry['assesmentid']) || !isset($entry['feedback_ai'])) {
                throw new Exception('Datos incompletos en una entrada: ' . print_r($entry, true));
            }

            $record = new stdClass();
            $record->assesmentid = clean_param($entry['assesmentid'], PARAM_INT);
            $record->feedback_ai = clean_param($entry['feedback_ai'], PARAM_TEXT);

            try {
                if (!$DB->insert_record('workshopeval_peerreview', $record)) {
                    throw new Exception('Error al insertar registro en la base de datos');
                }
            } catch (dml_exception $e) {
                throw new Exception('Error de base de datos: ' . $e->getMessage() . ' - SQL: ' . $e->debuginfo);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Datos guardados correctamente']);
    } else {
        throw new Exception('Método HTTP no permitido: ' . $_SERVER['REQUEST_METHOD']);
    }
} catch (Exception $e) {
    error_log('Error en evaluate_feedback_ai.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => debug_backtrace()
    ]);
}
?>
