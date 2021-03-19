<?php
namespace TYPO3\ListCal\RecordList;

use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;

class DatabaseRecordCalendarList extends DatabaseRecordList {
	protected $startTimestamp;
	protected $endTimestamp;
	protected $slot;
	protected $slotInfo;
	protected $slots;
	protected $nOtherTables;
	protected $nContent;
	protected $tableInfo;
	protected $viewType;
	protected $referenceTime;
	protected $limitDaysOfWeek;
	protected $calendarView;

	public function generateList() {
		$this->initializeCalendarView();
		$this->initializeTables();

		$this->slots = array();
		$this->nOtherTables = 0;
		$this->nContent = 0;

		$GLOBALS['LANG']->includeLLFile('EXT:list_cal/locallang_mod_web_listcal.xlf');

		if (empty($this->tableInfo)) {
			$flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$GLOBALS['LANG']->getLL('noConfiguredTableMessage'), '', FlashMessage::INFO);
			$this->HTMLcode = $flashMessage->render();
			return;
		}

		// Prepare tables, generate empty HTML
		parent::generateList();

		$this->slot = GeneralUtility::_GP('slot');
		if (!$this->slot) {
			$this->slot = $this->calendarView->timestampToSlot($this->referenceTime);
		}

		$this->buildSlotSelector();

		$this->startTimestamp = $this->calendarView->slotToTimestamp($this->slot);
		$this->endTimestamp = $this->calendarView->slotToTimestamp($this->calendarView->increaseSlot($this->slot));

		foreach ($this->tableInfo as $table => $tableInfo) {
			$this->collectRecords($table, $this->id, $tableInfo['rowlist']);
		}
		//var_dump($this->slots);

