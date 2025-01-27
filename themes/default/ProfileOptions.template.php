<?php

/**
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

/**
 * We need this template in order to look at ignored boards
 */
function template_ProfileOptions_init()
{
	global $context, $txt;

	theme()->getTemplates()->load('GenericBoards');

	if (!empty($context['menu_item_selected']) && $context['menu_item_selected'] === 'notification')
	{
		loadJavascriptFile('ext/jquery.multiselect.min.js');
		theme()->addInlineJavascript('
			$(\'.select_multiple\').multiselect({\'language_strings\': {\'Select all\': ' . JavascriptEscape($txt['notify_select_all']) . '}});'
			, true);

		loadCSSFile('multiselect.css');
	}
}

/**
 * Template for showing all the buddies of the current user.
 */
function template_editBuddies()
{
	global $context, $txt;

	echo '
	<div id="edit_buddies">
		<h2 class="category_header hdicon i-users">
			', $txt['editBuddies'], '
		</h2>
		<table class="table_grid">
			<tr class="table_head">
				<th scope="col" class="grid20">', $txt['name'], '</th>
				<th scope="col">', $txt['status'], '</th>';

	if ($context['can_send_email'])
	{
		echo '
				<th scope="col">', $txt['email'], '</th>';
	}

	echo '
				<th scope="col">', $txt['profile_contact'], '</th>
				<th scope="col"></th>
			</tr>';

	// If they don't have any buddies don't list them!
	if (empty($context['buddies']))
	{
		echo '
			<tr>
				<td colspan="5" class="centertext">
					<strong>', $txt['no_buddies'], '</strong>
				</td>
			</tr>';
	}

	// Now loop through each buddy showing info on each.
	foreach ($context['buddies'] as $buddy)
	{
		echo '
			<tr>
				<td>', $buddy['link'], '</td>
				<td>
					', template_member_online($buddy), '
				</td>';

		if ($context['can_send_email'])
		{
			echo '
				<td>', template_member_email($buddy), '</td>';
		}

		//  Any custom profile (with icon) fields to show
		$im = array();
		if (!empty($buddy['custom_fields']))
		{

			foreach ($buddy['custom_fields'] as $cpf)
			{
				if ((int) $cpf['placement'] === 1)
				{
					$im[] = $cpf['value'];
				}
			}
		}

		echo '
				<td>' . implode(' | ', $im) . '</td>';

		echo '
				<td class="righttext">
					<a href="', getUrl('action', ['action' => 'profile', 'area' => 'lists', 'sa' => 'buddies', 'u' => $context['id_member'], 'remove' => $buddy['id'], '{session_data}']), '" class="icon i-remove" title="', $txt['buddy_remove'], '"></a>
				</td>
			</tr>';
	}

	echo '
		</table>
	</div>';

	// Add a new buddy?
	echo '
	<form action="', getUrl('action', ['action' => 'profile', 'u' => $context['id_member'], 'area' => 'lists', 'sa' => 'buddies']), '" method="post" accept-charset="UTF-8">
		<div class="add_buddy">
			<h2 class="category_header">', $txt['buddy_add'], '</h2>
			<div class="well">
				<dl class="settings">
					<dt>
						<label for="new_buddy">', $txt['who_member'], '</label>
					</dt>
					<dd>
						<input type="text" name="new_buddy" id="new_buddy" size="30" class="input_text" />
						<input type="submit" value="', $txt['buddy_add_button'], '" />
					</dd>
				</dl>';

	if (!empty($context['token_check']))
	{
		echo '
				<input type="hidden" name="', $context[$context['token_check'] . '_token_var'], '" value="', $context[$context['token_check'] . '_token'], '" />';
	}

	echo '
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			</div>
		</div>
	</form>';

	// Initialize the member suggest object
	theme()->addInlineJavascript('
		isFunctionLoaded("elk_AutoSuggest").then((available) => { 
			if (available) {
				new elk_AutoSuggest({
					sSessionId: elk_session_id,
					sSessionVar: elk_session_var,
					sSuggestId: "new_buddy",
					sControlId: "new_buddy",
					sSearchType: "member",
				});
			}
		});', true);
}

/**
 * Template for showing the ignore list of the current user.
 */
function template_editIgnoreList()
{
	global $context, $txt;

	echo '
	<div id="edit_buddies">
		<h2 class="category_header hdicon i-user">
			', $txt['editIgnoreList'], '
		</h2>
		<table class="table_grid">
			<tr class="table_head">
				<th scope="col" style="width: 20%;">', $txt['name'], '</th>
				<th scope="col">', $txt['status'], '</th>';

	if ($context['can_send_email'])
	{
		echo '
				<th scope="col">', $txt['email'], '</th>';
	}

	echo '
				<th scope="col"></th>
			</tr>';

	// If they don't have anyone on their ignore list, don't list it!
	if (empty($context['ignore_list']))
	{
		echo '
			<tr>
				<td colspan="4" class="centertext">
					<strong>', $txt['no_ignore'], '</strong>
				</td>
			</tr>';
	}

	// Now loop through each buddy showing info on each.
	foreach ($context['ignore_list'] as $member)
	{
		echo '
			<tr>
				<td>', $member['link'], '</td>
				<td>
					', template_member_online($member), '
				</td>';

		if ($context['can_send_email'])
		{
			echo '
				<td>', template_member_email($member), '</td>';
		}

		echo '
				<td class="righttext">
					<a href="', getUrl('profile', ['action' => 'profile', 'u' => $context['id_member'], 'name' => $context['member']['name'], 'area' => 'lists', 'sa' => 'ignore', 'remove' => $member['id'], '{session_data}']), '" class="icon i-remove" title="', $txt['ignore_remove'], '">
					</a>
				</td>
			</tr>';
	}

	echo '
		</table>
	</div>';

	// Add to the ignore list?
	echo '
	<form action="', getUrl('action', ['action' => 'profile', 'u' => $context['id_member'], 'area' => 'lists', 'sa' => 'ignore']) . '" method="post" accept-charset="UTF-8">
		<div class="add_buddy">
			<h2 class="category_header">', $txt['ignore_add'], '</h2>
			<div class="well">
				<dl class="settings">
					<dt>
						<label for="new_ignore">', $txt['who_member'], '</label>
					</dt>
					<dd>
						<input type="text" name="new_ignore" id="new_ignore" size="25" class="input_text" />
						<input type="submit" value="', $txt['ignore_add_button'], '" />
					</dd>
				</dl>';

	if (!empty($context['token_check']))
	{
		echo '
				<input type="hidden" name="', $context[$context['token_check'] . '_token_var'], '" value="', $context[$context['token_check'] . '_token'], '" />';
	}

	echo '
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			</div>
		</div>
	</form>';

	theme()->addInlineJavascript('
		isFunctionLoaded("elk_AutoSuggest").then((available) => { 
			if (available) {
				new elk_AutoSuggest({
					sSessionId: elk_session_id,
					sSessionVar: elk_session_var,
					sSuggestId: "new_ignore",
					sControlId: "new_ignore",
					sSearchType: "member",
					sTextDeleteItem: ' . JavaScriptEscape($txt['autosuggest_delete_item']) . ',
					bItemList: false
				});
			}
		});', true);
}

/**
 * Template for editing profile options.
 *
 * @uses ParseError
 */
function template_edit_options()
{
	global $context, $txt;

	// The main header!
	echo '
		<form action="', (empty($context['profile_custom_submit_url']) ? getUrl('action', ['action' => 'profile', 'area' => $context['menu_item_selected'], 'u' => $context['id_member']]) : $context['profile_custom_submit_url']), '" method="post" accept-charset="UTF-8" name="creator" id="creator" enctype="multipart/form-data" onsubmit="return checkProfileSubmit();">
			<h2 class="category_header hdicon i-user">';

	// Don't say "Profile" if this isn't the profile...
	if (!empty($context['profile_header_text']))
	{
		echo '
				', $context['profile_header_text'];
	}
	else
	{
		echo '
				', $txt['profile'];
	}

	echo '
			</h2>';

	// Have we some description?
	if ($context['page_desc'])
	{
		echo '
			<p class="description">', $context['page_desc'], '</p>';
	}

	echo '
			<div class="content">';

	// Any bits at the start?
	if (!empty($context['profile_prehtml']))
	{
		echo '
				<div>', $context['profile_prehtml'], '</div>';
	}

	// Profile fields, standard and custom
	$lastItem = template_profile_options();
	template_custom_profile_options($lastItem);

	// Any closing HTML?
	if (!empty($context['profile_posthtml']))
	{
		echo '
				<div>', $context['profile_posthtml'], '</div>';
	}

	// Only show the password box if it's actually needed.
	template_profile_save();

	echo '
			</div>
		</form>';

	// Some javascript!
	echo '
		<script>
			function checkProfileSubmit()
			{';

	// If this part requires a password, make sure to give a warning.
	if ($context['require_password'])
	{
		echo '
				// Did you forget to type your password?
				if (document.forms.creator.oldpasswrd.value === "")
				{
					alert("', $txt['required_security_reasons'], '");
					return false;
				}';
	}

	// Any onsubmit javascript?
	if (!empty($context['profile_onsubmit_javascript']))
	{
		echo '
				', $context['profile_onsubmit_javascript'];
	}

	echo '
			}';

	if (!empty($context['load_google_authenticator']))
	{
		echo '
			var secret = document.getElementById("otp_secret").value;

			if (secret)
			{
				var qrcode = new QRCode("qrcode", {
					text: "otpauth://totp/' . $context['forum_name'] . '?secret=" + secret,
					width: 100,
					height: 100,
					colorDark : "#000000",
					colorLight : "#ffffff",
				});
			}

			/**
			* Generate a secret key for Google Authenticator
			*/
			function generateSecret() {
				var text = "",
					possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567",
					qr = document.getElementById("qrcode");

				for (var i = 0; i < 16; i++)
					text += possible.charAt(Math.floor(Math.random() * possible.length));

				document.getElementById("otp_secret").value = text;

				while (qr.firstChild) {
					qr.removeChild(qr.firstChild);
				}

				var qrcode = new QRCode("qrcode", {
					text: "otpauth://totp/' . $context['forum_name'] . '?secret=" + text,
					width: 100,
					height: 100,
					colorDark: "#000000",
					colorLight: "#ffffff",
				});
			}';
	}

	echo '
		</script>';
}

/**
 * All the profile options as defined in profile.subs or via an addon
 */
function template_profile_options()
{
	global $context;

	if (empty($context['profile_fields']))
	{
		return '';
	}

	// Start the big old loop 'of love.
	echo '
				<dl>';

	$lastItem = 'hr';
	foreach ($context['profile_fields'] as $key => $field)
	{
		// We add a little hack to be sure we never get more than one hr in a row!
		if ($lastItem === 'hr' && $field['type'] === 'hr')
		{
			continue;
		}

		$lastItem = $field['type'];
		if ($field['type'] === 'hr')
		{
			echo '
				</dl>
				<hr class="clear" />
				<dl>';
		}
		elseif ($field['type'] === 'callback')
		{
			if (isset($field['callback_func']) && function_exists('template_profile_' . $field['callback_func']))
			{
				$callback_func = 'template_profile_' . $field['callback_func'];
				$callback_func();
			}
		}
		else
		{
			echo '
					<dt>
						<label', empty($field['is_error']) ? '' : ' class="error"', ' for="' . $key . '">', $field['label'], '</label>';

			// Does it have any subtext to show?
			if (!empty($field['subtext']))
			{
				echo '
						<p class="smalltext">', $field['subtext'], '</p>';
			}

			echo '
					</dt>
					<dd>';

			// Want to put something in front of the box?
			if (!empty($field['preinput']))
			{
				echo '
						', $field['preinput'];
			}

			// What type of data are we showing?
			if ($field['type'] === 'label')
			{
				echo '
						', $field['value'];
			}
			// Maybe it's a text box - very likely!
			elseif (in_array($field['type'], array('int', 'float', 'text', 'password')))
			{
				echo '
				
						<input type="', $field['type'] === 'password' ? 'password' : 'text', '" name="', $key, '" id="', $key, '" size="', empty($field['size']) ? 30 : $field['size'], '" value="', $field['value'], '" tabindex="', $context['tabindex']++, '" ', $field['input_attr'], ' class="input_', $field['type'] === 'password' ? 'password' : 'text', '" />';
			}
			// Maybe it's an html5 input
			elseif (in_array($field['type'], array('url', 'search', 'date', 'email', 'color')))
			{
				echo '
						<input type="', $field['type'], '" name="', $key, '" id="', $key, '" size="', empty($field['size']) ? 30 : $field['size'], '" value="', $field['value'], '" ', $field['input_attr'], ' class="input_', $field['type'] == 'password' ? 'password' : 'text', '" />';
			}
			// You "checking" me out? ;)
			elseif ($field['type'] === 'check')
			{
				echo '
				<input type="hidden" name="', $key, '" value="0" /><input type="checkbox" name="', $key, '" id="', $key, '" ', empty($field['value']) ? '' : ' checked="checked"', ' value="1" tabindex="', $context['tabindex']++, '" ', $field['input_attr'], ' />';
			}
			// Always fun - select boxes!
			elseif ($field['type'] === 'select')
			{
				echo '
						<select name="', $key, '" id="', $key, '">';

				if (isset($field['options']))
				{
					// Is this some code to generate the options?
					if (!is_array($field['options']))
					{
						try
						{
							$field['options'] = eval($field['options']);
						}
						catch (ParseError)
						{
							$field['options'] = '';
						}
					}

					// Assuming we now have some!
					if (is_array($field['options']))
					{
						foreach ($field['options'] as $value => $name)
						{
							echo '
							<option value="', $value, '" ', $value == $field['value'] ? 'selected="selected"' : '', '>', $name, '</option>';
						}
					}
				}

				echo '
						</select>';
			}

			// Something to end with?
			if (!empty($field['postinput']))
			{
				echo '
						', $field['postinput'];
			}

			echo '
					</dd>';
		}
	}

	echo '
				</dl>';

	return $lastItem;
}

/**
 * Output any custom profile fields
 *
 * @param string $lastItem
 */
function template_custom_profile_options($lastItem = '')
{
	global $context;

	if (empty($context['custom_fields']))
	{
		return;
	}

	// Are there any custom profile fields - if so print them!
	if ($lastItem !== 'hr')
	{
		echo '
				<hr class="clear" />';
	}

	echo '
				<dl>';

	foreach ($context['custom_fields'] as $field)
	{
		echo '
					<dt>
						<strong>', $field['name'], '</strong><br />
						<span class="smalltext">', $field['desc'], '</span>
					</dt>
					<dd>
						', $field['input_html'], '
					</dd>';
	}

	echo '
			</dl>';
}

/**
 * Personal Message settings.
 */
function template_profile_pm_settings()
{
	global $context, $modSettings, $txt;

	echo '
							<dt>
								<label for="pm_settings">', $txt['pm_display_mode'], '</label>
							</dt>
							<dd>
								<select name="pm_settings" id="pm_settings">
									<option value="0"', $context['display_mode'] == 0 ? ' selected="selected"' : '', '>', $txt['pm_display_mode_all'], '</option>
									<option value="1"', $context['display_mode'] == 1 ? ' selected="selected"' : '', '>', $txt['pm_display_mode_one'], '</option>
									<option value="2"', $context['display_mode'] == 2 ? ' selected="selected"' : '', '>', $txt['pm_display_mode_linked'], '</option>
								</select>
							</dd>
							<dt>
								<label for="view_newest_pm_first">', $txt['recent_pms_at_top'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[view_newest_pm_first]" value="0" />
								<input type="checkbox" name="default_options[view_newest_pm_first]" id="view_newest_pm_first" value="1"', empty($context['member']['options']['view_newest_pm_first']) ? '' : ' checked="checked"', ' />
							</dd>
						</dl>
						<dl>
							<dt>
								<label for="pm_email_notify">', $txt['email_notify'], '</label>
							</dt>
							<dd>
								<select name="pm_email_notify" id="pm_email_notify">
									<option value="0"', empty($context['send_email']) ? ' selected="selected"' : '', '>', $txt['email_notify_never'], '</option>
									<option value="1"', !empty($context['send_email']) && ($context['send_email'] == 1 || (empty($modSettings['enable_buddylist']) && $context['send_email'] > 1)) ? ' selected="selected"' : '', '>', $txt['email_notify_always'], '</option>';

	if (!empty($modSettings['enable_buddylist']))
	{
		echo '
										<option value="2"', !empty($context['send_email']) && $context['send_email'] > 1 ? ' selected="selected"' : '', '>', $txt['email_notify_buddies'], '</option>';
	}

	echo '
								</select>
							</dd>
							<dt>
									<label for="popup_messages">', $txt['popup_messages'], '</label>
							</dt>
							<dd>
									<input type="hidden" name="default_options[popup_messages]" value="0" />
									<input type="checkbox" name="default_options[popup_messages]" id="popup_messages" value="1"', empty($context['member']['options']['popup_messages']) ? '' : ' checked="checked"', ' />
							</dd>
						</dl>
						<dl>
							<dt>
									<label for="pm_remove_inbox_label">', $txt['pm_remove_inbox_label'], '</label>
							</dt>
							<dd>
									<input type="hidden" name="default_options[pm_remove_inbox_label]" value="0" />
									<input type="checkbox" name="default_options[pm_remove_inbox_label]" id="pm_remove_inbox_label" value="1"', empty($context['member']['options']['pm_remove_inbox_label']) ? '' : ' checked="checked"', ' />
							</dd>';
}

/**
 * Template for showing theme settings. Note: template_options() actually adds the theme specific options.
 */
function template_profile_theme_settings()
{
	global $context, $modSettings, $txt;

	echo '
							<dt>
								<label for="use_sidebar_menu">', $txt['use_sidebar_menu'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[use_sidebar_menu]" value="0" />
								<input type="checkbox" name="default_options[use_sidebar_menu]" id="use_sidebar_menu" value="1"', empty($context['member']['options']['use_sidebar_menu']) ? '' : ' checked="checked"', ' />
							</dd>
							<dt>
								<label for="use_click_menu">', $txt['use_click_menu'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[use_click_menu]" value="0" />
								<input type="checkbox" name="default_options[use_click_menu]" id="use_click_menu" value="1"', empty($context['member']['options']['use_click_menu']) ? '' : ' checked="checked"', ' />
							</dd>
							<dt>
								<label for="show_no_avatars">', $txt['show_no_avatars'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[show_no_avatars]" value="0" />
								<input type="checkbox" name="default_options[show_no_avatars]" id="show_no_avatars" value="1"', empty($context['member']['options']['show_no_avatars']) ? '' : ' checked="checked"', ' />
							</dd>
							<dt>
								<label for="show_no_smileys">', $txt['show_no_smileys'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[show_no_smileys]" value="0" />
								<input type="checkbox" name="default_options[show_no_smileys]" id="show_no_smileys" value="1"', empty($context['member']['options']['show_no_smileys']) ? '' : ' checked="checked"', ' />
							</dd>
							<dt>
								<label for="hide_poster_area">', $txt['hide_poster_area'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[hide_poster_area]" value="0" />
								<input type="checkbox" name="default_options[hide_poster_area]" id="hide_poster_area" value="1"', empty($context['member']['options']['hide_poster_area']) ? '' : ' checked="checked"', ' />
							</dd>
							<dt>
								<label for="show_no_signatures">', $txt['show_no_signatures'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[show_no_signatures]" value="0" />
								<input type="checkbox" name="default_options[show_no_signatures]" id="show_no_signatures" value="1"', empty($context['member']['options']['show_no_signatures']) ? '' : ' checked="checked"', ' />
							</dd>';

	if ($context['allow_no_censored'])
	{
		echo '
							<dt>
								<label for="show_no_censored">', $txt['show_no_censored'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[show_no_censored]" value="0" />
								<input type="checkbox" name="default_options[show_no_censored]" id="show_no_censored" value="1"' . (empty($context['member']['options']['show_no_censored']) ? '' : ' checked="checked"') . ' />
							</dd>';
	}

	echo '
							<dt>
								<label for="return_to_post">', $txt['return_to_post'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[return_to_post]" value="0" />
								<input type="checkbox" name="default_options[return_to_post]" id="return_to_post" value="1"', empty($context['member']['options']['return_to_post']) ? '' : ' checked="checked"', ' />
							</dd>
							<dt>
								<label for="no_new_reply_warning">', $txt['no_new_reply_warning'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[no_new_reply_warning]" value="0" />
								<input type="checkbox" name="default_options[no_new_reply_warning]" id="no_new_reply_warning" value="1"', empty($context['member']['options']['no_new_reply_warning']) ? '' : ' checked="checked"', ' />
							</dd>
							<dt>
								<label for="wysiwyg_default">', $txt['wysiwyg_default'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[wysiwyg_default]" value="0" />
								<input type="checkbox" name="default_options[wysiwyg_default]" id="wysiwyg_default" value="1"', empty($context['member']['options']['wysiwyg_default']) ? '' : ' checked="checked"', ' />
							</dd>';

	if (empty($modSettings['disableCustomPerPage']))
	{
		echo '
							<dt>
								<label for="topics_per_page">', $txt['topics_per_page'], '</label>
							</dt>
							<dd>
								<select name="default_options[topics_per_page]" id="topics_per_page">
									<option value="0"', empty($context['member']['options']['topics_per_page']) ? ' selected="selected"' : '', '>', $txt['per_page_default'], ' (', $modSettings['defaultMaxTopics'], ')</option>
									<option value="5"', !empty($context['member']['options']['topics_per_page']) && $context['member']['options']['topics_per_page'] == 5 ? ' selected="selected"' : '', '>5</option>
									<option value="10"', !empty($context['member']['options']['topics_per_page']) && $context['member']['options']['topics_per_page'] == 10 ? ' selected="selected"' : '', '>10</option>
									<option value="25"', !empty($context['member']['options']['topics_per_page']) && $context['member']['options']['topics_per_page'] == 25 ? ' selected="selected"' : '', '>25</option>
									<option value="50"', !empty($context['member']['options']['topics_per_page']) && $context['member']['options']['topics_per_page'] == 50 ? ' selected="selected"' : '', '>50</option>
								</select>
							</dd>
							<dt>
								<label for="messages_per_page">', $txt['messages_per_page'], '</label>
							</dt>
							<dd>
								<select name="default_options[messages_per_page]" id="messages_per_page">
									<option value="0"', empty($context['member']['options']['messages_per_page']) ? ' selected="selected"' : '', '>', $txt['per_page_default'], ' (', $modSettings['defaultMaxMessages'], ')</option>
									<option value="5"', !empty($context['member']['options']['messages_per_page']) && $context['member']['options']['messages_per_page'] == 5 ? ' selected="selected"' : '', '>5</option>
									<option value="10"', !empty($context['member']['options']['messages_per_page']) && $context['member']['options']['messages_per_page'] == 10 ? ' selected="selected"' : '', '>10</option>
									<option value="25"', !empty($context['member']['options']['messages_per_page']) && $context['member']['options']['messages_per_page'] == 25 ? ' selected="selected"' : '', '>25</option>
									<option value="50"', !empty($context['member']['options']['messages_per_page']) && $context['member']['options']['messages_per_page'] == 50 ? ' selected="selected"' : '', '>50</option>
								</select>
							</dd>';
	}

	if (!empty($modSettings['cal_enabled']))
	{
		echo '
							<dt>
								<label for="calendar_start_day">', $txt['calendar_start_day'], '</label>
							</dt>
							<dd>
								<select name="default_options[calendar_start_day]" id="calendar_start_day">
									<option value="0"', empty($context['member']['options']['calendar_start_day']) ? ' selected="selected"' : '', '>', $txt['days'][0], '</option>
									<option value="1"', !empty($context['member']['options']['calendar_start_day']) && $context['member']['options']['calendar_start_day'] == 1 ? ' selected="selected"' : '', '>', $txt['days'][1], '</option>
									<option value="6"', !empty($context['member']['options']['calendar_start_day']) && $context['member']['options']['calendar_start_day'] == 6 ? ' selected="selected"' : '', '>', $txt['days'][6], '</option>
								</select>
								</dd>';
	}

	if (!empty($modSettings['drafts_enabled']) && !empty($modSettings['drafts_autosave_enabled']))
	{
		echo '
							<dt>
								<label for="drafts_autosave_enabled">', $txt['drafts_autosave_enabled'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[drafts_autosave_enabled]" value="0" />
								<label for="drafts_autosave_enabled"><input type="checkbox" name="default_options[drafts_autosave_enabled]" id="drafts_autosave_enabled" value="1"', empty($context['member']['options']['drafts_autosave_enabled']) ? '' : ' checked="checked"', ' /></label>
							</dd>';
	}

	echo '
							<dt>
								<label for="display_quick_reply">', $txt['display_quick_reply'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[display_quick_reply]" value="0" />
								<input type="checkbox" name="default_options[display_quick_reply]" id="display_quick_reply" value="1"', empty($context['member']['options']['display_quick_reply']) ? '' : ' checked="checked"', ' />
							</dd>
							<dt>
								<label for="display_quick_mod">', $txt['display_quick_mod'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="default_options[display_quick_mod]" value="0" />
								<input type="checkbox" name="default_options[display_quick_mod]" id="display_quick_mod" value="1"', empty($context['member']['options']['display_quick_mod']) ? '' : ' checked="checked"', ' />
							</dd>';
}

/**
 * Template for setting up how and what you want to be notified about
 */
function template_action_notification()
{
	global $context, $txt, $modSettings;

	// The main containing header.
	echo '
		<form id="creator" class="flow_hidden" action="', getUrl('action', ['action' => 'profile', 'area' => 'notification']), '" method="post" accept-charset="UTF-8">
			<h2 class="category_header hdicon i-comment">
				', $txt['notifications'], '
			</h2>
			<p class="description">', $txt['notification_settings_info'], '</p>
			<div class="content">
				<dl>
					<dt>
						<label for="notify_from">', $txt['notify_from'], '</label>
						<p class="smalltext">', $txt['notify_from_description'], '</p>
					</dt>
					<dd>
						<select name="notify_from" id="notify_from">
							<option value="0"', $context['member']['notify_from'] == 0 ? ' selected="selected"' : '', '>', $txt['receive_from_everyone'], '</option>
							<option value="1"', $context['member']['notify_from'] == 1 ? ' selected="selected"' : '', '>', $txt['receive_from_ignore'], '</option>
							<option value="2"', $context['member']['notify_from'] == 2 ? ' selected="selected"' : '', '>', $txt['receive_from_buddies'], '</option>
						</select>
					</dd>
				</dl>
				
				<dl>';

	foreach ($context['mention_types'] as $type => $mention_methods)
	{
		if ($type === 'watchedtopic' || $type === 'watchedboard')
		{
			continue;
		}

		echo '
					<dt>
						<label for="notify_', $type, '">', $txt['notify_type_' . $type], '</label>
					</dt>
					<dd>	
						<label for="notify_', $type, '_default">', $txt['notify_method_use_default'], '</label>
						<input id="notify_', $type, '_default" name="', $mention_methods['default_input_name'], '" class="toggle_notify" type="checkbox" value="', $mention_methods['value'], '" ', $mention_methods['value'] ? '' : 'checked="checked"', '/>
						<select class="select_multiple" multiple="multiple" id="notify_', $type, '" name="', $mention_methods['default_input_name'], '[]">';

		foreach ($mention_methods['data'] as $key => $method)
		{
			echo '
							<option value="', $key, '"', $method['enabled'] ? ' selected="selected"' : '', '>', $method['text'], '</option>';
		}

		echo '
						</select>
					</dd>';
	}

	echo '
				</dl>
			</div>
			<h2 class="category_header hdicon i-envelope">
				', $txt['notify_topic_board'], '
			</h2>
			<p class="description">', $txt['notification_info'], '</p>
			<div class="content">
				<dl>';

	// Allow notification on announcements to be disabled?
	if (!empty($modSettings['allow_disableAnnounce']))
	{
		echo '
					<dt>
						<label for="notify_announcements">', $txt['notify_important_email'], '</label>
					</dt>
					<dd>
						<input type="hidden" name="notify_announcements" value="0" />
						<input type="checkbox" id="notify_announcements" name="notify_announcements"', empty($context['member']['notify_announcements']) ? '' : ' checked="checked"', ' />
					</dd>';
	}

	// Auto notification when you reply / start a topic?
	echo '
					<dt>
						<label for="auto_notify">', $txt['auto_notify'], '</label>
					</dt>
					<dd>
						<input type="hidden" name="default_options[auto_notify]" value="0" />
						<input type="checkbox" id="auto_notify" name="default_options[auto_notify]" value="1"', empty($context['member']['options']['auto_notify']) ? '' : ' checked="checked"', ' />
						', (empty($modSettings['maillist_enabled']) ? '' : $txt['auto_notify_pbe_post']), '
					</dd>';

	// Can the body of the post be sent, PBE will ensure it can
	if (empty($modSettings['disallow_sendBody']))
	{
		echo '
					<dt>
						<label for="notify_send_body">', $txt['notify_send_body'], '</label>
					</dt>
					<dd>
						<input type="hidden" name="notify_send_body" value="0" />
						<input type="checkbox" id="notify_send_body" name="notify_send_body"', empty($context['member']['notify_send_body']) ? '' : ' checked="checked"', ' />
						', $txt['notify_send_body_pbe_post'], '
					</dd>';
	}

	// How often do you want to hear from us, instant, daily, weekly?
	echo '
					<dt>
						<label for="notify_regularity">', $txt['notify_regularity'], '</label>
					</dt>
					<dd>
						<select name="notify_regularity" id="notify_regularity">
							<option value="99"', $context['member']['notify_regularity'] == 99 ? ' selected="selected"' : '', '>', $txt['notify_regularity_none'], '</option>
							<option value="4"', $context['member']['notify_regularity'] == 4 ? ' selected="selected"' : '', '>', $txt['notify_regularity_onsite'], '</option>
							<option value="0"', $context['member']['notify_regularity'] == 0 ? ' selected="selected"' : '', '>', $txt['notify_regularity_instant'], '</option>
							<option value="1"', $context['member']['notify_regularity'] == 1 ? ' selected="selected"' : '', '>', $txt['notify_regularity_first_only'], '</option>
							<option value="2"', $context['member']['notify_regularity'] == 2 ? ' selected="selected"' : '', '>', $txt['notify_regularity_daily'], '</option>
							<option value="3"', $context['member']['notify_regularity'] == 3 ? ' selected="selected"' : '', '>', $txt['notify_regularity_weekly'], '</option>
						</select>
					</dd>
					<dt>
						<label for="notify_types">', $txt['notify_send_types'], '</label>
					</dt>
					<dd>
						<select name="notify_types" id="notify_types">';

	// Using the maillist functions, then limit the options, so they make sense
	if (empty($modSettings['maillist_enabled']) || (empty($modSettings['pbe_no_mod_notices'])))
	{
		echo '
							<option value="1"', $context['member']['notify_types'] == 1 ? ' selected="selected"' : '', '>', $txt['notify_send_type_everything'], '</option>
							<option value="2"', $context['member']['notify_types'] == 2 ? ' selected="selected"' : '', '>', $txt['notify_send_type_everything_own'], '</option>';
	}

	echo '
							<option value="3"', $context['member']['notify_types'] == 3 ? ' selected="selected"' : '', '>', $txt['notify_send_type_only_replies' . (empty($modSettings['maillist_enabled']) ? '' : '_pbe')], '</option>
							<option value="4"', $context['member']['notify_types'] == 4 ? ' selected="selected"' : '', '>', $txt['notify_send_type_nothing'], '</option>
						</select>
					</dd>
				</dl>
				
				<div class="submitbutton">
					<input id="notify_submit" name="notify_submit" type="submit" value="', $txt['notify_save'], '" />
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />', empty($context['token_check']) ? '' : '
					<input type="hidden" name="' . $context[$context['token_check'] . '_token_var'] . '" value="' . $context[$context['token_check'] . '_token'] . '" />', '
					<input type="hidden" name="u" value="', $context['id_member'], '" />
					<input type="hidden" name="sa" value="', $context['menu_item_selected'], '" />
					<input type="hidden" name="save" value="save" />
				</div>
			</div>
		</form>';
}

/**
 * Template for showing which boards you have subscribed to
 * and allowing for modification.
 */
function template_board_notification_list()
{
	template_show_list('board_notification_list');
}

/**
 * Template for showing which topics you have subscribed to
 * and allowing for modification.
 */
function template_topic_notification_list()
{
	template_show_list('topic_notification_list');
}

/**
 * Template for choosing group membership.
 */
function template_groupMembership()
{
	global $context, $txt;

	// The main containing header.
	echo '
		<form action="', getUrl('action', ['action' => 'profile', 'area' => 'groupmembership']), '" method="post" accept-charset="UTF-8" name="creator" id="creator">
			<h2 class="category_header hdicon i-user">
				', $txt['profile'], '
			</h2>
			<p class="description">', $txt['groupMembership_info'], '</p>';

	// Do we have an update message?
	if (!empty($context['update_message']))
	{
		echo '
			<div class="successbox">
				', $context['update_message'], '
			</div>';
	}

	// Requesting membership to a group?
	if (!empty($context['group_request']))
	{
		echo '
			<div class="groupmembership">
				<h2 class="category_header">', $txt['request_group_membership'], '</h2>
				<div class="well">
					', $txt['request_group_membership_desc'], ':
					<textarea name="reason" rows="4" style="width: 99%;"></textarea>
					<div class="submitbutton">
						<input type="hidden" name="gid" value="', $context['group_request']['id'], '" />
						<input type="submit" name="req" value="', $txt['submit_request'], '" />
					</div>
				</div>
			</div>';
	}
	else
	{
		echo '
			<table class="table_grid">
				<thead>
					<tr class="table_head">
						<th scope="col" ', $context['can_edit_primary'] ? ' colspan="2"' : '', '>', $txt['current_membergroups'], '</th>
						<th scope="col"></th>
					</tr>
				</thead>
				<tbody>';

		foreach ($context['groups']['member'] as $group)
		{
			echo '
					<tr  id="primdiv_', $group['id'], '">';

			if ($context['can_edit_primary'])
			{
				echo '
						<td>
							<input type="radio" name="primary" id="primary_', $group['id'], '" value="', $group['id'], '" ', $group['is_primary'] ? 'checked="checked" ' : '', $group['can_be_primary'] ? '' : 'disabled="disabled" ', ' />
						</td>';
			}

			echo '
						<td>
							<label for="primary_', $group['id'], '">
								<strong>', (empty($group['color']) ? $group['name'] : '<span style="color: ' . $group['color'] . '">' . $group['name'] . '</span>'), '</strong>', (empty($group['desc']) ? '' : '<br /><span class="smalltext">' . $group['desc'] . '</span>'), '
							</label>
						</td>
						<td class="grid17 righttext">';

			// Can they leave their group?
			if ($group['can_leave'])
			{
				echo '
							<a class="linkbutton" href="' . getUrl('action', ['action' => 'profile', 'save', 'u' => $context['id_member'], 'area' => 'groupmembership', '{session_data}', 'gid' => $group['id'], $context[$context['token_check'] . '_token_var'] => $context[$context['token_check'] . '_token']]), '">' . $txt['leave_group'] . '</a>';
			}

			echo '
						</td>
					</tr>';
		}

		echo '
				</tbody>
			</table>';

		if ($context['can_edit_primary'])
		{
			echo '
			<div class="submitbutton">
				<input type="submit" value="', $txt['make_primary'], '" />
			</div>';
		}

		// Any groups they can join?
		if (!empty($context['groups']['available']))
		{
			echo '
			<br />
			<table class="table_grid">
				<thead>
					<tr class="table_head">
						<th scope="col">
							', $txt['available_groups'], '
						</th>
						<th scope="col"></th>
					</tr>
				</thead>
				<tbody>';

			foreach ($context['groups']['available'] as $group)
			{
				echo '
					<tr>
						<td>
							<strong>', (empty($group['color']) ? $group['name'] : '<span style="color: ' . $group['color'] . '">' . $group['name'] . '</span>'), '</strong>', (empty($group['desc']) ? '' : '<br /><span class="smalltext">' . $group['desc'] . '</span>'), '
						</td>
						<td class="lefttext">';

				if ($group['type'] == 3)
				{
					echo '
							<a class="linkbutton floatright" href="', getUrl('action', ['action' => 'profile', 'save', 'u' => $context['id_member'], 'area' => 'groupmembership', '{session_data}', 'gid' => $group['id'], $context[$context['token_check'] . '_token_var'] => $context[$context['token_check'] . '_token']]), '">', $txt['join_group'], '</a>';
				}
				elseif ($group['type'] == 2 && $group['pending'])
				{
					echo '
							', $txt['approval_pending'];
				}
				elseif ($group['type'] == 2)
				{
					echo '
							<a class="linkbutton floatright" href="', getUrl('action', ['action' => 'profile', 'u' => $context['id_member'], 'area' => 'groupmembership', 'request' => $group['id'], '{session_data}']), '">', $txt['request_group'], '</a>';
				}

// @todo
//				<input type="hidden" name="', $context[$context['token_check'] . '_token_var'], '" value="', $context[$context['token_check'] . '_token'], '" />';

				echo '
						</td>
					</tr>';
			}

			echo '
				</tbody>
			</table>';
		}

		// Javascript for the selector stuff.
		echo '
		<script>
		console.log("bas");
			var prevClass = "",
				prevDiv = "";';

		echo '
		</script>';
	}

	if (!empty($context['token_check']))
	{
		echo '
				<input type="hidden" name="', $context[$context['token_check'] . '_token_var'], '" value="', $context[$context['token_check'] . '_token'], '" />';
	}

	echo '
				<input type="hidden" name="save" value="save" />
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				<input type="hidden" name="u" value="', $context['id_member'], '" />
			</form>';
}

/**
 * Display a list of boards so a user can choose to ignore some
 */
function template_ignoreboards()
{
	global $txt;

	// The main containing header.
	echo '
	<form id="creator" action="', getUrl('action', ['action' => 'profile', 'area' => 'ignoreboards']), '" method="post" accept-charset="UTF-8" name="creator">
		<h2 class="category_header hdicon i-user">
			', $txt['profile'], '
		</h2>
		<p class="description">', $txt['ignoreboards_info'], '</p>
		<div class="content flow_hidden">';

	template_pick_boards('creator', 'ignore_brd', false);

	// Show the standard "Save Settings" profile button.
	template_profile_save();

	echo '
			<input type="hidden" name="save" value="save" />
		</div>
	</form>
	<br />';
}

/**
 * Display a load of drop down selectors for allowing the user to change group.
 */
function template_profile_group_manage()
{
	global $context, $txt;

	echo '
							<dt>
								<label>', $txt['primary_membergroup'], '</label>
								<p class="smalltext">[<a href="', getUrl('action', ['action' => 'quickhelp', 'help' => 'moderator_why_missing']), '" onclick="return reqOverlayDiv(this.href);">', $txt['moderator_why_missing'], '</a>]</p>
							</dt>
							<dd>
								<select name="id_group" ', ($context['user']['is_owner'] && $context['member']['group_id'] == 1 ? 'onchange="if (this.value != 1 &amp;&amp; !confirm(\'' . $txt['deadmin_confirm'] . '\')) this.value = 1;"' : ''), '>';

	// Fill the select box with all primary membergroups that can be assigned to a member.
	foreach ($context['member_groups'] as $member_group)
	{
		if (!empty($member_group['can_be_primary']))
		{
			echo '
									<option value="', $member_group['id'], '"', $member_group['is_primary'] ? ' selected="selected"' : '', '>
										', $member_group['name'], '
									</option>';
		}
	}

	echo '
								</select>
							</dd>
							<dt>
								<label>', $txt['additional_membergroups'], '</label>
							</dt>
							<dd>
								<input type="hidden" name="additional_groups[]" value="0" />
								<fieldset id="additional_groupsList">
									<legend data-collapsed="', count($context['member_groups']) === 0 ? 'true' : 'false', '">', $txt['additional_membergroups_show'], '</legend>
									<ul>';

	// For each membergroup show a checkbox so members can be assigned to more than one group.
	foreach ($context['member_groups'] as $member_group)
	{
		if ($member_group['can_be_additional'])
		{
			echo '
										<li>
											<label for="additional_groups-', $member_group['id'], '"><input type="checkbox" name="additional_groups[]" value="', $member_group['id'], '" id="additional_groups-', $member_group['id'], '"', $member_group['is_additional'] ? ' checked="checked"' : '', ' /> ', $member_group['name'], '</label>
										</li>';
		}
	}

	echo '
									</ul>
								</fieldset>
							</dd>';
}

/**
 * Callback function for entering a birth date!
 */
function template_profile_birthdate()
{
	global $txt, $context;

	// Just show the pretty box!
	echo '
							<dt>
								<label>', $txt['dob'], '</label>
								<p class="smalltext">', $txt['dob_month'], ' - ', $txt['dob_day'], ' - ', $txt['dob_year'], '</p>
							</dt>
							<dd>
								<input type="date" name="bday1" value="', sprintf('%04d-%02d-%02d', $context['member']['birth_date']['year'], $context['member']['birth_date']['month'], $context['member']['birth_date']['day']), '" />
							</dd>';
}

/**
 * Show the signature editing box.
 */
function template_profile_signature_modify()
{
	global $txt, $context;

	echo '
							<dt id="current_signature"', isset($context['member']['current_signature']) ? '' : ' class="hide"', '>
								<label>', $txt['current_signature'], ':</label>
							</dt>
							<dd id="current_signature_display"', isset($context['member']['current_signature']) ? '' : ' class="hide"', '>
								', $context['member']['current_signature'] ?? '', '<hr />
							</dd>

							<dt id="preview_signature"', isset($context['member']['signature_preview']) ? '' : ' class="hide"', '>
								<label>', $txt['signature_preview'], ':</label>
							</dt>
							<dd id="preview_signature_display"', isset($context['member']['signature_preview']) ? '' : ' class="hide"', '>
								', $context['member']['signature_preview'] ?? '', '<hr />
							</dd>
							<dt>
								<label>', $txt['signature'], '</label>
								<p class="smalltext">', $txt['sig_info'], '</p>
							</dt>
							<dd>
								<textarea class="editor" id="signature" name="signature" rows="5" cols="50" style="min-width: 50%; width: 99%;">', $context['member']['signature'], '</textarea>';

	// If there is a limit at all!
	if (!empty($context['signature_limits']['max_length']))
	{
		echo '
								<p class="smalltext">', sprintf($txt['max_sig_characters'], $context['signature_limits']['max_length']), ' <span id="signatureLeft">', $context['signature_limits']['max_length'], '</span></p>';
	}

	if (!empty($context['show_preview_button']))
	{
		echo '
								<input type="submit" name="preview_signature" id="preview_button" value="', $txt['preview_signature'], '"  tabindex="', $context['tabindex']++, '" class="right_submit" />';
	}

	if ($context['signature_warning'])
	{
		echo '
								<span class="smalltext">', $context['signature_warning'], '</span>';
	}

	// Some javascript used to count how many characters have been used so far in the signature.
	echo '
								<script>
									var maxLength = ', $context['signature_limits']['max_length'], ';

									document.getElementById("signature").addEventListener("keyup", function(event) {
									    calcCharLeft(false, event);
									});

									$(function() {
										calcCharLeft(true);
										$("#preview_button").click(function() {
											return ajax_getSignaturePreview(true);
										});
									});
								</script>
							</dd>';
}

/**
 * Interface to select an avatar in profile.
 */
function template_profile_avatar_select()
{
	global $context, $txt, $modSettings;

	// Start with left side menu
	echo '
							<dt>
								<label id="personal_picture">', $txt['personal_picture'], '</label>
								<ul id="avatar_choices">
									<li>
										<input type="radio" onclick="swap_avatar();" name="avatar_choice" id="avatar_choice_none" value="none"' . ($context['member']['avatar']['choice'] == 'none' ? ' checked="checked"' : '') . ' />
										<label for="avatar_choice_none"' . (isset($context['modify_error']['bad_avatar']) ? ' class="error"' : '') . '>
											' . $txt['no_avatar'] . '
										</label>
									</li>', empty($context['member']['avatar']['allow_server_stored']) ? '' : '
									<li>
										<input type="radio" onclick="swap_avatar();" name="avatar_choice" id="avatar_choice_server_stored" value="server_stored"' . ($context['member']['avatar']['choice'] === 'server_stored' ? ' checked="checked"' : '') . ' />
										<label for="avatar_choice_server_stored"' . (isset($context['modify_error']['bad_avatar']) ? ' class="error"' : '') . '>
											' . $txt['choose_avatar_gallery'] . '
										</label>
									</li>', empty($context['member']['avatar']['allow_external']) ? '' : '
									<li>
										<input type="radio" onclick="swap_avatar();" name="avatar_choice" id="avatar_choice_external" value="external"' . ($context['member']['avatar']['choice'] === 'external' ? ' checked="checked"' : '') . ' />
										<label for="avatar_choice_external"' . (isset($context['modify_error']['bad_avatar']) ? ' class="error"' : '') . '>
											' . $txt['my_own_pic'] . '
										</label>
									</li>', empty($context['member']['avatar']['allow_gravatar']) ? '' : '
									<li>
										<input type="radio" onclick="swap_avatar();" name="avatar_choice" id="avatar_choice_gravatar" value="gravatar"' . ($context['member']['avatar']['choice'] === 'gravatar' ? ' checked="checked"' : '') . ' />
										<label for="avatar_choice_gravatar"' . (isset($context['modify_error']['bad_avatar']) ? ' class="error"' : '') . '>
											' . $txt['gravatar'] . '
										</label>
									</li>', empty($context['member']['avatar']['allow_upload']) ? '' : '
									<li>
										<input type="radio" onclick="swap_avatar();" name="avatar_choice" id="avatar_choice_upload" value="upload"' . ($context['member']['avatar']['choice'] === 'upload' ? ' checked="checked"' : '') . ' />
										<label for="avatar_choice_upload"' . (isset($context['modify_error']['bad_avatar']) ? ' class="error"' : '') . '>
											' . $txt['avatar_will_upload'] . '
										</label>
									</li>', '
								</ul>
							</dt>
							<dd>';

	// If users are allowed to choose avatars stored on the server show the selection boxes to choose them.
	if (!empty($context['member']['avatar']['allow_server_stored']))
	{
		echo '
								<div id="avatar_server_stored">
									<div>
										<select name="cat" id="cat" size="10" onchange="changeSel(\'\');">';

		// This lists all the file categories.
		foreach ($context['avatars'] as $avatar)
		{
			echo '
											<option value="', $avatar['filename'] . ($avatar['is_dir'] ? '/' : ''), '"', ($avatar['checked'] ? ' selected="selected"' : ''), '>', $avatar['name'], '</option>';
		}

		echo '
										</select>
									</div>
									<div>
										<select id="file" name="file" size="10" class="hide" onchange="showAvatar()" disabled="disabled">
											<option> </option>
										</select>
									</div>
									<div>
										<img id="avatar" class="avatar avatarresize" src="', $modSettings['avatar_url'] . '/blank.png', '" alt="" />
									</div>
								</div>';
	}

	// If the user can link to an off server avatar, show them a box to input the address.
	if (!empty($context['member']['avatar']['allow_external']))
	{
		echo '
								<div id="avatar_external">
									<div class="smalltext">
										<label for="userpicpersonal">', $txt['avatar_by_url'], '</label>
									</div>
									<input type="url" id="userpicpersonal" name="userpicpersonal" value="', $context['member']['avatar']['external'], '" onchange="previewExternalAvatar(this.value);" class="input_text" placeholder="', $context['member']['avatar']['placeholder'] ?? '', '"/>
									<br /><br />
									<img id="external" src="', $context['member']['avatar']['choice'] === 'external' ? $context['member']['avatar']['external'] : $modSettings['avatar_url'] . '/blank.png', '" alt="" class="avatar avatarresize" />
								</div>';
	}

	// If the user is allowed to use a Gravatar.
	if (!empty($context['member']['avatar']['allow_gravatar']))
	{
		echo '
								<div id="avatar_gravatar">
									<img src="' . $context['member']['avatar']['gravatar_preview'] . '" alt="" />
								</div>';
	}

	// If the user is able to upload avatars to the server show them an upload box.
	if (!empty($context['member']['avatar']['allow_upload']))
	{
		echo '
								<div id="avatar_upload">
									<input type="file" name="attachment" id="avatar_upload_box" class="input_file" accept="image/*" onchange="previewUploadedAvatar(this)"/>
									', ($context['member']['avatar']['id_attach'] > 0 ? '
									<br /><br />
									<img id="current_avatar" class="avatar avatarresize" src="' . $context['member']['avatar']['href'] . (strpos($context['member']['avatar']['href'], '?') === false ? '?' : '&') . 'time=' . time() . '" alt="" />
									<div id="current_avatar_new" class="hide">
										<img id="current_avatar_new_preview" class="avatar avatarresize border_error" style="vertical-align: middle" alt="" src="" />
										<span>' . $txt['preview'] . '</span>
									</div>
									<input type="hidden" name="id_attach" value="' . $context['member']['avatar']['id_attach'] . '" />' : ''), '
								</div>';
	}

	echo '
								<script>
									var files = ["' . implode('", "', $context['avatar_list']) . '"],
										cat = document.getElementById("cat"),
										file = document.getElementById("file"),
										selavatar = "' . $context['avatar_selected'] . '",
										avatardir = "' . $modSettings['avatar_url'] . '/",
										refuse_too_large = ', !empty($modSettings['avatar_action_too_large']) && $modSettings['avatar_action_too_large'] == 'option_refuse' ? 'true' : 'false', ',
										maxHeight = ', empty($modSettings['avatar_max_height']) ? 0 : $modSettings['avatar_max_height'], ',
										maxWidth = ', empty($modSettings['avatar_max_width']) ? 0 : $modSettings['avatar_max_width'], ';

									// Display the right avatar box based on what they are using
									init_avatars();
								</script>
							</dd>';
}

/**
 * Callback for modifying karma.
 */
function template_profile_karma_modify()
{
	global $context, $modSettings, $txt;

	echo '
							<dt>
								<label>', $modSettings['karmaLabel'], '</label>
							</dt>
							<dd>
								<label for="karma_good">', $modSettings['karmaApplaudLabel'], '</label> <input type="text" id="karma_good" name="karma_good" size="4" value="', $context['member']['karma']['good'], '" style="margin-right: 2ex;" class="input_text" />
								<label for="karma_bad">', $modSettings['karmaSmiteLabel'], '</label> <input type="text" id="karma_bad" name="karma_bad" size="4" value="', $context['member']['karma']['bad'], '" class="input_text" /><br />
								(', $txt['total'], ': <span id="karmaTotal">', ($context['member']['karma']['good'] - $context['member']['karma']['bad']), '</span>)
							</dd>';

	echo "
							<script>
							document.addEventListener('DOMContentLoaded', function () {
								let karma_good = document.querySelector('#karma_good'),
									karma_bad = document.querySelector('#karma_bad'),
									karmaTotal = document.querySelector('#karmaTotal');
						
								// Profile options changing karma
								[karma_good, karma_bad].forEach(function (input) {
									input.addEventListener('keyup', function () {
										let good = parseInt(karma_good.value, 10),
											bad = parseInt(karma_bad.value, 10);
										karmaTotal.innerText = (isNaN(good) ? 0 : good) - (isNaN(bad) ? 0 : bad);
									});
								});
							});
							</script>";
}

/**
 * Select the time format!.
 */
function template_profile_timeformat_modify()
{
	global $context, $txt;

	echo '
							<dt>
								<label for="easyformat">', $txt['time_format'], '</label>
								<p>
									<a href="', getUrl('action', ['action' => 'quickhelp', 'help' => 'time_format']), '" onclick="return reqOverlayDiv(this.href);" class="helpicon i-help"><s>', $txt['help'], '</s></a>
									&nbsp;', $txt['date_format'], '
								</p>
							</dt>
							<dd>
								<select name="easyformat" id="easyformat" onchange="document.forms.creator.time_format.value = this.options[this.selectedIndex].value;" style="margin-bottom: 4px;">';

	// Help the user by showing a list of common time formats.
	foreach ($context['easy_timeformats'] as $time_format)
	{
		echo '
									<option value="', $time_format['format'], '"', $time_format['format'] == $context['member']['time_format'] ? ' selected="selected"' : '', '>', $time_format['title'], '</option>';
	}

	echo '
								</select>
								<br />
								<input type="text" name="time_format" id="time_format" value="', $context['member']['time_format'], '" size="30" class="input_text" />
							</dd>';
}

/**
 * Time offset.
 */
function template_profile_timeoffset_modify()
{
	global $txt, $context;

	echo '
							<dt>
								<label', (isset($context['modify_error']['bad_offset']) ? ' class="error"' : ''), ' for="time_offset">', $txt['time_offset'], '</label>
								<p>', $txt['personal_time_offset'], '</p>
							</dt>
							<dd>
								<input type="text" name="time_offset" id="time_offset" size="5" maxlength="5" value="', $context['member']['time_offset'], '" class="input_text" /> ', $txt['hours'], ' <a class="linkbutton" href="javascript:void(0);" onclick="currentDate = new Date(', $context['current_forum_time_js'], '); document.getElementById(\'time_offset\').value = autoDetectTimeOffset(currentDate); return false;">', $txt['timeoffset_autodetect'], '</a><br />', $txt['current_time'], ': <em>', $context['current_forum_time'], '</em>
							</dd>';
}

/**
 * Button to allow the member to pick a theme.
 */
function template_profile_theme_pick()
{
	global $txt, $context;

	echo '
							<dt>
								<label>', $txt['current_theme'], '</label>
							</dt>
							<dd>
								', $context['member']['theme']['name'], ' <a class="linkbutton" href="', getUrl('action', ['action' => 'profile', 'area' => 'pick', 'u' => $context['id_member'], '{session_data}']), '">', $txt['change'], '</a>
							</dd>';
}

/**
 * Interface to allow the member to change the way they login to the forum.
 */
function template_authentication_method()
{
	global $context, $modSettings, $txt;

	// The main header!
	echo '
		<form action="', getUrl('action', ['action' => 'profile', 'area' => 'authentication']), '" method="post" accept-charset="UTF-8" name="creator" id="creator" enctype="multipart/form-data">
			<h2 class="category_header hdicon i-user">
				', $txt['authentication'], '
			</h2>
			<p class="description">', $txt['change_authentication'], '</p>
			<div class="content">
				<dl>
					<dt>
						<input type="radio" name="authenticate" value="passwd" id="auth_pass"', $context['auth_method'] == 'password' ? ' checked="checked"' : '', ' />
						<label for="auth_pass">', $txt['authenticate_password'], '</label>
					</dt>
					<dd>
						<dl id="password1_group">
							<dt>
								<em>', $txt['choose_pass'], ':</em>
							</dt>
							<dd>
								<input type="password" name="passwrd1" id="elk_autov_pwmain" size="30" autocomplete="new-password" tabindex="', $context['tabindex']++, '" class="input_password" placeholder="', $txt['choose_pass'], '" />
								<span id="elk_autov_pwmain_div" class="hide">
									<i id="elk_autov_pwmain_img" class="icon i-warn" alt="*"></i>
								</span>
							</dd>
						</dl>
						<dl id="password2_group">
							<dt>
								<em for="elk_autov_pwverify">', $txt['verify_pass'], ':</em>
							</dt>
							<dd>
								<input type="password" name="passwrd2" id="elk_autov_pwverify" size="30" autocomplete="new-password" tabindex="', $context['tabindex']++, '" class="input_password" placeholder="', $txt['verify_pass'], '" />
								<span id="elk_autov_pwverify_div" class="hide">
									<i id="elk_autov_pwverify_img" class="icon i-warn" alt="*"></i>
								</span>
							</dd>
						</dl>
					</dd>
				</dl>';

	template_profile_save();

	echo '
				<input type="hidden" name="save" value="save" />
			</div>
		</form>';

	// The password stuff.
	echo '
	<script>
		var regTextStrings = {
			"password_short": "', $txt['registration_password_short'], '",
			"password_reserved": "', $txt['registration_password_reserved'], '",
			"password_numbercase": "', $txt['registration_password_numbercase'], '",
			"password_no_match": "', $txt['registration_password_no_match'], '",
			"password_valid": "', $txt['registration_password_valid'], '"
		};
		var verificationHandle = new elkRegister("creator", ', empty($modSettings['password_strength']) ? 0 : $modSettings['password_strength'], ', regTextStrings);

	</script>';
}

/**
 * This template allows for the selection of different themes.
 */
function template_pick()
{
	global $context, $scripturl, $txt;

	echo '
	<div id="pick_theme">
		<form action="', $scripturl, '?action=profile;area=pick;u=', $context['current_member'], ';', $context['session_var'], '=', $context['session_id'], '" method="post" accept-charset="UTF-8">';

	// Just go through each theme and show its information - thumbnail, etc.
	foreach ($context['available_themes'] as $theme)
	{
		echo '
			<h2 class="category_header">
				', $theme['name'], '
			</h2>
			<div class="flow_hidden content">
				<div class="floatright">
					<a href="', $scripturl, '?action=profile;area=pick;u=', $context['current_member'], ';theme=', $theme['id'], ';variant=', $theme['selected_variant'], ';', $context['session_var'], '=', $context['session_id'], '" id="theme_thumb_preview_', $theme['id'], '" title="', $txt['theme_preview'], '">
						<img class="avatar" src="', $theme['thumbnail_href'], '" id="theme_thumb_', $theme['id'], '" alt="" />
					</a>
				</div>
				<p>', $theme['description'], '</p>';

		if (!empty($theme['variants']))
		{
			echo '
				<label for="variant', $theme['id'], '">
					<strong>', $theme['pick_label'], '</strong>
				</label>
				<select id="variant', $theme['id'], '" name="vrt[', $theme['id'], ']" onchange="changeVariant', $theme['id'], '(this.value);">';

			foreach ($theme['variants'] as $key => $variant)
			{
				echo '
					<option value="', $key, '" ', $theme['selected_variant'] == $key ? 'selected="selected"' : '', '>', $variant['label'], '</option>';
			}

			echo '
				</select>
				<noscript>
					<input type="submit" name="save[', $theme['id'], ']" value="', $txt['save'], '" />
				</noscript>';
		}

		echo '
				<br />
				<div class="separator"></div>
				<a class="linkbutton" href="', $scripturl, '?action=profile;area=pick;u=', $context['current_member'], ';th=', $theme['id'], ';', $context['session_var'], '=', $context['session_id'], empty($theme['variants']) ? '' : ';vrt=' . $theme['selected_variant'], '" id="theme_use_', $theme['id'], '">', $txt['theme_set'], '</a>
				<a class="linkbutton" href="', $scripturl, '?action=profile;area=pick;u=', $context['current_member'], ';theme=', $theme['id'], ';', $context['session_var'], '=', $context['session_id'], ';variant=', $theme['selected_variant'], '" id="theme_preview_', $theme['id'], '">', $txt['theme_preview'], '</a>
			</div>';

		if (!empty($theme['variants']))
		{
			echo '
			<script>
				let sBaseUseUrl', $theme['id'], " = elk_prepareScriptUrl(elk_scripturl) + 'action=profile;area=pick;u=", $context['current_member'], ';th=', $theme['id'], ';', $context['session_var'], '=', $context['session_id'], '\',
					sBasePreviewUrl', $theme['id'], " = elk_prepareScriptUrl(elk_scripturl) + 'action=profile;area=pick;u=", $context['current_member'], ';theme=', $theme['id'], ';', $context['session_var'], '=', $context['session_id'], '\',
					oThumbnails', $theme['id'], ' = {';

			// All the variant thumbnails.
			$count = 1;
			foreach ($theme['variants'] as $key => $variant)
			{
				echo '
					\'', $key, "': '", $variant['thumbnail'], "'", (count($theme['variants']) === $count ? '' : ',');

				$count++;
			}

			echo '
				};

				function changeVariant', $theme['id'], '(sVariant)
				{
					document.getElementById(\'theme_thumb_', $theme['id'], "').src = oThumbnails", $theme['id'], '[sVariant];
					document.getElementById(\'theme_use_', $theme['id'], "').href = sBaseUseUrl", $theme['id'] == 0 ? $context['default_theme_id'] : $theme['id'], ' + \';vrt=\' + sVariant;
					document.getElementById(\'theme_thumb_preview_', $theme['id'], "').href = sBasePreviewUrl", $theme['id'], ' + \';variant=\' + sVariant;
					document.getElementById(\'theme_preview_', $theme['id'], "').href = sBasePreviewUrl", $theme['id'], ' + \';variant=\' + sVariant;
				}
			</script>';
		}
	}

	echo '
		</form>
	</div>';
}
