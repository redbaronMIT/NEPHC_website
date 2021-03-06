<?php

if ( ! class_exists( 'NavyBlue_Theme_Setup' ) ) {

	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * @since 1.0.0
	 */
	class NavyBlue_Theme_Setup {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * A reference to an instance of cherry framework core class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private $core = null;

		/**
		 * Holder for CSS layout scheme.
		 *
		 * @since 1.0.0
		 * @var   array
		 */
		public $layout = array();

		/**
		 * Holder for current customizer module instance.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		public $customizer = null;

		/**
		 * Holder for current dynamic_css module instance.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		public $dynamic_css = null;

		/**
		 * Sets up needed actions/filters for the theme to initialize.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// Set the constants needed by the theme.
			add_action( 'after_setup_theme', array( $this, 'constants' ), -1 );

			// Load the installer core.
			add_action( 'after_setup_theme', require( trailingslashit( get_template_directory() ) . 'cherry-framework/setup.php' ), 0 );

			// Load the core functions/classes required by the rest of the theme.
			add_action( 'after_setup_theme', array( $this, 'get_core' ), 1 );

			// Language functions and translations setup.
			add_action( 'after_setup_theme', array( $this, 'l10n' ), 2 );

			// Handle theme supported features.
			add_action( 'after_setup_theme', array( $this, 'theme_support' ), 3 );

			// Load the theme includes.
			add_action( 'after_setup_theme', array( $this, 'includes' ), 4 );

			// Initialization of modules.
			add_action( 'after_setup_theme', array( $this, 'init' ), 10 );

			// Load admin files.
			add_action( 'wp_loaded', array( $this, 'admin' ), 1 );

			// Enqueue admin assets.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

			// Register public assets.
			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 9 );

			// Enqueue public assets.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 12 );

		}

		/**
		 * Defines the constant paths for use within the core and theme.
		 *
		 * @since 1.0.0
		 */
		public function constants() {
			global $content_width;

			/**
			 * Fires before definitions the constants.
			 *
			 * @since 1.0.0
			 */
			do_action( 'navyblue_constants_before' );

			$template  = get_template();
			$theme_obj = wp_get_theme( $template );

			/** Sets the theme version number. */
			define( 'NAVYBLUE_THEME_VERSION', $theme_obj->get( 'Version' ) );

			/** Sets the theme directory path. */
			define( 'NAVYBLUE_THEME_DIR', get_template_directory() );

			/** Sets the theme directory URI. */
			define( 'NAVYBLUE_THEME_URI', get_template_directory_uri() );

			/** Sets the path to the core framework directory. */
			defined( 'CHERRY_DIR' ) or define( 'CHERRY_DIR', trailingslashit( NAVYBLUE_THEME_DIR ) . 'cherry-framework' );

			/** Sets the path to the core framework directory URI. */
			defined( 'CHERRY_URI' ) or define( 'CHERRY_URI', trailingslashit( NAVYBLUE_THEME_URI ) . 'cherry-framework' );

			/** Sets the theme includes paths. */
			define( 'NAVYBLUE_THEME_CLASSES', trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/classes' );
			define( 'NAVYBLUE_THEME_WIDGETS', trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/widgets' );
			define( 'NAVYBLUE_THEME_EXT', trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/extensions' );

			/** Sets the theme assets URIs. */
			define( 'NAVYBLUE_THEME_CSS', trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/css' );
			define( 'NAVYBLUE_THEME_JS', trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/js' );

			// Sets the content width in pixels, based on the theme's design and stylesheet.
			if ( ! isset( $content_width ) ) {
				$content_width = 1170;
			}
		}

		/**
		 * Loads the core functions. These files are needed before loading anything else in the
		 * theme because they have required functions for use.
		 *
		 * @since  1.0.0
		 */
		public function get_core() {
			/**
			 * Fires before loads the core theme functions.
			 *
			 * @since 1.0.0
			 */
			do_action( 'navyblue_core_before' );

			global $chery_core_version;

			if ( null !== $this->core ) {
				return $this->core;
			}

			if ( 0 < sizeof( $chery_core_version ) ) {
				$core_paths = array_values( $chery_core_version );

				require_once( $core_paths[0] );
			}else{
				die('Class Cherry_Core not found');
			}

			$this->core = new Cherry_Core( array(
				'base_dir' => CHERRY_DIR,
				'base_url' => CHERRY_URI,
				'modules'  => array(
					'cherry-js-core' => array(
						'priority' => 999,
						'autoload' => true,
					),
					'cherry-ui-elements' => array(
						'priority' => 999,
						'autoload' => false,
					),
					'cherry-interface-builder' => array(
						'autoload' => false,
					),
					'cherry-utility' => array(
						'priority' => 999,
						'autoload' => true,
						'args'     => array(
							'meta_key' => array(
								'term_thumb' => 'cherry_terms_thumbnails'
							),
						)
					),
					'cherry-widget-factory' => array(
						'priority' => 999,
						'autoload' => true,
					),
					'cherry-post-formats-api' => array(
						'priority' => 999,
						'autoload' => true,
						'args'     => array(
							'rewrite_default_gallery' => true,
							'gallery_args'            => array(
								'size'           => 'navyblue-thumb-l',
								'base_class'     => 'post-gallery',
								'container'      => '<div class="%2$s swiper-container" id="%4$s" %3$s><div class="swiper-wrapper">%1$s</div><div class="swiper-button-prev"><i class="material-icons">navigate_before</i></div><div class="swiper-button-next"><i class="material-icons">navigate_next</i></div></div>',
								'slide'          => '<figure class="%2$s swiper-slide">%1$s</figure>',
								'img_class'      => 'swiper-image',
								'slider_handle'  => 'jquery-swiper',
								'slider'         => 'sliderPro',
								'slider_init'    => array(
									'buttons' => false,
									'arrows'  => true,
								),
								'popup'          => 'magnificPopup',
								'popup_handle'   => 'magnific-popup',
								'popup_init'     => array(
									'type' => 'image',
								),
							),
							'image_args' => array(
								'size'         => 'navyblue-thumb-l',
								'popup'        => 'magnificPopup',
								'popup_handle' => 'magnific-popup',
								'popup_init'   => array(
									'type' => 'image',
								),
							),
						),
					),
					'cherry-customizer' => array(
						'priority' => 999,
						'autoload' => false,
					),
					'cherry-dynamic-css' => array(
						'priority' => 999,
						'autoload' => false,
					),
					'cherry-google-fonts-loader' => array(
						'priority' => 999,
						'autoload' => false,
					),
					'cherry-term-meta' => array(
						'priority' => 999,
						'autoload' => false,
					),
					'cherry-post-meta' => array(
						'priority' => 999,
						'autoload' => false,
					),
					'cherry-breadcrumbs' => array(
						'priority' => 999,
						'autoload' => false,
					),
				),
			) );

			return $this->core;
		}

		/**
		 * Loads the theme translation file.
		 *
		 * @since 1.0.0
		 */
		public function l10n() {
			/*
			 * Make theme available for translation.
			 * Translations can be filed in the /languages/ directory.
			 */
			load_theme_textdomain( 'navyblue', trailingslashit( NAVYBLUE_THEME_DIR ) . 'languages' );
		}

		/**
		 * Adds theme supported features.
		 *
		 * @since 1.0.0
		 */
		public function theme_support() {

			// Enable support for Post Thumbnails on posts and pages.
			add_theme_support( 'post-thumbnails' );

			// Enable HTML5 markup structure.
			add_theme_support( 'html5', array(
				'comment-list', 'comment-form', 'search-form', 'gallery', 'caption',
			) );

			// Enable default title tag.
			add_theme_support( 'title-tag' );

			// Enable post formats.
			add_theme_support( 'post-formats', array(
				'aside', 'gallery', 'image', 'link', 'quote', 'video', 'audio', 'status',
			) );

			// Enable custom background.
			add_theme_support( 'custom-background', array( 'default-color' => 'ffffff', ) );

			// Add default posts and comments RSS feed links to head.
			add_theme_support( 'automatic-feed-links' );

			// timetable
			add_theme_support('mp-timetable');
		}

		/**
		 * Loads the theme files supported by themes and template-related functions/classes.
		 *
		 * @since 1.0.0
		 */
		public function includes() {
			/**
			 * Configurations.
			 */
			require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'config/layout.php';
			require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'config/menus.php';
			require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'config/sidebars.php';
			require_if_theme_supports( 'post-thumbnails', trailingslashit( NAVYBLUE_THEME_DIR ) . 'config/thumbnails.php' );

			/**
			 * Functions.
			 */
			if ( ! is_admin() ) {
				require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/template-tags.php';
				require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/template-menu.php';
				require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/template-meta.php';
				require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/template-comment.php';
				require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/extras.php';
			}

			require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'builder/tm-content-builder-extras.php';

			require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/context.php';
			require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/customizer.php';
			require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/hooks.php';
			require_once trailingslashit( NAVYBLUE_THEME_DIR ) . 'inc/register-plugins.php';

			/**
			 * Widgets.
			 */
			require_once trailingslashit( NAVYBLUE_THEME_WIDGETS ) . 'about/class-about-widget.php';
			require_once trailingslashit( NAVYBLUE_THEME_WIDGETS ) . 'about-author/class-about-author-widget.php';
			require_once trailingslashit( NAVYBLUE_THEME_WIDGETS ) . 'banner/class-banner-widget.php';
			require_once trailingslashit( NAVYBLUE_THEME_WIDGETS ) . 'carousel/class-carousel-widget.php';
			require_once trailingslashit( NAVYBLUE_THEME_WIDGETS ) . 'custom-posts/class-custom-posts-widget.php';
			require_once trailingslashit( NAVYBLUE_THEME_WIDGETS ) . 'instagram/class-instagram-widget.php';
			require_once trailingslashit( NAVYBLUE_THEME_WIDGETS ) . 'subscribe-follow/class-subscribe-follow-widget.php';
			require_once trailingslashit( NAVYBLUE_THEME_WIDGETS ) . 'contact-information/class-contact-information-widget.php';

			/**
			 * Classes.
			 */
			if ( ! is_admin() ) {
				require_once trailingslashit( NAVYBLUE_THEME_CLASSES ) . 'class-wrapping.php';
			}

			require_once trailingslashit( NAVYBLUE_THEME_CLASSES ) . 'class-widget-area.php';
			require_once trailingslashit( NAVYBLUE_THEME_CLASSES ) . 'class-tgm-plugin-activation.php';

			/**
			 * Extensions.
			 */
			require_once trailingslashit( NAVYBLUE_THEME_EXT ) . 'woocommerce.php';
		}

		/**
		 * Run initialization of modules.
		 *
		 * @since 1.0.0
		 */
		public function init() {
			$this->customizer  = $this->get_core()->init_module( 'cherry-customizer', navyblue_get_customizer_options() );
			$this->dynamic_css = $this->get_core()->init_module( 'cherry-dynamic-css', navyblue_get_dynamic_css_options() );
			$this->get_core()->init_module( 'cherry-google-fonts-loader', navyblue_get_fonts_options() );
			$this->get_core()->init_module( 'cherry-term-meta', array(
				'tax'      => 'category',
				'priority' => 10,
				'fields'   => array(
					'cherry_terms_thumbnails' => array(
						'type'               => 'media',
						'value'              => '',
						'multi_upload'       => false,
						'library_type'       => 'image',
						'upload_button_text' => esc_html__( 'Set thumbnail', 'navyblue' ),
						'label'              => esc_html__( 'Category thumbnail', 'navyblue' ),
					),
				),
			) );
			$this->get_core()->init_module( 'cherry-term-meta', array(
				'tax'      => 'post_tag',
				'priority' => 10,
				'fields'   => array(
					'cherry_terms_thumbnails' => array(
						'type'               => 'media',
						'value'              => '',
						'multi_upload'       => false,
						'library_type'       => 'image',
						'upload_button_text' => esc_html__( 'Set thumbnail', 'navyblue' ),
						'label'              => esc_html__( 'Tag thumbnail', 'navyblue' ),
					),
				),
			) );
			$this->get_core()->init_module( 'cherry-post-meta', array(
				'id'            => 'post-layout',
				'title'         => esc_html__( 'Layout Options', 'navyblue' ),
				'page'          => array( 'post', 'page' ),
				'context'       => 'normal',
				'priority'      => 'high',
				'callback_args' => false,
				'fields'        => array(
					'navyblue_sidebar_position' => array(
						'type'        => 'radio',
						'title'       => esc_html__( 'Layout', 'navyblue' ),
						'value'         => 'inherit',
						'display_input' => false,
						'options'       => array(
							'inherit' => array(
								'label'   => esc_html__( 'Inherit', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/inherit.svg',
							),
							'one-left-sidebar' => array(
								'label'   => esc_html__( 'Sidebar on left side', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/page-layout-left-sidebar.svg',
							),
							'one-right-sidebar' => array(
								'label'   => esc_html__( 'Sidebar on right side', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/page-layout-right-sidebar.svg',
							),
							'fullwidth' => array(
								'label'   => esc_html__( 'No sidebar', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/page-layout-fullwidth.svg',
							),
						)
					),
					'navyblue_header_container_type' => array(
						'type'        => 'radio',
						'title'       => esc_html__( 'Header layout', 'navyblue' ),
						'value'         => 'inherit',
						'display_input' => false,
						'options'       => array(
							'inherit' => array(
								'label'   => esc_html__( 'Header Inherit Layout', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/inherit.svg',
							),
							'boxed' => array(
								'label'   => esc_html__( 'Header Boxed Layout', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/type-boxed.svg',
							),
							'fullwidth' => array(
								'label'   => esc_html__( 'Header Fullwidth Layout', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/type-fullwidth.svg',
							),
						)
					),
					'navyblue_content_container_type' => array(
						'type'        => 'radio',
						'title'       => esc_html__( 'Content layout', 'navyblue' ),
						'value'         => 'inherit',
						'display_input' => false,
						'options'       => array(
							'inherit' => array(
								'label'   => esc_html__( 'Content Inherit Layout', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/inherit.svg',
							),
							'boxed' => array(
								'label'   => esc_html__( 'Content Boxed Layout', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/type-boxed.svg',
							),
							'fullwidth' => array(
								'label'   => esc_html__( 'Content Fullwidth Layout', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/type-fullwidth.svg',
							),
						)
					),
					'navyblue_footer_container_type' => array(
						'type'        => 'radio',
						'title'       => esc_html__( 'Footer layout', 'navyblue' ),
						'value'         => 'inherit',
						'display_input' => false,
						'options'       => array(
							'inherit' => array(
								'label'   => esc_html__( 'Footer Inherit Layout', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/inherit.svg',
							),
							'boxed' => array(
								'label'   => esc_html__( 'Footer Boxed Layout', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/type-boxed.svg',
							),
							'fullwidth' => array(
								'label'   => esc_html__( 'Footer Fullwidth Layout', 'navyblue' ),
								'img_src' => trailingslashit( NAVYBLUE_THEME_URI ) . 'assets/images/admin/type-fullwidth.svg',
							),
						)
					),
				),
			) );
			$this->get_core()->init_module( 'cherry-post-meta', array(
				'id'            => 'post-header-layout-type',
				'title'         => esc_html__( 'Header Layout Type', 'navyblue' ),
				'page'          => array( 'post', 'page' ),
				'context'       => 'normal',
				'priority'      => 'low',
				'callback_args' => false,
				'fields'        => array(
					'navyblue_header_layout_type' => array(
						'type'          => 'radio',
						'title'         => esc_html__( 'Layout', 'navyblue' ),
						'value'         => 'inherit',
						'display_input' => false,
						'options'       => navyblue_get_header_layout_pm_options(),
					)
				),
			) );
		}

		/**
		 * Load admin files for the theme.
		 *
		 * @since 1.0.0
		 */
		public function admin() {

			// Check if in the WordPress admin.
			if ( ! is_admin() ) {
				return;
			}
		}

		/**
		 * Enqueue admin-specific assets.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_admin_assets() {
			wp_enqueue_script( 'navyblue-admin-script', NAVYBLUE_THEME_JS . '/admin.min.js', array( 'cherry-js-core' ), NAVYBLUE_THEME_VERSION, true );
		}

		/**
		 * Register assets.
		 *
		 * @since 1.0.0
		 */
		public function register_assets() {
			wp_register_script( 'jquery-slider-pro', NAVYBLUE_THEME_JS . '/jquery.sliderpro.min.js', array( 'jquery' ), '1.2.4', true );
			wp_register_script( 'jquery-swiper', NAVYBLUE_THEME_JS . '/swiper.jquery.min.js', array( 'jquery' ), '3.3.0', true );
			wp_register_script( 'magnific-popup', NAVYBLUE_THEME_JS . '/jquery.magnific-popup.min.js', array( 'jquery' ), '1.0.1', true );
			wp_register_script( 'jquery-stickup', NAVYBLUE_THEME_JS . '/jquery.stickup.js', array( 'jquery' ), '1.0.0', true );
			wp_register_script( 'jquery-totop', NAVYBLUE_THEME_JS . '/jquery.ui.totop.min.js', array( 'jquery' ), '1.2.0', true );
			wp_register_script( 'jquery-isotope', NAVYBLUE_THEME_JS . '/jquery.isotope.min.js', array( 'jquery' ), '4.0.0', true );
			wp_register_script( 'super-guacamole', NAVYBLUE_THEME_JS . '/super-guacamole.js', array( 'jquery' ), '1.0.0', true );

			wp_register_style( 'jquery-slider-pro', NAVYBLUE_THEME_CSS . '/slider-pro.min.css', array(), '1.2.4' );
			wp_register_style( 'jquery-swiper', NAVYBLUE_THEME_CSS . '/swiper.min.css', array(), '3.3.0' );
			wp_register_style( 'magnific-popup', NAVYBLUE_THEME_CSS . '/magnific-popup.min.css', array(), '1.0.1' );
			wp_register_style( 'font-awesome', NAVYBLUE_THEME_CSS . '/font-awesome.min.css', array(), '4.6.0' );
			wp_register_style( 'material-icons', NAVYBLUE_THEME_CSS . '/material-icons.min.css', array(), '2.2.0' );
		}

		/**
		 * Enqueue assets.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_assets() {
			wp_enqueue_style( 'navyblue-theme-style', get_stylesheet_uri(), array( 'font-awesome', 'material-icons', 'magnific-popup' ), NAVYBLUE_THEME_VERSION );

			/**
			 * Filter the depends on main theme script.
			 *
			 * @since 1.0.0
			 * @var   array
			 */
			$depends = apply_filters( 'navyblue_theme_script_depends', array( 'cherry-js-core', 'hoverIntent', 'super-guacamole' ) );

			wp_enqueue_script( 'navyblue-theme-script', NAVYBLUE_THEME_JS . '/theme-script.js', $depends, NAVYBLUE_THEME_VERSION, true );

			/**
			 * Filter the strings that send to scripts.
			 *
			 * @since 1.0.0
			 * @var   array
			 */
			$labels = apply_filters( 'navyblue_theme_localize_labels', array(
				'totop_button' => '',
				'hidden_menu_items_title' => get_theme_mod( 'hidden_menu_items_title', navyblue_theme()->customizer->get_default( 'hidden_menu_items_title' ) ),
			) );

			wp_localize_script( 'navyblue-theme-script', 'navyblue', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'labels'  => $labels,
			) );

			// Threaded Comments.
			if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
				wp_enqueue_script( 'comment-reply' );
			}
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}
	}
}


/**
 * Returns instanse of main theme configuration class.
 *
 * @since  1.0.0
 * @return object
 */
function navyblue_theme() {
	return NavyBlue_Theme_Setup::get_instance();
}
/*
 *  Add custom information (first attempt at USHPA number integration)
*add_action( 'show_user_profile', 'extra_user_profile_fields' );
*add_action( 'edit_user_profile', 'extra_user_profile_fields' );
*
*function extra_user_profile_fields( $user ) { ?>
<h3><?php _e("USHPA", "blank"); ?></h3>

<table class="form-table">
<tr>
<th><label for="ushpa-number"><?php _e("USHPA#"); ?></label></th>
<td>
<input type="number" name="ushpa-number" id="ushpa-number" value="<?php echo esc_attr( get_the_author_meta( 'ushpa-number', $user->ID ) ); ?>" class="regular-text" /><br />
<!--<input type="text" name="ushpa_number" id="ushpa_number" value="<?php echo esc_attr($profileuser->uspha_number) ?>" class="regular-text" /><br />-->
<span class="description"><?php _e("Please enter your USHPA#."); ?></span>
</td>
</tr>
</table>
<?php }

add_action( 'personal_options_update', 'save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_extra_user_profile_fields' );

function save_extra_user_profile_fields( $user_id ) {

if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }

update_user_meta( $user_id, 'ushpa-number', $_POST['ushpa-number'] );
}
 */


/*
 * CREATE CONTACT FORM PDF
* 
*add_action( 'wpcf7_before_send_mail', 'create_contact_form_pdf' );
*
*function create_contact_form_pdf($cf7){
//check if this is the right form - replace your contact form 7 id here//
if ($cf7->id==4090){
//include pdf generation file//
//require_once('tcpdf/tcpdf_cf7.php');
	require_once('../fpdf/tcpdf_cf7.php');
	$submission = WPCF7_Submission::get_instance();
if ($submission){
$posted_data = $submission->get_posted_data();
$name = $posted_data['first_name']." ".$posted_data['last_name'];
$address = $posted_data['address'];
$city = $posted_data['city'];
$postcode = $posted_data['postcode'];
$phone = $posted_data['phone'];
// create new PDF document
$createpdf = new CREATE_FPDFCF7();
//upload path
$uploads = wp_upload_dir();
define ('PDF_FILE_PATH',$uploads['basedir'].'/cf7_pdf/');
$fname = $createpdf->CREATE_FPDFCF7Fn($name,$address,$city,$postcode,$phone,PDF_FILE_PATH);
//set filenames
$pdf_filename= PDF_FILE_PATH.$fname;
//use the same tag used in contact form 7 mail tab
$submission->add_uploaded_file('submission_pdf', $pdf_filename);
}
}
}
 */
//quick and dirty action debugging
//add_action( 'all', create_function( '', 'var_dump( current_filter());' ) );
//init theme
navyblue_theme();
