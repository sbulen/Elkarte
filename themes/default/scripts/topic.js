/*!
 * @package   ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause (see accompanying LICENSE.txt file)
 *
 * This file contains code covered by:
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 *
 * @version 2.0 dev
 */

/**
 * This file contains javascript associated with the topic viewing including
 * Quick Modify, Quick Reply, In Topic Moderation, thumbnail expansion etc
 */

/**
 * *** QuickModifyTopic object.
 * Used to quick edit a topic subject by double-clicking next to the subject name
 * in a topic listing
 *
 * @param {object} oOptions
 */
function QuickModifyTopic (oOptions)
{
	this.opt = oOptions;
	this.aHidePrefixes = this.opt.aHidePrefixes;
	this.iCurTopicId = 0;
	this.sCurMessageId = '';
	this.sBuffSubject = '';
	this.oSavetipElem = false;
	this.oCurSubjectDiv = null;
	this.oTopicModHandle = document;
	this.bInEditMode = false;
	this.bMouseOnDiv = false;
	this.init();
}

// Used to initialise the object event handlers
QuickModifyTopic.prototype.init = function() {
	// Detect and act on keypress
	this.oTopicModHandle.onkeydown = this.modify_topic_keypress.bind(this);

	// Used to detect when we've stopped editing.
	this.oTopicModHandle.onclick = this.modify_topic_click.bind(this);
};

// called from the double click in the div
QuickModifyTopic.prototype.modify_topic = function(topic_id, first_msg_id) {
	// already editing
	if (this.bInEditMode)
	{
		// Same message then just return, otherwise drop out of this edit.
		if (this.iCurTopicId === topic_id)
		{
			return;
		}

		this.modify_topic_cancel();
	}

	this.bInEditMode = true;
	this.bMouseOnDiv = true;
	this.iCurTopicId = topic_id;

	// Get the topics current subject
	ajax_indicator(true);
	sendXMLDocument.call(this, elk_prepareScriptUrl(elk_scripturl) + 'action=quotefast;quote=' + first_msg_id + ';modify;api=xml', '', this.onDocReceived_modify_topic);
};

// Callback function from the modify_topic ajax call
QuickModifyTopic.prototype.onDocReceived_modify_topic = function(XMLDoc) {
	// If it is not valid then clean up
	if (!XMLDoc || !XMLDoc.getElementsByTagName('message'))
	{
		this.modify_topic_cancel();
		ajax_indicator(false);
		return true;
	}

	this.sCurMessageId = XMLDoc.getElementsByTagName('message')[0].getAttribute('id');
	this.oCurSubjectDiv = document.getElementById('msg_' + this.sCurMessageId.substring(4));
	this.sBuffSubject = this.oCurSubjectDiv.innerHTML;

	// Hide the tooltip text, don't want them for this element during the edit
	if (typeof SiteTooltip === 'function')
	{
		this.oSavetipElem = this.oCurSubjectDiv.parentElement;
		if (this.oSavetipElem)
		{
			this.sSavetip = this.oSavetipElem.dataset.title;
			this.oSavetipElem.dataset.title = '';
		}
	}

	// Here we hide any other things they want hidden on edit.
	this.set_hidden_topic_areas('none');

	// Show we are in edit mode and allow the edit
	ajax_indicator(false);
	this.modify_topic_show_edit(XMLDoc.getElementsByTagName('subject')[0].childNodes[0].nodeValue);
};

// Cancel out of an edit and return things to back to what they were
QuickModifyTopic.prototype.modify_topic_cancel = function() {
	this.oCurSubjectDiv.innerHTML = this.sBuffSubject;
	this.set_hidden_topic_areas('');
	this.bInEditMode = false;

	// Put back the hover text
	if (this.oSavetipElem)
	{
		this.oSavetipElem.dataset.title = this.sSavetip;
	}

	return false;
};

// Simply restore/show any hidden bits during topic editing.
QuickModifyTopic.prototype.set_hidden_topic_areas = function(set_style) {
	for (let i = 0; i < this.aHidePrefixes.length; i++)
	{
		if (document.getElementById(this.aHidePrefixes[i] + this.sCurMessageId.substring(4)) !== null)
		{
			document.getElementById(this.aHidePrefixes[i] + this.sCurMessageId.substring(4)).style.display = set_style;
		}
	}
};

// For templating, shown that an inline edit is being made.
QuickModifyTopic.prototype.modify_topic_show_edit = function(subject) {
	// Just template the subject.
	this.oCurSubjectDiv.innerHTML = '<input type="text" name="subject" value="' + subject + '" size="60" style="width: 95%;" maxlength="80" class="input_text" autocomplete="off" /><input type="hidden" name="topic" value="' + this.iCurTopicId + '" /><input type="hidden" name="msg" value="' + this.sCurMessageId.substring(4) + '" />';

	// Attach mouse over and out events to this new div
	this.oCurSubjectDiv.onmouseout = this.modify_topic_mouseout.bind(this);
	this.oCurSubjectDiv.onmouseover = this.modify_topic_mouseover.bind(this);
};

// Yup that's right, save it
QuickModifyTopic.prototype.modify_topic_save = function(cur_session_id, cur_session_var) {
	if (!this.bInEditMode)
	{
		return true;
	}

	// Send in the call to save the updated topic subject
	ajax_indicator(true);
	let formData = serialize(document.forms.quickModForm); // includes sessionID
	sendXMLDocument.call(this, elk_prepareScriptUrl(elk_scripturl) + 'action=jsmodify;api=xml', formData, this.modify_topic_done);

	return false;
};

// Done with the edit, if all went well show the new topic title
QuickModifyTopic.prototype.modify_topic_done = function(XMLDoc) {
	ajax_indicator(false);

	// If it is not valid then clean up
	if (!XMLDoc || !XMLDoc.getElementsByTagName('subject'))
	{
		this.modify_topic_cancel();
		return true;
	}

	let message = XMLDoc.getElementsByTagName('elk')[0].getElementsByTagName('message')[0],
		subject = message.getElementsByTagName('subject')[0],
		error = message.getElementsByTagName('error')[0];

	// No subject or other error?
	if (!subject || error)
	{
		return false;
	}

	this.modify_topic_hide_edit(subject.childNodes[0].nodeValue);
	this.set_hidden_topic_areas('');
	this.bInEditMode = false;

	// Redo tooltips if they are on since we just pulled the rug out on this one
	if (this.oSavetipElem)
	{
		this.oSavetipElem.dataset.title = this.sSavetip;
	}

	return false;
};

