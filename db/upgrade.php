<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_workshopeval_peerreview_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025190101) {
        // Define table workshopeval_peerreview to be created
        $table = new xmldb_table('workshopeval_peerreview');

        // Adding fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('assessmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('feedback_ai', XMLDB_TYPE_CHAR, '25', null, null, null, 'Pendiente');

        // Adding keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fkuq_workshop', XMLDB_KEY_FOREIGN_UNIQUE, ['assessmentid'], 'workshop_assessments', ['id']);

        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Register web service if it doesn't exist
        $service = $DB->get_record('external_services', ['shortname' => 'workshopeval_peerreview']);
        if (!$service) {
            $service = new stdClass();
            $service->name = 'Peer Review Evaluation Service';
            $service->shortname = 'workshopeval_peerreview';
            $service->enabled = 1;
            $service->restrictedusers = 0;
            $service->downloadfiles = 0;
            $service->uploadfiles = 0;
            $DB->insert_record('external_services', $service);
        }

        // Register the external function if it doesn't exist
        $function = $DB->get_record('external_functions', ['name' => 'workshopeval_peerreview_save_feedback']);
        if (!$function) {
            $function = new stdClass();
            $function->name = 'workshopeval_peerreview_save_feedback';
            $function->classname = 'workshopeval_peerreview\external';
            $function->methodname = 'save_feedback';
            $function->classpath = 'mod/workshop/eval/peerreview/classes/external.php';
            $function->component = 'workshopeval_peerreview';
            $function->capabilities = 'mod/workshop:view';
            $DB->insert_record('external_functions', $function);
        }

        upgrade_plugin_savepoint(true, 2025190101, 'workshopeval', 'peerreview');
    }

    if ($oldversion < 2025190104) {
        // Registrar servicios web
        $service = new stdClass();
        $service->name = 'Peer Review Evaluation Service';
        $service->shortname = 'workshopeval_peerreview';
        $service->enabled = 1;
        $service->restrictedusers = 0;
        $service->downloadfiles = 0;
        $service->uploadfiles = 0;

        if (!$DB->record_exists('external_services', array('shortname' => 'workshopeval_peerreview'))) {
            $service->id = $DB->insert_record('external_services', $service);
        }

        // Registrar funciones
        $functions = array(
            array(
                'name' => 'workshopeval_peerreview_save_feedback',
                'classname' => 'workshopeval_peerreview\\external',
                'methodname' => 'save_feedback',
                'classpath' => 'mod/workshop/eval/peerreview/classes/external.php',
                'description' => 'Save AI feedback evaluation'
            ),
            array(
                'name' => 'workshopeval_peerreview_create_initial_records',
                'classname' => 'workshopeval_peerreview\\external',
                'methodname' => 'create_initial_records',
                'classpath' => 'mod/workshop/eval/peerreview/classes/external.php',
                'description' => 'Create initial records in workshopeval_peerreview table'
            )
        );

        foreach ($functions as $function) {
            if (!$DB->record_exists('external_functions', array('name' => $function['name']))) {
                $DB->insert_record('external_functions', $function);
            }
        }

        upgrade_plugin_savepoint(true, 2025190104, 'workshopeval', 'peerreview');
    }

    return true;
}
