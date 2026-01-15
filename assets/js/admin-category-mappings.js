(function($) {

	$(document).ready(function() {

		var coursesSteps = [];

		const commonErrorMessage = "Something went wrong. Please refresh the page and try again. If you see this message frequently, contact the developer.";

		$(window).bind('beforeunload', function() {
			// If the user is editing any data on the page, that means at least one '.edit-mode' element is visible on the page.
			if ( $( '.edit-mode' ).is( ':visible' ) ) {
				return 'Are you sure you want to leave?';
			}
		});

		// Click on the "edit" or "cancel" actions.
		$(".categories .actions .edit, .categories .actions .cancel").click(function() {
			const parentTr = $(this).closest("tr");
			parentTr.find(".show-mode, .edit-mode").toggle();
			return false;
		});

		// To make life easier for the editors. Generally, they will edit categories for a specific quiz, 
		// so we can help programmatically selecting a course option.
		$(".categories .actions .edit").click(function() {
			const parentTr = $(this).closest("tr");
			const catID    = parentTr.attr("data-cat_id");
			const prevTr   = parentTr.prev();

			if ( 'undefined' !== typeof prevTr ) {
				const prevCatID = prevTr.attr("data-cat_id");
				const prevCourseId = $("#course-" + prevCatID).val();

				if ( 'undefined' !== typeof prevCourseId ) {
					$("#course-" + catID).val( prevCourseId ).change();
				}
			}
		});
	
		// After selecting a course from the dropdown.
		$( '.categories .course-selector' ).change( function( e ) {
			const parentTr = $(this).closest("tr");
			const courseId = $(this).val();
			const catID    = parentTr.attr("data-cat_id");

			if ( 0 == courseId ) {
				return false;
			}

			if ( typeof coursesSteps[courseId] !== 'undefined' ) {
				setLessons( catID, courseId );
			} else {
				const data = {
					action: 'ldqcm_lesson_rec_course_steps',
					course_id: courseId,
				};
	
				$.ajax( {
					url: wp.ajax.settings.url,
					data,
					dataType: 'json',
					method: 'POST',
					success: function( response ) {
						if ( $.trim( response ) != '' ) {
							coursesSteps[ courseId ] = response;
							setLessons( catID, courseId );
						} else {
							alert( commonErrorMessage );
						}
					},
					error: function() {
						alert( commonErrorMessage );
					}
				} );
			}
		} );

		// Saving the data.
		$( '.categories .actions .save' ).click( function() {
			const parentTr = $(this).closest("tr");
			const catID    = parentTr.attr("data-cat_id");
			const courseId = $( "#course-" + catID ).val();
			const lessonId = $( "#lesson-" + catID ).val();
			const topicId  = $( "#topic-" + catID ).val();

			if ( undefined === catID || '' === catID ) {
				alert( commonErrorMessage );
				return false;
			}
			if ( 0 == courseId || 0 === lessonId ) {
				alert( "Required data missing." );
				return false;
			}

			const data = {
				action: 'ldqcm_lesson_rec_cat_meta',
				cat_id: catID,
				course_id: courseId,
				lesson_id: lessonId,
				topic_id: topicId,
			};

			$.ajax( {
				url: wp.ajax.settings.url,
				data,
				dataType: 'json',
				method: 'POST',
				success: function( response ) {
					if ( response.success ) {
						// set course title.
						courseTitle = ( courseId > 0 ) ? $("#course-" + catID + " :selected").text() : '-';
						$("#course-title-" + catID ).html( courseTitle );

						// set lesson title.
						lessonTitle = ( lessonId > 0 ) ? $("#lesson-" + catID + " :selected").text() : '-';
						$("#lesson-title-" + catID ).html( lessonTitle );

						// set topic title.
						topicTitle = ( topicId > 0 ) ? $("#topic-" + catID + " :selected").text() : '-';
						$("#topic-title-" + catID ).html( topicTitle );

						parentTr.find(".show-mode, .edit-mode").toggle();
						parentTr.removeClass("not-mapped");
						parentTr.find(".step-link").html( '<a href="' + response.data.step_link +'" target="_blank"><span class="dashicons dashicons-external"></span></a>');
					} else {
						alert( commonErrorMessage );
					}
				},
				error: function() {
					alert( commonErrorMessage );
				}
			} );
			return false;
		});

		/**
		 * After changing the lesson value from the dropdown.
		 */
		$( '.categories .lesson-selector' ).change( function() {
			const parentTr = $(this).closest("tr");
			const catID    = parentTr.attr("data-cat_id");
			const courseId = $( "#course-" + catID ).val();
			const lessonId = $(this).val();

			setTopics( catID, courseId, lessonId );	
		});

		// Click on the "unlink" action.
		$(".categories .actions .unlink").click(function() {
			const parentTr = $(this).closest("tr");
			const catID    = parentTr.attr("data-cat_id");
			const catName  = parentTr.find(".row-category .category-name").text();
			
			if ( confirm( "Are you sure to unlink " + catName + " category?" ) ) {
				const data = {
					action: 'ldqcm_lesson_clear_rec_cat_meta',
					cat_id: catID,
				};
	
				$.ajax( {
					url: wp.ajax.settings.url,
					data,
					dataType: 'json',
					method: 'POST',
					success: function( response ) {
						if ( response.success ) {
							// erase course title.
							$("#course-" + catID ).val(0);
							$("#course-title-" + catID ).html( '-' );
	
							// erase lesson title.
							$("#lesson-" + catID ).val(0);
							$("#lesson-title-" + catID ).html( '-' );
	
							// erase topic title.
							$("#topic-" + catID ).val(0);
							$("#topic-title-" + catID ).html( '-' );
	
							parentTr.addClass("not-mapped");
							parentTr.find(".step-link").html( '');
						} else {
							alert( commonErrorMessage );
						}
					},
					error: function() {
						alert( commonErrorMessage );
					}
				} );
			}
			parentTr.find(".show-mode, .edit-mode").toggle();
			return false;
		});

		/**
		 * Empties the current options and appends new.
		 * 
		 * @param int catID    Pro Category ID.
		 * @param int courseId Course ID.
		 */
		function setLessons( catID, courseId ) {
			$("#lesson-" + catID ).empty();
			let option = $( '<option></option>' ).attr( "value", 0 ).text( "--Select--" );
			$("#lesson-" + catID ).append(option);

			if ( typeof coursesSteps[ courseId ] !== 'undefined' ) {
				$.each( coursesSteps[ courseId ], function( lessonId, lessonData ) {
					let option = $( '<option></option>' ).attr( "value", lessonId ).text( lessonData.title );
					$("#lesson-" + catID ).append(option);
				});
			}
		}

		/**
		 * Empties the current options for topic's dropdown and appends new.
		 * 
		 * @param int catID    Pro Category ID.
		 * @param int courseId Course ID.
		 * @param int lessonId Lesson ID.
		 */
		function setTopics( catID, courseId, lessonId ) {
			$("#topic-" + catID ).empty();
			let option = $( '<option></option>' ).attr( "value", 0 ).text( "--Select--" );
			$("#topic-" + catID ).append(option);

			if ( 0 != lessonId && 0 != courseId && typeof coursesSteps[ courseId ] !== 'undefined' && typeof coursesSteps[ courseId ][ lessonId ] !== 'undefined' ) {
				$.each( coursesSteps[ courseId ][ lessonId ][ 'topics' ], function( topicId, topicData ) {
					let option = $( '<option></option>' ).attr( "value", topicId ).text( topicData.title );
					$("#topic-" + catID ).append(option);
				});
			}
		}
	});

})(jQuery);
