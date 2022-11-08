<?php

namespace OllieJones;

use WP_Post;

require_once 'class-wordpress-hooks.php';

class Super_Sonic_Search_Hooks extends Word_Press_Hooks {

  private $ingester;

  public function __construct() {
    $this->ingester = new Ingester();
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
    $this->ingester->post_ingest( $post );
  }
}
