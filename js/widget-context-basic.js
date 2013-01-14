jQuery(window).ready(function($) {

	// Add fancy sidebar collapse thing
	$('.related-widgets-sidebar').each(function() {
		var $widgets_sidebar = this;
		var $sidebar_id = $(this).attr('id').replace('rs-sidebar-', '');
		var $widget_titles = {};

		var ajax_req = {
			action: 'get_widget_titles',
			sidebar_id: $sidebar_id
		};

		$.post( ajaxurl, ajax_req, function( $widget_controls ) {
			$('.widget', $widget_controls).each(function() {
				var $widget_title = $('.widget-content input[id*="-title"]', this).val();
				if ( $widget_title )
					$('.widget-' + $('input.widget-id', this).val() + ' .in-title', $widgets_sidebar).text( ': ' + $widget_title );
			});
		});
	});

	$('#tab-widgets-new .widget').each(function() {
		$('.widget-control-actions', this).prepend('<input type="button" class="button button-primary right add-widget-new" value="Add" />');
	});

	$('#tab-widgets-available .widget').each(function() {
		$('input, textarea, select', this).attr('disabled', 'disabled');
		$('.widget-control-actions', this).prepend('<input rel="'+ $('.widget-id', this).val() +'" type="button" class="button button-primary right add-widget-new" value="Add" />');
	});

	$('.related-widgets-options .replace-widget-area select').change(function() {
		if ( $(this).val().length )
			$( '#' + $(this).attr('rel') + ' .related_widgets' ).slideUp('fast');
		else
			$( '#' + $(this).attr('rel') + ' .related_widgets' ).slideDown('fast');
	}).each(function() {
		if ( $(this).val().length )
			$( '#' + $(this).attr('rel') + ' .related_widgets' ).hide();
	});

	// Add sortables to widgets inside sidebars
	$('.related-widgets-sidebar .related_widgets').sortable({
		axis: 'y',
		distance: 15
	});

	$('.related-widgets-picker').tabs();

	$('.button-add-widget').on( 'click', function() {
		$('.widget-settings').toggleClass('picker-active');	
		/* $('.related-widgets-picker').tabs( 'select', 0 ); */

		return false;
	});

	$('#picker-back').on( 'click', function() {
		$('.widget-settings').toggleClass('picker-active');
		return false;
	});
});