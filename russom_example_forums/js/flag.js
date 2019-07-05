/**
 * @file
 * Attaches is flag rating.
 */

(function ($, Drupal) {
	Drupal.behaviors.flagRating = {
		attach: function (context, settings) {
			$('body').find('.flag').each(function () {
				var $this = $(this);
				$(this).find('select').once('processed').each(function () {
					$this.find('[type=submit]').hide();

					var $select = $(this);
					var isPreview = $select.data('is-edit');
					var vote_own_value = $select.data('user-submitted-vote');

					$select.removeClass('russom_example__select-menu__select', 'js-dropdown-select');
					$select.prop('disabled', false);

					$select.before('<div class="flag-rating">' +
						'<a href="#" class="add-flag">' +
						'<i class="fa fa-flag-o"></i><span>Flag comment</span>' +
						'</a>' +
						'<a href="#" class="remove-flag">' +
						'<i class="fa fa-flag"></i><span>Remove flag</span>' +
						'</a>' +
						'</div>').hide();


					if (vote_own_value === 1) {
						$this.find('.flag-rating a.add-flag').hide();
						$this.find(".flag-rating").append('<div class="messages">Comment has been flagged by you.</div>');

					}

					if (vote_own_value === 0 || vote_own_value === "undefined") {
						$this.find('.flag-rating a.remove-flag').hide();
					}

					$this.find('.flag-rating a').eq(0).each(function () {
						// Add flag.
						$(this).bind('click', function (e) {
							if (isPreview) {
								return;
							}
							e.preventDefault();

							$select.get(0).selectedIndex = 1;

							$this.find('[type=submit]').trigger('click');
							$this.find('a').addClass('disabled');
							$this.find('.vote-result').html();
						})
					});
					$this.find('.flag-rating a').eq(1).each(function () {
						// Remove flag.
						$(this).bind('click', function (e) {
							if (isPreview) {
								return;
							}
							e.preventDefault();

							$select.get(0).selectedIndex = 0;

							$this.find('[type=submit]').trigger('click');
							$this.find('a').addClass('disabled');
							$this.find('.vote-result').html();
						});
					});
				});
			});
		}
	};
})(jQuery, Drupal);
