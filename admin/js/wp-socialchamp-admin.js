(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).on('change', '.socialchampTags', function(){
		let selected = $(this).find(':selected').val();
		let arrLength = ['{excerpt(?)}', '{content(?)}'];
		let arrWords = ['{excerpt(?_words)}', '{content(?_words)}'];

		if (jQuery.inArray( selected, arrLength ) > -1){
			let uInput = prompt("Enter Character Limit");
			selected = selected.replace('?', uInput ?? 0);
		}

		if (jQuery.inArray( selected, arrWords ) > -1){
			let uInput = prompt("Enter Words Limit");
			selected = selected.replace('?', uInput ?? 0);
		}
		let el = $(this).closest('.statusSection').find('.full').find('textarea');
		el.val(
			el.val() + " " + selected
		);
	});

	$(document).on('click', '.socialchampAddStatus', function(){
		let parent = $( "#default-" + $(this).data('key') + "-" + $(this).data('subkey') + "-statuses" );
		let element = $(parent.find('.socialchampStatusCounter:first').clone());
		parent.append(element);
		element.find('textarea').val('{title} {url}');
		element.find('.socialchampImage').val(0);
		element.find('.count').text( '#' + parent.children().length );
		element.find('.socialchampDelete').css( 'display', 'block' );
	});

	$(document).on('click', '.socialchampDelete', function(){
		$(this).closest('.socialchampStatusCounter').remove();
	});

})( jQuery );
