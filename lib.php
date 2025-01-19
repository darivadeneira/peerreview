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
 * @copyright 2025 Erick Lasluisa, Ariel Rivadeneira, Augusto Salazar <ealasluisa@espe.edu.ec, darivadeneira7@espe.edu.ec, casalazar7@espe.edu.ec>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   workshopeval_peerreview
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/workshop/eval/best/lib.php');

/**
 * Workshop evaluation class.
 *
 * @copyright 2025 Erick Lasluisa, Ariel Rivadeneira, Augusto Salazar <ealasluisa@espe.edu.ec, darivadeneira7@espe.edu.ec, casalazar7@espe.edu.ec>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   workshopeval_peerreview
 */

class workshop_peerreview_evaluation extends workshop_best_evaluation {
    /**
     * Returns an instance of the form to provide evaluation settings.
     *
     * @param moodle_url|null $actionurl
     * @return \workshopeval_peerreview\settings_form
     */
    public function get_settings_form(moodle_url $actionurl = null) {
        global $OUTPUT;
        // Datos personalizados para el formulario
        $customdata['workshop'] = $this->workshop;
        $customdata['current'] = $this->settings;
        $attributes = ['class' => 'evalsettingsform best'];

        // Mostrar cuadro con mensaje y agregar una imagen y un botón centrado
        $output = html_writer::div(
            html_writer::div(
                get_string('plugin_active', 'workshopeval_peerreview'),
                'plugin-active-box'
            )
        );

        echo $output;

        return new \workshopeval_peerreview\settings_form($actionurl, $customdata, 'post', '', $attributes);
    }
    /**
     * Obtiene los nombres, apellidos y retroalimentación de los estudiantes para una tarea.
     *
     * @param int $assessment_id El ID de la evaluación.
     * @return array Los registros con los nombres, apellidos y retroalimentación.
     */
    // Definir la función correctamente en lib.php
    function get_feedback_data() {
        
        global $DB, $PAGE;

        $workshopid = $PAGE->cm->instance;
    

        // Consulta SQL para obtener los nombres de los revisores y de los revisados
        $query = "
            SELECT
                CONCAT(u.firstname, ' ', u.lastname) AS author,
                CONCAT(u2.firstname, ' ', u2.lastname) AS reviewer,
                wa.feedbackauthor,
                wa.grade,
                mws.workshopid
            FROM
                {workshop_submissions} mws
            JOIN
                {user} u ON mws.authorid = u.id
            JOIN
                {workshop_assessments} wa ON mws.id = wa.submissionid
            JOIN
                {user} u2 ON wa.reviewerid = u2.id
            WHERE
                mws.workshopid = :workshopid
        ";

        // Ejecutar la consulta SQL
        return $DB->get_records_sql($query, ['workshopid' => $this->workshop->id]);
    }    
}
?>