<?php
require_once('../../config.php'); // Incluye la configuración de Moodle para acceder a su API.

require_login();
if (!isloggedin()) {
    throw new moodle_exception('notloggedin', 'error');
}

$feedbackdata = required_param('feedbackdata', PARAM_RAW); // Recibimos los datos en JSON

global $DB;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manejar la solicitud POST para guardar los datos
    $data = json_decode($feedbackdata, true);
    if (!empty($data)) {
        foreach ($data as $entry) {
            $record = new stdClass();
            $record->assesmentid = $entry['assesmentid'];
            $record->feedback_ai = $entry['feedback_ai'];

            // Guarda el registro en la tabla personalizada
            $DB->insert_record('workshopeval_peerreview', $record);
        }
        echo json_encode(['status' => 'success', 'message' => 'Datos guardados correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos válidos.']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Manejar la solicitud PUT para actualizar los datos
    parse_str(file_get_contents("php://input"), $putData);
    $feedbackdata = json_decode($putData['feedbackdata'], true);

    // Aquí actualizas la base de datos con los nuevos datos
    // Ejemplo de cómo podrías hacer la actualización, dependiendo de cómo manejes la IA
    if (isset($feedbackdata['aiActivated'])) {
        // Actualiza el estado de la IA (por ejemplo, en una tabla de configuración)
        $aiActivated = $feedbackdata['aiActivated'];

        // Actualiza en la base de datos si es necesario
        // $DB->set_field('config', 'ai_activated', $aiActivated, ['apikey' => $apikey]);

        echo json_encode(['status' => 'success', 'message' => 'Datos actualizados correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Datos no válidos para actualizar.']);
    }
}

exit;
?>
