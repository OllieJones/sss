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

  /** When a post is updated, rework the search index.
   * @param int $post_ID Changed post id.
   * @param WP_Post $post_after The Post object after the change.
   * @param WP_Post $post_before The Post object before the change.
   * @return void
   */
  public function action__post_updated( $post_ID, WP_Post $post_after, WP_Post $post_before ) {

    $this->ingester->post_ingest( $post_after );
  }
}
