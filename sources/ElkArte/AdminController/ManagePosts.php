<?php

/**
 * Handles all the administration settings for topics and posts.
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

namespace ElkArte\AdminController;

use ElkArte\AbstractController;
use ElkArte\Action;
use ElkArte\Exceptions\Exception;
use ElkArte\SettingsForm\SettingsForm;

/**
 * ManagePosts controller handles all the administration settings for topics and posts.
 *
 * @package Posts
 */
class ManagePosts extends AbstractController
{
	/**
	 * The main entrance point for the 'Posts and topics' screen.
	 *
	 * What it does:
	 *
	 * - Like all others, it checks permissions, then forwards to the right function
	 * based on the given sub-action.
	 * - Defaults to sub-action 'posts'.
	 * - Accessed from ?action=admin;area=postsettings.
	 * - Requires (and checks for) the admin_forum permission.
	 *
	 * @event integrate_sa_manage_posts used to add new subactions
	 * @see AbstractController::action_index()
	 */
	public function action_index()
	{
		global $context, $txt;

		$subActions = array(
			'posts' => array(
				$this, 'action_postSettings_display', 'permission' => 'admin_forum'),
			'censor' => array(
				$this, 'action_censor', 'permission' => 'admin_forum'),
			'topics' => array(
				'function' => 'action_index',
				'controller' => ManageTopics::class,
				'permission' => 'admin_forum'),
		);

		// Good old action handle
		$action = new Action('manage_posts');

		// Default the sub-action to 'posts'. call integrate_sa_manage_posts
		$subAction = $action->initialize($subActions, 'posts');

		// Just for the template
		$context['page_title'] = $txt['manageposts_title'];
		$context['sub_action'] = $subAction;

		// Tabs for browsing the different post functions.
		$context[$context['admin_menu_name']]['object']->prepareTabData([
			'title' => 'manageposts_title',
			'help' => 'posts_and_topics',
			'description' => 'manageposts_description',
			'tabs' => [
				'posts' => [
					'description' => $txt['manageposts_settings_description'],
				],
				'censor' => [
					'description' => $txt['admin_censored_desc'],
				],
				'topics' => [
					'description' => $txt['manageposts_topic_settings_description'],
				],
			]]
		);

		// Call the right function for this sub-action.
		$action->dispatch($subAction);
	}