// Done with the edit, put in new subject and link.
QuickModifyTopic.prototype.modify_topic_hide_edit = function(subject) {
	// Re-template the subject!
	this.oCurSubjectDiv.innerHTML = '<a href="' + elk_scripturl + '?topic=' + this.iCurTopicId + '.0">' + subject + '<' + '/a>';
};

// keypress event ... like enter or escape
QuickModifyTopic.prototype.modify_topic_keypress = function(oEvent) {
	if (typeof (oEvent.keyCode) !== 'undefined' && this.bInEditMode)
	{
		if (oEvent.keyCode === 27)
		{
			this.modify_topic_cancel();
			if (typeof (oEvent.preventDefault) === 'undefined')
			{
				oEvent.returnValue = false;
			}
			else
			{
				oEvent.preventDefault();
			}
		}
		else if (oEvent.keyCode === 13)
		{
			this.modify_topic_save(elk_session_id, elk_session_var);
			if (typeof (oEvent.preventDefault) === 'undefined')
			{
				oEvent.returnValue = false;
			}
			else
			{
				oEvent.preventDefault();
			}
		}
	}
};

// A click event to signal the finish of the edit
QuickModifyTopic.prototype.modify_topic_click = function(oEvent) {
	if (this.bInEditMode && !this.bMouseOnDiv)
	{
		this.modify_topic_save(elk_session_id, elk_session_var);
	}
};

// Moved out of the editing div
QuickModifyTopic.prototype.modify_topic_mouseout = function(oEvent) {
	this.bMouseOnDiv = false;
};

// Moved back over the editing div
QuickModifyTopic.prototype.modify_topic_mouseover = function(oEvent) {
	this.bMouseOnDiv = true;
	oEvent.preventDefault();
};

/**
 * QuickReply object, this allows for selecting the quote button and
 * having the quote appear in the quick reply box
 *
 * @param {type} oOptions
 */
function QuickReply (oOptions)
{
	this.opt = oOptions;
	this.bCollapsed = this.opt.bDefaultCollapsed;

	// If the initial state is to be collapsed, collapse it.
	if (this.bCollapsed)
	{
		this.swap(true);
	}
}

// When a user presses quote, put it in the quick reply box (if expanded).
QuickReply.prototype.quote = function(iMessageId, xDeprecated) {
	ajax_indicator(true);

	// Collapsed on a quote, then simply got to the full post screen
	if (this.bCollapsed)
	{
		// Instead of going to full post screen, lets expand the collapsed QR
		this.swap(false, false);

		//window.location.href = elk_prepareScriptUrl(this.opt.sScriptUrl) + 'action=post;quote=' + iMessageId + ';topic=' + this.opt.iTopicId + '.' + this.opt.iStart;
		//return false;
	}

	// Insert the quote
	insertQuoteFast(iMessageId);

	// Move the view to the quick reply box.
	this.delay(250).then(() => document.getElementById(this.opt.sJumpAnchor).scrollIntoView());

	return false;
};

QuickReply.prototype.delay = function(time) {
	return new Promise(resolve => setTimeout(resolve, time));
};

// The function handling the swapping of the quick reply area
QuickReply.prototype.swap = function(bInit, bSavestate) {
	let oQuickReplyContainer = document.getElementById(this.opt.sClassId),
		sEditorId = this.opt.sEditorId || this.opt.sContainerId;

	// Default bInit to false and bSavestate to true
	bInit = typeof (bInit) !== 'undefined' ? bInit : false;
	bSavestate = typeof (bSavestate) === 'undefined' ? true : bSavestate;

	// Flip our current state if not responding to an initial loading
	if (!bInit)
	{
		this.bCollapsed = !this.bCollapsed;
	}

	// Swap the class on the expcol image as needed
	let sTargetClass = this.bCollapsed ? this.opt.sClassExpanded : this.opt.sClassCollapsed;

	if (oQuickReplyContainer.className !== sTargetClass)
	{
		oQuickReplyContainer.className = sTargetClass;
	}

	// And show the new title
	oQuickReplyContainer.title = oQuickReplyContainer.title = this.bCollapsed ? this.opt.sTitleCollapsed : this.opt.sTitleExpanded;

	// Show or hide away
	if (this.bCollapsed)
	{
		document.getElementById(this.opt.sContainerId).slideUp();
	}
	else
	{
		document.getElementById(this.opt.sContainerId).slideDown(250, function() {
			// Force the editor to a min height, otherwise its just 2 lines
			let instance = sceditor.instance(document.getElementById(sEditorId));
			if (instance)
			{
				instance.height(250);
			}
		});
	}

	// Using a cookie for guests?
	if (bSavestate && 'oCookieOptions' in this.opt && this.opt.oCookieOptions.bUseCookie)
	{
		this.oCookie.set(this.opt.oCookieOptions.sCookieName, this.bCollapsed ? '1' : '0');
	}

	// Save the expand /collapse preference
	if (!bInit && bSavestate && 'oThemeOptions' in this.opt && this.opt.oThemeOptions.bUseThemeSettings)
	{
		elk_setThemeOption(this.opt.oThemeOptions.sOptionName, this.bCollapsed ? '1' : '0', 'sThemeId' in this.opt.oThemeOptions ? this.opt.oThemeOptions.sThemeId : null, 'sAdditionalVars' in this.opt.oThemeOptions ? this.opt.oThemeOptions.sAdditionalVars : null);
	}
};

/**
 * QuickModify object.
 * This will allow for the quick editing of a post via ajax
 *
 * @param {object} oOptions
 */
function QuickModify (oOptions)
{
	this.opt = oOptions;
	this.bInEditMode = false;
	this.sCurMessageId = '';
	this.oCurMessageDiv = null;
	this.oCurInfoDiv = null;
	this.oCurSubjectDiv = null;
	this.oMsgIcon = null;
	this.sMessageBuffer = '';
	this.sSubjectBuffer = '';
	this.sInfoBuffer = '';
	this.aAccessKeys = [];

	// Show the edit buttons
	let aShowQuickModify = document.getElementsByClassName(this.opt.sClassName);
	for (let i = 0, length = aShowQuickModify.length; i < length; i++)
	{
		aShowQuickModify[i].style.display = 'inline';
	}
}

