<?php
namespace CP\ListCal\Xclass;

class NewsFormEngineOverride extends \GeorgRinger\News\Hooks\FormEngine {

	public function getSingleField_preProcess($table, $field, array &$row) {
		// Do not call the hook if 'datetime' is already set using defVars
		if ($row['datetime'])
			return;

		parent::getSingleField_preProcess($table, $field, $row);
	}

}