	/**
	 * Shows an interface to set and test censored words.
	 *
	 * - It uses the censor_vulgar, censor_proper, censorWholeWord, and
	 * censorIgnoreCase settings.
	 * - Requires the admin_forum permission.
	 * - Accessed from ?action=admin;area=postsettings;sa=censor.
	 *
	 * @event integrate_save_censors
	 * @event integrate_censors
	 * @uses the Admin template and the edit_censored sub template.
	 */
	public function action_censor()
	{
		global $txt, $modSettings, $context;

		if (!empty($this->_req->post->save_censor))
		{
			// Make sure censoring is something they can do.
			checkSession();
			validateToken('admin-censor');

			$censored_vulgar = array();
			$censored_proper = array();

			// Rip it apart, then split it into two arrays.
			if (isset($this->_req->post->censortext))
			{
				$this->_req->post->censortext = explode("\n", strtr($this->_req->post->censortext, array("\r" => '')));

				foreach ($this->_req->post->censortext as $c)
				{
					[$censored_vulgar[], $censored_proper[]] = array_pad(explode('=', trim($c)), 2, '');
				}
			}
			elseif (isset($this->_req->post->censor_vulgar, $this->_req->post->censor_proper))
			{
				if (is_array($this->_req->post->censor_vulgar))
				{
					foreach ($this->_req->post->censor_vulgar as $i => $value)
					{
						if (trim(strtr($value, '*', ' ')) === '')
						{
							unset($this->_req->post->censor_vulgar[$i], $this->_req->post->censor_proper[$i]);
						}
					}

					$censored_vulgar = $this->_req->post->censor_vulgar;
					$censored_proper = $this->_req->post->censor_proper;
				}
				else
				{
					$censored_vulgar = explode("\n", strtr($this->_req->post->censor_vulgar, array("\r" => '')));
					$censored_proper = explode("\n", strtr($this->_req->post->censor_proper, array("\r" => '')));
				}
			}

			// Set the new arrays and settings in the database.
			$updates = array(
				'censor_vulgar' => implode("\n", $censored_vulgar),
				'censor_proper' => implode("\n", $censored_proper),
				'censorWholeWord' => empty($this->_req->post->censorWholeWord) ? '0' : '1',
				'censorIgnoreCase' => empty($this->_req->post->censorIgnoreCase) ? '0' : '1',
				'allow_no_censored' => empty($this->_req->post->allow_no_censored) ? '0' : '1',
			);

			call_integration_hook('integrate_save_censors', array(&$updates));

			updateSettings($updates);
		}

		// Testing a word to see how it will be censored?
		$pre_censor = '';
		if (isset($this->_req->post->censortest))
		{
			require_once(SUBSDIR . '/Post.subs.php');
			$censorText = htmlspecialchars($this->_req->post->censortest, ENT_QUOTES, 'UTF-8');
			preparsecode($censorText);
			$pre_censor = $censorText;
			$context['censor_test'] = strtr(censor($censorText), array('"' => '&quot;'));
		}

		// Set everything up for the template to do its thang.
		$censor_vulgar = explode("\n", $modSettings['censor_vulgar']);
		$censor_proper = explode("\n", $modSettings['censor_proper']);

		$context['censored_words'] = array();
		foreach ($censor_vulgar as $i => $censor_vulgar_i)
		{
			if ($censor_vulgar_i === '' || $censor_vulgar_i === '0')
			{
				continue;
			}

			// Skip it, it's either spaces or stars only.
			if (trim(strtr($censor_vulgar_i, '*', ' ')) === '')
			{
				continue;
			}

			$context['censored_words'][htmlspecialchars(trim($censor_vulgar_i))] = isset($censor_proper[$i])
				? htmlspecialchars($censor_proper[$i], ENT_COMPAT, 'UTF-8')
				: '';
		}

		call_integration_hook('integrate_censors');
		createToken('admin-censor');

		// Using ajax?
		if (isset($this->_req->post->censortest) && $this->getApi() === 'json')
		{
			// Clear the templates
			setJsonTemplate();

			// Send back a response
			$context['json_data'] = array(
				'result' => true,
				'censor' => $pre_censor . ' <i class="icon i-chevron-circle-right"></i> ' . $context['censor_test'],
				'token_val' => $context['admin-censor_token_var'],
				'token' => $context['admin-censor_token'],
			);
		}
		else
		{
			$context['sub_template'] = 'edit_censored';
			$context['page_title'] = $txt['admin_censored_words'];
		}
	}