// Function called when a user presses the edit button.
QuickModify.prototype.modifyMsg = function(iMessageId) {
	// Removes the accesskeys from the quickreply inputs and saves them in an array to use them later
	if (typeof (this.opt.sFormRemoveAccessKeys) !== 'undefined')
	{
		if (typeof (document.forms[this.opt.sFormRemoveAccessKeys]))
		{
			let aInputs = document.forms[this.opt.sFormRemoveAccessKeys].getElementsByTagName('input');
			for (let i = 0; i < aInputs.length; i++)
			{
				if (aInputs[i].accessKey !== '')
				{
					this.aAccessKeys[aInputs[i].name] = aInputs[i].accessKey;
					aInputs[i].accessKey = '';
				}
			}
		}
	}

	// First cancel if there's another message still being edited.
	if (this.bInEditMode)
	{
		this.modifyCancel();
	}

	// At least NOW we're in edit mode
	this.bInEditMode = true;

	// Send out the XMLhttp request to get more info
	ajax_indicator(true);
	sendXMLDocument.call(this, elk_prepareScriptUrl(elk_scripturl) + 'action=quotefast;quote=' + iMessageId + ';modify;api=xml', '', this.onMessageReceived);
};

// The callback function used for the XMLhttp request retrieving the message.
QuickModify.prototype.onMessageReceived = function(XMLDoc) {
	let sBodyText = '',
		sSubjectText;

	// No longer show the 'loading...' sign.
	ajax_indicator(false);

	// Grab the message ID.
	this.sCurMessageId = XMLDoc.getElementsByTagName('message')[0].getAttribute('id');

	// Show the message icon if it was hidden and its set
	if (this.opt.sIconHide !== null)
	{
		this.oMsgIcon = document.getElementById('messageicon_' + this.sCurMessageId.replace('msg_', ''));
		if (this.oMsgIcon !== null && getComputedStyle(this.oMsgIcon).getPropertyValue('display') === 'none')
		{
			this.oMsgIcon.style.display = 'inline';
		}
	}

	// If this is not valid then simply give up.
	if (!document.getElementById(this.sCurMessageId))
	{
		if ('console' in window && console.info)
		{
			console.info('no id');
		}

		return this.modifyCancel();
	}

	// Replace the body part.
	for (let i = 0; i < XMLDoc.getElementsByTagName("message")[0].childNodes.length; i++)
	{
		sBodyText += XMLDoc.getElementsByTagName('message')[0].childNodes[i].nodeValue;
	}

	this.oCurMessageDiv = document.getElementById(this.sCurMessageId);
	this.sMessageBuffer = this.oCurMessageDiv.innerHTML;

	// Actually create the content
	this.oCurMessageDiv.innerHTML = this.opt.sTemplateBodyEdit.replace(/%msg_id%/g, this.sCurMessageId.substring(4)).replace(/%body%/, sBodyText);

	// Save and hide the existing subject div
	if (this.opt.sIDSubject !== null)
	{
		this.oCurSubjectDiv = document.getElementById(this.opt.sIDSubject + this.sCurMessageId.substring(4));
		if (this.oCurSubjectDiv !== null)
		{
			this.oCurSubjectDiv.style.display = 'none';
			this.sSubjectBuffer = this.oCurSubjectDiv.innerHTML;
		}
	}

	// Save the info div, then open an input field on it
	sSubjectText = XMLDoc.getElementsByTagName('subject')[0].childNodes[0].nodeValue;
	if (this.opt.sIDInfo !== null)
	{
		this.oCurInfoDiv = document.getElementById(this.opt.sIDInfo + this.sCurMessageId.substring(4));
		if (this.oCurInfoDiv !== null)
		{
			this.sInfoBuffer = this.oCurInfoDiv.innerHTML;
			this.oCurInfoDiv.innerHTML = this.opt.sTemplateSubjectEdit.replace(/%subject%/, sSubjectText);
		}
	}

	// Position the editor in the window
	document.getElementById('info_' + this.sCurMessageId.substring(this.sCurMessageId.lastIndexOf('_') + 1)).scrollIntoView();

	// Handle custom function hook before showing the new select.
	if ('funcOnAfterCreate' in this.opt)
	{
		this.tmpMethod = this.opt.funcOnAfterCreate;
		this.tmpMethod(this);
		delete this.tmpMethod;
	}

	return true;
};

// Function in case the user presses cancel (or other circumstances cause it).
QuickModify.prototype.modifyCancel = function() {
	// Roll back the HTML to its original state.
	if (this.oCurMessageDiv)
	{
		this.oCurMessageDiv.innerHTML = this.sMessageBuffer;
		this.oCurInfoDiv.innerHTML = this.sInfoBuffer;

		if (this.oCurSubjectDiv !== null)
		{
			this.oCurSubjectDiv.innerHTML = this.sSubjectBuffer;
			this.oCurSubjectDiv.style.display = '';
		}
	}

	// Hide the message icon if we are doing that
	if (this.opt.sIconHide)
	{
		let oCurrentMsgIcon = document.getElementById('msg_icon_' + this.sCurMessageId.replace('msg_', ''));

		if (oCurrentMsgIcon !== null && oCurrentMsgIcon.src.indexOf(this.opt.sIconHide) > 0)
		{
			this.oMsgIcon.style.display = 'none';
		}
	}

	// No longer in edit mode, that's right.
	this.bInEditMode = false;

	// Let's put back the accesskeys to their original place
	if (typeof (this.opt.sFormRemoveAccessKeys) !== 'undefined')
	{
		if (typeof (document.forms[this.opt.sFormRemoveAccessKeys]))
		{
			let aInputs = document.forms[this.opt.sFormRemoveAccessKeys].getElementsByTagName('input');
			for (let i = 0; i < aInputs.length; i++)
			{
				if (typeof (this.aAccessKeys[aInputs[i].name]) !== 'undefined')
				{
					aInputs[i].accessKey = this.aAccessKeys[aInputs[i].name];
				}
			}
		}
	}

	return false;
};

