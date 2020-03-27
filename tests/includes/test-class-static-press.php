<?php
/**
 * Class Static_Press_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\includes;

const DATE_FOR_TEST = '2019-12-23 12:34:56';
const TIME_FOR_TEST = '12:34:56';
/**
 * Override date() in current namespace for testing
 *
 * @return string
 */
function date() {
	return DATE_FOR_TEST;
}

/**
 * Override time() in current namespace for testing
 *
 * @return int
 */
function time() {
	return strtotime( TIME_FOR_TEST );
}

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-die-exception.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-repository-for-test.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-test-utility.php';
use Mockery;
use static_press\includes\Static_Press;
use static_press\includes\Static_Press_Model_Url;
use static_press\tests\testlibraries\Die_Exception;
use static_press\tests\testlibraries\Model_Url;
use static_press\tests\testlibraries\Repository_For_Test;
use static_press\tests\testlibraries\Test_Utility;

/**
 * StaticPress test case.
 *
 * @noinspection PhpUndefinedClassInspection
 */
class Static_Press_Test extends \WP_UnitTestCase {
	const OUTPUT_DIRECTORY = '/tmp/static/';
	/**
	 * Sets administrator as current user.
	 *
	 * @see https://wordpress.stackexchange.com/a/207363
	 */
	public function tearDown() {
		self::delete_files( self::OUTPUT_DIRECTORY );
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * PHP delete function that deals with directories recursively.
	 *
	 * @see https://paulund.co.uk/php-delete-directory-and-files-in-directory
	 *
	 * @param string $target Example: '/path/for/the/directory/' .
	 */
	public static function delete_files( $target ) {
		if ( is_dir( $target ) ) {
			$files = glob( $target . '*', GLOB_MARK ); // GLOB_MARK adds a slash to directories returned.
			foreach ( $files as $file ) {
				self::delete_files( $file );
			}
			rmdir( $target );
		} elseif ( is_file( $target ) ) {
			unlink( $target );
		}
	}

	/**
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_init_param_static_url
	 * 
	 * @param string $static_url Argument.
	 * @param string $expect     Expect return value.
	 */
	public function test_init_param_static_url( $static_url, $expect ) {
		$static_press       = new Static_Press( $static_url );
		$reflector          = new \ReflectionClass( $static_press );
		$reflector_property = $reflector->getProperty( 'static_site_url' );
		$reflector_property->setAccessible( true );

		$this->assertEquals( $expect, $reflector_property->getValue( $static_press ) );
	}

	/**
	 * Function init_param() should set
	 * persed home URL or DOMAIN_CURRENT_SITE or contents of the Host: header from the current request
	 * when parameter is not HTTP nor HTTPS.
	 * Function init_param() should set parameter when parameter is HTTP.
	 * Function init_param() should set parameter when parameter is HTTPS.
	 */
	public function provider_init_param_static_url() {
		return array(
			array( '/', 'http://example.org/' ),
			array( '//domain.com/', 'http://example.org/' ),
			array( 'http://domain.com/', 'http://domain.com/' ),
			array( 'https://domain.com/', 'https://domain.com/' ),
		);
	}

	/**
	 * Test steps for constructor.
	 * 
	 * @dataProvider provider_init_param_dump_directory
	 * 
	 * @param string $static_url     Argument.
	 * @param string $dump_directory Argument.
	 * @param string $expect         Expect return value.
	 */
	public function test_init_param_dump_directory( $static_url, $dump_directory, $expect ) {
		$static_press       = new Static_Press( $static_url, $dump_directory );
		$reflector          = new \ReflectionClass( $static_press );
		$reflector_property = $reflector->getProperty( 'dump_directory' );
		$reflector_property->setAccessible( true );

		$this->assertEquals( $expect, $reflector_property->getValue( $static_press ) );
	}

	/**
	 * Function init_param() should set WordPress directory when parameter is empty.
	 * Function init_param() should set parameter when parameter is not empty.
	 * Function init_param() should set path which end with slash.
	 * Function init_param() should set path added relative URL.
	 */
	public function provider_init_param_dump_directory() {
		return array(
			array( '/', '', ABSPATH ),
			array( 'http://domain.com/', '', ABSPATH ),
			array( 'https://domain.com/test', '', ABSPATH . 'test/' ),
			array( '/', '/tmp/', '/tmp/' ),
			array( '/', '/tmp', '/tmp/' ),
			array( 'http://domain.com/', '/tmp', '/tmp/' ),
			array( 'https://domain.com/test', '/tmp/', '/tmp/test/' ),
		);
	}

	/**
	 * Function activate() should ensure that database table which list URL exists.
	 */
	public function test_constructor_create_table() {
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		Repository_For_Test::ensure_table_is_dropped();
		$this->assertFalse( Repository_For_Test::url_table_exists() );
		new Static_Press();
		$this->assertTrue( Repository_For_Test::url_table_exists() );
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Function activate() should ensure that database table which list URL exists.
	 */
	public function test_activate() {
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$static_press = new Static_Press();
		Repository_For_Test::ensure_table_is_dropped();
		$this->assertFalse( Repository_For_Test::url_table_exists() );
		$static_press->activate();
		$this->assertTrue( Repository_For_Test::url_table_exists() );
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Function activate() should ensure that database table which list URL has column 'enable'.
	 */
	public function test_activate_2() {
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		Repository_For_Test::ensure_table_is_dropped();
		Repository_For_Test::create_legacy_table();
		$this->assertFalse( Repository_For_Test::column_enable_exists() );
		$static_press = new Static_Press();
		$static_press->activate();
		$this->assertTrue( Repository_For_Test::column_enable_exists() );
		$column = Repository_For_Test::get_column_enable();
		$this->assertEquals( 'enable', $column->Field );         // phpcs:ignore
		$this->assertEquals( 'int(1) unsigned', $column->Type ); // phpcs:ignore
		$this->assertEquals( 'NO', $column->Null );              // phpcs:ignore
		$this->assertEquals( '', $column->Key );                 // phpcs:ignore
		$this->assertEquals( '1', $column->Default );            // phpcs:ignore
		$this->assertEquals( '', $column->Extra );               // phpcs:ignore
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Function activate() should ensure that database table which list URL exists.
	 */
	public function test_deactivate() {
		global $wpdb;
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$static_press = new Static_Press();
		if ( $wpdb->get_var( "show tables like '{$this->url_table()}'" ) != $this->url_table() ) {
			Repository_For_Test::create_latest_table();
		}
		$this->assertEquals( Repository_For_Test::url_table(), $wpdb->get_var( "show tables like '{$this->url_table()}'" ) );
		$static_press->deactivate();
		$this->assertNotEquals( Repository_For_Test::url_table(), $wpdb->get_var( "show tables like '{$this->url_table()}'" ) );
		Repository_For_Test::create_latest_table();
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Returns database table name for URL list.
	 */
	private static function url_table() {
		global $wpdb;
		return $wpdb->prefix . 'urls';
	}

	/**
	 * Function ajax_init() should die.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_ajax_init() {
		$this->sign_on_to_word_press();
		$static_press = new Static_Press( '/', '', array(), null, Test_Utility::set_up_seo_url( 'http://example.org/' ) );
		ob_start();
		try {
			$static_press->ajax_init( Test_Utility::create_terminator_mock() );
		} catch ( Die_Exception $exception ) {
			$output = ob_get_clean();
			$this->assertEquals( 'Dead!', $exception->getMessage() );
			$array_json = json_decode( $output, true );
			$this->assertTrue( $array_json['result'] );
			$array_urls_count  = $array_json['urls_count'];
			$url_count_content = $array_urls_count[0];
			$this->assertEquals( 'content_file', $url_count_content['type'] );
			$this->assertGreaterThan( 0, $url_count_content['count'] );
			$url_count_front = $array_urls_count[1];
			$this->assertEquals( 'front_page', $url_count_front['type'] );
			$this->assertEquals( 1, $url_count_front['count'] );
			$url_count_seo = $array_urls_count[2];
			$this->assertEquals( 'seo_files', $url_count_seo['type'] );
			$this->assertEquals( 5, $url_count_seo['count'] );
			return;
		}
		$this->fail();
	}

	/**
	 * Function ajax_fetch() should die.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_ajax_fetch_without_record() {
		$this->sign_on_to_word_press();

		$expect       = '{"result":false,"final":true}';
		$static_press = new Static_Press();
		ob_start();
		try {
			$static_press->ajax_fetch( Test_Utility::create_terminator_mock() );
		} catch ( Die_Exception $exception ) {
			$output = ob_get_clean();
			$this->assertEquals( 'Dead!', $exception->getMessage() );
			$this->assertEquals( $expect, $output );
			return;
		}
		$this->fail();
	}

	/**
	 * Test steps for ajax_fetch_with_record().
	 * Function ajax_fetch() should die.
	 * 
	 * @dataProvider provider_ajax_fetch_with_record
	 * 
	 * @param string $array_record   Array record.
	 * @param string $expect         Expect return value.
	 * @runInSeparateProcess
	 */
	public function test_ajax_fetch_with_record( $array_record, $expect ) {
		$this->sign_on_to_word_press();
		Repository_For_Test::truncate_table();
		foreach ( $array_record as $record ) {
			Repository_For_Test::insert_url( $record );
		}

		$static_press = new Static_Press( '/', '', array(), null, Test_Utility::create_remote_getter_mock() );
		ob_start();
		try {
			$static_press->ajax_fetch( Test_Utility::create_terminator_mock() );
		} catch ( Die_Exception $exception ) {
			$output = ob_get_clean();
			$this->assertEquals( 'Dead!', $exception->getMessage() );
			$this->assertEquals( $expect, json_decode( $output, true ) );
			return;
		}
		$this->fail();
	}

	/**
	 * Function ajax_fetch() should .
	 */
	public function provider_ajax_fetch_with_record() {
		return array(
			array(
				array(
					new Model_Url(
						1,
						Static_Press_Model_Url::TYPE_OTHER_PAGE,
						'/test1/',
						0,
						'',
						0,
						2,
						1,
						'',
						'0000-00-00 00:00:00',
						0,
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00'
					),
					new Model_Url(
						2,
						Static_Press_Model_Url::TYPE_OTHER_PAGE,
						'/test2/',
						0,
						'',
						0,
						2,
						1,
						'',
						'0000-00-00 00:00:00',
						0,
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00'
					),
				),
				array(
					'result' => true,
					'files'  => array(
						'1'   => array(
							'ID'     => '1',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_OTHER_PAGE,
							'url'    => '/test1/',
							'static' => ABSPATH . 'test1/index.html',
						),
						'1-2' => array(
							'ID'     => '1',
							'page'   => 2,
							'type'   => Static_Press_Model_Url::TYPE_OTHER_PAGE,
							'url'    => '/test1/page/2',
							'static' => ABSPATH . 'test1/page/2/index.html',
						),
						'2'   => array(
							'ID'     => '2',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_OTHER_PAGE,
							'url'    => '/test2/',
							'static' => ABSPATH . 'test2/index.html',
						),
						'3'   => array(
							'ID'     => '3',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_OTHER_PAGE,
							'url'    => '/test1/page/',
							'static' => ABSPATH . 'test1/page/index.html',
						),
					),
					'final'  => true,
				),
			),
			array(
				array(
					new Model_Url(
						1,
						Static_Press_Model_Url::TYPE_SINGLE,
						'/?attachment_id=3/',
						3,
						'attachment',
						0,
						2,
						1,
						'',
						'0000-00-00 00:00:00',
						0,
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00'
					),
					new Model_Url(
						2,
						Static_Press_Model_Url::TYPE_SINGLE,
						'/?attachment_id=4/',
						4,
						'attachment',
						0,
						2,
						1,
						'',
						'0000-00-00 00:00:00',
						0,
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00',
						'0000-00-00 00:00:00'
					),
				),
				array(
					'result' => true,
					'files'  => array(
						'1'   => array(
							'ID'     => '1',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_SINGLE,
							'url'    => '/?attachment_id=3/',
							'static' => ABSPATH . '?attachment_id=3/index.html',
						),
						'1-2' => array(
							'ID'     => '1',
							'page'   => 2,
							'type'   => Static_Press_Model_Url::TYPE_SINGLE,
							'url'    => '/?attachment_id=3/2',
							'static' => ABSPATH . '?attachment_id=3/2/index.html',
						),
						'2'   => array(
							'ID'     => '2',
							'page'   => 1,
							'type'   => Static_Press_Model_Url::TYPE_SINGLE,
							'url'    => '/?attachment_id=4/',
							'static' => ABSPATH . '?attachment_id=4/index.html',
						),
					),
					'final'  => true,
				),
			),
		);
	}

	/**
	 * Function ajax_finalyze() should die.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_ajax_finalyze() {
		$user_id = $this->sign_on_to_word_press();
		set_transient( "static static - {$user_id}", array( 'fetch_last_id' => 2 ), 3600 );
		$expect       = '{"result":true}';
		$static_press = new Static_Press();
		ob_start();
		try {
			$static_press->ajax_finalyze( Test_Utility::create_terminator_mock() );
		} catch ( Die_Exception $exception ) {
			$output = ob_get_clean();
			$this->assertEquals( 'Dead!', $exception->getMessage() );
			$this->assertEquals( $expect, $output );
			$this->assertFalse( get_transient( 'static static' ) );
			return;
		}
		$this->fail();
	}

	/**
	 * Signs on to WordPress.
	 */
	private function sign_on_to_word_press() {
		$user_name     = 'User Name';
		$user_password = 'passW@rd';
		wp_create_user( $user_name, $user_password );
		$result = wp_signon(
			array(
				'user_login'    => $user_name,
				'user_password' => $user_password,
			)
		);
		wp_set_current_user( $result->ID );
		return $result->ID;
	}

	/**
	 * Test steps for replace_url().
	 *
	 * @dataProvider provider_replace_url
	 *
	 * @param string $url argument.
	 * @param string $expect Expect return value.
	 */
	public function test_replace_url( $url, $expect ) {
		$static_press = new Static_Press();
		$this->assertEquals( $expect, $static_press->replace_url( $url ) );
	}

	/**
	 * Function replace_url() should return relative URL when same host.
	 * Function replace_url() should return absolute URL when different host.
	 * Function replace_url() should return URL end with '/' when no extension is set.
	 * Function replace_url() should return URL end without '/' when extension is registered.
	 * Function replace_url() should return URL end with '/' when extension is not registered.
	 */
	public function provider_replace_url() {
		return array(
			array( '', '/' ),
			array( 'http://example.org/', '/' ),
			array( 'http://google.com/', 'http://google.com/' ),
			array( 'http://example.org/test', '/test/' ),
			array( 'http://example.org/test.php', '/test.php' ),
			array( 'http://example.org/test.xlsx', '/test.xlsx/' ),  // Maybe, not intended.
		);
	}

	/**
	 * Test steps for static_url().
	 *
	 * @dataProvider provider_static_url
	 *
	 * @param string $permalink argument.
	 * @param string $expect    Expect return value.
	 */
	public function test_static_url( $permalink, $expect ) {
		$static_press = new Static_Press();
		$this->assertEquals( $expect, $static_press->static_url( $permalink ) );
	}

	/**
	 * Function static_url() should return index.html based on permalink when permalink doesn't end with extension.
	 * Function static_url() should return argument when permalink ends with extension.
	 */
	public function provider_static_url() {
		return array(
			array( '/', '/index.html' ),
			array( '/test', '/test/index.html' ),
			array( '/test/', '/test/index.html' ),
			array( '/test/test', '/test/test/index.html' ),
			array( '/test/test.png', '/test/test.png' ),
			array( '/sitemap.xml', '/sitemap.xml' ),
		);
	}

	/**
	 * Function remove_link_tag() should remove link tag of pingback.
	 * Function remove_link_tag() should remove link tag of EditURI.
	 * Function remove_link_tag() should remove link tag of shortlink.
	 * Function remove_link_tag() should remove link tag of wlwmanifest.
	 * Function remove_link_tag() should not remove link tag of shortcut icon.
	 * Function remove_link_tag() should remove link tag of alternate type of application/rss+xml.
	 * Function remove_link_tag() should not remove link tag of alternate type of application/atom+xml.
	 */
	public function test_remove_link_tag() {
		$parameter    = Test_Utility::get_test_resource_content( 'remove-link-tag-before.html' );
		$expect       = Test_Utility::get_test_resource_content( 'remove-link-tag-after.html' );
		$static_press = new Static_Press();
		$actual       = $static_press->remove_link_tag( $parameter );
		$this->assertEquals( $expect, $actual );
	}

	/**
	 * Test steps for add_last_modified().
	 * 
	 * @dataProvider provider_add_last_modified
	 * 
	 * @param string $file_name_before File name of before state.
	 * @param string $http_code        HTTP status code.
	 * @param string $file_name_after  File name of after state.
	 */
	public function test_add_last_modified( $file_name_before, $http_code, $file_name_after ) {
		$content      = Test_Utility::get_test_resource_content( $file_name_before );
		$expect       = Test_Utility::get_test_resource_content( $file_name_after );
		$static_press = new Static_Press(
			'/',
			'',
			array(),
			Test_Utility::create_date_time_factory_mock( 'create_gmdate', 'D, d M Y H:i:s', 'Mon, 23 Des 2019 12:34:56' )
		);
		$actual       = $static_press->add_last_modified( $content, $http_code );
		$this->assertEquals( $expect, $actual );
	}

	/**
	 * Function add_last_modified() should add whether URL exists or not.
	 *
	 * @return array[]
	 */
	public function provider_add_last_modified() {
		return array(
			array(
				'add-last-modified-html-without-attribute-before.html',
				200,
				'add-last-modified-html-without-attribute-after.html',
			),
			array(
				'add-last-modified-html-with-attribute-before.html',
				200,
				'add-last-modified-html-with-attribute-after.html',
			),
			array(
				'add-last-modified-xhtml-without-attribute-before.html',
				200,
				'add-last-modified-xhtml-without-attribute-after.html',
			),
			array(
				'add-last-modified-xhtml-with-attribute-before.html',
				200,
				'add-last-modified-xhtml-with-attribute-after.html',
			),
			array(
				'add-last-modified-html-without-attribute-before.html',
				404,
				'add-last-modified-html-without-attribute-before.html',
			),
			array(
				'add-last-modified-html-with-attribute-before.html',
				404,
				'add-last-modified-html-with-attribute-before.html',
			),
			array(
				'add-last-modified-xhtml-without-attribute-before.html',
				404,
				'add-last-modified-xhtml-without-attribute-before.html',
			),
			array(
				'add-last-modified-xhtml-with-attribute-before.html',
				404,
				'add-last-modified-xhtml-with-attribute-before.html',
			),
		);
	}

	/**
	 * Function rewrite_generator_tag() should return generator meta tag which added plugin name and version.
	 */
	public function test_rewrite_generator_tag() {
		$content        = '<meta name="generator" content="WordPress 5.3" />';
		$file_data      = get_file_data(
			dirname( dirname( dirname( __FILE__ ) ) ) . '/plugin.php',
			array(
				'pluginname' => 'Plugin Name',
				'version'    => 'Version',
			)
		);
		$plugin_name    = $file_data['pluginname'];
		$plugin_version = $file_data['version'];
		$expect         = '<meta name="generator" content="WordPress 5.3 with ' . $plugin_name . ' ver.' . $plugin_version . '" />';

		$static_press = new Static_Press();
		$result       = $static_press->rewrite_generator_tag( $content );
		$this->assertEquals( $expect, $result );
	}

	/**
	 * Function replace_relative_uri() should return generator meta tag which added plugin name and version.
	 */
	public function test_replace_relative_uri() {
		update_option( 'home', 'https://dynamic-site.com/sub/' );
		$content      = Test_Utility::get_test_resource_content( 'replace_relative_uri-before.html' );
		$expect       = Test_Utility::get_test_resource_content( 'replace_relative_uri-after.html' );
		$static_press = new Static_Press( 'https://static-site.com/sub/' );
		$result       = $static_press->replace_relative_uri( $content );
		$this->assertEquals( $expect, $result );
	}

	/**
	 * Test steps for test_replace_relative_URI().
	 *
	 * @dataProvider provider_replace_relative_URI
	 *
	 * @param string $content argument.
	 * @param string $expect Expect return value.
	 */
	public function test_replace_relative_uri2( $content, $expect ) {
		$static_press = new Static_Press( 'http://example.org/static' );
		$this->assertEquals( $expect, $static_press->replace_relative_uri( $content ) );
	}

	/**
	 * Function replace_relative_URI() should replace site URL to the static URL.
	 * Function replace_relative_URI() should replace relative path in the attributes to the static path.
	 * Function replace_relative_URI() should not replace external URL starts with "//".
	 */
	public function provider_replace_relative_URI() {
		return array(
			array(
				'http://example.org/foo/bar/',
				'http://example.org/static/foo/bar/',
			),
			array(
				'<a href="/foo/bar/"></a>',
				'<a href="/static/foo/bar/"></a>',
			),
			array(
				'<a href="//example.test/foo/bar/"></a>',
				'<a href="//example.test/foo/bar/"></a>',
			),
		);
	}
}
