<?php

/**
 * Settings class
 */
if ( ! class_exists( 'WooCommerce_PDF_Invoices_Settings' ) ) {

	class WooCommerce_PDF_Invoices_Settings {
	
		public static $options_page_hook;
		public static $general_settings;
		public static $template_settings;

		public function __construct() {
			add_action( 'admin_menu', array( &$this, 'menu' ) ); // Add menu.
			add_action( 'admin_init', array( &$this, 'init_settings' ) ); // Registers settings
			add_action( 'admin_enqueue_scripts', array( &$this, 'load_scripts_styles' ) ); // Load scripts
			
			// Add links to WordPress plugins page
			add_filter( 'plugin_action_links_'.WooCommerce_PDF_Invoices::$plugin_basename, array( &$this, 'wpo_wcpdf_add_settings_link' ) );
			add_filter( 'plugin_row_meta', array( $this, 'add_support_links' ), 10, 2 );
			
			$this->general_settings = get_option('wpo_wcpdf_general_settings');
			$this->template_settings = get_option('wpo_wcpdf_template_settings');
		}
	
		public function menu() {
			if (class_exists('WPOvernight_Core')) {
				$parent_slug = 'wpo-core-menu';
			} else {
				$parent_slug = 'woocommerce';
			}
			
			$this->options_page_hook = add_submenu_page(
				$parent_slug,
				__( 'PDF Invoices', 'wpo_wcpdf' ),
				__( 'PDF Invoices', 'wpo_wcpdf' ),
				'manage_options',
				'wpo_wcpdf_options_page',
				array( $this, 'settings_page' )
			);
		}
		
		/**
		 * Styles for settings page
		 */
		public function load_scripts_styles ( $hook ) {
			if( $hook != $this->options_page_hook ) 
				return;
			
			wp_enqueue_script( 'wcpdf-upload-js', plugins_url( 'js/media-upload.js' , dirname(__FILE__) ) );
			wp_enqueue_style( 'wpo-wcpdf', WooCommerce_PDF_Invoices::$plugin_url . 'css/style.css' );
			wp_enqueue_media();
		}
	
		/**
		 * Add settings link to plugins page
		 */
		public function wpo_wcpdf_add_settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=wpo_wcpdf_options_page">'. __( 'Settings', 'woocommerce' ) . '</a>';
			array_push( $links, $settings_link );
			return $links;
		}
		
		/**
		 * Add various support links to plugin page
		 * after meta (version, authors, site)
		 */
		public function add_support_links( $links, $file ) {
			if ( !current_user_can( 'install_plugins' ) ) {
				return $links;
			}
		
			if ( $file == WooCommerce_PDF_Invoices::$plugin_basename ) {
				$links[] = '<a href="..." target="_blank" title="' . __( '...', 'wpo_wcpdf' ) . '">' . __( '...', 'wpo_wcpdf' ) . '</a>';
			}
			return $links;
		}
	
		public function settings_page() {
			$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
			?>
	
				<div class="wrap">
					<div class="icon32" id="icon-options-general"><br /></div>
					<h2><?php _e( 'WooCommerce PDF Invoices', 'wpo_wcpdf' ); ?></h2>
					<h2 class="nav-tab-wrapper">  
						<a href="?page=wpo_wcpdf_options_page&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General','wpo_wcpdf') ?></a>  
						<a href="?page=wpo_wcpdf_options_page&tab=template" class="nav-tab <?php echo $active_tab == 'template' ? 'nav-tab-active' : ''; ?>"><?php _e('Template','wpo_wcpdf') ?></a>  
					</h2>

					<?php if (!class_exists('WooCommerce_PDF_IPS_Templates')) {
						$template_url = '<a href="https://wpovernight.com/downloads/woocommerce-pdf-invoices-packing-slips-premium-templates/" target="_blank">wpovernight.com</a>';
						?>
	
						<div class="wcpdf-pro-templates" style="border: 1px solid #ccc; border-radius: 5px; padding: 10px; margin-top: 15px; background-color: #eee;">
							<?php printf( __("Looking for more advanced templates? Check out the Premium PDF Invoice & Packing Slips templates at %s.", 'wpo_wcpdf'), $template_url );?>
						</div>

					<?php } ?>

					<form method="post" action="options.php">
						<?php
							switch ($active_tab) {
								case 'general':
									settings_fields( 'wpo_wcpdf_general_settings' );
									do_settings_sections( 'wpo_wcpdf_general_settings' );
									break;
								case 'template':
									settings_fields( 'wpo_wcpdf_template_settings' );
									do_settings_sections( 'wpo_wcpdf_template_settings' );
									break;
								default:
									settings_fields( 'wpo_wcpdf_general_settings' );
									do_settings_sections( 'wpo_wcpdf_general_settings' );
									break;
							}
	
							submit_button();
						?>
	
					</form>
	
				</div>
	
			<?php
		}
		
		/**
		 * User settings.
		 * 
		 */
		
		public function init_settings() {
			global $woocommerce;
	
			/**************************************/
			/*********** GENERAL SETTINGS *********/
			/**************************************/
	
			$option = 'wpo_wcpdf_general_settings';
		
			// Create option in wp_options.
			if ( false == get_option( $option ) ) {
				add_option( $option );
			}
		
			// Section.
			add_settings_section(
				'general_settings',
				__( 'General settings', 'wpo_wcpdf' ),
				array( &$this, 'section_options_callback' ),
				$option
			);
	
			add_settings_field(
				'download_display',
				__( 'How do you want to view the PDF?', 'wpo_wcpdf' ),
				array( &$this, 'radio_element_callback' ),
				$option,
				'general_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'download_display',
					'options' 		=> array(
						'download'	=> __( 'Download the PDF' , 'wpo_wcpdf' ),
						'display'	=> __( 'Open the PDF in a new browser tab/window' , 'wpo_wcpdf' ),
					),
				)
			);
			
			$tmp_path  = WooCommerce_PDF_Invoices::$plugin_path . 'tmp/';
			$tmp_path_check = !is_writable( $tmp_path );

			add_settings_field(
				'email_pdf',
				__( 'Email invoice (attach to order confirmation or invoice email)', 'wpo_wcpdf' ),
				array( &$this, 'checkbox_element_callback' ),
				$option,
				'general_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'email_pdf',
					'description'	=> $tmp_path_check ? '<span class="wpo-warning">' . sprintf( __( 'It looks like the temp folder (<code>%s</code>) is not writable, check the permissions for this folder! Without having write access to this folder, the plugin will not be able to email invoices.', 'wpo_wcpdf' ), $tmp_path ).'</span>':'',
				)
			);
	
			// Register settings.
			register_setting( $option, $option, array( &$this, 'validate_options' ) );
	
	
			/**************************************/
			/********** TEMPLATE SETTINGS *********/
			/**************************************/
	
			$option = 'wpo_wcpdf_template_settings';
		
			// Create option in wp_options.
			if ( false == get_option( $option ) ) {
				add_option( $option );
			}
	
			// Section.
			add_settings_section(
				'template_settings',
				__( 'PDF Template settings', 'wpo_wcpdf' ),
				array( &$this, 'section_options_callback' ),
				$option
			);

			add_settings_field(
				'template_path',
				__( 'Choose a template', 'wpo_wcpdf' ),
				array( &$this, 'select_element_callback' ),
				$option,
				'template_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'template_path',
					'options' 		=> $this->find_templates(),
					'description'	=> __( 'Want to use your own template? Copy all the files from <code>woocommerce-pdf-invoices-packing-slips/templates/pdf/Simple/</code> to <code>yourtheme/woocommerce/pdf/yourtemplate/</code> to customize them' , 'wpo_wcpdf' ),
				)
			);			

			add_settings_field(
				'paper_size',
				__( 'Paper size', 'wpo_wcpdf' ),
				array( &$this, 'select_element_callback' ),
				$option,
				'template_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'paper_size',
					'options' 		=> array(
						'a4'		=> __( 'A4' , 'wpo_wcpdf' ),
						'letter'	=> __( 'Letter' , 'wpo_wcpdf' ),
					),
				)
			);

			add_settings_field(
				'header_logo',
				__( 'Shop header/logo', 'wpo_wcpdf' ),
				array( &$this, 'media_upload_callback' ),
				$option,
				'template_settings',
				array(
					'menu'							=> $option,
					'id'							=> 'header_logo',
					'uploader_title'				=> __( 'Select or upload your invoice header/logo', 'wpo_wcpdf' ),
					'uploader_button_text'			=> __( 'Set image', 'wpo_wcpdf' ),
					'remove_button_text'			=> __( 'Remove image', 'wpo_wcpdf' ),
					//'description'					=> __( '...', 'wpo_wcpdf' ),
				)
			);

			add_settings_field(
				'shop_name',
				__( 'Shop Name', 'wpo_wcpdf' ),
				array( &$this, 'text_element_callback' ),
				$option,
				'template_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'shop_name',
					'size'			=> '72',
				)
			);

			add_settings_field(
				'shop_address',
				__( 'Shop Address', 'wpo_wcpdf' ),
				array( &$this, 'textarea_element_callback' ),
				$option,
				'template_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'shop_address',
					'width'			=> '28',
					'height'		=> '8',
					//'description'			=> __( '...', 'wpo_wcpdf' ),
				)
			);

			/*
			add_settings_field(
				'personal_notes',
				__( 'Personal notes', 'wpo_wcpdf' ),
				array( &$this, 'textarea_element_callback' ),
				$option,
				'template_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'personal_notes',
					'width'			=> '72',
					'height'		=> '4',
					//'description'			=> __( '...', 'wpo_wcpdf' ),
				)
			);
			 */
	
			add_settings_field(
				'footer',
				__( 'Footer: terms & conditions, policies, etc.', 'wpo_wcpdf' ),
				array( &$this, 'textarea_element_callback' ),
				$option,
				'template_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'footer',
					'width'			=> '72',
					'height'		=> '4',
					//'description'			=> __( '...', 'wpo_wcpdf' ),
				)
			);

			// Section.
			add_settings_section(
				'extra_template_fields',
				__( 'Extra template fields', 'wpo_wcpdf' ),
				array( &$this, 'custom_fields_section' ),
				$option
			);
	
			add_settings_field(
				'extra_1',
				__( 'Extra field 1', 'wpo_wcpdf' ),
				array( &$this, 'textarea_element_callback' ),
				$option,
				'extra_template_fields',
				array(
					'menu'			=> $option,
					'id'			=> 'extra_1',
					'width'			=> '28',
					'height'		=> '8',
					'description'	=> __( 'This is footer column 1 in the <i>Modern (Premium)</i> template', 'wpo_wcpdf' ),
				)
			);

			add_settings_field(
				'extra_2',
				__( 'Extra field 2', 'wpo_wcpdf' ),
				array( &$this, 'textarea_element_callback' ),
				$option,
				'extra_template_fields',
				array(
					'menu'			=> $option,
					'id'			=> 'extra_2',
					'width'			=> '28',
					'height'		=> '8',
					'description'	=> __( 'This is footer column 2 in the <i>Modern (Premium)</i> template', 'wpo_wcpdf' ),
				)
			);

			add_settings_field(
				'extra_3',
				__( 'Extra field 3', 'wpo_wcpdf' ),
				array( &$this, 'textarea_element_callback' ),
				$option,
				'extra_template_fields',
				array(
					'menu'			=> $option,
					'id'			=> 'extra_3',
					'width'			=> '28',
					'height'		=> '8',
					'description'	=> __( 'This is footer column 3 in the <i>Modern (Premium)</i> template', 'wpo_wcpdf' ),
				)
			);

			// Register settings.
			register_setting( $option, $option, array( &$this, 'validate_options' ) );

			// Register defaults if settings empty (might not work in case there's only checkboxes and they're all disabled)
			$option_values = get_option($option);
			if ( empty( $option_values ) )
				$this->default_settings();
		}

		/**
		 * Set default settings.
		 */
		public function default_settings() {
			global $wpo_wcpdf;

			$default_general = array(
				'download_display'	=> 'download',
				'email_pdf'			=> '1',
			);

			$default_template = array(
				'paper_size'		=> 'a4',
				'template_path'		=> $wpo_wcpdf->export->template_default_base_path . 'Simple',
			);

			update_option( 'wpo_wcpdf_general_settings', $default_general );
			update_option( 'wpo_wcpdf_template_settings', $default_template );
		}
		
		// Text element callback.
		public function text_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
			$size = isset( $args['size'] ) ? $args['size'] : '25';
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
		
			$html = sprintf( '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" size="%4$s"/>', $id, $menu, $current, $size );
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
		
			echo $html;
		}
		
		// Text element callback.
		public function textarea_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
			$width = $args['width'];
			$height = $args['height'];
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
		
			$html = sprintf( '<textarea id="%1$s" name="%2$s[%1$s]" cols="%4$s" rows="%5$s"/>%3$s</textarea>', $id, $menu, $current, $width, $height );
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
		
			echo $html;
		}
	
	
		/**
		 * Checkbox field callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return string	  Checkbox field.
		 */
		public function checkbox_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
		
			$html = sprintf( '<input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1"%3$s />', $id, $menu, checked( 1, $current, false ) );
		
			//$html .= sprintf( '<label for="%s"> %s</label><br />', $id, __( 'Activate/Deactivate', 'wpo_wcpdf' ) );
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
		
			echo $html;
		}
		
		/**
		 * Select element callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return string	  Select field.
		 */
		public function select_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
		
			$html = sprintf( '<select id="%1$s" name="%2$s[%1$s]">', $id, $menu );
	
			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label );
			}
	
			$html .= '</select>';
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
		
			echo $html;
		}
		
		/**
		 * Displays a radio settings field
		 *
		 * @param array   $args settings field args
		 */
		public function radio_element_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
		
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
	
			$html = '';
			foreach ( $args['options'] as $key => $label ) {
				$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $menu, $id, $key, checked( $current, $key, false ) );
				$html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s</label><br>', $menu, $id, $key, $label);
			}
			
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
	
			echo $html;
		}

		/**
		 * Media upload callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return string	  Media upload button & preview.
		 */
		public function media_upload_callback( $args ) {
			$menu = $args['menu'];
			$id = $args['id'];
			$options = get_option( $menu );
		
			if ( isset( $options[$id] ) ) {
				$current = $options[$id];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}

			$uploader_title = $args['uploader_title'];
			$uploader_button_text = $args['uploader_button_text'];
			$remove_button_text = $args['remove_button_text'];

			$html = '';
			if( !empty($current) ) {
				$attachment = wp_get_attachment_image_src( $current, 'full', false );
				
				$attachment_src = $attachment[0];
				$attachment_width = $attachment[1];
				$attachment_height = $attachment[2];

				$attachment_resolution = round($attachment_height/(3/2.54));
				
				$html .= sprintf('<img src="%1$s" style="display:block" id="img-%4$s"/>', $attachment_src, $attachment_width, $attachment_height, $id );
				$html .= '<div class="attachment-resolution"><p class="description">'.__('Image resolution').': '.$attachment_resolution.'dpi (default height = 3cm)</p></div>';
				$html .= sprintf('<span class="button remove_image_button" data-input_id="%1$s">%2$s</span>', $id, $remove_button_text );
			}

			$html .= sprintf( '<input id="%1$s" name="%2$s[%1$s]" type="hidden" value="%3$s" />', $id, $menu, $current );
			
			$html .= sprintf( '<span class="button upload_image_button %4$s" data-uploader_title="%1$s" data-uploader_button_text="%2$s" data-remove_button_text="%3$s" data-input_id="%4$s">%2$s</span>', $uploader_title, $uploader_button_text, $remove_button_text, $id );
		
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
		
			echo $html;
		}

		/**
		 * Section null callback.
		 *
		 * @return void.
		 */
		public function section_options_callback() {
		}
		
		/**
		 * Section null callback.
		 *
		 * @return void.
		 */
		public function custom_fields_section() {
			_e( 'These are used for the (optional) footer columns in the <em>Modern (Premium)</em> template, but can also be used for other elements in your custom template' , 'wpo_wcpdf' );
		}

		/**
		 * Validate options.
		 *
		 * @param  array $input options to valid.
		 *
		 * @return array		validated options.
		 */
		public function validate_options( $input ) {
			// Create our array for storing the validated options.
			$output = array();
		
			// Loop through each of the incoming options.
			foreach ( $input as $key => $value ) {
		
				// Check to see if the current option has a value. If so, process it.
				if ( isset( $input[$key] ) ) {
		
					// Strip all HTML and PHP tags and properly handle quoted strings.
					$output[$key] = strip_tags( stripslashes( $input[$key] ) );
					
					// Or alternatively: don't strip HTML! :o)
					//$output[$key] = stripslashes( $input[$key] );
				}
			}
		
			// Return the array processing any additional functions filtered by this action.
			return apply_filters( 'wpo_wcpdf_validate_input', $output, $input );
		}

		/**
		 * List templates in plugin folder, theme folder & child theme folder
		 * @return array		template path => template name
		 */
		public function find_templates() {
			global $wpo_wcpdf;
			$installed_templates = array();

			// get base paths
			$template_paths = array (
					// note the order: child-theme before theme, so that array_unique filters out parent doubles
					'default'		=> $wpo_wcpdf->export->template_default_base_path,
					'child-theme'	=> get_stylesheet_directory() . '/' . $wpo_wcpdf->export->template_base_path,
					'theme'			=> get_template_directory() . '/' . $wpo_wcpdf->export->template_base_path,
				);

			$template_paths = apply_filters( 'wpo_wcpdf_template_paths', $template_paths );

			foreach ($template_paths as $template_source => $template_path) {
				$dirs = glob( $template_path . '*' , GLOB_ONLYDIR);
				
				foreach ($dirs as $dir) {
					if ( file_exists($dir."/invoice.php") && file_exists($dir."/packing-slip.php"))
						$installed_templates[$dir] = basename($dir);
				}
			}

			// remove parent doubles
			$installed_templates = array_unique($installed_templates);

			return $installed_templates;
		}
	
	} // end class WooCommerce_PDF_Invoices_Settings

} // end class_exists