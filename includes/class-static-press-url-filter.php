<?php
/**
 * Class Static_Press_Url_Filter
 *
 * @package static_press\includes
 */

namespace static_press\includes;

if ( ! class_exists( 'static_press\includes\Static_Press_Model_Static_File' ) ) {
	require dirname( __FILE__ ) . '/class-static-press-model-static-file.php';
}
use static_press\includes\Static_Press_Model_Static_File;

/**
 * URL filter.
 * This class should be instantiated before entering loop since constructor includes loop process.
 */
class Static_Press_Url_Filter {
	/**
	 * Regex.
	 * 
	 * @var string
	 */
	private $regex;
	/**
	 * Constructor.
	 */
	public function __construct() {
		$static_files_filter = Static_Press_Model_Static_File::get_filtered_array_extension();
		$this->regex         = '#[^/]+\.' . implode( '|', array_merge( $static_files_filter, array( 'php' ) ) ) . '$#i';
	}
	/**
	 * Replaces URL.
	 * 
	 * @param  string $url URL.
	 * @return string Replaced URL.
	 */
	public function replace_url( $url ) {
		$url_dynamic = trailingslashit( Static_Press_Url_Collector::get_site_url() );
		$url         = trim( str_replace( $url_dynamic, '/', $url ) );
		if ( ! preg_match( $this->regex, $url ) ) {
			$url = trailingslashit( $url );
		}
		unset( $static_files_filter );
		return $url;
	}
}