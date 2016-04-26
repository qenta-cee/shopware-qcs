<?php
/*
 * Die vorliegende Software ist Eigentum von Wirecard CEE und daher vertraulich
 * zu behandeln. Jegliche Weitergabe an dritte, in welcher Form auch immer, ist
 * unzulaessig. Software & Service Copyright (C) by Wirecard Central Eastern
 * Europe GmbH, FB-Nr: FN 195599 x, http://www.wirecard.at
 */

return Array(
        'DATA_STORAGE_URL' => 'https://checkout.wirecard.com/seamless/dataStorage',
        'FRONTEND_URL' => 'https://checkout.wirecard.com/seamless/frontend',
        'BACKEND_URL' => 'https://checkout.wirecard.com/seamless/backend',
        'MODULE_NAME' => 'WirecardCEE_QMore',
        'MODULE_VERSION' => '3.1.0',
        'DEPENDENCIES' => array(
                'FRAMEWORK_NAME' => 'Zend Framework',
                'FRAMEWORK_VERSION' => Array(
                        'MINIMUM' => '1.11.10',
                        'CURRENT' => Zend_Version::VERSION
                ),
        ),
        'USE_DEBUG' => FALSE
);