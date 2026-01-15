(function($) {
	$(document).ready(function() {
		/**
		 * Get links to the categories using ajax request. This is on the quiz single page.
		 */
		if ( $(".wpProQuiz_content").length > 0 ) { // this means the current page has a quiz.
			$(document).on('learndash-quiz-finished', replaceQuizCatNameWithURL );
		}

		/**
		 * Replaces quiz category names with URLs in the WP Pro Quiz category overview.
		 *
		 * This function retrieves quiz metadata and category IDs, then makes an AJAX request
		 * to fetch URLs for the categories. If successful, it updates the category names to be
		 * clickable links pointing to the corresponding URLs.
		 *
		 * @function replaceQuizCatNameWithURL
		 * @returns {void}
		 *
		 * Process:
		 * - Retrieves quiz metadata including the `quiz_pro_id`.
		 * - Collects category IDs from the `.wpProQuiz_catOverview` list.
		 * - Sends an AJAX request to fetch URLs for the categories.
		 * - Updates category names with hyperlinks if data is returned.
		 *
		 * AJAX Request:
		 * - Action: `ldqcm_get_rec_steps`
		 * - Parameters:
		 *   - `all_cat_ids`: JSON string of category IDs
		 *   - `quiz_pro_id`: Quiz Pro ID
		 *   - `nonce`: Security nonce from `ldqcm_front`
		 * - On success, updates category names with the corresponding links.
		 *
		 * Dependencies:
		 * - jQuery
		 * - `ldqcm_front` global object (contains `admin_url` and `nonce`)
		 */
		function replaceQuizCatNameWithURL() {
			const quizMeta  = JSON.parse( $(".wpProQuiz_content").attr( "data-quiz-meta" ) );
			const quizProID = quizMeta?.quiz_pro_id;

			const $wpProQuizCatOverview = $(".wpProQuiz_catOverview");

			if ( $wpProQuizCatOverview.length > 0 && quizProID ) {
				let allCatIDs = [];
				const $list   = $wpProQuizCatOverview.find("ol"); // get the element list of all categories.

				if ( $list.length > 0 ) {
					$( $list.find( "li" ) ).each( function( ind, el ) {
						let categoryID = parseInt( $(el).attr( 'data-category_id' ) );
						if ( categoryID !== 0 ) {
							allCatIDs.push( categoryID );
						}
					} );

					if ( allCatIDs.length > 0 ) {
						const data = {
							action:      'ldqcm_get_rec_steps',
							all_cat_ids: JSON.stringify( allCatIDs ),
							quiz_pro_id: quizProID,
							nonce:       ldqcm_front.nonce
						};
			
						$.ajax( {
							url: ldqcm_front.admin_url,
							data,
							dataType: 'json',
							method: 'POST',
							success: function( response ) {
								if ( $.trim( response.data ) != '' ) {
									$.each( response.data, function( catID, catData ) {
										$catLi = $( '.wpProQuiz_catOverview li[data-category_id="' + catID + '"]');
										$catLi.find( ".wpProQuiz_catName").wrapInner('<a href="' + catData.step_link + '" target="_blank"></a>' );
									});
								}
							}
						} );
					}
				}
			}
		}

		/**
		 * Apply class to `a` tag after getting the result.
		 * We apply 'below-average' class to category links with less than 80% score.
		 * Applies only on the single quiz page.
		 */
		var isCatOverviewShowed = false;
		$( '.wpProQuiz_catOverview' ).on( 'show', function() {
			if ( false === isCatOverviewShowed ) {
				setTimeout(() => {
					const $catList = $( '.wpProQuiz_catOverview ol li' );
					if ( $catList.length > 0 ) {
						$catList.each(function( index, catLi ) {
							$catLi = $(catLi);
							if ( $catLi.find(".wpProQuiz_catName a").length > 0 ) {
								let per = Number( $catLi.find( ".wpProQuiz_catPercent").text().replace( "%", "" ) );
								if ( per < 80 ) {
									$catLi.find(".wpProQuiz_catName a").addClass( "below-average" );
								}
							}
						} );
					}
				}, 2000);

				isCatOverviewShowed = true;
			}
		} );

		/**
		 * This code should only run whenever the user tries to view the statistics.
		 * The reason we have used ajax completed event because there is no other reliable method 
		 * to detect the statistics has been loaded successfully to proceed with the request to fetch category links.
		 */
		$(document).ajaxSuccess(function( event, xhr, settings ) { // An individual AJAX call has completed successfully.
			const dataString = settings?.data;

			// if true, this means the ajax request was to load statistics.
			if ( dataString && dataString.indexOf('action=wp_pro_quiz_admin_ajax_statistic_load_user' ) >= 0 ) {

				var paramsArray = dataString.split('&');
				var paramsObj   = {};

				// Loop through the array and populate the object with key-value pairs.
				paramsArray.forEach(function(item) {
					var pair       = item.split('=');
					var key        = decodeURIComponent(pair[0]);
					var value      = decodeURIComponent(pair[1]);
					paramsObj[key] = value;
				});

				const quizProID = parseInt( paramsObj['data[quizId]'] );

				/**
				 * Finding all category names.
				 */
				// Find all tr elements with class 'categoryTr' that do not have 'id' attribute
				var $categoryTrs = $('.wpProQuiz_modal_window #wpProQuiz_user_content tr.categoryTr:not([id])');

				// Create an empty array to store category names.
				var allCatNames = [];

				// Loop through each categoryTr element.
				$categoryTrs.each( function() {
					// Find the second span element within the th element. BE CAREFUL WITH THIS. 
					// ANY CHANGE IN THE TEMPLATE COULD BREAK OUR LOGIC.
					let $categoryNameSpan = $( this ).find( 'th span:eq(1)' );

					if ( $categoryNameSpan ) {
						// Extract the text content from the span element.
						let categoryName = $categoryNameSpan.text();
						allCatNames.push( categoryName );
					}
				});

				if ( allCatNames.length > 0 && quizProID > 0 ) {
					const data = {
						action:        'ldqcm_get_rec_steps_by_name',
						all_cat_names: JSON.stringify( allCatNames ),
						quiz_pro_id:   quizProID,
						nonce:         ldqcm_front.nonce
					};

					$.ajax( {
						url: ldqcm_front.admin_url,
						data,
						dataType: 'json',
						method: 'POST',
						success: function( response ) {
							if ( $.trim( response.data ) != '' ) {
								$.each( response.data, function( catID, catData ) {
									let catName  = catData.name.replace(/&amp;/g, '&').replace(/&apos;/g, "'").replace(/&quot;/g, '"');
									let $catSpan = $('tr.categoryTr span:contains("' + catName + '")');

									$catSpan.wrapInner('<a href="' + catData.step_link + '" target="_blank"></a>' );
								});
							}
						}
					} );
				}
			}
		});
	});
})(jQuery);

/**
 * jQuery Trigger Event on Show/Hide of Element.
 * Credits: https://www.viralpatel.net/jquery-trigger-custom-event-show-hide-element/
 */
(function ($) {
	$.each(['show', 'hide'], function (i, ev) {
		var el = $.fn[ev];
		$.fn[ev] = function () {
			this.trigger(ev);
			return el.apply(this, arguments);
		};
	});
})(jQuery);