// The function called after a user wants to save his precious message.
QuickModify.prototype.modifySave = function() {
	let i = 0,
		formData = '';

	// We cannot save if we weren't in edit mode.
	if (!this.bInEditMode)
	{
		return true;
	}

	this.bInEditMode = false;

	// Let's put back the accesskeys to their original place
	if (typeof (this.opt.sFormRemoveAccessKeys) !== 'undefined')
	{
		if (typeof (document.forms[this.opt.sFormRemoveAccessKeys]))
		{
			let aInputs = document.forms[this.opt.sFormRemoveAccessKeys].getElementsByTagName('input');
			for (i = 0; i < aInputs.length; i++)
			{
				if (typeof (this.aAccessKeys[aInputs[i].name]) !== 'undefined')
				{
					aInputs[i].accessKey = this.aAccessKeys[aInputs[i].name];
				}
			}
		}
	}

	// Send in the XMLhttp request and let's hope for the best.
	ajax_indicator(true);
	formData = serialize(document.forms.quickModForm); // uses form sessionID
	sendXMLDocument.call(this, elk_prepareScriptUrl(this.opt.sScriptUrl) + 'action=jsmodify;;api=xml', formData, this.onModifyDone);

	return false;
};

// Callback function of the XMLhttp request sending the modified message.
QuickModify.prototype.onModifyDone = function(XMLDoc) {
	let oErrordiv;

	// We've finished the loading stuff.
	ajax_indicator(false);

	// If we didn't get a valid document, just cancel.
	if (!XMLDoc || !XMLDoc.getElementsByTagName('elk')[0])
	{
		// Mozilla will nicely tell us what's wrong.
		if (typeof XMLDoc.childNodes !== 'undefined' && XMLDoc.childNodes.length > 0 && XMLDoc.firstChild.nodeName === 'parsererror')
		{
			oErrordiv = document.getElementById('error_box');
			oErrordiv.innerHTML = XMLDoc.firstChild.textContent;
			oErrordiv.style.display = '';
		}
		else
		{
			this.modifyCancel();
		}
		return;
	}

	let message = XMLDoc.getElementsByTagName('elk')[0].getElementsByTagName('message')[0],
		oBody = message.getElementsByTagName('body')[0],
		oSubject = message.getElementsByTagName('subject')[0],
		oModified = message.getElementsByTagName('modified')[0],
		oError = message.getElementsByTagName('error')[0];

	document.forms.quickModForm.message.classList.remove('border_error');
	document.forms.quickModForm.subject.classList.remove('border_error');

	if (oBody)
	{
		// Show new body.
		let bodyText = '';
		for (let i = 0; i < oBody.childNodes.length; i++)
		{
			bodyText += oBody.childNodes[i].nodeValue;
		}

		this.sMessageBuffer = this.opt.sTemplateBodyNormal.replace(/%body%/, bodyText);
		this.oCurMessageDiv.innerHTML = this.sMessageBuffer;

		// Show new subject div, update in case it changed
		let sSubjectText = oSubject.childNodes[0].nodeValue;

		this.sSubjectBuffer = this.opt.sTemplateSubjectNormal.replace(/%subject%/, sSubjectText);
		this.oCurSubjectDiv.innerHTML = this.sSubjectBuffer;
		this.oCurSubjectDiv.style.display = '';

		// If this is the first message, also update the category header.
		if (oSubject.getAttribute('is_first') === '1')
		{
			let subjectHeader = document.getElementById('topic_subject');
			if (subjectHeader)
			{
				subjectHeader.innerHTML = this.sSubjectBuffer;
			}
		}

		// Show this message as 'modified'.
		if (this.opt.bShowModify)
		{
			let modifiedSpan = document.querySelector('#modified_' + this.sCurMessageId.substring(4));
			if (modifiedSpan)
			{
				modifiedSpan.innerHTML = oModified.childNodes[0].nodeValue;
			}
		}

		// Restore the info bar div
		this.oCurInfoDiv.innerHTML = this.sInfoBuffer;

		// Show this message as 'modified on x by y'.
		if (this.opt.bShowModify)
		{
			let modified_element = document.getElementById('modified_' + this.sCurMessageId.substring(4));
			modified_element.innerHTML = message.getElementsByTagName('modified')[0].childNodes[0].nodeValue;

			// Just in case it's the first time the message is modified and the element is hidden
			modified_element.style.display = 'block';
		}

		// Hide the icon if we were told to
		if (this.opt.sIconHide !== null)
		{
			let oCurrentMsgIcon = document.getElementById('msg_icon_' + this.sCurMessageId.replace('msg_', ''));
			if (oCurrentMsgIcon !== null && oCurrentMsgIcon.src.indexOf(this.opt.sIconHide) > 0)
			{
				this.oMsgIcon.style.display = 'none';
			}
		}

		// Re embed any video links if the feature is available
		if (typeof $.fn.linkifyvideo === 'function')
		{
			$().linkifyvideo(oEmbedtext, this.sCurMessageId);
		}

		// Hello, Sweetie
		let spoilerHeader = document.getElementById(this.sCurMessageId).querySelector('.spoilerheader');
		if (spoilerHeader)
		{
			spoilerHeader.addEventListener('click', function() {
				let content = this.nextElementSibling.children;
				for (let i = 0; i < content.length; i++)
				{
					content[i].slideToggle(150);
				}
			});
		}

		// Re-Fix quote blocks
		if (typeof elk_quotefix === 'function')
		{
			elk_quotefix();
		}

		// Re-Fix code blocks
		if (typeof elk_codefix === 'function')
		{
			elk_codefix();
		}

		// Re-Fix quote blocks
		if (typeof elk_quotefix === 'function')
		{
			elk_quotefix();
		}

		// And pretty the code
		if (typeof prettyPrint === 'function')
		{
			prettyPrint();
		}
	}
	else if (oError)
	{
		oErrordiv = document.getElementById('error_box');
		oErrordiv.innerHTML = oError.childNodes[0].nodeValue;
		oErrordiv.style.display = '';

		if (oError.getAttribute('in_body') === '1')
		{
			document.forms.quickModForm.message.classList.add('border_error');
		}

		if (oError.getAttribute('in_subject') === '1')
		{
			document.forms.quickModForm.subject.classList.add('border_error');
		}
	}
};

