<?php
namespace TYPO3\ListCal\RecordList;

use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

class DatabaseRecordCalendarList extends DatabaseRecordList {
	protected $startTimestamp;
	protected $endTimestamp;
	protected $slots;
	protected $tableInfo;

	public function generateList() {
		// Initialize (month/week/...) view:
		$this->viewType = 'month';
		//$this->referenceTime = mktime(0, 0, 0, $month);
		$this->referenceTime = $GLOBALS['EXEC_TIME'];
		#$this->startTimestamp = strtotime("first day of this month 0:0", $this->referenceTime);
		#$this->endTimestamp = strtotime("first day of next month 0:0", $this->referenceTime);
		$this->slots = array();

		// Prepare tables, generate empty HTML
		$dummyHTML = parent::generateList();

		// TODO moduleToken
		$this->HTMLcode .= '<form name="dblistForm" method="post" action="mod.php?M=web_listcal">' . PHP_EOL;
		if (count($this->tableInfo)) {
			$tableNames = array();
			foreach ($this->tableInfo as $tableName => $tableInfo) {
				$tableNames[$tableName] = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
			}
			$this->HTMLcode .= '<p>Tables: ' . implode($tableNames, ', ') . '</p>' . PHP_EOL;
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
					$timestamp = mktime(9, 0, 0, $month, $mday, $year);
					$this->HTMLcode .= '<table class="typo3-dblist" cellspacing="0" cellpadding="0" border="0"><tbody>' . PHP_EOL;
					$this->HTMLcode .= '<tr class="c-table-row-spacer"></tr>' . PHP_EOL;	// what for?
					$this->HTMLcode .= '<tr class="t3-row-header">' . PHP_EOL;
					$this->HTMLcode .= '<td class="col-icon" nowrap="nowrap"><img src="' . ExtensionManagementUtility::extRelPath('list_cal') . 'mod1/list_cal.gif" /></td>' . PHP_EOL;
					$this->HTMLcode .= '<td class="" nowrap="nowrap" colspan="6">' . strftime("%A %B %e", mktime(0, 0, 0, $month, $mday, $year)) . '</td>' . PHP_EOL;
					$this->HTMLcode .= '</tr>';

					$this->HTMLcode .= '<tr class="c-headline">' . PHP_EOL;
					$this->HTMLcode .= '<td class="col-icon" nowrap="nowrap" colspan="6">';
					foreach ($this->tableInfo as $tableName => $tableInfo) {
						$onClick = BackendUtility::editOnClick($parameters, '', GeneralUtility::linkThisScript());
						$params = '&edit[' . $tableName . '][' . $this->id . ']=new&defVals[' . $tableName . '][' . $tableInfo['dateColumn'] . ']=' . $timestamp;

						$overlay = IconUtility::getSpriteIcon('extensions--status-overlay-record-new');

						$this->HTMLcode .= '<a href="#" onclick="' .
							htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) .
							'" title="' . $GLOBALS['LANG']->getLL('new', TRUE) . ' (' . $tableNames[$tableName] . ')">' .
							IconUtility::getSpriteIconForRecord($tableName, array(), array(
								'html' => $overlay,
							)) . '</a> &nbsp;';
					}
					$this->HTMLcode .= '</td>';
					$this->HTMLcode .= '</tr>' . PHP_EOL;

					$cc = 0;
					$rowsTable = array();
					if ($rowsByDay[$mday]) {
						ksort($rowsByDay[$mday]);	// order by timestamp
						foreach ($rowsByDay[$mday] as $tstamp => &$rows2) {
							foreach ($rows2 as $row) {
								$rowsTable[$row['uid']] = $row;
							}
						}
					}

					$this->totalItems = count($rowsTable);
					$this->HTMLcode .= $this->renderListNavigation('top'); // TODO test with many records for one day
					foreach ($rowsTable as $row) {
						$cc++;
						$table = $row['__mod_listview_table'];
						$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
						$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
						$this->fieldArray = array($titleCol, $thumbsCol, $this->dateColumns[$table]);
						$this->HTMLcode .= $this->renderListRow($table, $row, $cc, $titleCol, $thumbsCol);
					}
					$this->HTMLcode .= $this->renderListNavigation('bottom');
					$this->HTMLcode .= '</tbody></table>' . PHP_EOL;
				}
				$this->HTMLcode .= "</td>";
			}
		}

		$this->HTMLcode .= "</tbody></table>";
		$this->HTMLcode .= '</form>' . PHP_EOL;
	}

	public function getTable($table, $id, $fields) {
		if ($this->tableTSconfigOverTCA[$table . '.']['dateColumn']) {
			$dateColumn = $this->tableTSconfigOverTCA[$table . '.']['dateColumn'];
		} else if ($GLOBALS['TCA'][$tableName]['ctrl']['_listcal_dateColumn']) {
			$dateColumn = $GLOBALS['TCA'][$tableName]['ctrl']['_listcal_dateColumn'];
		} else {
			return;
		}

		$this->dateColumns[$table] = $dateColumn;

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
			$this->tableInfo[$table]['dateColumn'] = $dateColumn;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				$dateinfo = getdate($row[$dateColumn]);
				// TODO what about week view?
				$row['__mod_listview_table'] = $table;
				$this->slots[$dateinfo['year']][$dateinfo['mon']][$dateinfo['mday']][$row[$dateColumn]][] = $row;
			}
		}
	}
}
