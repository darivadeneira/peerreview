<?php
namespace workshopeval_peerreview;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class external extends \external_api {
    
    public static function save_feedback_parameters() {
        return new \external_function_parameters(
            array(
                'feedbackdata' => new \external_value(PARAM_RAW, 'JSON string with feedback data')
            )
        );
    }

    public static function save_feedback($feedbackdata) {
        global $DB;

        // Parámetros de contexto y validación
        $params = self::validate_parameters(self::save_feedback_parameters(), array(
            'feedbackdata' => $feedbackdata
        ));

        try {
            $data = json_decode($params['feedbackdata'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \moodle_exception('invalidjson', 'workshopeval_peerreview');
            }

            // Iniciar transacción
            $transaction = $DB->start_delegated_transaction();

            foreach ($data as $entry) {
                // Validar datos requeridos
                if (!isset($entry['assesmentid']) || !isset($entry['feedback_ai'])) {
                    throw new \moodle_exception('missingdata', 'workshopeval_peerreview');
                }

                // Preparar registro
                $record = new \stdClass();
                $record->assessmentid = clean_param($entry['assesmentid'], PARAM_INT);
                $record->feedback_ai = clean_param($entry['feedback_ai'], PARAM_TEXT);
                $record->timecreated = time();

                // Verificar si ya existe un registro para este assessment
                $existing = $DB->get_record('workshopeval_peerreview', 
                    array('assessmentid' => $record->assessmentid));

                if ($existing) {
                    // Actualizar registro existente
                    $record->id = $existing->id;
                    if (!$DB->update_record('workshopeval_peerreview', $record)) {
                        throw new \moodle_exception('dberror', 'workshopeval_peerreview');
                    }
                } else {
                    // Insertar nuevo registro
                    if (!$DB->insert_record('workshopeval_peerreview', $record)) {
                        throw new \moodle_exception('dberror', 'workshopeval_peerreview');
                    }
                }
            }

            // Confirmar transacción
            $transaction->allow_commit();

            return array(
                'status' => 'success',
                'message' => get_string('feedbacksaved', 'workshopeval_peerreview')
            );

        } catch (\Exception $e) {
            if (isset($transaction)) {
                $transaction->rollback($e);
            }
            throw new \moodle_exception('dberror', 'workshopeval_peerreview', '', $e->getMessage());
        }
    }

    public static function save_feedback_returns() {
        return new \external_single_structure(
            array(
                'status' => new \external_value(PARAM_TEXT, 'Status of the operation'),
                'message' => new \external_value(PARAM_TEXT, 'Message describing the result')
            )
        );
    }

    public static function create_initial_records_parameters() {
        return new \external_function_parameters(
            array(
                'workshopid' => new \external_value(PARAM_INT, 'Workshop ID')
            )
        );
    }

    public static function create_initial_records($workshopid) {
        global $DB;

        try {
            // Verificar que el workshopid es válido
            if (!$workshopid || !is_numeric($workshopid)) {
                throw new \moodle_exception('invalidworkshopid', 'workshopeval_peerreview');
            }

            // Iniciar transacción
            $transaction = $DB->start_delegated_transaction();

            // Verificar si el workshop existe
            if (!$DB->record_exists('workshop', array('id' => $workshopid))) {
                throw new \moodle_exception('invalidworkshopid', 'workshopeval_peerreview');
            }

            // Obtener todas las evaluaciones existentes que no tienen registro en workshopeval_peerreview
            $sql = "SELECT DISTINCT wa.id as assessmentid 
                   FROM {workshop_assessments} wa 
                   JOIN {workshop_submissions} ws ON wa.submissionid = ws.id 
                   LEFT JOIN {workshopeval_peerreview} wp ON wa.id = wp.assessmentid 
                   WHERE ws.workshopid = :workshopid 
                   AND wp.id IS NULL";
            
            $assessments = $DB->get_records_sql($sql, ['workshopid' => $workshopid]);

            $createdCount = 0;
            foreach ($assessments as $assessment) {
                $record = new \stdClass();
                $record->assessmentid = $assessment->assessmentid;
                $record->feedback_ai = 'Pendiente';  // Cambiado de string vacío a 'Pendiente'
                $record->timecreated = time();

                if ($DB->insert_record('workshopeval_peerreview', $record)) {
                    $createdCount++;
                }
            }

            $transaction->allow_commit();

            return [
                'success' => true,
                'message' => "Se crearon $createdCount registros correctamente",
                'count' => $createdCount
            ];

        } catch (\Exception $e) {
            if (isset($transaction)) {
                $transaction->rollback($e);
            }
            throw new \moodle_exception('errorprocessing', 'workshopeval_peerreview', '', $e->getMessage());
        }
    }

    public static function create_initial_records_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'message' => new \external_value(PARAM_TEXT, 'Status message'),
            'count' => new \external_value(PARAM_INT, 'Number of records created')
        ]);
    }
}
