<?php
defined('MOODLE_INTERNAL') || die();

// Definir el servicio web
$services = array(
    'Peer Review Evaluation Service' => array(
        'functions' => array('workshopeval_peerreview_save_feedback'),
        'shortname' => 'workshopeval_peerreview',
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);

// Definir las funciones del servicio web
$functions = array(
    'workshopeval_peerreview_save_feedback' => array(
        'classname'     => 'workshopeval_peerreview\external',
        'methodname'    => 'save_feedback',
        'classpath'     => 'mod/workshop/eval/peerreview/classes/external.php',
        'description'   => 'Save AI feedback evaluation',
        'type'          => 'write',
        'capabilities'  => 'mod/workshop:view',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'workshopeval_peerreview_create_initial_records' => array(
        'classname'     => 'workshopeval_peerreview\external',
        'methodname'    => 'create_initial_records',
        'classpath'     => 'mod/workshop/eval/peerreview/classes/external.php',
        'description'   => 'Create initial records in workshopeval_peerreview table',
        'type'          => 'write',
        'capabilities'  => 'mod/workshop:view',
        'ajax'          => true,
        'loginrequired' => true
    )
);