/**
 * Quick Moderation for the topic view
 *
 * @param {type} oOptions
 */
function InTopicModeration (oOptions)
{
	this.opt = oOptions;
	this.bButtonsShown = false;
	this.iNumSelected = 0;

	this.init();
}

InTopicModeration.prototype.init = function() {
	// Add checkboxes w/click event to the messages that can be removed/split
	this.opt.aMessageIds.forEach((messageID) => {
		// Create the checkbox.
		let oCheckbox = document.createElement('input'),
			oCheckboxContainer = document.getElementById(this.opt.sCheckboxContainerMask + messageID);

		oCheckbox.type = 'checkbox';
		oCheckbox.name = 'msgs[]';
		oCheckbox.value = messageID;
		oCheckbox.title = 'checkbox ' + messageID;
		oCheckbox.onclick = this.handleClick.bind(this, oCheckbox);

		// Append it to the container
		oCheckboxContainer.appendChild(oCheckbox);
		oCheckboxContainer.classList.remove('hide');
	});
};

// They clicked a checkbox in a message, now show the button options to them
InTopicModeration.prototype.handleClick = function(oCheckbox) {
	let oButtonStrip = document.getElementById(this.opt.sButtonStrip),
		oButtonStripDisplay = document.getElementById(this.opt.sButtonStripDisplay),
		aButtonCounter = ['remove', 'restore', 'split'];

	if (!this.bButtonsShown && this.opt.sButtonStripDisplay)
	{
		// Make sure it can go somewhere.
		if (typeof oButtonStripDisplay === 'object' && oButtonStripDisplay !== null)
		{
			oButtonStripDisplay.style.display = '';
		}
		else
		{
			let oNewDiv = document.createElement('div'),
				oNewList = document.createElement('ul');

			oNewDiv.id = this.opt.sButtonStripDisplay;
			oNewDiv.className = this.opt.sButtonStripClass ? this.opt.sButtonStripClass : 'buttonlist';

			oNewDiv.appendChild(oNewList);
			oButtonStrip.appendChild(oNewDiv);
		}

		// Add the special selected buttons.
		aButtonCounter.forEach((addButton) => {
			let upperButton = addButton[0].toUpperCase() + addButton.substring(1);

			// As in bCanRemove etc.
			if (this.opt['bCan' + upperButton])
			{
				elk_addButton(this.opt.sButtonStrip, this.opt.bUseImageButton, {
					sId: addButton + '_button',
					sText: this.opt['s' + upperButton + 'ButtonLabel'],
					sImage: this.opt['s' + upperButton + 'ButtonImage'],
					sUrl: '#',
					aEvents: [
						['click', this.handleSubmit.bind(this, addButton)]
					]
				});
			}
		});

		// Adding these buttons once should be enough.
		this.bButtonsShown = true;
	}

	// Keep stats on how many items were selected.
	this.iNumSelected += oCheckbox.checked ? 1 : -1;

	// Show the number of messages selected in each of the buttons.
	aButtonCounter.forEach((addButton) => {
		let upperButton = addButton[0].toUpperCase() + addButton.substring(1);
		if (this.opt['bCan' + upperButton])
		{
			oButtonStrip.querySelector('#' + addButton + '_button_text').innerHTML = this.opt['s' + upperButton + 'ButtonLabel'] + ' [' + this.iNumSelected + ']';
			oButtonStrip.querySelector('#' + addButton + '_button').style.display = this.iNumSelected < 1 ? 'none' : '';
		}
	});

	// Toggle the sticky class based on if something is selected
	if (this.iNumSelected < 1)
	{
		oButtonStrip.classList.remove('sticky');
	}
	else
	{
		oButtonStrip.classList.add('sticky');
	}
};

// Called when the user clicks one of the buttons that we added
InTopicModeration.prototype.handleSubmit = function(sSubmitType) {
	// Make sure this form isn't submitted in another way than this function.
	let oForm = document.getElementById(this.opt.sFormId),
		oInput = document.createElement('input');

	oInput.type = 'hidden';
	oInput.name = this.opt.sSessionVar;
	oInput.value = this.opt.sSessionId;
	oForm.appendChild(oInput);

	// Set the form action based on the button they clicked
	switch (sSubmitType)
	{
		case 'remove':
			if (!confirm(this.opt.sRemoveButtonConfirm))
			{
				return false;
			}

			oForm.action = oForm.action.replace(/;split_selection=1/, '');
			oForm.action = oForm.action.replace(/;restore_selected=1/, '');
			break;

		case 'restore':
			if (!confirm(this.opt.sRestoreButtonConfirm))
			{
				return false;
			}

			oForm.action = oForm.action.replace(/;split_selection=1/, '');
			oForm.action += ';restore_selected=1';
			break;

		case 'split':
			if (!confirm(this.opt.sRestoreButtonConfirm))
			{
				return false;
			}

			oForm.action = oForm.action.replace(/;restore_selected=1/, '');
			oForm.action += ';split_selection=1';
			break;

		default:
			return false;
	}

	oForm.submit();
	return true;
};

/**
 * Expands an attachment thumbnail when its clicked
 *
 * @param {string} thumbID
 * @param {string} messageID
 */
