<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Workshop evaluation class.
 *
 * @copyright 2025 Erick Lasluisa, Ariel Rivadeneira, Augusto Salazar
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

// Obtener la API key y workshopId proporcionados por el profesor.
$api_key = $data['apiKey'];
$workshopId = $data['workshopId'];

// Asegúrate de que la clave API y el workshopId estén presentes.
if (empty($api_key) || empty($workshopId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere una API Key y un workshopId.']);
    exit;
}

// Función para obtener los datos de retroalimentación.
function get_feedback_data($workshopId) {
    global $DB;
    
    $query = "
        SELECT
            wa.id AS assessmentid,
            wa.feedbackauthor,
            mws.content
        FROM
            {workshop_submissions} mws
        JOIN
            {workshop_assessments} wa ON mws.id = wa.submissionid
        WHERE
            mws.workshopid = :workshopid
    ";

    return $DB->get_records_sql($query, ['workshopid' => $workshopId]);
}

// Obtener los datos de la retroalimentación.
$feedback_data = get_feedback_data($workshopId);

// Construir el 'prompt' dinámicamente para enviar a la API de OpenAI.
foreach ($feedback_data as $feedback) {
    $prompt = "Evalúa si esta retroalimentación corrige adecuadamente la respuesta del estudiante sobre el tema dado. 
    Responde únicamente con 'Alineada' o 'Revisión'. 
    Respuesta del estudiante: {$feedback->content} 
    Retroalimentación del revisor: {$feedback->feedbackauthor} 
    Tema: Pruebas de testeo";

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
            // Actualiza la base de datos con la evaluación de la IA.
            $DB->update_record('workshop_assessments', [
                'id' => $feedback->assessmentid,  // Asegúrate de que el ID de la evaluación esté en los datos
                'feedbackreviewer' => $evaluacion,
            ]);
            echo json_encode(['success' => true, 'evaluation' => $evaluacion]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Respuesta inesperada de la IA.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al procesar la solicitud a la IA.']);
    }
}
?>
