<?php

/**
 * Handles the access and viewing of a users history
 *
 * @package   ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause (see accompanying LICENSE.txt file)
 *
 * This file contains code covered by:
 * copyright: 2011 Simple Machines (http://www.simplemachines.org)
 *
 * @version 2.0 dev
 *
 */

namespace ElkArte\Profile;

use ElkArte\AbstractController;
use ElkArte\Action;
use ElkArte\Exceptions\Exception;
use ElkArte\Languages\Txt;
use ElkArte\Member;
use ElkArte\MembersList;

/**
 * Show a users login, profile edits, IP history
 */
class ProfileHistory extends AbstractController
{
	/** @var int Member id for the history being viewed */
	private $_memID = 0;

	/** @var Member The \ElkArte\Member object is stored here to avoid some global */
	private $_profile;

	/**
	 * Called before all other methods when coming from the dispatcher or
	 * action class.
	 */
	public function pre_dispatch()
	{
		require_once(SUBSDIR . '/Profile.subs.php');

		$this->_memID = currentMemberID();
		$this->_profile = MembersList::get($this->_memID);
	}

	/**
	 * Profile history entry point.
	 * Re-directs to sub-actions.
	 *
	 * @see AbstractController::action_index()
	 */
	public function action_index()
	{
		global $context, $txt;

		$subActions = [
			'activity' => ['controller' => $this, 'function' => 'action_trackactivity', 'label' => $txt['trackActivity']],
			'ip' => ['controller' => $this, 'function' => 'action_trackip', 'label' => $txt['trackIP']],
			'edits' => ['controller' => $this, 'function' => 'action_trackedits', 'label' => $txt['trackEdits']],
			'logins' => ['controller' => $this, 'function' => 'action_tracklogin', 'label' => $txt['trackLogins']],
		];

		// Create the tabs for the template. (Mostly done by prepareTabData function of Menu)
		$context[$context['profile_menu_name']]['object']->prepareTabData([
			'title' => $txt['history'],
			'description' => $txt['history_description'],
			'class' => 'i-poll',
		]);

		// Set up action/subaction stuff.
		$action = new Action('profile_history');

		// Yep, sub-action time and call integrate_sa_profile_history as well
		$subAction = $action->initialize($subActions, 'activity');
		$context['sub_action'] = $subAction;

		// Set a page title.
		$context['history_area'] = $subAction;
		$context['page_title'] = $txt['trackUser'] . ' - ' . $subActions[$subAction]['label'] . ' - ' . $this->_profile['real_name'];

		// Pass on to the actual method.
		$action->dispatch($subAction);
	}

	/**
	 * Subaction for profile history actions: activity log.
	 */
	public function action_trackactivity()
	{
		global $scripturl, $txt, $modSettings, $context;

		// Verify if the user has sufficient permissions.
		isAllowedTo('moderate_forum');

		$context['last_ip'] = $this->_profile['member_ip'];

		if ($context['last_ip'] !== $this->_profile['member_ip2'])
		{
			$context['last_ip2'] = $this->_profile['member_ip2'];
		}

		$context['member']['name'] = $this->_profile['real_name'];

		// Set the options for the list component.
		$listOptions = [
			'id' => 'track_name_user_list',
			'title' => $txt['errors_by'] . ' ' . $context['member']['name'],
			'items_per_page' => $modSettings['defaultMaxMessages'],
			'no_items_label' => $txt['no_errors_from_user'],
			'base_href' => $scripturl . '?action=profile;area=history;sa=user;u=' . $this->_memID,
			'default_sort_col' => 'date',
			'get_items' => [
				'function' => fn($start, $items_per_page, $sort, $where, $where_vars = []) => $this->list_getUserErrors($start, $items_per_page, $sort, $where, $where_vars),
				'params' => [
					'le.id_member = {int:current_member}',
					['current_member' => $this->_memID],
				],
			],
			'get_count' => [
				'function' => fn($where, $where_vars = []) => $this->list_getUserErrorCount($where, $where_vars),
				'params' => [
					'id_member = {int:current_member}',
					['current_member' => $this->_memID],
				],
			],
			'columns' => [
				'ip_address' => [
					'header' => [
						'value' => $txt['ip_address'],
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=profile;area=history;sa=ip;searchip=%1$s;u=' . $this->_memID . '">%1$s</a>',
							'params' => [
								'ip' => false,
							],
						],
					],
					'sort' => [
						'default' => 'le.ip',
						'reverse' => 'le.ip DESC',
					],
				],
				'message' => [
					'header' => [
						'value' => $txt['message'],
					],
					'data' => [
						'sprintf' => [
							'format' => '%1$s<br /><a href="%2$s">%2$s</a>',
							'params' => [
								'message' => false,
								'url' => false,
							],
						],
					],
				],
				'date' => [
					'header' => [
						'value' => $txt['date'],
					],
					'data' => [
						'db' => 'time',
					],
					'sort' => [
						'default' => 'le.id_error DESC',
						'reverse' => 'le.id_error',
					],
				],
			],
			'additional_rows' => [
				[
					'position' => 'after_title',
					'value' => $txt['errors_desc'],
					'style' => 'padding: 1ex 2ex;',
				],
			],
		];