function expandThumbLB (thumbID, messageID)
{
	var link = document.getElementById('link_' + thumbID),
		siblings = $('a[data-lightboxmessage="' + messageID + '"]'),
		navigation = [],
		xDown = null,
		yDown = null,
		$elk_expand_icon = $('<span id="elk_lb_expand"></span>'),
		$elk_next_icon = $('<span id="elk_lb_next"></span>'),
		$elk_prev_icon = $('<span id="elk_lb_prev"></span>'),
		$elk_lightbox = $('#elk_lightbox'),
		$elk_lb_content = $('#elk_lb_content'),
		ajaxIndicatorOn = function() {
			$('<div id="lightbox-loading"><i class="icon icon-xl i-concentric"></i><div>').appendTo($elk_lb_content);
			$('html, body').addClass('elk_lb_no_scrolling');
		},
		ajaxIndicatorOff = function() {
			$('#lightbox-loading').remove();
		},
		closeLightbox = function() {
			// Close the lightbox and remove handlers
			$elk_expand_icon.off('click');
			$elk_next_icon.off('click');
			$elk_prev_icon.off('click');
			$elk_lightbox.hide();
			$elk_lb_content.html('').removeAttr('style').removeClass('expand');
			$('html, body').removeClass('elk_lb_no_scrolling');
			$(window).off('resize.lb');
			$(window).off('keydown.lb');
			$(window).off('touchstart.lb');
			$(window).off('touchmove.lb');
		},
		openLightbox = function() {
			// Load and open an image in the lightbox
			$('<img id="elk_lb_img" src="' + link.href + '">')
				.on('load', function() {
					var screenWidth = (window.innerWidth ? window.innerWidth : $(window).width()) * (is_mobile ? 0.8 : 0.9),
						screenHeight = (window.innerHeight ? window.innerHeight : $(window).height()) * 0.9;

					$(this).css({
						'max-width': Math.floor(screenWidth) + 'px',
						'max-height': Math.floor(screenHeight) + 'px'
					});

					// Add expand and prev/next links
					if (navigation.length > 1)
					{
						$elk_lb_content.html($(this)).append($elk_expand_icon).append($elk_next_icon).append($elk_prev_icon);
					}
					else
					{
						$elk_lb_content.html($(this)).append($elk_expand_icon);
					}

					ajaxIndicatorOff();
				})
				.on('error', function() {
					// Perhaps a message, but for now make it look like we tried and failed
					setTimeout(function() {
						ajaxIndicatorOff();
						closeLightbox();
						window.location = link.href;
					}, 1500);
				});
		},
		nextNav = function() {
			// Get / Set the next image ID in the array (with wrap around)
			thumbID = navigation[($.inArray(thumbID, navigation) + 1) % navigation.length];
		},
		prevNav = function() {
			// Get / Set the previous image ID in the array (with wrap around)
			thumbID = navigation[($.inArray(thumbID, navigation) - 1 + navigation.length) % navigation.length];
		},
		navLightbox = function() {
			// Navigate to the next image and show it in the lightbox
			$elk_lb_content.html('').removeAttr('style').removeClass('expand');
			ajaxIndicatorOn();
			$elk_expand_icon.off('click');
			$elk_next_icon.off('click');
			$elk_prev_icon.off('click');
			link = document.getElementById('link_' + thumbID);
			openLightbox();
			expandLightbox();
		},
		expandLightbox = function() {
			// Add an expand the image to full size when the expand icon is clicked
			$elk_expand_icon.on('click', function() {
				$('#elk_lb_content').addClass('expand').css({
					'height': Math.floor(window.innerHeight * 0.95) + 'px',
					'width': Math.floor(window.innerWidth * 0.9) + 'px',
					'left': '0'
				});
				$('#elk_lb_img').removeAttr('style');
				$elk_expand_icon.hide();
				$(window).off('keydown.lb');
				$(window).off('touchmove.lb');
			});
			$elk_next_icon.on('click', function(event) {
				event.preventDefault();
				event.stopPropagation();
				nextNav();
				navLightbox();
			});
			$elk_prev_icon.on('click', function(event) {
				event.preventDefault();
				event.stopPropagation();
				prevNav();
				navLightbox();
			});
		};

	// Create the lightbox container only if needed
	if ($elk_lightbox.length <= 0)
	{
		// For easy manipulation
		$elk_lightbox = $('<div id="elk_lightbox"></div>');
		$elk_lb_content = $('<div id="elk_lb_content"></div>');

		$('body').append($elk_lightbox.append($elk_lb_content));
	}

	// Load the navigation array
	siblings.each(function() {
		navigation[navigation.length] = $(this).data('lightboximage');
	});

	// We should always have at least the thumbID
	if (navigation.length === 0)
	{
		navigation[navigation.length] = thumbID;
	}

	// Load and show the initial lightbox container div
	ajaxIndicatorOn();
	$elk_lightbox.fadeIn(200);
	openLightbox();
	expandLightbox();

	// Click anywhere on the page (except the expand icon) to close the lightbox
	$elk_lightbox.on('click', function(event) {
		if (event.target.id !== $elk_expand_icon.attr('id'))
		{
			event.preventDefault();
			closeLightbox();
		}
	});

	// Provide some keyboard navigation
	$(window).on('keydown.lb', function(event) {
		event.preventDefault();

		// escape
		if (event.which === 27)
		{
			closeLightbox();
		}

		// left
		if (event.which === 37)
		{
			prevNav();
			navLightbox();
		}

		// right
		if (event.which === 39)
		{
			nextNav();
			navLightbox();
		}
	});

	// Make the image size fluid as the browser window changes
	$(window).on('resize.lb', function() {
		// Account for either a normal or expanded view
		var $_elk_lb_content = $('#elk_lb_content');

		if ($_elk_lb_content.hasClass('expand'))
		{
			$_elk_lb_content.css({'height': window.innerHeight * 0.85, 'width': window.innerWidth * 0.9});
		}
		else
		{
			$('#elk_lb_img').css({'max-height': window.innerHeight * 0.9, 'max-width': window.innerWidth * 0.8});
		}
	});

	// Swipe navigation start, record press x/y
	$(window).on('touchstart.lb', function(event) {
		xDown = event.originalEvent.touches[0].clientX;
		yDown = event.originalEvent.touches[0].clientY;
	});

	// Swipe navigation left / right detection
	$(window).on('touchmove.lb', function(event) {
		// No known start point ?
		if (!xDown || !yDown)
		{
			return;
		}

		// Where are we now
		var xUp = event.originalEvent.touches[0].clientX,
			yUp = event.originalEvent.touches[0].clientY,
			xDiff = xDown - xUp,
			yDiff = yDown - yUp;

		// Moved enough to know what direction they are swiping
		if (Math.abs(xDiff) > Math.abs(yDiff))
		{
			if (xDiff > 0)
			{
				// Swipe left
				prevNav();
				navLightbox();
			}
			else
			{
				// Swipe right
				nextNav();
				navLightbox();
			}
		}

		// Reset values
		xDown = null;
		yDown = null;
	});

	return false;
}

/**
 * Provides a way to toggle an ignored message(s) visibility
 *
 * @param {object} msgids
 * @param {string} text
 */
