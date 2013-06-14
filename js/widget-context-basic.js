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

		if ( $( 'hgroup input', $widgets_sidebar ).not(':checked').size() ) {
			$('.related-widgets-config', $widgets_sidebar ).hide();
		}

		$( 'hgroup input', this ).on( 'change', function() {
			$('.related-widgets-config', $widgets_sidebar ).slideToggle('fast');
		});
	});

	// Add sortables to widgets inside sidebars
	$('.related-widgets-sidebar .related_widgets').sortable({
		axis: 'y',
		distance: 15,
		placeholder: 'sortable-placeholder'
	});
});