		$this->buildCalendar();
	}

	protected function buildSlotSelector() {
		if (count($this->slots)) {
			ksort($this->slots);
			reset($this->slots);
			$firstSlot = min($this->slot, key($this->slots));
			end($this->slots);
			$lastSlot = max($this->slot, key($this->slots));
		} else {
			$firstSlot = $lastSlot = $this->slot;
		}
		$firstSlot = $this->calendarView->increaseSlot($firstSlot, -3);
		$lastSlot = $this->calendarView->increaseSlot($lastSlot, 3);

		$this->HTMLcode .= '<h4>Monate</h4>';
		$this->HTMLcode .= '<div>';
		for ($slot = $firstSlot; $slot <= $lastSlot; $slot = $this->calendarView->increaseSlot($slot)) {
			if($_count++ > 100) return;
			$this->HTMLcode .= '<span style="padding:3px;' .
				($this->slots[$slot] ? 'font-weight:bold;' : '') .
				($slot == $this->slot ? 'text-decoration:underline;' : '') .
				'">' . 
				'<a href="' . BackendUtility::getModuleUrl(GeneralUtility::_GP('M'), array('id' => $this->id, 'slot' => $slot)) . '">' .
				$this->calendarView->slotToText($slot, true) . 
				'</a></span> &nbsp; ';
		}
		$this->HTMLcode .= '</div>';
	}

	protected function buildCalendar() {
		// TODO moduleToken
		$this->HTMLcode .= '<form name="dblistForm" method="post" action="mod.php?M=web_listcal">' . PHP_EOL;
		if (count($this->tableInfo)) {
			$tableNames = array();
			foreach ($this->tableInfo as $tableName => $tableInfo) {
				if (!$tableInfo['count'])
					continue;

				$tableNames[$tableName] =
					IconUtility::getSpriteIconForRecord($tableName, array()) .
					$tableInfo['tableTitle'];
			}
			$this->HTMLcode .= '<p>Tables: ' . implode($tableNames, ', ') . '</p>' . PHP_EOL;
		}
		ksort($this->slots);

		$this->HTMLcode .= '<table class="t3-page-columns t3-gridTable"><thead><tr>';
		//foreach ($this->slots as $slot => &$rowsBySlot) {
		$slot = $this->slot;//TODO
		$rowsBySlot = $this->slots[$slot];//TODO
			$timestamp = $this->calendarView->slotToTimestamp($slot);
			$this->HTMLcode .= '<th>' . strftime('%B %Y', $timestamp) . '</th>';
		//}
		$this->HTMLcode .= "</tr></thead>";
		$this->HTMLcode .= "<tbody><tr>";

		//foreach ($this->slots as $slot => &$rowsBySlot) {
		$slot = $this->slot;//TODO
		$rowsBySlot = $this->slots[$slot];//TODO
		$rowsTable = $this->slots[$slot]['rowsTable'];
		$rowlist = $this->slots[$slot]['rowlist'];
			$timestamp = $this->calendarView->slotToTimestamp($slot);
			$month = date('m', $timestamp);//TODO
			$year = date('Y', $timestamp); //TODO
			$nDays = date('d', mktime(0, 0, 0, $month+1, 1, $year)-1);//TODO!!
			$this->HTMLcode .= "<td>";
			for ($mday = 1; $mday <= $nDays; $mday++) {
				$timestamp = mktime($this->modTSconfig['properties']['newItemHour'], 0, 0, $month, $mday, $year);
				$dayOfWeek = date('w', $timestamp);
				if (!in_array($dayOfWeek, $this->limitDaysOfWeek))
					continue;

				$rowsTable = array();
				if ($rowsBySlot[$mday]) {
					ksort($rowsBySlot[$mday]);	// order by timestamp
					foreach ($rowsBySlot[$mday] as $tstamp => &$rows2) {
						foreach ($rows2 as $row) {
							$rowsTable[$row['uid']] = $row;
						}
					}
				}

				$this->HTMLcode .= $this->renderDayBox($rowsTable, $timestamp, $rowlist);
			}
			$this->HTMLcode .= "</td>";
		//}

		$this->HTMLcode .= "</tbody></table>";
		$this->HTMLcode .= '</form>' . PHP_EOL;

		$this->HTMLcode .= $this->getHeaderFlashMessagesForCurrentPid();
	}

	protected function renderDayBox(&$rowsTable, $timestamp, $rowlist) {
		$head = '<table class="typo3-dblist" cellspacing="0" cellpadding="0" border="0"><tbody>' . PHP_EOL;
		$head .= '<tr class="c-table-row-spacer"></tr>' . PHP_EOL;	// what for?
		$head .= '<tr class="t3-row-header">' . PHP_EOL;
		$head .= '<td class="col-icon" nowrap="nowrap"><img src="' . ExtensionManagementUtility::extRelPath('list_cal') . 'mod1/list_cal.gif" /></td>' . PHP_EOL;
		$head .= '<td class="" nowrap="nowrap" colspan="6">' . strftime("%A %B %e", $timestamp) . '</td>' . PHP_EOL;
		$head .= '</tr>';

		$head .= '<tr class="c-headline">' . PHP_EOL;
		$head .= '<td class="col-icon" nowrap="nowrap" colspan="6">';
		foreach ($this->tableInfo as $tableName => $tableInfo) {
			$onClick = BackendUtility::editOnClick($parameters, '', GeneralUtility::linkThisScript());
			$params = '&edit[' . $tableName . '][' . $this->id . ']=new&defVals[' . $tableName . '][' . $tableInfo['dateColumn'] . ']=' . $timestamp;

			$overlay = IconUtility::getSpriteIcon('extensions--status-overlay-record-new');

			$head .= '<a href="#" onclick="' .
				htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
				'" title="' . $GLOBALS['LANG']->getLL('new', TRUE) . ' (' . $tableInfo['tableTitle'] . ')">' .
				IconUtility::getSpriteIconForRecord($tableName, array(), array(
					'html' => $overlay,
				)) . '</a> &nbsp;';
		}
		$head .= '</td>';
		$head .= '</tr>' . PHP_EOL;

		$cc = 0;

		$this->totalItems = count($rowsTable);
		$head .= $this->renderListNavigation('top'); // TODO test with many records for one day
		$body = '';
		foreach ($rowsTable as $row) {
			$cc++;
			$table = $row['__mod_listview_table'];
			$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
			$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
			$this->fieldArray = array($titleCol, $thumbsCol, $this->tableInfo[$table]['dateColumn']);
			// Control-Panel
			if (!GeneralUtility::inList($rowlist, '_CONTROL_')) {
				$this->fieldArray[] = '_CONTROL_';
				$this->fieldArray[] = '_AFTERCONTROL_';
			}
			// Clipboard
			if ($this->showClipboard) {
				$this->fieldArray[] = '_CLIPBOARD_';
			}
			$body .= $this->renderListRow($table, $row, $cc, $titleCol, $thumbsCol);
		}
		$tail = $this->renderListNavigation('bottom');
		$tail .= '</tbody></table>' . PHP_EOL;

		return $head . $body . $tail;
	}

	protected function initializeTables() {
		foreach($GLOBALS['TCA'] as $table => &$config) {
			if ($this->tableTSconfigOverTCA[$table . '.']['dateColumn']) {
				$dateColumn = $this->tableTSconfigOverTCA[$table . '.']['dateColumn'];
			} else if ($config['ctrl']['_listcal_dateColumn']) {
				$dateColumn = $config['ctrl']['_listcal_dateColumn'];
			} else {
				continue;
			}

			$this->tableInfo[$table] = array(
				'dateColumn' => $dateColumn,
				'tableTitle' => $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']),
			);
			$this->setFields[$table] = array(
				'uid', 'pid', $config['ctrl']['label'], $config['ctrl']['thumbnail'], '_PATH_', '_CONTROL_'
			);
		}
	}

	protected function initializeCalendarView() {
		$this->calendarView = GeneralUtility::makeInstance('TYPO3\\ListCal\\CalendarViews\\MonthView');
		// TODO get arguments
		// Initialize (month/week/...) view:
		$this->viewType = 'month';
		//$this->referenceTime = mktime(0, 0, 0, $month);
		$this->referenceTime = $GLOBALS['EXEC_TIME'];
		#$this->startTimestamp = strtotime("first day of this month 0:0", $this->referenceTime);
		#$this->endTimestamp = strtotime("first day of next month 0:0", $this->referenceTime);

		$this->limitDaysOfWeek = GeneralUtility::intExplode(',', $this->modTSconfig['properties']['limitDaysOfWeek']);
	}

	public function getTable($table, $id, $rowlist) {
		$dateColumn = $this->tableInfo[$table]['dateColumn'];
		if (!$dateColumn) {
			if ($table == 'tt_content') {
				$this->nContent++;
			} else {
				$this->nOtherTables++;
			}
			return;
		}

		$this->tableInfo[$table]['rowlist'] = $rowlist;

		$queryParts = $this->makeQueryArray($table, $id, '', 'COUNT(*) AS count, EXTRACT(YEAR_MONTH FROM FROM_UNIXTIME(' . $dateColumn . ')) as slot');
		$queryParts['GROUPBY'] = 'slot';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);

		while($slotInfo = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
			$this->slots[$slotInfo['slot']]['countByTable'][$table] = $slotInfo['count'];
		}

		$GLOBALS['TYPO3_DB']->sql_free_result($result);
	}

	protected function collectRecords($table, $id, $rowlist) {
		$dateColumn = $this->tableInfo[$table]['dateColumn'];

		$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
		$addWhere = '';
		if ($this->startTimestamp) {
			$addWhere .= 'AND ' . $table . '.' . $dateColumn . ' >= ' . $this->startTimestamp . ' ';
		}
		if ($this->endTimestamp) {
			$addWhere .= 'AND ' . $table . '.' . $dateColumn . ' < ' . $this->endTimestamp . ' ';
		}

		// Creating the list of fields to include in the SQL query:
		$selectFields = $this->fieldArray;
		$selectFields[] = 'uid';
		$selectFields[] = 'pid';
		if ($titleCol) $selectFields[] = $titleCol;
		if ($thumbsCol) $selectFields[] = $thumbsCol;
		$selectFields[] = $dateColumn;
		if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
			$selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['type']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['type'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['typeicon_column']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			$selectFields[] = 't3ver_id';
			$selectFields[] = 't3ver_state';
			$selectFields[] = 't3ver_wsid';
		}
		if ($l10nEnabled) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
			$selectFields = array_merge($selectFields, GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], TRUE));
		}
		// Unique list!
		$selectFields = array_unique($selectFields);

		$selFieldList = implode(',', $selectFields);
		$queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);

		// Finding the total amount of records on the page
		// (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
		$this->setTotalItems($queryParts);

		$this->iLimit = 10;

		if ($this->totalItems) {
			// Fetch records only if not in single table mode or if in multi table mode and not collapsed
			if ($listOnlyInSingleTableMode || !$this->table && $tableCollapsed) {
				$dbCount = $this->totalItems;
			} else {
				// Set the showLimit to the number of records when outputting as CSV
				if ($this->csvOutput) {
					$this->showLimit = $this->totalItems;
					$this->iLimit = max($this->totalItems, 1);	// TODO what about many
				}
				$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
				$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
			}
		}

		if ($dbCount) {
			$this->tableInfo[$table]['count'] = $dbCount;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				$dateinfo = getdate($row[$dateColumn]);
				$row['__mod_listview_table'] = $table;
				$this->slots[$dateinfo['year'] . $dateinfo['mon']][$dateinfo['mday']][$row[$dateColumn]][] = $row;
			}
		}
	}

	/**
	 * Generate the flashmessages for current pid
	 *
	 * @return string HTML content with flashmessages
	 */
	protected function getHeaderFlashMessagesForCurrentPid() {
		$content = '';

		if ($this->nOtherTables == 0 && $this->nContent == 0)
			return;

		// Access to list module
		$moduleLoader = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleLoader');
		$moduleLoader->load($GLOBALS['TBE_MODULES']);
		$modules = $moduleLoader->modules;

		if ($this->nOtherTables && is_array($modules['web']['sub']['list'])) {
			$flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$GLOBALS['LANG']->getLL('goToListModuleMessage') . '<br />' .
				IconUtility::getSpriteIcon('actions-system-list-open') . '<a href="javascript:top.goToModule( \'web_list\',1);">' . $GLOBALS['LANG']->getLL('goToListModule') . '</a>', '', FlashMessage::INFO);
			$content .= $flashMessage->render();
		}

		if ($this->nContent && is_array($modules['web']['sub']['layout'])) {
			$flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$GLOBALS['LANG']->getLL('goToLayoutModuleMessage') . '<br />' .
				IconUtility::getSpriteIcon('actions-page-open') . '<a href="javascript:top.goToModule( \'web_layout\',1);">' . $GLOBALS['LANG']->getLL('goToLayoutModule') . '</a>', '', FlashMessage::INFO);
			$content .= $flashMessage->render();
		}

		return $content;
	}
}
