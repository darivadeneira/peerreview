<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'workshopeval_peerreview_save_feedback' => array(
        'classname'   => 'workshopeval_peerreview\external',
        'methodname'  => 'save_feedback',
        'description' => 'Guarda la retroalimentación de la IA',
        'type'        => 'write',
        'ajax'        => true,
    )
);
