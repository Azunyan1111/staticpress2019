<?php
/**
 * Class Environment
 *
 * @package static_press\tests\testlibraries
 */

namespace static_press\tests\testlibraries;

/**
 * URL Collector.
 */
class Environment {
	const DIRECTORY_NAME_WORD_PRESS = 'wordpress';
	/**
	 * Gets document root.
	 * 
	 * @return string Document root.
	 */
	public static function get_document_root() {
		return dirname( ABSPATH );
	}
}
