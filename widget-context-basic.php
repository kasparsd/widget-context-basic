<?php
/*
 Plugin Name: Widget Context (Basic)
 Plugin URI: http://konstruktors.com
 Description: Show or hide widgets on specific sections of your site
 Version: 0.4
 Author: Kaspars Dambis
 Author URI: http://konstruktors.com
 Text Domain: wivi
 */

new widget_context_basic();

class widget_context_basic {

	var $options = array();

	function widget_context_basic() {
		add_action( 'template_redirect',                 array( $this, 'get_options_frontend' ) );
		add_filter( 'sidebars_widgets',                  array( $this, 'modify_sidebars_and_widgets' ), 20 );
		add_action( 'init',                              array( $this, 'filter_enable_related_widgets' ) );
		add_action( 'admin_init',                        array( $this, 'enable_related_widgets' ) );
		add_action( 'save_post',                         array( $this, 'save_related_widgets' ) );
		add_action( 'wp_ajax_get_widget_titles',         array( $this, 'ajax_get_widget_titles' ) );
		add_action( 'admin_init',                        array( $this, 'add_custom_related_widgets' ) );
		add_action( 'admin_print_styles-edit-tags.php',  array( $this, 'add_admin_scripts' ) );
		add_action( 'admin_print_styles-post.php',  array( $this, 'add_admin_scripts' ) );
		add_action( 'edit_term',                         array( $this, 'save_term_related_widgets' ), 10, 3 );
		add_action( 'delete_term',                       array( $this, 'delete_term_related_widgets' ), 10, 3 );
		add_filter( 'plugins_url',                       array( $this, 'plugins_symlink_fix' ), 10, 3 );
	}

	function plugins_symlink_fix( $url, $path, $plugin ) {
		if ( strstr( $plugin, basename(__FILE__) ) )
			return str_replace( dirname(__FILE__), '/' . basename( dirname( $plugin ) ), $url );

		return $url;
	}

	function add_admin_scripts() {
		wp_enqueue_script( 'widget-context-basic-admin-js', plugins_url( '/js/widget-context-basic.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable' ) );
		wp_enqueue_style( 'widget-context-basic-admin-css', plugins_url( '/js/widget-context-basic.css', __FILE__ ) );
	}

	function add_custom_related_widgets() {
		$taxonomies = get_taxonomies( array( 'public' => true ) );
		
		foreach ( $taxonomies as $taxonomy )
			add_action( $taxonomy . '_edit_form_fields', array( $this, 'show_term_related_widgets' ), 10, 2 );
	}

	function show_term_related_widgets( $tag, $taxonomy ) {
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="related_widgets"><?php _e('Widgets Control'); ?></label>
			</th>
			<td>
				<?php $this->post_type_related_widgets( 'related_widgets_tax_'. $taxonomy . '_' . $tag->term_id ); ?>
			</td>
		</tr>
		<?php
	}

	function save_term_related_widgets( $term_id, $tt_id, $taxonomy ) {
		if ( ! isset( $_POST['related_widgets'] ) )
			return;

		if ( ! add_option( 'related_widgets_tax_'. $taxonomy . '_' . $term_id, $_POST['related_widgets'], null, 'no' ) )
			update_option( 'related_widgets_tax_'. $taxonomy . '_' . $term_id, $_POST['related_widgets'] );
	}

	function delete_term_related_widgets( $term_id, $tt_id, $taxonomy ) {
		delete_option( 'related_widgets_tax_'. $taxonomy . '_' . $term_id );
	}

	function get_options_frontend() {
		global $wp_query;
		
		if ( is_category() )
			$this->options = get_option( sprintf( 'related_widgets_tax_category_%s', $wp_query->get_queried_object_id() ) );
		elseif ( is_tag() )
			$this->options = get_option( sprintf( 'related_widgets_tax_post_tag_%s', $wp_query->get_queried_object_id() ) );
		elseif ( is_tax() )
			$this->options = get_option( sprintf( 'related_widgets_tax_%s_%s', $wp_query->get('taxonomy'), $wp_query->get_queried_object_id() ) );
		else if ( isset( $wp_query->post ) )
			$this->options = get_post_meta( $wp_query->post->ID, 'related_widgets', true );
	}