		// Create the list for viewing.
		createList($listOptions);

		// Get all IP addresses this user has used for his messages.
		$ips = getMembersIPs($this->_memID);
		$context['ips'] = [];
		$context['error_ips'] = [];

		foreach ($ips['message_ips'] as $ip)
		{
			$context['ips'][] = '<a href="' . $scripturl . '?action=profile;area=history;sa=ip;searchip=' . $ip . ';u=' . $this->_memID . '">' . $ip . '</a>';
		}

		foreach ($ips['error_ips'] as $ip)
		{
			$context['error_ips'][] = '<a href="' . $scripturl . '?action=profile;area=history;sa=ip;searchip=' . $ip . ';u=' . $this->_memID . '">' . $ip . '</a>';
		}

		// Find other users that might use the same IP.
		$context['members_in_range'] = [];

		$all_ips = array_unique(array_merge($ips['message_ips'], $ips['error_ips']));
		if (!empty($all_ips))
		{
			$members_in_range = getMembersInRange($all_ips, $this->_memID);
			foreach ($members_in_range as $row)
			{
				$context['members_in_range'][$row['id_member']] = '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>';
			}
		}

		theme()->getTemplates()->load('ProfileHistory');
		$context['sub_template'] = 'trackActivity';
	}

	/**
	 * Get a list of error messages from this ip (range).
	 *
	 * Pass though to getUserErrors for createList in action_trackip() and action_trackactivity()
	 *
	 * @param int $start The item to start with (for pagination purposes)
	 * @param int $items_per_page The number of items to show per page
	 * @param string $sort A string indicating how to sort the results
	 * @param string $where
	 * @param array $where_vars array of values used in the where statement
	 * @return array error messages array
	 */
	public function list_getUserErrors($start, $items_per_page, $sort, $where, $where_vars = [])
	{
		require_once(SUBSDIR . '/ProfileHistory.subs.php');

		return getUserErrors($start, $items_per_page, $sort, $where, $where_vars);
	}

	/**
	 * Get the number of user errors
	 *
	 * Pass though for createList to getUserErrorCount
	 * used in action_trackip() and action_trackactivity()
	 *
	 * @param string $where
	 * @param array $where_vars = array() or values used in the where statement
	 * @return string number of user errors
	 */
	public function list_getUserErrorCount($where, $where_vars = [])
	{
		require_once(SUBSDIR . '/ProfileHistory.subs.php');

		return getUserErrorCount($where, $where_vars);
	}

	/**
	 * Track an IP address.
	 * Accessed through ?action=trackip
	 * and through ?action=profile;area=history;sa=ip
	 */
	public function action_trackip()
	{
		global $scripturl, $txt, $modSettings, $context;

		// Can the user do this?
		isAllowedTo('moderate_forum');

		theme()->getTemplates()->load('Profile');
		theme()->getTemplates()->load('ProfileHistory');
		Txt::load('Profile');

		if ($this->_memID === 0)
		{
			$context['ip'] = $this->user->ip;
			$context['page_title'] = $txt['profile'];
			$context['base_url'] = $scripturl . '?action=trackip';
		}
		else
		{
			$context['ip'] = $this->_profile['member_ip'];
			$context['base_url'] = $scripturl . '?action=profile;area=history;sa=ip;u=' . $this->_memID;
		}

		// Searching?
		if (isset($this->_req->query->searchip))
		{
			$context['ip'] = trim($this->_req->query->searchip);
		}

		if (preg_match('/^\d{1,3}\.(\d{1,3}|\*)\.(\d{1,3}|\*)\.(\d{1,3}|\*)$/', $context['ip']) !== 1
			&& isValidIPv6($context['ip']) === false)
		{
			throw new Exception('invalid_tracking_ip', false);
		}

		$ip_var = str_replace('*', '%', $context['ip']);
		$ip_string = strpos($ip_var, '%') === false ? '= {string:ip_address}' : 'LIKE {string:ip_address}';

		if (empty($context['history_area']))
		{
			$context['page_title'] = $txt['trackIP'] . ' - ' . $context['ip'];
		}

		// Fetch the members that are associated with the ip's
		require_once(SUBSDIR . '/Members.subs.php');
		$context['ips'] = loadMembersIPs($ip_string, $ip_var);

		// Start with the user messages.
		$listOptions = [
			'id' => 'track_message_list',
			'title' => $txt['messages_from_ip'] . ' ' . $context['ip'],
			'start_var_name' => 'messageStart',
			'items_per_page' => $modSettings['defaultMaxMessages'],
			'no_items_label' => $txt['no_messages_from_ip'],
			'base_href' => $context['base_url'] . ';searchip=' . $context['ip'],
			'default_sort_col' => 'date',
			'get_items' => [
				'function' => fn($start, $items_per_page, $sort, $where, $where_vars = []) => $this->list_getIPMessages($start, $items_per_page, $sort, $where, $where_vars),
				'params' => [
					'm.poster_ip ' . $ip_string,
					['ip_address' => $ip_var],
				],
			],
			'get_count' => [
				'function' => fn($where, $where_vars = []) => $this->list_getIPMessageCount($where, $where_vars),
				'params' => [
					'm.poster_ip ' . $ip_string,
					['ip_address' => $ip_var],
				],
			],
			'columns' => [
				'ip_address' => [
					'header' => [
						'value' => $txt['ip_address'],
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $context['base_url'] . ';searchip=%1$s">%1$s</a>',
							'params' => [
								'ip' => false,
							],
						],
					],
					'sort' => [
						'default' => 'm.poster_ip',
						'reverse' => 'm.poster_ip DESC',
					],
				],
				'poster' => [
					'header' => [
						'value' => $txt['poster'],
					],
					'data' => [
						'db' => 'member_link',
					],
				],
				'subject' => [
					'header' => [
						'value' => $txt['subject'],
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?topic=%1$s.msg%2$s#msg%2$s" rel="nofollow">%3$s</a>',
							'params' => [
								'topic' => false,
								'id' => false,
								'subject' => false,
							],
						],
					],
				],
				'date' => [
					'header' => [
						'value' => $txt['date'],
					],
					'data' => [
						'db' => 'time',
					],
					'sort' => [
						'default' => 'm.id_msg DESC',
						'reverse' => 'm.id_msg',
					],
				],
			],
			'additional_rows' => [
				[
					'position' => 'after_title',
					'value' => $txt['messages_from_ip_desc'],
					'style' => 'padding: 1ex 2ex;',
				],
			],
		];

		// Create the messages list.
		createList($listOptions);

		// Set the options for the error lists.
		$listOptions = [
			'id' => 'track_ip_user_list',
			'title' => $txt['errors_from_ip'] . ' ' . $context['ip'],
			'start_var_name' => 'errorStart',
			'items_per_page' => $modSettings['defaultMaxMessages'],
			'no_items_label' => $txt['no_errors_from_ip'],
			'base_href' => $context['base_url'] . ';searchip=' . $context['ip'],
			'default_sort_col' => 'date2',
			'get_items' => [
				'function' => fn($start, $items_per_page, $sort, $where, $where_vars = []) => $this->list_getUserErrors($start, $items_per_page, $sort, $where, $where_vars),
				'params' => [
					'le.ip ' . $ip_string,
					['ip_address' => $ip_var],
				],
			],
			'get_count' => [
				'function' => fn($where, $where_vars = []) => $this->list_getUserErrorCount($where, $where_vars),
				'params' => [
					'ip ' . $ip_string,
					['ip_address' => $ip_var],
				],
			],
			'columns' => [
				'ip_address2' => [
					'header' => [
						'value' => $txt['ip_address'],
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $context['base_url'] . ';searchip=%1$s">%1$s</a>',
							'params' => [
								'ip' => false,
							],
						],
					],
					'sort' => [
						'default' => 'le.ip',
						'reverse' => 'le.ip DESC',
					],
				],
				'display_name' => [
					'header' => [
						'value' => $txt['display_name'],
					],
					'data' => [
						'db' => 'member_link',
					],
				],
				'message' => [
					'header' => [
						'value' => $txt['message'],
					],
					'data' => [
						'sprintf' => [
							'format' => '%1$s<br /><a href="%2$s">%2$s</a>',
							'params' => [
								'message' => false,
								'url' => false,
							],
						],
					],
				],
				'date2' => [
					'header' => [
						'value' => $txt['date'],
					],
					'data' => [
						'db' => 'time',
					],
					'sort' => [
						'default' => 'le.id_error DESC',
						'reverse' => 'le.id_error',
					],
				],
			],
			'additional_rows' => [
				[
					'position' => 'after_title',
					'value' => $txt['errors_from_ip_desc'],
					'style' => 'padding: 1ex 2ex;',
				],
			],
		];

		// Create the error list.
		createList($listOptions);

		$context['single_ip'] = strpos($context['ip'], '*') === false;
		if ($context['single_ip'])
		{
			$context['whois_servers'] = [
				'apnic' => [
					'name' => $txt['whois_apnic'],
					'url' => 'https://wq.apnic.net/apnic-bin/whois.pl?searchtext=' . $context['ip'],
				],
				'arin' => [
					'name' => $txt['whois_arin'],
					'url' => 'https://whois.arin.net/rest/ip/' . $context['ip'],
				],
				'lacnic' => [
					'name' => $txt['whois_lacnic'],
					'url' => 'https://query.milacnic.lacnic.net/search?id=' . $context['ip'],
				],
				'ripe' => [
					'name' => $txt['whois_ripe'],
					'url' => 'https://apps.db.ripe.net/db-web-ui/query?searchtext=' . $context['ip'],
				],
				'iplocation' => [
					'name' => $txt['whois_iplocation'],
					'url' => 'https://www.iplocation.net/?query=' . $context['ip'],
				]
			];

			// Let integration add whois servers easily
			call_integration_hook('integrate_trackip');
		}

		$context['sub_template'] = 'trackIP';
	}

	/**
	 * Fetch a listing of messages made from a given IP
	 *
	 * Pass through to getIPMessages used by createList() in TrackIP()
	 *
	 * @param int $start The item to start with (for pagination purposes)
	 * @param int $items_per_page The number of items to show per page
	 * @param string $sort A string indicating how to sort the results
	 * @param string $where
	 * @param array $where_vars array of values used in the where statement
	 * @return array an array of basic messages / details
	 */
	public function list_getIPMessages($start, $items_per_page, $sort, $where, $where_vars = [])
	{
		require_once(SUBSDIR . '/ProfileHistory.subs.php');

		return getIPMessages($start, $items_per_page, $sort, $where, $where_vars);
	}

	/**
	 * count of messages from a matching IP
	 *
	 * Pass though to getIPMessageCount for createList() in TrackIP()
	 *
	 * @param string $where
	 * @param array $where_vars array of values used in the where statement
	 * @return string count of messages matching the IP
	 */
	public function list_getIPMessageCount($where, $where_vars = [])
	{
		require_once(SUBSDIR . '/ProfileHistory.subs.php');

		return getIPMessageCount($where, $where_vars);
	}

	/**
	 * Tracks the logins of a given user.
	 *
	 * - Accessed by ?action=trackip and ?action=profile;area=history;sa=ip
	 */
	public function action_tracklogin()
	{
		global $scripturl, $txt, $context;

		if ($this->_memID === 0)
		{
			$context['base_url'] = $scripturl . '?action=trackip';
		}
		else
		{
			$context['base_url'] = $scripturl . '?action=profile;area=history;sa=ip;u=' . $this->_memID;
		}

		// Start with the user messages.
		$listOptions = [
			'id' => 'track_logins_list',
			'title' => $txt['trackLogins'],
			'no_items_label' => $txt['trackLogins_none_found'],
			'base_href' => $context['base_url'],
			'get_items' => [
				'function' => fn($start, $items_per_page, $sort, $where, $where_vars = []) => $this->list_getLogins($start, $items_per_page, $sort, $where, $where_vars),
				'params' => [
					'id_member = {int:current_member}',
					['current_member' => $this->_memID],
				],
			],
			'get_count' => [
				'function' => fn($where, $where_vars = []) => $this->list_getLoginCount($where, $where_vars),
				'params' => [
					'id_member = {int:current_member}',
					['current_member' => $this->_memID],
				],
			],
			'columns' => [
				'time' => [
					'header' => [
						'value' => $txt['date'],
					],
					'data' => [
						'db' => 'time',
					],
				],
				'ip' => [
					'header' => [
						'value' => $txt['ip_address'],
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $context['base_url'] . ';searchip=%1$s">%1$s</a> (<a href="' . $context['base_url'] . ';searchip=%2$s">%2$s</a>) ',
							'params' => [
								'ip' => false,
								'ip2' => false
							],
						],
					],
				],
			],
			'additional_rows' => [
				[
					'position' => 'after_title',
					'value' => $txt['trackLogins_desc'],
					'style' => 'padding: 1ex 2ex;',
				],
			],
		];

		// Create the messages list.
		createList($listOptions);

		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'track_logins_list';
	}

	/**
	 * List of login history for a user
	 *
	 * Pass through to getLogins for trackLogins data.
	 *
	 * @param int $start The item to start with (for pagination purposes)
	 * @param int $items_per_page The number of items to show per page
	 * @param string $sort A string indicating how to sort the results
	 * @param string $where
	 * @param array $where_vars array of values used in the where statement
	 *
	 * @return array an array of messages
	 */
	public function list_getLogins($start, $items_per_page, $sort, $where, $where_vars = [])
	{
		require_once(SUBSDIR . '/ProfileHistory.subs.php');

		return getLogins($where, $where_vars);
	}

	/**
	 * Get list of all times this account was logged into
	 *
	 * Pass through to getLoginCount for trackLogins for counting history.
	 * (createList() in TrackLogins())
	 *
	 * @param string $where
	 * @param array $where_vars array of values used in the where statement
	 * @return string count of messages matching the IP
	 */
	public function list_getLoginCount($where, $where_vars = [])
	{
		require_once(SUBSDIR . '/ProfileHistory.subs.php');

		return getLoginCount($where, $where_vars);
	}

	/**
	 * Logs edits to a members profile.
	 */
	public function action_trackedits()
	{
		global $scripturl, $txt, $modSettings, $context;

		// Get the names of any custom fields.
		require_once(SUBSDIR . '/ManageFeatures.subs.php');
		$context['custom_field_titles'] = loadAllCustomFields();

		// Set the options for the error lists.
		$listOptions = [
			'id' => 'edit_list',
			'title' => $txt['trackEdits'],
			'items_per_page' => $modSettings['defaultMaxMessages'],
			'no_items_label' => $txt['trackEdit_no_edits'],
			'base_href' => $scripturl . '?action=profile;area=history;sa=edits;u=' . $this->_memID,
			'default_sort_col' => 'time',
			'get_items' => [
				'function' => fn($start, $items_per_page, $sort) => $this->list_getProfileEdits($start, $items_per_page, $sort),
			],
			'get_count' => [
				'function' => fn() => $this->list_getProfileEditCount(),
			],
			'columns' => [
				'action' => [
					'header' => [
						'value' => $txt['trackEdit_action'],
					],
					'data' => [
						'db' => 'action_text',
					],
				],
				'before' => [
					'header' => [
						'value' => $txt['trackEdit_before'],
					],
					'data' => [
						'db' => 'before',
					],
				],
				'after' => [
					'header' => [
						'value' => $txt['trackEdit_after'],
					],
					'data' => [
						'db' => 'after',
					],
				],
				'time' => [
					'header' => [
						'value' => $txt['date'],
					],
					'data' => [
						'db' => 'time',
					],
					'sort' => [
						'default' => 'id_action DESC',
						'reverse' => 'id_action',
					],
				],
				'applicator' => [
					'header' => [
						'value' => $txt['trackEdit_applicator'],
					],
					'data' => [
						'db' => 'member_link',
					],
				],
			],
		];

		// Create the error list.
		createList($listOptions);

		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'edit_list';
	}

	/**
	 * List of profile edits for display
	 *
	 * Pass through to getProfileEdits function for createList in trackEdits().
	 *
	 * @param int $start The item to start with (for pagination purposes)
	 * @param int $items_per_page The number of items to show per page
	 * @param string $sort A string indicating how to sort the results
	 * @return array array of profile edits
	 */
	public function list_getProfileEdits($start, $items_per_page, $sort)
	{
		require_once(SUBSDIR . '/ProfileHistory.subs.php');

		return getProfileEdits($start, $items_per_page, $sort, $this->_memID);
	}

	/**
	 * How many profile edits
	 *
	 * Pass through to getProfileEditCount.
	 *
	 * @return string number of profile edits
	 */
	public function list_getProfileEditCount()
	{
		require_once(SUBSDIR . '/ProfileHistory.subs.php');

		return getProfileEditCount($this->_memID);
	}
}
