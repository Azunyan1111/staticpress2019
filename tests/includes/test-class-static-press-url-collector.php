<?php
/**
 * Class Static_Press_Url_Collector_Test
 *
 * @package static_press\tests\includes
 */

namespace static_press\tests\includes;

require_once dirname( __FILE__ ) . '/../testlibraries/class-category-array-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-fixture-category.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-fixture-post.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-fixture-post-author.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-fixture-post-single.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-mock-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-creator.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-model-url-handler.php';
require_once dirname( __FILE__ ) . '/../testlibraries/class-post-array-creator.php';
use static_press\includes\Static_Press_Url_Collector;
use static_press\tests\testlibraries\Category_Array_Creator;
use static_press\tests\testlibraries\Fixture_Category;
use static_press\tests\testlibraries\Fixture_Post;
use static_press\tests\testlibraries\Fixture_Post_Author;
use static_press\tests\testlibraries\Fixture_Post_Single;
use static_press\tests\testlibraries\Mock_Creator;
use static_press\tests\testlibraries\Model_Url_Creator;
use static_press\tests\testlibraries\Model_Url_Handler;
use static_press\tests\testlibraries\Post_Array_Creator;
/**
 * Reposistory test case.
 */
class Static_Press_Url_Collector_Test extends \WP_UnitTestCase {
	/**
	 * Fixture category parent.
	 * 
	 * @var Fixture_Category
	 */
	private $fixture_category_parent;
	/**
	 * Fixture category child.
	 * 
	 * @var Fixture_Category
	 */
	private $fixture_category_child;
	/**
	 * Fixture post single.
	 * 
	 * @var Fixture_Post_Single
	 */
	private $fixture_post_single_1;
	/**
	 * Fixture post single.
	 * 
	 * @var Fixture_Post_Single
	 */
	private $fixture_post_single_2;
	/**
	 * Fixture post author.
	 * 
	 * @var Fixture_Post
	 */
	private $fixture_post_term;
	/**
	 * Fixture post author.
	 * 
	 * @var Fixture_Post_Author
	 */
	private $fixture_post_author;

	/**
	 * Insert post.
	 */
	public function setUp() {
		$this->fixture_category_parent = new Fixture_Category( Category_Array_Creator::create_parent() );
		$this->fixture_category_child  = new Fixture_Category( Category_Array_Creator::create_child( $this->fixture_category_parent->category_id ) );
		$this->fixture_post_single_1   = new Fixture_Post( Post_Array_Creator::create_single() );
		$this->fixture_post_single_2   = new Fixture_Post( Post_Array_Creator::create_single( 'Post Title 2', 'test<!--nextpage-->test<!--nextpage-->test' ) );
		$this->fixture_post_term       = new Fixture_Post( Post_Array_Creator::create_term( $this->fixture_category_child->category_id ) );
		$this->fixture_post_author     = new Fixture_Post_Author( Post_Array_Creator::create_author( 1 ) );
	}

	/**
	 * Delete post.
	 */
	public function tearDown() {
		$this->fixture_post_author->delete();
		$this->fixture_post_term->delete();
		$this->fixture_post_single_2->delete();
		$this->fixture_post_single_1->delete();
		$this->fixture_category_child->delete();
		$this->fixture_category_parent->delete();
	}

	/**
	 * Function collect() should return urls of front page, static files, and SEO.
	 * 
	 * @runInSeparateProcess
	 */
	public function test_collect() {
		file_put_contents( ABSPATH . 'test.txt', '' );
		$expect_urls   = array_merge(
			Model_Url_Creator::get_expect_urls_front_page(),
			Model_Url_Creator::get_expect_urls_static_files( Mock_Creator::DATE_FOR_TEST ),
			Model_Url_Creator::get_expect_urls_seo( Mock_Creator::DATE_FOR_TEST )
		);
		$url_collector = new Static_Press_Url_Collector(
			Mock_Creator::create_remote_getter_mock(),
			Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s', Mock_Creator::DATE_FOR_TEST )
		);
		$actual        = $url_collector->collect();
		Model_Url_Handler::assert_contains_urls( $this, $expect_urls, $actual );
	}

