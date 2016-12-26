<?php if (!defined('APPLICATION')) exit();

# Sample configuration file. Copy/rename to config.php and customize.
function RecentDiscussionFilterPluginConfig() {
	$Config = [];

	// Text for link to turn off the filter
	$Config['TurnOnFilterLabel'] = T('Show discussions ending in 0');

	// Text for link to turn on the filter
	$Config['TurnOffFilterLabel'] = T('Show all discussions');

	// Modify the passed query object to apply the filter.
	$Config['ApplyFilter'] = function($SQL) {
		$SQL->Where('MOD(d.DiscussionID, 10)', 0);
	};

	// Return true when the filter cannot be used.
	$Config['FilterUnavailableOverride'] = function($Sender) {
		return $_REQUEST['override'] == '1';
	};

	return $Config;
}
