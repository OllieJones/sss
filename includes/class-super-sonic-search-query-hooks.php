<?php

namespace OllieJones;

use WP_Post;
use WP_Query;

require_once 'lib/class-search.php';
require_once 'lib/class-wordpress-hooks.php';

class Super_Sonic_Search_Query_Hooks extends Word_Press_Hooks {
  private $searcher;

  public function __construct() {
    parent::__construct();
    $this->searcher = new Searcher();
  }

  /**
   * Filters the posts array before the query takes place.
   *
   * Return a non-null value to bypass WordPress' default post queries.
   *
   * Filtering functions that require pagination information are encouraged to set
   * the `found_posts` and `max_num_pages` properties of the WP_Query object,
   * passed to the filter by reference. If WP_Query does not perform a database
   * query, it will not have enough information to generate these values itself.
   *
   * @param WP_Post[]|int[]|null $posts Return an array of post data to short-circuit WP's query,
   *                                    or null to allow WP to run its normal queries.
   * @param WP_Query $query The WP_Query instance (passed by reference).
   *
   * @returns WP_Post[]|int[]|null $posts Return an array of post data to short-circuit WP's query,
   *                                    or null to allow WP to run its normal queries.
   * @since 4.6.0
   *
   */
  public function filter__posts_pre_query( $posts, $query ) {
    if ( $query->is_search() && ! isset( $posts ) ) {
      $searchterm = $query->query_vars['s'];

      return $this->searcher->search( $searchterm, $query );
    }

    return $posts;
  }

  /**
   * Fires after the query variable object is created, but before the actual query is run.
   *
   * Note: If using conditional tags, use the method versions within the passed instance
   * (e.g. $this->is_main_query() instead of is_main_query()). This is because the functions
   * like is_main_query() test against the global $wp_query instance, not the passed one.
   *
   * @param WP_Query $query The WP_Query instance (passed by reference).
   *
   * @since 2.0.0
   *
   */
  public function action__pre_get_posts( $query ) {
    $a = $query;
  }

  /**
   * Fires after the main query vars have been parsed.
   *
   * @param WP_Query $query The WP_Query instance (passed by reference).
   *
   * @since 1.5.0
   *
   */
  public function action__parse_query( $query ) {
    $a = $query;
  }

  /**
   * Filters the search SQL that is used in the WHERE clause of WP_Query.
   *
   * @param string $search Search SQL for WHERE clause.
   * @param WP_Query $query The current WP_Query object.
   *
   * @since 3.0.0
   *
   */
  public function filter__posts_search( $search, $query ) {
    return $search;
  }

  /**
   * Fires once the post data has been set up.
   *
   * @param WP_Post $post The Post object (passed by reference).
   * @param WP_Query $query The current Query object (passed by reference).
   *
   * @since 2.8.0
   * @since 4.1.0 Introduced `$query` parameter.
   *
   */
  public function action__the_post( $post, $query ) {
    $a = $post;
  }

}
