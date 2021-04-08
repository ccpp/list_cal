<?php
namespace CP\ListCal\Controller;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class CalendarModuleController extends \TYPO3\CMS\Recordlist\Controller\RecordListController
{
	/**
	* The name of the module
	*
	* @var string
	*/
	protected $moduleName = 'web_listcal';

	/**
	 * Initialize function menu array
	 * Only called in TYPO3 v9
	 */
	protected function menuConfig()
	{
		// MENU-ITEMS:
		$this->MOD_MENU = [
			'bigControlPanel' => '',
			'clipBoard' => '',
		];
		// Loading module configuration:
		$this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_listcal.'] ?? [];
		// Clean up settings:
		$this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), 'web_listcal');
	}

	/**
	 * Initialize the module
	 */
	protected function init(ServerRequestInterface $request = null): void
	{
		parent::init($request);

		if ($request)
		{
			// TYPO3 10 onwards
			$parsedBody = $request->getParsedBody();

			// Override module name
			// Loading module configuration:
			$this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_listcal.'] ?? [];
			// Clean up settings:
			$this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, (array)($parsedBody['SET'] ?? $queryParams['SET'] ?? []), 'web_listcal');
		}
	}

	/**
	 * Render the backend module.
	 * This extends the record recordlist sysext, only for this backend modue.
	 */
	public function main(ServerRequestInterface $request = null): string
	{
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = array(
			'className' => 'CP\\ListCal\\RecordList\\DatabaseRecordCalendarList'
		);
		$result = parent::main($request);
		return $result ? $result : '';
	}
}

?>
