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

        // Parámetros de contexto
        $params = self::validate_parameters(self::save_feedback_parameters(), array(
            'feedbackdata' => $feedbackdata
        ));

        $data = json_decode($params['feedbackdata'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('invalidjson', 'workshopeval_peerreview');
        }

        foreach ($data as $entry) {
            $record = new \stdClass();
            $record->assesmentid = $entry['assesmentid'];
            $record->feedback_ai = $entry['feedback_ai'];

            try {
                $DB->insert_record('workshopeval_peerreview', $record);
            } catch (\dml_exception $e) {
                throw new \moodle_exception('dberror', 'workshopeval_peerreview');
            }
        }

        return array(
            'status' => 'success',
            'message' => 'Datos guardados correctamente'
        );
    }

    public static function save_feedback_returns() {
        return new \external_single_structure(
            array(
                'status' => new \external_value(PARAM_TEXT, 'Status of the operation'),
                'message' => new \external_value(PARAM_TEXT, 'Message describing the result')
            )
        );
    }
}
