<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['RecentDiscussionFilter'] = array(
	'Name' => 'Recent Discussion Filter',
	'Description' => 'Lets users hide discussions in Recent Discussions based on a specified criteria.',
	'Version' => '1.0',
	'Author' => "Jason Barnabe",
	'RequiredApplications' => array('Vanilla' => '2.1'),
	'AuthorEmail' => 'jason.barnabe@gmail.com',
	'AuthorUrl' => 'https://github.com/JasonBarnabe/RecentDiscussionFilter',
	'MobileFriendly' => TRUE,
	'License' => "GNU GPL2"
);

require_once dirname(__FILE__).'/config.php';

class RecentDiscussionFilterPlugin extends Gdn_Plugin {

	public $Config = null;

	public function __construct() {
		parent::__construct();
		$this->Config = RecentDiscussionFilterPluginConfig();
	}

	# Add CSS, JS, and link to main site
	public function Base_Render_Before($Sender) {
		$Sender->addCssFile('global.css', 'plugins/RecentDiscussionFilter');
	}

	// Update the query for the filter
	public function DiscussionModel_BeforeGet_Handler($Sender) {
		if ($this->FilterAvailable($Sender) && $this->FilterActive()) {
			$this->Config['ApplyFilter']($Sender->SQL);
		}
	}

	// Update the pager for the filter
	public function DiscussionsController_BeforeBuildPager_Handler($Sender) {
		if ($this->FilterAvailable($Sender)) {
			if ($this->FilterActive()) {
				$DiscussionModel = new DiscussionModel();
				$DiscussionModel->SQL->Select('d.DiscussionID', 'count', 'CountDiscussions')
					->From('Discussion d');
				$this->Config['ApplyFilter']($DiscussionModel->SQL);
				$Row = $DiscussionModel->SQL->Get()->FirstRow();
				$Sender->SetData('CountDiscussions', $Row->CountDiscussions);
			}
			$this->ShowFilterLinks($Sender);
		}
	}

	// Update the user's filter setting
	public function ProfileController_SetRecentDiscussionFilter_Create($Sender, $Args = array()) {
		// Check intent
		if (isset($Args[1]))
			Gdn::Session()->ValidateTransientKey($Args[1]);
		else
			Redirect($_SERVER['HTTP_REFERER']);

		if (isset($Args[0])) {
			if (CheckPermission('Garden.SignIn.Allow')) {
				$this->SetUserMeta(Gdn::Session()->UserID, 'RecentDiscussionFilterActive', $Args[0] == 'true');
			}
		}

		// Back from whence we came
		Redirect($_SERVER['HTTP_REFERER']);
	 }

	// UI to update filter setting
	public function ShowFilterLinks($Sender) {
		// Block guests until guest sessions are restored
		if ($Sender->MasterView == 'admin' || !CheckPermission('Garden.SignIn.Allow')) {
			return;
		}
		$FilterOn = $this->FilterActive();
		$Url = 'profile/setrecentdiscussionfilter/'.($FilterOn ? 'false' : 'true').'/'.Gdn::Session()->TransientKey();
		$Link = Wrap(Anchor(($FilterOn ? $this->Config['TurnOffFilterLabel'] : $this->Config['TurnOnFilterLabel']), $Url), 'span', array('class' => 'RecentDiscussionFilter'));
		$FilterLinks = Wrap($Link, 'div', array('class' => 'RecentDiscussionFilterOptions'));
		$Sender->AddAsset('Content', $FilterLinks, 'RecentDiscussionFilter');
	}

	private function FilterActive() {
		return $this->GetUserMeta(Gdn::Session()->UserID, 'RecentDiscussionFilterActive', false, true);
	}

	private function FilterAvailable($Sender) {
		return $this->OnRecentDiscussions($Sender) && !$this->Config['FilterUnavailableOverride']($Sender);
	}

	private function OnRecentDiscussions($Sender) {
		// i can't find a better way to detect this.
		foreach (debug_backtrace() as $i) {
			#echo $i['class'].':'.$i['function']."\n";
			# we don't want to do this for...
			if ((isset($i['class']) && ($i['class'] == 'BookmarkedModule' // bookmarks in the sidebar
			|| $i['class'] == 'CategoriesController' // category listings
			|| $i['class'] == 'ParticipatedPlugin')) // participated
			|| $i['function'] == 'GetAnnouncements' // announcements
			|| $i['function'] == 'Bookmarked' // bookmarks listings
			|| $i['function'] == 'Mine' // my discussions
			|| $i['function'] == 'ProfileController_Discussions_Create' // profile discussion list
			) {
				return false;
			}
		}
		return true;
	}

}