	function modify_sidebars_and_widgets( $sidebars_widgets ) {
		if ( empty( $this->options ) || is_admin() )
			return $sidebars_widgets;

		// Reorder or hide widets inside widget areas
		foreach ( $sidebars_widgets as $sidebar_id => $widgets )
			if ( isset( $this->options['sidebars'][ $sidebar_id ]['enabled'] ) )
				$sidebars_widgets[ $sidebar_id ] = array_diff( (array) $this->options['sidebars'][ $sidebar_id ]['widgets'], (array) $this->options['sidebars'][ $sidebar_id ]['hidden'] );

		$replaced_sidebars = array();

		// Replace widget areas
		foreach ( $sidebars_widgets as $sidebar_id => $widgets )
			if ( isset( $this->options['sidebars'][ $sidebar_id ]['enabled'] ) && isset( $this->options['sidebars'][ $sidebar_id ]['replace'] ) && ! empty( $this->options['sidebars'][ $sidebar_id ]['replace'] ) )
				$replaced_sidebars[ $sidebar_id ] = $sidebars_widgets[ $this->options['sidebars'][ $sidebar_id ]['replace'] ];
			else
				$replaced_sidebars[ $sidebar_id ] = $widgets;

		return $replaced_sidebars;
	}

	function save_related_widgets( $post_id ) {
		if ( wp_is_post_revision( $post_id ) )
			return;
		
		if ( isset( $_POST['related_widgets'] ) && current_user_can( 'edit_pages' ) )
			update_post_meta( $post_id, 'related_widgets', $_POST['related_widgets'] );
	}