function ignore_toggles (msgids, text)
{
	for (let i = 0; i < msgids.length; i++)
	{
		let msgid = msgids[i];

		new elk_Toggle({
			bToggleEnabled: true,
			bCurrentlyCollapsed: true,
			aSwappableContainers: [
				'msg_' + msgid,
			],
			aSwapLinks: [
				{
					sId: 'msg_' + msgid + '_ignored_link',
					msgExpanded: 'X',
					msgCollapsed: text
				}
			]
		});
	}
}

/**
 * Used to split a topic.
 * Allows selecting a message so it can be moved from the original to the spit topic or back
 *
 * @param {string} direction up / down / reset
 * @param {int} msg_id message id that is being moved
 */
function topicSplitselect (direction, msg_id)
{
	getXMLDocument(elk_prepareScriptUrl(elk_scripturl) + 'action=splittopics;sa=selectTopics;subname=' + topic_subject + ';topic=' + topic_id + '.' + start[0] + ';start2=' + start[1] + ';move=' + direction + ';msg=' + msg_id + ';api=xml', onTopicSplitReceived);

	return false;
}

/**
 * Callback function for topicSplitselect
 *
 * @param {xmlCallback} XMLDoc
 */
function onTopicSplitReceived (XMLDoc)
{
	let i,
		j,
		pageIndex;

	// Find the selected and not_selected page index containers
	for (i = 0; i < 2; i++)
	{
		pageIndex = XMLDoc.getElementsByTagName('pageIndex')[i];

		// Update the page container with our xml response
		document.getElementById('pageindex_' + pageIndex.getAttribute('section')).innerHTML = pageIndex.firstChild.nodeValue;
		start[i] = pageIndex.getAttribute('startFrom');
	}

	var numChanges = XMLDoc.getElementsByTagName('change').length,
		curChange,
		curSection,
		curAction,
		curId,
		curList,
		newItem,
		sInsertBeforeId,
		oListItems,
		right_arrow = '<i class="icon icon-big i-chevron-circle-right"></i>',
		left_arrow = '<i class="icon icon-big i-chevron-circle-left"></i>';

	// Loop through all changes ajax returned
	for (i = 0; i < numChanges; i++)
	{
		curChange = XMLDoc.getElementsByTagName('change')[i];
		curSection = curChange.getAttribute('section');
		curAction = curChange.getAttribute('curAction');
		curId = curChange.getAttribute('id');
		curList = document.getElementById('messages_' + curSection);

		// Remove it from the source list, so we can insert it in the destination list
		if (curAction === 'remove')
		{
			curList.removeChild(document.getElementById(curSection + '_' + curId));
		}
		// Insert a message.
		else
		{
			// By default, insert the element at the end of the list.
			sInsertBeforeId = null;

			// Loop through the list to try and find an item to insert after.
			oListItems = curList.getElementsByTagName('li');
			for (j = 0; j < oListItems.length; j++)
			{
				if (parseInt(oListItems[j].id.substring(curSection.length + 1)) < curId)
				{
					// This would be a nice place to insert the row.
					sInsertBeforeId = oListItems[j].id;

					// We're done for now. Escape the loop.
					j = oListItems.length + 1;
				}
			}

			// Let's create a nice container for the message.
			newItem = document.createElement('li');
			newItem.className = '';
			newItem.id = curSection + '_' + curId;
			newItem.innerHTML = '' +
				'<div class="content">' +
				'   <div class="message_header">' +
				'       <a class="split_icon float' + (curSection === 'selected' ? 'left' : 'right') + '" href="' + elk_prepareScriptUrl(elk_scripturl) + 'action=splittopics;sa=selectTopics;subname=' + topic_subject + ';topic=' + topic_id + '.' + not_selected_start + ';start2=' + selected_start + ';move=' + (curSection === 'selected' ? 'up' : 'down') + ';msg=' + curId + '" onclick="return topicSplitselect(\'' + (curSection === 'selected' ? 'up' : 'down') + '\', ' + curId + ');">' +
				(curSection === 'selected' ? left_arrow : right_arrow) +
				'       </a>' +
				'       <strong>' + curChange.getElementsByTagName('subject')[0].firstChild.nodeValue + '</strong> ' + txt_by + ' <strong>' + curChange.getElementsByTagName('poster')[0].firstChild.nodeValue + '</strong>' +
				'       <br />' +
				'       <em>' + curChange.getElementsByTagName('time')[0].firstChild.nodeValue + '</em>' +
				'   </div>' +
				'   <div class="post">' + curChange.getElementsByTagName('body')[0].firstChild.nodeValue + '</div>' +
				'</div>';

			// So, where do we insert it?
			if (typeof sInsertBeforeId === 'string')
			{
				curList.insertBefore(newItem, document.getElementById(sInsertBeforeId));
			}
			else
			{
				curList.appendChild(newItem);
			}
		}
	}
}

/**
 * Quick Moderation for the message listing
 *
 * @param {type} oOptions
 *      - aQmActions: array of possible actions restore, markread, merge, etc
 * 	    - sButtonStrip: string identifying the button container, typically moderationbuttons
 * 		- sButtonStripDisplay: string of ID to attach to UL if creating, typically moderationbuttons_strip
 *      - bUseImageButton: If to show an icon on the button when selected
 * 		- sFormId: The id of the QM form, which we will submit on a button (link) click
 * 	    - bHideStrip: boolean If to initially hide the button strip
 * 	Button Definitions are (remove shown as example)
 * 	    - bCanRemove: boolean, if this button can be shown at all
 * 		- aActionRemove: optional array of specific topic id's that increase the counter (remove in this example)
 * 		- sRemoveButtonLabel: Button text
 * 		- sRemoveButtonImage: svg icon name from css, like "i-delete",
 * 		- sRemoveButtonConfirm: optional, text to show in a confirm dialog
 */
function InTopicListModeration (oOptions)
{
	this.opt = oOptions;
	this.bButtonsShown = false;
	this.iNumSelected = 0;
	this.opt.bHideStrip = this.opt.bHideStrip || true;

	this.init();
}

