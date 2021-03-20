<?php
namespace CP\ListCal\RecordList;

use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;

class DatabaseRecordCalendarList extends DatabaseRecordList {
	protected $startTimestamp;
	protected $endTimestamp;
	protected $slots;
	protected $nOtherTables;
	protected $nContent;
	protected $tableInfo;
	protected $viewType;
	protected $referenceTime;
	protected $limitDaysOfWeek;

	public function generateList() {
		$this->initializeCalendarView();
		$this->initializeTables();

		$this->slots = array();
		$this->nOtherTables = 0;
		$this->nContent = 0;
		$this->iLimit = 100;

		$GLOBALS['LANG']->includeLLFile('EXT:list_cal/locallang_mod_web_listcal.xlf');

		if (empty($this->tableInfo)) {
			$flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$GLOBALS['LANG']->getLL('noConfiguredTableMessage'), '', FlashMessage::INFO);

			$flashMessageService = GeneralUtility::makeInstance('TYPO3\CMS\Core\Messaging\FlashMessageService');
			$flashMessageService->getMessageQueueByIdentifier()->addMessage($flashMessage);
			return;
		}

		// Prepare tables, generate empty HTML
		parent::generateList();

		if (empty($this->slots)) {
			$this->slots = array(
				date('Y', $this->referenceTime) => array(
					date('m', $this->referenceTime) => array()
				));
		}

