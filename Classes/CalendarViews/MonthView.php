<?php
namespace TYPO3\ListCal\CalendarViews;

class MonthView {

	public function increaseSlot($slot, $offset = 1) {
		if (preg_match('/^(\d{4})-?(\d{1,2})$/', $slot, $matches)) {
			$year = $matches[1];
			$month = $matches[2] + $offset;
			if ($month > 12 || $month < 1) {
				$year += floor(($month - 1) / 12);
				$month = (($month - 1) % 12) + 1;
				if ($month <= 0) {
					// PHP's modulo operator returns negative numbers!
					$month += 12;
				}
			}

			return sprintf("%04u%02u", $year, $month);
		}
	}

	public function slotToTimestamp($slot) {
		if (preg_match('/^(\d{4})-?(\d{1,2})$/', $slot, $matches)) {
			$year = $matches[1];
			$month = $matches[2];
			return mktime(12, 0, 0, $month, 1, $year);
		}
	}

	public function slotToText($slot, $short = false) {
		$format = $short ? '%b-%Y' : '%B %Y';
		return strftime($format, $this->slotToTimestamp($slot));
	}

	public function timestampToSlot($timestamp) {
		return date("Ym", $timestamp);
	}
};