InTopicListModeration.prototype.init = function() {
	// fetch all topic[] checkbox elements
	let eCheckboxes = document.getElementsByName('topics[]'),
		eSelectAll = document.getElementById('select_all');

	// Bind a click event to each action button
	eCheckboxes.forEach((eCheck) => {
		eCheck.onclick = this.handleClick.bind(this, eCheck);
	});

	// Hide the entire strip until needed, use of style is required
	if (this.opt.bHideStrip)
	{
		document.getElementById(this.opt.sButtonStrip).style.display = 'none';
	}

	// Watch for the select/unselect all click
	if (eSelectAll !== null)
	{
		eSelectAll.addEventListener('change', () => {
			this.handleClick();
		});
	}
};

// They clicked a checkbox in the topic listing, show additional button options / counters
InTopicListModeration.prototype.handleClick = function(oCheckbox) {
	let oButtonStrip = document.getElementById(this.opt.sButtonStrip),
		eCheckboxes = document.querySelectorAll('input[name="topics[]"]:checked'),
		aCurrentlySelected = [];

	// Topic array of currently selected (checked) topics
	eCheckboxes.forEach((check) => {
		aCurrentlySelected.push(parseInt(check.value));
	});

	// If the buttons are not there, add them
	if (!this.bButtonsShown && this.opt.sButtonStripDisplay)
	{
		this.addButtons();
	}

	// Keep stats on how many checkboxes are selected.
	this.iNumSelected = aCurrentlySelected.length;

	// Check each action, update counters to show availability for the selected topics
	this.opt.aQmActions.forEach((action) => {
		let upperAction = action[0].toUpperCase() + action.substring(1),
			thisTopics = this.opt['aAction' + upperAction],
			indicator = 0;

		// Update the button counters to show where this action is allowed
		if (this.opt['bCan' + upperAction])
		{
			let button = oButtonStrip.querySelector('#button_strip_' + action),
				buttonAlt = oButtonStrip.querySelector('#button_strip_' + action + '_text');

			// No specified action array, so this can be done everywhere
			if (typeof thisTopics === 'undefined')
			{
				indicator = this.iNumSelected;
			}
			// Availability array, can this be done here (e.g. can approve)
			else
			{
				let filteredArray = thisTopics.filter(value => aCurrentlySelected.includes(value));

				indicator = filteredArray.length;
			}

			// Update the button counter, if zero leave the button hidden
			if (indicator < 1)
			{
				if (buttonAlt !== null)
				{
					buttonAlt.text = this.opt['s' + upperAction + 'ButtonLabel'];
					button.classList.add('hide');
				}
				else
				{
					button.text = this.opt['s' + upperAction + 'ButtonLabel'];
					button.parentElement.classList.add('hide');
				}
			}
			else
			{
				if (buttonAlt !== null)
				{
					buttonAlt.textContent = this.opt['s' + upperAction + 'ButtonLabel'] + ' [' + indicator + ']';
					button.classList.remove('hide');
				}
				else
				{
					button.text = this.opt['s' + upperAction + 'ButtonLabel'] + ' [' + indicator + ']';
					button.parentElement.classList.remove('hide');
				}
			}
		}
	});

	if (this.opt.bCanRestore)
	{
		let restore = oButtonStrip.querySelector('#restore_button_text');
		if (restore)
		{
			restore.innerHTML = this.opt.sRestoreButtonLabel + ' [' + this.iNumSelected + ']';
			if (this.iNumSelected < 1)
			{
				oButtonStrip.querySelector('#restore_button').classList.add('hide');
			}
			else
			{
				oButtonStrip.querySelector('#restore_button').classList.remove('hide');
			}
		}
	}

	// Toggle the sticky class based on if something is selected
	if (this.iNumSelected < 1)
	{
		oButtonStrip.classList.remove('sticky');
		if (this.opt.bHideStrip)
		{
			oButtonStrip.style.display = 'none';
		}
	}
	else
	{
		oButtonStrip.classList.add('sticky');
		oButtonStrip.style.display = '';
	}
};

// Add / modifying buttons as defined in the options
InTopicListModeration.prototype.addButtons = function() {
	this.opt.aQmActions.forEach((action) => {
		let sUpperAction = action[0].toUpperCase() + action.substring(1),
			bCheck = document.getElementById('button_strip_' + action);

		if (!this.opt['bCan' + sUpperAction])
		{
			return;
		}

		// Button does not exist
		if (bCheck === null)
		{
			elk_addButton(this.opt.sButtonStrip, this.opt.bUseImageButton, {
				sId: 'button_strip_' + action,
				sText: this.opt['s' + sUpperAction + 'ButtonLabel'],
				sImage: this.opt['s' + sUpperAction + 'ButtonImage'],
				sUrl: '#',
				aEvents: [
					['click', this.handleSubmit.bind(this, action)]
				]
			});

			return;
		}

		// It exists, but they want the icons.  Do a full replacement
		if (this.opt.bUseImageButton)
		{
			bCheck.parentElement.remove();
			elk_addButton(this.opt.sButtonStrip, this.opt.bUseImageButton, {
				sId: 'button_strip_' + action,
				sText: this.opt['s' + sUpperAction + 'ButtonLabel'],
				sImage: this.opt['s' + sUpperAction + 'ButtonImage'],
				sUrl: '#',
				aEvents: [
					['click', this.handleSubmit.bind(this, action)]
				]
			});

			return;
		}

		// Default, bind a click to the link, which will submit the form
		bCheck.addEventListener('click', this.handleSubmit.bind(this, action));
	});

	// Adding these buttons once is enough.
	this.bButtonsShown = true;
};

// Called when the user clicks one of the buttons that we added
InTopicListModeration.prototype.handleSubmit = function(sSubmitType) {
	// Make sure this form isn't submitted in another way than this function.
	let oForm = document.getElementById(this.opt.sFormId),
		oInput = document.getElementById('qaction'),
		upperAction = sSubmitType[0].toUpperCase() + sSubmitType.substring(1);

	// Set the forms' qaction value based on the button they clicked
	oInput.value = sSubmitType;

	// Any confirmation before we submit
	if (typeof this.opt['s' + upperAction + 'ButtonConfirm'] !== 'undefined')
	{
		if (!confirm(this.opt['s' + upperAction + 'ButtonConfirm']))
		{
			return false;
		}
	}

	// Submit and hope for the best :P
	oForm.submit();
	return true;
};
