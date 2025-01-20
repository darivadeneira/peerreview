<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_ComparisonofevaluationwithAI_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Obtén el administrador de base de datos.

    // Agregar una nueva columna a una tabla existente en una versión específica.
    if ($oldversion < 2022112800) {
        // Define la tabla donde se añadirá la nueva columna.
        $table = new xmldb_table('workshop_assessments'); // Reemplaza con el nombre exacto de la tabla.

        // Define la nueva columna.
        $field = new xmldb_field(
            'feedback_ai',       // Nombre de la nueva columna.
            XMLDB_TYPE_CHAR,     // Tipo de dato.
            '50',                // Tamaño máximo.
            null,                // Precisión (nulo para tipos no numéricos).
            XMLDB_NOTNULL,       // No permite valores nulos.
            null,                // Clave foránea (si aplica).
            'Pendiente',         // Valor por defecto.
            'feedbackreviewerformat' // Columna previa (puede ser null).
        );
        

        // Verifica si la columna no existe ya.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field); // Agrega la nueva columna.
        }

        // Guarda el punto de actualización.
        upgrade_plugin_savepoint(true, 2025140100, 'mod', 'workshop');
    }

    return true;
}
