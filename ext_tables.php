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
	properties {
		# allowedNewTables = 
	}
	table {
		pages.hideTable = 1
		be_groups.hideTable = 1
		be_users.hideTable = 1
		fe_groups.hideTable = 1
		fe_users.hideTable = 1
		sys_file_storage.hideTable = 1
		sys_filemounts.hideTable = 1
		sys_language = 1
		backend_layout.hideTable = 1
		tx_devlog = 1
		tx_news_domain_model_news {
			dateColumn = datetime
		}
	}
}');