		$this->buildCalendar();
	}

	protected function buildCalendar() {
		$iconFactory = GeneralUtility::makeInstance('TYPO3\CMS\Core\Imaging\IconFactory');

		// TODO moduleToken
		$this->HTMLcode .= '<form name="dblistForm" method="post" action="mod.php?M=web_listcal">' . PHP_EOL;
		if (count($this->tableInfo)) {
			$tableNames = array();
			foreach ($this->tableInfo as $tableName => $tableInfo) {
				if (!$tableInfo['count'])
					continue;

				$tableNames[$tableName] =
					$iconFactory->getIconForRecord($tableName, array(), Icon::SIZE_SMALL) .
					$tableInfo['tableTitle'];
			}
			$this->HTMLcode .= '<p>Tables: ' . implode(', ', $tableNames) . '</p>' . PHP_EOL;
		}
		ksort($this->slots);

		foreach ($this->slots as $year => &$rowsByMonth) {
			$this->HTMLcode .= '<h2>' . $year . '</h2>';
			ksort($rowsByMonth);
			$this->HTMLcode .= '<table class="t3-page-columns t3-gridTable"><thead><tr>';
			foreach ($rowsByMonth as $month => &$rowsByDay) {
				$this->HTMLcode .= '<th>' . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . '</th>';
			}
			$this->HTMLcode .= "</tr></thead>";
			$this->HTMLcode .= "<tbody><tr>";
			foreach ($rowsByMonth as $month => &$rowsByDay) {
				$nDays = date('d', mktime(0, 0, 0, $month+1, 0, $year));
				$this->HTMLcode .= "<td>";
				for ($mday = 1; $mday <= $nDays; $mday++) {
					$timestamp = mktime($this->modTSconfig['properties']['newItemHour'], 0, 0, $month, $mday, $year);
					$dayOfWeek = date('w', $timestamp);
					if (!in_array($dayOfWeek, $this->limitDaysOfWeek))
						continue;

					$rowsTable = array();
					if ($rowsByDay[$mday]) {
						ksort($rowsByDay[$mday]);	// order by timestamp
						foreach ($rowsByDay[$mday] as $tstamp => &$rows2) {
							foreach ($rows2 as $row) {
								$rowsTable[$row['uid']] = $row;
							}
						}
					}

					$this->HTMLcode .= $this->renderDayBox($rowsTable, $timestamp, $rowlist);
				}
				$this->HTMLcode .= "</td>";
			}
		}

		$this->HTMLcode .= "</tbody></table>";
		$this->HTMLcode .= '</form>' . PHP_EOL;

		$this->HTMLcode .= $this->getHeaderFlashMessagesForCurrentPid();
	}

	protected function renderDayBox(&$rowsTable, $timestamp, $rowlist) {
		$iconFactory = GeneralUtility::makeInstance('TYPO3\CMS\Core\Imaging\IconFactory');

		// Box wrapper
		$head = '<div class="panel panel-space panel-default recordlist">' . PHP_EOL;

		// Header showing date
		$head .= '<div class="panel-heading">' . PHP_EOL;
		$head .= '<span class="col-icon" nowrap="nowrap"><img src="' . ExtensionManagementUtility::extRelPath('list_cal') . 'ext_icon.gif" /></span>' . PHP_EOL;
		$head .= '<a title="Collapse" data-table="x" data-toggle="collapse" data-target="#recordlist-' . $timestamp . '">x</a>';
		$head .= '<span class="" nowrap="nowrap">' . strftime("%A %B %e", $timestamp) . '</span>' . PHP_EOL;
		$head .= '</div>';

		// Table wrapper
		$head .= '<div id="recordlist-' . $timestamp . '" class="collapse in" data-state="expanded">';
		$head .= '<div class="table-fit">';
		$head .= '<table class="table table-striped table-hover">' . PHP_EOL;

		// 2nd Header with "Add" icon
		$head .= '<thead><tr>' . PHP_EOL;
		$head .= '<td class="col-xxx" nowrap="nowrap" colspan="7">';
		foreach ($this->tableInfo as $tableName => $tableInfo) {
			$onClick = BackendUtility::editOnClick($parameters, '', GeneralUtility::linkThisScript());
			$params = '&edit[' . $tableName . '][' . $this->id . ']=new&defVals[' . $tableName . '][' . $tableInfo['dateColumn'] . ']=' . $timestamp;

			$iconIdentifier = $iconFactory->mapRecordTypeToIconIdentifier($tableName, array());
			$icon = $iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL, 'overlay-new');

			$head .= '<a href="#" onclick="' .
				htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
				'" title="' . $GLOBALS['LANG']->getLL('new', TRUE) . ' (' . $tableInfo['tableTitle'] . ')">' .
				$icon . '</a> &nbsp;';
		}
		$head .= '</td>';
		$head .= '</tr>' . PHP_EOL;
		$head .= '</thead>';
		$head .= '<tbody>';

		// Render actual table inside tbody

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
		$tail .= '</div>' . PHP_EOL;	// panel
		$tail .= '</div>' . PHP_EOL;	// recordlist
		$tail .= '</div>' . PHP_EOL;	// table-fit

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
		// TODO get arguments
		// Initialize (month/week/...) view:
		$this->viewType = 'month';
		//$this->referenceTime = mktime(0, 0, 0, $month);
		$this->referenceTime = $GLOBALS['EXEC_TIME'];
		#$this->startTimestamp = strtotime("first day of this month 0:0", $this->referenceTime);
		#$this->endTimestamp = strtotime("first day of next month 0:0", $this->referenceTime);

		$this->limitDaysOfWeek = GeneralUtility::intExplode(',', $this->modTSconfig['properties']['limitDaysOfWeek']);
	}

	public function getTable($table, $id, $rowlist = '') {
		$dateColumn = $this->tableInfo[$table]['dateColumn'];
		if (!$dateColumn) {
			if ($table == 'tt_content') {
				$this->nContent++;
			} else {
				$this->nOtherTables++;
			}
			return;
		}

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
		if (defined('TYPO3_version') && TYPO3_version < 8)
		{
			$this->setTotalItems($queryParts);
		}
		else
		{
			// TYPO3 8 onwards
			$this->setTotalItems($table, $id, array(/*TODO*/));
		}

		if ($this->totalItems) {
			// Fetch records only if not in single table mode or if in multi table mode and not collapsed
			if ($listOnlyInSingleTableMode || !$this->table && $tableCollapsed) {
				$dbCount = $this->totalItems;
			} else {
				// Set the showLimit to the number of records when outputting as CSV
				if ($this->csvOutput) {
					$this->showLimit = $this->totalItems;
					$this->iLimit = $this->totalItems;
				}
				$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
				$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
			}
		}

		if ($dbCount) {
			$this->tableInfo[$table]['count'] = $dbCount;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				$dateinfo = getdate($row[$dateColumn]);
				// TODO what about week view?
				$row['__mod_listview_table'] = $table;
				$this->slots[$dateinfo['year']][$dateinfo['mon']][$dateinfo['mday']][$row[$dateColumn]][] = $row;
			}
		}
	}

	/**
	 * Generate the flashmessages for current pid
	 *
	 * @return string HTML content with flashmessages
	 */
	protected function getHeaderFlashMessagesForCurrentPid() {
		$iconFactory = GeneralUtility::makeInstance('TYPO3\CMS\Core\Imaging\IconFactory');
		$content = '';

		if ($this->nOtherTables == 0 && $this->nContent == 0)
			return;

		// Access to list module
		$moduleLoader = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleLoader');
		$moduleLoader->load($GLOBALS['TBE_MODULES']);
		$modules = $moduleLoader->modules;

		if ($this->nOtherTables && is_array($modules['web']['sub']['list'])) {
			$content .= '<p>' .
				$GLOBALS['LANG']->getLL('goToListModuleMessage') . '<br />' .
				$iconFactory->getIcon('actions-system-list-open', Icon::SIZE_SMALL) .
				'<a href="javascript:top.goToModule( \'web_list\',1);">' . $GLOBALS['LANG']->getLL('goToListModule') . '</a>';
		}

		if ($this->nContent && is_array($modules['web']['sub']['layout'])) {
			$content .= '<p>' .
				$GLOBALS['LANG']->getLL('goToLayoutModuleMessage') . '<br />' .
				$iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL) .
				'<a href="javascript:top.goToModule( \'web_layout\',1);">' . $GLOBALS['LANG']->getLL('goToLayoutModule') . '</a>';
		}

		return $content;
	}
}
