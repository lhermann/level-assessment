(function($) {
	$(document).ready(function(){

		/* --------------------------------------------- *
		 * Functions for the Level Assessment Entry Page *
		 * --------------------------------------------- */

		/*
		 * Validate the Form
		 */
		$('.la-form').validator({delay: 1500});



		/* -------------------------------------------- *
		 * Functions for the Level Assessment Test Page *
		 * -------------------------------------------- */

		/*
		 * Which level to show the tooltip and wiggle
		 * tooltip[0] = wiggle level
		 * tooltip[1] = tooltip level
		 */
		tooltip = [ 0, 0 ];

		/*
		 * Update the $SESSION when closing the alert box
		 */
		$('#LevelAssessmentAlert').on('close.bs.alert', function () {
			$.post(
				ajaxurl,
				{ 'action': 'update_session', 'data': { 'level_assessment_alert' : 'hide' } },
				function(response){ },
				'json'
			);
		});

		/*
		 * Action taken when the Modal is activated
		 * -> Get language and level and load the content
		 */
		$('#LevelAssessmentModal').on('show.bs.modal', function (event) {
			LA_Tooltip( 'hide' ); // hide tooltip
			$('#LAStartTooltip').fadeOut(); // Fade out the black tooltip "Click here to start"

			// Extract data-* attributes from clicked button
			button = $(event.relatedTarget); // Button that triggered the modal
			language = button.data('language');
			level = button.data('level');

			// AJAX call
			var modal = $(this);
			updateModal ( modal, language, level, false, 'open' );

		}).on('hidden.bs.modal', function (event) {
			/** Action taken when the Modal is closed **/
			LA_Tooltip( 'show' ); // show tooltip and make the next level-button wiggle

			EnableNextButton( false, false ); // Always disable Next Button when closing the modal
			$(this).find('.modal-body').removeClass( 'preloader' ); // Turn off preloader
		});

		/*
		 * Add an active class to a fill-in-the-blank select field, so it turns green (and activates next button)
		 * Comment: Liz basically never uses this kind of button
		 */
		$( "#LevelAssessmentModal" ).on( "change", "#LAChoice", function() {
			EnableNextButton( true, true );
			if( parseInt( $( this ).attr( 'value' ) ) === 0 ) {
				$( this ).removeClass( 'active' );
			} else {
				$( this ).addClass( 'active' );
			};
		});

		/*
		 * Action taken when the next button is clicked
		 */
		$( "#LANextButton" ).on( "click", function() {

			EnableNextButton( false, true );

			// Get selected value and do the AJAX call
			var fillBlank		= $( '#LAChoice' ).attr( 'value' );
			var multipleChoice 	= $( '#LAChoice .active input' ).data( 'value' );
			var chosenAnswer 	= ( fillBlank === undefined ? multipleChoice : fillBlank )
			updateModal ( $('#LevelAssessmentModal'), language, level, chosenAnswer, 'next' );

		});

		/*
		 * Add an active class to a fill-in-the-blank select field, so it turns green (and activates next button)
		 * Comment: Liz basically never uses this kind of button
		 */
		$( "#LevelAssessmentModal" ).on( "click", "#LASignupButton", function(event) {
			EnableNextButton( false, false );
			level = $('#LASignupButton').data('level');
			updateModal ( $('#LevelAssessmentModal'), language, level, false, 'signup' );
		});

		/*
		 * Function to do the AJAX call for the Modal content
		 *
		 * Response Array:
		 * 	print				-> HTML output
		 *	questions-current	-> (int)
		 * 	questions-total		-> (int)
		 * 	progressbar-percent	-> (int) Progress bar Percentage
		 * 	progressbar-label	-> HTML for the Progress Bar
		 *	title				-> Modal Title
		 * 	level-passed		-> (bool)
		 * 	next-button			-> (bool) true (next button active) or false (next button deactivated)
		 */
		function updateModal ( modal, language, level, chosenAnswer, interaction ) {
			modal.find('.modal-body').addClass( 'preloader' ); // Activate preloader

			// Create an array with all the data
			var data = {
				language: language,
				level: level,
				interaction: ( interaction ? interaction : 'open' )
			};
			if ( chosenAnswer ) {
				data['chosenAnswer'] = chosenAnswer;
			}

			// AJAX call
			$.post(
				ajaxurl,
				{
					'action': 'get_la_question',
					'data': data
				},
				function(response){
					modal.find('.modal-body').removeClass( 'preloader' ); // Deactivate preloader
					modal.find('#LALabel').html( response['title'] ); // Update the title
					modal.find('.modal-body-inner').html( response['print'] ); // Update the content
					modal.find('#LAProgressBar .progress-bar').attr( 'aria-valuenow', response['progressbar'] ).width( response['progressbar']+'%' ).html( response['progressbar-label'] ); // Update the progress bar

					// Next Button
					EnableNextButton( response['enable-next-btn'], response['unlock-next-btn'] );

					// Progress Saved Message
					ShowProgressSaved ( modal, response['show-saved-info'] );

					// Update Level Buttons

					tooltip = [ 100, 100 ]; // reset toltip and wiggle
					response['level-index'].forEach( UpdateLevelButtons );
				},
				'json'
			);

		};

		/*
		 * Function to disable and enable next button
		 */
		/*function DisableNextButton ( a ) {
			a = typeof a !== 'undefined' ? a : true;
			$('#LANextButton').prop( 'disabled', a );
		} OBSOLET*/
		function EnableNextButton ( enabled, unlocked ) {
			enabled = typeof enabled !== 'undefined' ? enabled : true;
			unlocked = typeof unlocked !== 'undefined' ? unlocked : true;
			//console.log( 'EnableNextButton ' + enabled + ' ' + unlocked );
			var toRemove = unlocked ? 'btn-default' : 'btn-success';
			var toAdd = unlocked ? 'btn-success' : 'btn-default';
			$('#LANextButton').prop( 'disabled', !enabled ).removeClass( toRemove ).addClass( toAdd );
			// Highligt Close Button if Next Button is greyed out
			$('#LACloseButton').removeClass( toAdd ).addClass( toRemove );
		}


		function ShowProgressSaved ( modal, show ) {
			if( show ) {
				modal.find('.la-progress-saved').removeClass( 'hidden' );
			} else {
				modal.find('.la-progress-saved').addClass( 'hidden' );
			}
		}

		/*
		 * update/enable/hide level-buttons
		 * @since 1.0
		 * @update 1.2
		 */
		function UpdateLevelButtons( element, key, array ) {
			var lvlp = $( "p#level-"+key );
			var lvlBridge = $( "p#level-"+key+".button-level" );
			var lvlButton = lvlBridge.find( ".btn" );
			//console.log( lvlBridge );
			if( element >= 1 ) {
				lvlp.next( ".button-notice" ).removeClass( "hidden" )
				lvlButton.prop( "disabled", false ).removeClass();
				tooltip[0] = key;
			}
			if( element === 1 ) tooltip[1] = key;
			// Level Buttons
			switch ( element ) {
				case 1: lvlButton.addClass( "btn btn-primary btn-lg" ); break;
				case 2: lvlButton.addClass( "btn btn-success btn-lg" ); break;
				case 3: lvlButton.addClass( "btn btn-success btn-lg" ); break;
			}
			// Level Bridge
			switch ( element ) {
				case 2: lvlBridge.addClass( "level-passed" ); break;
				case 3: lvlBridge.addClass( "level-failed" ); break;
			}
		}

		/*
		 * Show and Hide the Tooltip (Klick here to Start) and wiggle the level
		 * @since 1.2
		 */
		function LA_Tooltip( action ) {
			console.log( tooltip );
			switch ( action ) {
				case 'show':
					$( '#la-button-tooltip-'+tooltip[1] ).fadeIn();
					$( "#level-"+tooltip[0]+" .btn" )
						.animate({ padding: "10px 40px" })
						.animate({ padding: "10px 16px" });
					break;
				case 'hide':
				default:
					$( '#la-button-tooltip-'+tooltip[1] ).fadeOut();
					break;
			}
		}

		/*
		 * Better Time Select Event Handler
		 */
		$( "#LevelAssessmentModal" ).on( "change", "#LASelectTimes", function() {
			var container = $( '.la-better-time-container' );
			container.find( '.preloader-box' ).removeClass( 'hidden' );
			container.find( '.dashicons-yes' ).addClass( 'hidden' );
			$.post(
				ajaxurl,
				{
					'action': 'la_better_time',
					'data': { value: $(this).attr('value'), level: $(this).data('level') }
				},
				function(response){
					container.find( '.preloader-box' ).addClass( 'hidden' );
					container.find( '.dashicons-yes' ).removeClass( 'hidden' );
					if( response['status'] == 'false' ) console.log( 'Error when saving the time' );
				},
				'json'
			);
			console.log( $( this ).attr( 'value' ) );
		});

	});
})( jQuery );