	/**
	 * Function front_page_url() should return appropriate URLs.
	 */
	public function test_front_page_url() {
		$expect = Model_Url_Creator::get_expect_urls_front_page();
		$actual = $this->create_accessable_method(
			Mock_Creator::create_remote_getter_mock(),
			'front_page_url',
			array(),
			Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s', Mock_Creator::DATE_FOR_TEST )
		);
		Model_Url_Handler::assert_array_model_url( $this, $expect, $actual );
	}

	/**
	 * Function single_url() should return URLs of posts.
	 * Function single_url() should return number of pages by split post content by nextpage tag.
	 */
	public function test_single_url() {
		$expect = array(
			Model_Url_Creator::create_model_url_single( $this->fixture_post_single_1 ),
			Model_Url_Creator::create_model_url_single( $this->fixture_post_single_2 ),
			Model_Url_Creator::create_model_url_single( $this->fixture_post_term ),
			Model_Url_Creator::create_model_url_single( $this->fixture_post_author ),
		);
		$actual = $this->create_accessable_method( null, 'single_url', array() );
		Model_Url_Handler::assert_array_model_url( $this, $expect, $actual );
	}

	/**
	 * Function terms_url() should return URLs of terms.
	 */
	public function test_terms_url() {
		$expect = Model_Url_Creator::create_array_model_url_term();
		$actual = $this->create_accessable_method( null, 'terms_url', array() );
		Model_Url_Handler::assert_array_model_url( $this, $expect, $actual );
	}

	/**
	 * Function author_url() should return URLs of authors.
	 */
	public function test_author_url() {
		$expect = array( Model_Url_Creator::create_model_url_author( $this->fixture_post_author ) );
		$actual = $this->create_accessable_method( null, 'author_url', array() );
		Model_Url_Handler::assert_array_model_url( $this, $expect, $actual );
	}

	/**
	 * Function static_files_url() should return URLs of authors.
	 */
	public function test_static_files_url() {
		file_put_contents( ABSPATH . 'test.txt', '' );
		$expect = Model_Url_Creator::get_expect_urls_static_files( Mock_Creator::DATE_FOR_TEST );
		$actual = $this->create_accessable_method( null, 'static_files_url', array() );
		Model_Url_Handler::assert_contains_urls( $this, $expect, $actual );
	}

	/**
	 * Function seo_url() should trancate database table for list URL.
	 */
	public function test_seo_url() {
		$expect_urls     = Model_Url_Creator::get_expect_urls_seo( Mock_Creator::DATE_FOR_TEST );
		$actual          = $this->create_accessable_method( Mock_Creator::set_up_seo_url( 'http://example.org/' ), 'seo_url', array(), Mock_Creator::create_date_time_factory_mock( 'create_date', 'Y-m-d h:i:s' ) );
		$array_array_url = array();
		foreach ( $actual as $url ) {
			$array_array_url[] = $url->to_array();
		}
		Model_Url_Handler::assert_array_model_url( $this, $expect_urls, $actual );
	}

	/**
	 * Creates accessable method.
	 * 
	 * @param mixed         $remote_getter_mock     Mock for Remote Getter.
	 * @param string        $method_name            Method name.
	 * @param array         $array_parameter        Array of parameter.
	 * @param MockInterface $date_time_factory_mock Mock interface for Date time factory.
	 */
	private function create_accessable_method( $remote_getter_mock, $method_name, $array_parameter, $date_time_factory_mock = null ) {
		$url_collector = new Static_Press_Url_Collector( $remote_getter_mock, $date_time_factory_mock, Mock_Creator::create_docuemnt_root_getter_mock() );
		$reflection    = new \ReflectionClass( get_class( $url_collector ) );
		$method        = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $url_collector, $array_parameter );
	}
}
