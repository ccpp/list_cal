<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: Web>List Calendar
 *
 * Listing database records from the tables configured in $GLOBALS['TCA'] in a calendar BE module
 *
 * @author Christian Plattner <ccpp@gmx.at>
 */

// TODO: TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList
// TODO: TYPO3\\CMS\\Recordlist\\RecordList->modTSconfig['properties']['allowedNewTables']

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = array(
	'className' => 'TYPO3\\ListCal\\RecordList\\DatabaseRecordCalendarList'
);

\TYPO3\CMS\Backend\Utility\BackendUtility::lockRecords();

$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Recordlist\\RecordList');
$SOBE->init();
$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();
