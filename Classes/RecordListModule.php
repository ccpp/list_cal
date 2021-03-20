<?php
namespace CP\ListCal;

class RecordListModule extends \TYPO3\CMS\Recordlist\RecordList
{
	/**
	* The name of the module
	*
	* @var string
	*/
	protected $moduleName = 'web_listcal';

	/**
	 * Render the backend module.
	 * This extends the record recordlist sysext, only for this backend modue.
	 */
	public function main()
	{
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = array(
			'className' => 'CP\\ListCal\\RecordList\\DatabaseRecordCalendarList'
		);
		return parent::main();
	}
}

?>
