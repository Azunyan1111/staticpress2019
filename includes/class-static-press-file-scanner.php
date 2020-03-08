<?php
/**
 * Class Static_Press_File_Scanner
 *
 * @package static_press\includes
 */

namespace static_press\includes;

/**
 * File scanner.
 */
class Static_Press_File_Scanner {
	/**
	 * Concatenated extensions of static file.
	 * 
	 * @var string
	 */
	private $concatenated_extension_static_file;

	/**
	 * Constructor.
	 * 
	 * @param array $array_extension_static_file Array of extension of static file.
	 */
	public function __construct( $array_extension_static_file ) {
		$static_files_filter = $array_extension_static_file;
		foreach ( $static_files_filter as &$file_ext ) {
			$file_ext = '*.' . $file_ext;
		}
		$this->concatenated_extension_static_file = '{' . implode( ',', $static_files_filter ) . '}';        
	}

	/**
	 * Scans files.
	 * 
	 * @param string $dir       Directory.
	 * @param bool   $recursive Whether scan recursive or not.
	 */
	public function scan( $dir, $recursive = true ) {
		$list = array();
		if ( $recursive ) {
			$tmp = array();
			foreach ( glob( $dir . '*/', GLOB_ONLYDIR ) as $child_dir ) {
				$tmp = $this->scan( $child_dir, $recursive );
				if ( $tmp ) {
					$list = array_merge( $list, $tmp );
				}
			}
		}

		foreach ( glob( $dir . $this->concatenated_extension_static_file, GLOB_BRACE ) as $image ) {
			$list[] = $image;
		}

		return $list;
	}
}