	function filter_enable_related_widgets() {
		global $wp_registered_sidebars;

		$related_widgets_enabled = apply_filters( 'enable_widget_control', array() );

		foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar )
			//if ( in_array( $sidebar_id, $related_widgets_enabled ) )
				$wp_registered_sidebars[ $sidebar_id ][ 'enable_related_widgets' ] = true;
	}

	function enable_related_widgets() {
		$post_types = get_post_types( array( 'public' => true ) );

		if ( empty( $post_types ) || ! current_user_can( 'edit_pages' ) )
			return;

		foreach ( $post_types as $post_type )
			add_meta_box( 
				'related-widgets', 
				__( 'Widget Settings' ), 
				array( $this, 'post_type_related_widgets' ), 
				$post_type, 
				'normal' 
			);
	}

	function post_type_related_widgets( $post ) {
		global $wp_registered_sidebars;
		
		// We are on post edit screen
		if ( is_object( $post ) )
			$this->options = get_post_meta( $post->ID, 'related_widgets', true );
		else
			$this->options = get_option( $post );

		$sidebar_options = array();

		foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar ) {
			if ( ! isset( $sidebar['enable_related_widgets'] ) )
				continue;

			if ( ! empty( $this->options ) && isset( $this->options['sidebars'][ $sidebar_id ]['enabled'] ) )
				$related_enabled = ' checked="checked" ';
			else
				$related_enabled = '';

			$sidebar_options[] = sprintf( 
					'<div class="related-widgets-sidebar" id="rs-sidebar-%1$s">
						<hgroup>
							<h4>%2$s</h4>
							<label class="related-widgets-enable"><input type="checkbox" name="related_widgets[sidebars][%1$s][enabled]" value="1" %4$s rel="rs-sidebar-%1$s" /> %3$s</label>
							<span class="toggle" rel="rs-sidebar-%1$s"></span>
						</hgroup>
						<div class="related-widgets-config">
							%5$s
						</div>
					</div>', 
					$sidebar_id, 
					$sidebar['name'],
					__( 'Enable' ),
					$related_enabled,
					$this->show_related_widgets_for_sidebar( $sidebar_id, $post )
				);
		}

		if ( empty( $sidebar_options ) ) {
			$sidebar_options[] = sprintf( '<p>%s</p>', __('Please define which sidebars should be configurable. See plugin docs for more information.') );
			return;
		}

		printf( 
				'<div class="related-widgets-options">
					%s
					<p class="edit-widgets-link">%s</p>
				</div>', 
				implode( '', $sidebar_options ),
				sprintf( '<a href="%s">%s</a>', admin_url( 'widgets.php' ), __('Manage Widgets') )
			);
	}

	function show_related_widgets_for_sidebar( $sidebar_id, $post ) {
		global $wp_registered_sidebars, $wp_registered_widgets;

		$sidebar = $wp_registered_sidebars[ $sidebar_id ];
		$all_widgets = wp_get_sidebars_widgets();

		if ( empty( $all_widgets[ $sidebar_id ] ) )
			return sprintf( '<p>%s</p>', __( 'No widgets available.' ) );

		/**
		 * Set widget visiblity and order
		 */

		$widget_dropdown = array( sprintf( '<option value="">%s</option>', __( 'Choose Widget' ) ) );
		$widget_list = array();

		if ( ! empty( $this->options ) && isset( $this->options['sidebars'][ $sidebar_id ]['enabled'] ) )
			$all_widgets[ $sidebar_id ] = array_intersect_key( $this->options['sidebars'][ $sidebar_id ]['widgets'], $all_widgets[ $sidebar_id ] ) 
											+ array_diff_key( $this->options['sidebars'][ $sidebar_id ]['widgets'], $all_widgets[ $sidebar_id ] );

		foreach ( $all_widgets[ $sidebar_id ] as $widget_id ) {
			$widget_dropdown[] = sprintf( '<option value="%s">%s</option>', $widget_id, $widget_id );

			$hidden_enabled = '';

			if ( ! empty( $this->options ) && isset( $this->options['sidebars'][ $sidebar_id ]['hidden'] ) && isset( $this->options['sidebars'][ $sidebar_id ]['enabled'] ) )
				if ( in_array( $widget_id, $this->options['sidebars'][ $sidebar_id ]['hidden'] ) )
					$hidden_enabled = ' checked="checked" ';
			
			$widget_list[] = sprintf( 
								'<li class="widget-%1$s">
									<strong>%3$s</strong><span class="in-title"></span>
									<input type="hidden" name="related_widgets[sidebars][%2$s][widgets][]" value="%1$s" />
									<label class="widget-hide"><input type="checkbox" name="related_widgets[sidebars][%2$s][hidden][]" value="%1$s" %5$s /> %4$s</label>
								</li>',
								$widget_id,
								$sidebar_id,
								$wp_registered_widgets[ $widget_id ]['name'], 
								__( 'Hide' ),
								$hidden_enabled
							);
		}


		/**
		 * Replace Widget Area
		 */

		$sidebar_dropdown = array();

		foreach ( $wp_registered_sidebars as $sidebar )
			$sidebar_dropdown[ $sidebar['id'] ] = array( 
									sprintf( '<option value="%s">%s</option>', $sidebar['id'], $sidebar['name'] ),
									sprintf( '<option value="%s" selected="selected">%s</option>', $sidebar['id'], $sidebar['name'] )
								);

		$sidebar_dropdowns = array( sprintf( '<option value="">%s</option>', __('None') ) );

		foreach ( $sidebar_dropdown as $sid => $s_tmp )
			if ( ! empty( $this->options ) && isset( $this->options['sidebars'][ $sidebar_id ]['replace'] ) && $sid == $this->options['sidebars'][ $sidebar_id ]['replace'] )
				$sidebar_dropdowns[ $sid ] = $sidebar_dropdown[ $sid ][ 1 ];
			elseif ( $sid !== $sidebar_id )
				$sidebar_dropdowns[ $sid ] = $sidebar_dropdown[ $sid ][ 0 ];

		return sprintf( 
					'<!--<p class="add-widget">
						<select class="related_widgets">%s</select> 
						<input type="button" class="button" value="%s" />
					</p>-->
					<ul class="related_widgets">
						%s
					</ul>
					<p class="replace-widget-area">
						<label>
							<strong>%s</strong> 
							<select name="related_widgets[sidebars][%s][replace]" rel="rs-sidebar-%5$s">%s</select>
						</label>
					</p>
					',
					implode( '', $widget_dropdown ),
					__( 'Add' ),
					implode( '', $widget_list ),
					__( 'Replace widget area with:' ),
					$sidebar_id,
					implode( '', $sidebar_dropdowns )
				);
	}


	// There is no clean way for retrieving widget titles, because
	// they are generated on the fly (think echo instead of return).
	function ajax_get_widget_titles() {
		/** WordPress Administration Widgets API */
		require_once( ABSPATH . 'wp-admin/includes/widgets.php' );
		
		if ( ! isset( $_POST['sidebar_id'] ) )
			return;

		ob_start();
			wp_list_widget_controls( strval( $_POST['sidebar_id'] ) );
			$widget_html = ob_get_contents();
 		ob_end_clean();

		die( $widget_html );
	}

}

