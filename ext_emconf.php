<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FTP Finisher for powermail',
    'description' => 'Save email as xml on a remote server',
    'category' => 'backend',
    'author' => 'Georg Ringer',
    'author_email' => 'mail@ringer.it',
    'state' => 'beta',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-8.9.99',
            'powermail' => '3.8.0-3.9.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