	/**
	 * Modify any setting related to posts and posting.
	 *
	 * - Requires the admin_forum permission.
	 * - Accessed from ?action=admin;area=postsettings;sa=posts.
	 *
	 * @event integrate_save_post_settings
	 * @uses Admin template, edit_post_settings sub-template.
	 */
	public function action_postSettings_display()
	{
		global $context, $txt, $modSettings;

		// Initialize the form
		$settingsForm = new SettingsForm(SettingsForm::DB_ADAPTER);

		if (!empty($modSettings['nofollow_allowlist']))
		{
			$modSettings['nofollow_allowlist'] = implode("\n", (array) json_decode($modSettings['nofollow_allowlist'], true));
		}

		// Initialize it with our settings
		$settingsForm->setConfigVars($this->_settings());

		// Setup the template.
		$context['page_title'] = $txt['manageposts_settings'];
		$context['sub_template'] = 'show_settings';

		// Are we saving them - are we??
		if (isset($this->_req->query->save))
		{
			checkSession();
			$db = database();

			// If we're changing the message length (and we are using MySQL) let's check the column is big enough.
			if (isset($this->_req->post->max_messageLength) && $this->_req->post->max_messageLength != $modSettings['max_messageLength'] && $db->supportMediumtext())
			{
				require_once(SUBSDIR . '/Maintenance.subs.php');
				$colData = getMessageTableColumns();
				foreach ($colData as $column)
				{
					if ($column['name'] === 'body')
					{
						$body_type = $column['type'];
					}
				}

				if (isset($body_type) && ($this->_req->post->max_messageLength > 65535 || $this->_req->post->max_messageLength == 0) && $body_type === 'text')
				{
					throw new Exception('convert_to_mediumtext', false, array(getUrl('admin', ['action' => 'admin', 'area' => 'maintain', 'sa' => 'database'])));
				}
			}

			// If we're changing the post preview length let's check its valid
			if (!empty($this->_req->post->preview_characters))
			{
				$this->_req->post->preview_characters = (int) min(max(0, $this->_req->post->preview_characters), 512);
			}

			// Set a min quote length of 3 lines of text (@ default font size)
			if (!empty($this->_req->post->heightBeforeShowMore))
			{
				$this->_req->post->heightBeforeShowMore = max((int) $this->_req->post->heightBeforeShowMore, 155);
			}

			$allowList = array_unique(explode("\n", $this->_req->post['nofollow_allowlist']));
			$allowList = array_filter(array_map('\ElkArte\Helper\Util::htmlspecialchars', array_map('trim', $allowList)));
			$this->_req->post['nofollow_allowlist'] = json_encode($allowList);

			call_integration_hook('integrate_save_post_settings');

			$settingsForm->setConfigValues((array) $this->_req->post);
			$settingsForm->save();
			redirectexit('action=admin;area=postsettings;sa=posts');
		}

		// Final settings...
		$context['post_url'] = getUrl('admin', ['action' => 'admin', 'area' => 'postsettings', 'save', 'sa' => 'posts']);
		$context['settings_title'] = $txt['manageposts_settings'];

		// Prepare the settings...
		$settingsForm->prepare();
	}

	/**
	 * Return admin configuration settings for posts.
	 *
	 * @event integrate_modify_post_settings
	 */
	private function _settings()
	{
		global $txt;

		// Initialize it with our settings
		$config_vars = [
			// Quote options...
			['int', 'removeNestedQuotes', 'postinput' => $txt['zero_for_none']],
			['int', 'heightBeforeShowMore', 'postinput' => $txt['zero_to_disable']],
			'',
			// Video options
			['check', 'enableVideoEmbeding'],
			['int', 'video_embed_limit', 'postinput' => $txt['video_embed_limit_note']],
			'',
			// Posting limits...
			['int', 'max_messageLength', 'subtext' => $txt['max_messageLength_zero'], 'postinput' => $txt['manageposts_characters']],
			['int', 'topicSummaryPosts', 'postinput' => $txt['manageposts_posts']],
			'',
			// Posting time limits...
			['int', 'spamWaitTime', 'postinput' => $txt['manageposts_seconds']],
			['int', 'edit_wait_time', 'postinput' => $txt['manageposts_seconds']],
			['int', 'edit_disable_time', 'subtext' => $txt['edit_disable_time_zero'], 'postinput' => $txt['manageposts_minutes']],
			['check', 'show_modify'],
			'',
			['check', 'show_user_images'],
			['check', 'hide_post_group'],
			'',
			// First & Last message preview lengths
			['select', 'message_index_preview', [$txt['message_index_preview_off'], $txt['message_index_preview_first'], $txt['message_index_preview_last']]],
			['int', 'preview_characters', 'subtext' => $txt['preview_characters_zero'], 'postinput' => $txt['preview_characters_units']],
			// Misc
			['title', 'mods_cat_modifications_misc'],
			['check', 'enableCodePrettify'],
			['check', 'autoLinkUrls'],
			['large_text', 'nofollow_allowlist', 'subtext' => $txt['nofollow_allowlist_desc']],
			['check', 'enablePostHTML'],
			['check', 'enablePostMarkdown'],
		];

		// Add new settings with a nice hook, makes them available for admin settings search as well
		call_integration_hook('integrate_modify_post_settings', array(&$config_vars));

		return $config_vars;
	}

	/**
	 * Return the post settings for use in admin search
	 */
	public function settings_search()
	{
		return $this->_settings();
	}
}
