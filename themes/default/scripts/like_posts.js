/**
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 1.0 Beta 2
 *
 * Ajaxifying likes - WIP
 */

/**
 * Simply invoke the constructor by calling likePosts
 */
(function() {
	function likePosts() {}

	/**
	 * This is bound to a click event on the page like/unlike buttons
	 * likePosts.prototype.likeUnlikePosts(event, messageID, topidID)
	 */
	likePosts.prototype = function() {
		var init = '',
			likeUnlikePosts = function(e, mId, tId) {
				var messageId = parseInt(mId, 10),
					topicId = parseInt(tId, 10),
					subAction = '',
					check = $(e.target).attr('class');

				// Set the subAction to what they are doing
				if (check.indexOf('unlike_button') >= 0)
					subAction = 'unlikepost';
				else
					subAction = 'likepost';

				// Need to know what we are liking of course
				var values = {
					'topic': topicId,
					'msg': messageId
				};

				// Make the ajax call to the likes system
				$.ajax({
					url: elk_scripturl + '?action=likes;sa=' + subAction + ';api;' + elk_session_var + '=' + elk_session_id,
					type: 'POST',
					dataType: 'json',
					data: values,
					cache: false
				})
				.done(function(resp) {
					// json response from the server says success?
					if (resp.result === true)
					{
						// Update the page with the new likes information
						updateUi({
							'elem': $(e.target),
							'count': resp.count,
							'text': resp.text,
							'title': resp.title,
							'action': subAction
						});
					}
					// Some failure trying to process the request
					else
						handleError(resp);
				})
				.fail(function(err, textStatus, errorThrown) {
					// Some failure sending the request
					console.log(textStatus);
					console.log(errorThrown);
					handleError(err);
				});
			},

			/**
			 * Does the actual update to the page the user is viewing
			 *
			 * @param {object} params object of new values from the ajax request
			 */
			updateUi = function(params)
			{
				var currentClass = (params.action === 'unlikepost') ? 'unlike_button' : 'like_button',
					nextClass = (params.action === 'unlikepost') ? 'like_button' : 'unlike_button';

				// Swap the button class as needed, update the text for the hover
				$(params.elem).removeClass(currentClass).addClass(nextClass);
				$(params.elem).html('&nbsp;' + params.text);
				$(params.elem).attr('title', params.title);

				// Changed the title text, update the tooltips
				$("." + nextClass).SiteTooltip({hoverIntent: {sensitivity: 10, interval: 150, timeout: 50}});
			},

			/**
			 * Show a non modal error box when something goes wrong with
			 * sending the request or processing it
			 *
			 * @param {type} params
			 */
			handleError = function(params)
			{
				var str = '<div class="floating_error"><div class="error_heading">Error in Likes</div><p class="error_msg">' + params.data + '</p><p class="error_btn">OK</p></div>';
				$('body').append(str);

				var screenWidth = $(window).width(),
					screenHeight = $(window).height(),
					popupHeight = $('.floating_error').outerHeight(),
					popupWidth = $('.floating_error').outerWidth(),
					topPopUpOffset = (screenHeight - popupHeight) / 2,
					leftPopUpOffset = (screenWidth - popupWidth) / 2;

				$('.floating_error').css({
					top: topPopUpOffset + 'px',
					left: leftPopUpOffset + 'px'
				});

				$(document).one('click keyup', removeOverlay);
			},

			/**
			 * Clear the error box from the screen by click or escape key
			 *
			 * @param {type} e
			 */
			removeOverlay = function(e)
			{
				if (typeof(e) === 'undefined' && this.timeoutTimer === null)
					return false;
				else if ((e.type === 'keyup' && e.keyCode === 27) || e.type === 'click')
				{
					$('.floating_error').remove();
					$('.floating_error').unbind('click');
					$(document).unbind('click', removeOverlay);
					$(document).unbind('keyup', removeOverlay);
				}
			};

		return {
			likeUnlikePosts: likeUnlikePosts
		};
	}();

	// instead of this, we can use namespace too
	this.likePosts = likePosts;
}());