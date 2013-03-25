<?php

/**
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 1.0 Alpha
 *
 */

if (!defined('ELKARTE'))
	die('No access...');

/**
 * ManageBBC controller handles administration options for BBC tags.
 */
class ManageBBC_Controller
{
	/**
	 * Administration page in Posts and Topics > BBC.
	 * This method handles displaying and changing which BBC tags are enabled on the forum.
	 *
	 * requires the admin_forum permission.
	 * Accessed from ?action=admin;area=postsettings;sa=bbc.
	 *
	 * @uses Admin template, edit_bbc_settings sub-template.
	 */
	function action_settings()
	{
		global $context, $txt, $modSettings, $helptxt, $scripturl;

		$config_vars = $this->settings();

		// Make sure a nifty javascript will enable/disable checkboxes, according to BBC globally set or not.
		$context['settings_post_javascript'] = '
			toggleBBCDisabled(\'disabledBBC\', ' . (empty($modSettings['enableBBC']) ? 'true' : 'false') . ');';

		call_integration_hook('integrate_modify_bbc_settings', array(&$config_vars));

		// We'll need this forprepareDBSettingContext() and save_db()
		require_once(SUBSDIR . '/Settings.php');

		// Make sure we check the right tags!
		$modSettings['bbc_disabled_disabledBBC'] = empty($modSettings['disabledBBC']) ? array() : explode(',', $modSettings['disabledBBC']);

		// Save page
		if (isset($_GET['save']))
		{
			checkSession();

			// Security: make a pass through all tags and fix them as necessary
			$bbcTags = array();
			foreach (parse_bbc(false) as $tag)
				$bbcTags[] = $tag['tag'];

			if (!isset($_POST['disabledBBC_enabledTags']))
				$_POST['disabledBBC_enabledTags'] = array();
			elseif (!is_array($_POST['disabledBBC_enabledTags']))
				$_POST['disabledBBC_enabledTags'] = array($_POST['disabledBBC_enabledTags']);
			// Work out what is actually disabled!
			$_POST['disabledBBC'] = implode(',', array_diff($bbcTags, $_POST['disabledBBC_enabledTags']));

			// notify add-ons and integrations
			call_integration_hook('integrate_save_bbc_settings', array($bbcTags));

			// save the result
			Settings_Form::save_db($config_vars);

			// and we're out of here!
			redirectexit('action=admin;area=postsettings;sa=bbc');
		}

		// Make sure the template stuff is ready now...
		$context['sub_template'] = 'show_settings';
		$context['page_title'] = $txt['manageposts_bbc_settings_title'];

		$context['post_url'] = $scripturl . '?action=admin;area=postsettings;save;sa=bbc';
		$context['settings_title'] = $txt['manageposts_bbc_settings_title'];

		Settings_Form::prepareDBSettingContext($config_vars);
	}

	/**
	 * Return the BBC settings of the forum.
	 *
	 * @return array
	 */
	function settings()
	{
		$config_vars = array(
				array('check', 'enableBBC'),
				array('check', 'enableBBC', 0, 'onchange' => 'toggleBBCDisabled(\'disabledBBC\', !this.checked);'),
				array('check', 'enablePostHTML'),
				array('check', 'autoLinkUrls'),
			'',
				array('bbc', 'disabledBBC'),
		);

		return $config_vars;
	}
}
