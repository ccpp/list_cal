<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'listcal',
		'',
		'',
		array (
			'routeTarget' => 'CP\\ListCal\\RecordListModule::mainAction',
			'access' => 'user,group',
			'name' => 'web_listcal',
			'labels' => array (
				'tabs_images' => array ('tab' => 'EXT:list_cal/ext_icon.gif'),
				'll_ref' => 'LLL:EXT:list_cal/locallang_mod_web_listcal.xlf'
			)
		)
	);

	// Register element browser wizard
	#\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
	#	'wizard_element_browser',
	#	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/ElementBrowserWizard/'
	#);

	// Override tx_news hook into TCEforms
	// (Do not call the tx_news hook if 'datetime' is already set using defVars)
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['GeorgRinger\\News\\Hooks\\FormEngine'] = array(
		 'className' => 'CP\\ListCal\\Xclass\\NewsFormEngineOverride'
	 );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
mod.web_listcal < mod.web_list
mod.web_listcal {
	# noCreateRecordsLink = 1
	# allowedNewTables = 
	newItemHour = 9
	limitDaysOfWeek = 0,1,2,3,4,5,6
	table {
		pages.hideTable = 1
		tx_news_domain_model_news {
			dateColumn = datetime
		}
		tt_news {
			dateColumn = datetime
		}
	}
}');
