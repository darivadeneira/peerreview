<?php
// Este archivo es parte de Moodle - http://moodle.org/
// Moodle es software libre: puedes redistribuirlo y/o modificarlo
// bajo los términos de la Licencia Pública General GNU publicada por
// la Free Software Foundation, ya sea en la versión 3 de la Licencia, o
// (a tu elección) cualquier versión posterior.
//
// Moodle se distribuye con la esperanza de que sea útil,
// pero SIN NINGUNA GARANTÍA; sin siquiera la garantía implícita de
// COMERCIABILIDAD o APTITUD PARA UN PROPÓSITO PARTICULAR. Consulta la
// Licencia Pública General GNU para más detalles.
//
// Deberías haber recibido una copia de la Licencia Pública General GNU
// junto con Moodle. Si no, consulta <http://www.gnu.org/licenses/>.

/**
 * Clase de evaluación del taller.
 *
 * @copyright 2025 Erick Lasluisa, Ariel Rivadeneira, Augusto Salazar
 * @license   https://www.gnu.org/copyleft/gpl.html Licencia GPL v3 o posterior
 * @package   workshopeval_peerreview
 */

require_once(__DIR__ . '/../../../../config.php'); // Ruta al archivo de configuración de Moodle.
require_once($CFG->libdir . '/filelib.php'); // Biblioteca de Moodle para realizar peticiones HTTP.

require_login();
if (!is_siteadmin()) {
    throw new moodle_exception('accessdenied', 'admin');
}

// Validar y recibir datos de la solicitud POST.
$data = json_decode(file_get_contents('php://input'), true);

// Validar la clave API
if (empty($data['apiKey'])) {
    echo json_encode(['error' => 'API Key es requerida.']);
    http_response_code(400);
    exit;
}

if (empty($data['feedbackData']) || !is_array($data['feedbackData'])) {
    echo json_encode(['error' => 'La retroalimentación es necesaria y debe ser un arreglo.']);
    http_response_code(400);
    exit;
}

// Obtener la API key proporcionada por el profesor.
$api_key = $data['apiKey'];
$feedback_data = $data['feedbackData'];

// Construir el 'prompt' dinámicamente para enviar a la API de OpenAI.
foreach ($feedback_data as $feedback) {
    $prompt = "Evalúa si esta retroalimentación corrige adecuadamente la respuesta del estudiante sobre el tema dado. 
    Responde únicamente con 'Alineada' o 'Revisión'. 
    Respuesta del estudiante: {$feedback['author']} 
    Retroalimentación del revisor: {$feedback['feedbackauthor']} 
    Tema: Test de prueba";

    // Datos para la solicitud a la API de OpenAI
    $request_data = [
        'model' => 'gpt-3.5-turbo-0125',  // Modelo de OpenAI que estés utilizando
        'prompt' => $prompt,
        'max_tokens' => 10,
        'temperature' => 0,
    ];

    // Preparar las opciones para la solicitud HTTP a la API.
    $options = [
        'headers' => [
            'Authorization: Bearer ' . $api_key,  // Tu API Key de OpenAI
            'Content-Type: application/json',
        ],
        'body' => json_encode($request_data),
    ];

    // Enviar la solicitud a la API de OpenAI.
    $response = \core\file\curl::post($api_url, $options);
    $respuesta_ia = json_decode($response, true);

    // Procesar la respuesta de la API de OpenAI.
    if (isset($respuesta_ia['choices'][0]['text'])) {
        $evaluacion = trim($respuesta_ia['choices'][0]['text']); // Limpia espacios o saltos de línea.

        if ($evaluacion === 'Alineada' || $evaluacion === 'Revisión') {
            // Aquí puedes hacer lo que necesites con la evaluación, como guardarla en la base de datos.
            // Por ejemplo:
            // $DB->update_record('workshop_assessments', [
            //     'id' => $feedback['assessment_id'],  // Asegúrate de que el ID de la evaluación esté en los datos
            //     'feedbackreviewer' => $evaluacion,
            // ]);
            echo json_encode(['success' => true, 'evaluation' => $evaluacion]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Respuesta inesperada de la IA.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al procesar la solicitud a la IA.']);
    }
}
