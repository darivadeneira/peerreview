<?php
require_once('../../config.php');

require_login();
if (!isloggedin()) {
    throw new moodle_exception('notloggedin', 'error');
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['feedbackdata'])) {
            throw new Exception('No se recibieron datos');
        }

        $feedbackdata = json_decode($_POST['feedbackdata'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
        }

        if (empty($feedbackdata)) {
            throw new Exception('Los datos recibidos están vacíos');
        }

        global $DB;
        
        foreach ($feedbackdata as $entry) {
            if (!isset($entry['assesmentid']) || !isset($entry['feedback_ai'])) {
                throw new Exception('Datos incompletos en una entrada');
            }

            $record = new stdClass();
            $record->assesmentid = $entry['assesmentid'];
            $record->feedback_ai = $entry['feedback_ai'];

            try {
                $DB->insert_record('workshopeval_peerreview', $record);
            } catch (Exception $e) {
                throw new Exception('Error al insertar en la base de datos: ' . $e->getMessage());
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Datos guardados correctamente']);
    } else {
        throw new Exception('Método no permitido');
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
