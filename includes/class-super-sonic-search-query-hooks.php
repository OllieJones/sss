<?php

namespace OllieJones;

use WP_Post;
use WP_Query;

require_once 'lib/class-wordpress-hooks.php';

class Super_Sonic_Search_Query_Hooks extends Word_Press_Hooks {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Fires once a post has been saved.
   *
   * @param int $post_ID Post ID.
   * @param WP_Post $post Post object.
   * @param bool $update Whether this is an existing post being updated.
   *
   * @since 2.0.0
   *
   */
  public function action__wp_insert_post( $post_ID, $post, $update ) {

    require_once plugin_dir_path( __FILE__ ) . 'lib/class-ingest.php';
    $ingester = new Ingester();
    $ingester->post_ingest( $post );
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
      require_once 'lib/class-search.php';
      $searcher = new Searcher();
      $searchterm = $query->query_vars['s'];

      return $searcher->search( $searchterm, $query );
    }

    return $posts;
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
