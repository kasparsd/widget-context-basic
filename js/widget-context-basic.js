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
		
		$(this).addClass('collapsed');
		$('.related-widgets-config', this).hide();
		
		$('.toggle, h4', this).click(function() {
			$('.related-widgets-config', $widgets_sidebar).slideToggle('fast');
			$($widgets_sidebar).toggleClass('collapsed');

			$($widgets_sidebar).siblings('.related-widgets-sidebar').each(function() {
				if ( ! $(this).hasClass('collapsed') ) {
					$(this).addClass('collapsed');
					$('.related-widgets-config', this).slideToggle('fast');
				}
			});
		});
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
		distance: 15,
		placeholder: 'sortable-placeholder'
	});
});