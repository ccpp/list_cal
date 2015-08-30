<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'web_listcal',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'listcal',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/'
	);

	// Register element browser wizard
	#\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
	#	'wizard_element_browser',
	#	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/ElementBrowserWizard/'
	#);
}

t3lib_extMgm::addPageTSConfig('
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

\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(array(
	'status-overlay-record-new' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/status-overlay-record-new.png',
       	$_EXTKEY));
