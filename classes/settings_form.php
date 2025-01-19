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
 * Settings form.
 *
 * @copyright 2014-2023 Albert Gasset <albertgasset@fsfe.org>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   workshopeval_peerreview
 */

namespace workshopeval_peerreview;

// Ensure the necessary file is included
require_once($CFG->dirroot . '/mod/workshop/eval/peerreview/lib.php');

use html_writer; // Add this line to use the html_writer class

class settings_form extends \workshop_evaluation_settings_form {
    /**
     * Definition of evaluation settings.
     */
    protected function definition_sub() {
        global $OUTPUT, $PAGE; // Add $PAGE to use the global $PAGE variable
        $mform = $this->_form;
        $current = $this->_customdata['current'];

        // Create an instance of the evaluation class with the required argument
        $evaluation = new \workshop_peerreview_evaluation($this->_customdata['workshop']);

        // Call the method to get feedback data
        $feedback_data = $evaluation->get_feedback_data();
        $workshopid = $this->_customdata['workshop']->id; // Obtener el workshopid

        // Create an HTML table to display the results
        $table_html = '<table id="feedback-table" class="table table-striped" data-workshopid="' . $workshopid . '">
                         <thead>
                             <tr>
                                <th>Workshop ID</th>
                                <th>Estudiante</th>
                                <th>Estudiante Asignado a Calificar</th>
                                <th>Retroalimentación</th>
                                <th>Calificación</th>
                             </tr>
                         </thead>
                         <tbody>';

        // Verificar si hay datos y agregar filas a la tabla
        if ($feedback_data) {
            foreach ($feedback_data as $data) {
                $table_html .= '<tr>
                                    <td>' . htmlspecialchars($data->workshopid) . '</td>
                                    <td>' . htmlspecialchars($data->author) . '</td>
                                    <td>' . htmlspecialchars($data->reviewer) . '</td>
                                    <td>' . $data->feedbackauthor . '</td>
                                    <td>' . htmlspecialchars($data->grade) . '</td>
                                </tr>';
            }
        } else {
            $table_html .= '<tr><td colspan="5">No se encontraron datos.</td></tr>';
        }

        $table_html .= '</tbody></table>';

        // Add buttons above the table
        $buttons_html = html_writer::div(
            html_writer::tag('button', get_string('activate_ai', 'workshopeval_peerreview'), ['type' => 'button', 'class' => 'btn btn-primary', 'id' => 'toggle-ai-button']),
            'buttons-wrapper'
        );

        // Add the header and table to the form
        $mform->addElement('header', 'feedback_header', 'Retroalimentación de Evaluaciones');
        $mform->addElement('html', $buttons_html);
        $mform->addElement('html', $table_html);

        // Include the external JavaScript file
        $PAGE->requires->js('/mod/workshop/eval/peerreview/js/toggle_ai.js');

        // Selección de comparación.
    
        $this->set_data($current);
    }

    // Assuming this method retrieves the grades
    private function get_grades() {
        global $DB;
        return $DB->get_records('workshop_grades'); // Adjust the table name and fields as necessary
    }
}
