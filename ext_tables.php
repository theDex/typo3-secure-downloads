<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);

/*
 * Register the backend module if we are in BE context and the log option is set in extension configuration
 */
if (TYPO3_MODE === 'BE' && $_EXTCONF['log']) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Bitmotion.' . $_EXTKEY,
        'web',
        'TrafficLog',
        '10',
        [
            'Log' => 'show,list',
        ], [
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.svg',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_log.xlf',
        ]
    );
}

unset ($_EXTCONF);