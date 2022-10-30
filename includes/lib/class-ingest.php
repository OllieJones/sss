<?php

namespace OllieJones;

use Psonic\Client;
use Psonic\Exceptions\ConnectionException;
use Psonic\Ingest;
use Psonic\Control;
use WP_Post;
use WP_Query;

class Ingester {
  const SUPER_SONIC_SEARCH_OBJECTS = 'super_sonic_search_objects';
  const BUCKET = 'wordpress';
  const SEO_DESCRIPTION_META_KEYS = [
    '_yoast_wpseo_metadesc',
    '_aioseo_description',
    'rank_math_description',
    '_seopress_titles_desc',
  ];
  private $ingest;
  private $ingested = false;
  private $control;
  private $collection;
  private $locale;

  public function __construct() {
    $this->collection = $this->getCollectionName();
  }

  public function __destruct() {
    $this->disconnect();
  }

  /** Connect to Sonic's Control and Ingest channels.
   * @return void
   */
  private function connect() {
    if ( ! $this->ingest || ! $this->control ) {
      $this->ingested = false;
      //TODO use a URI: sonic://:password@localhost:port?timeout=xxx
      $this->control = new Control( new Client( 'localhost', 1491, 30 ) );
      $this->ingest  = new Ingest( new Client( 'localhost', 1491, 30 ) );
      $this->locale  = $this->getThreeLetterLanguageCode();
      try {
        $this->ingest->connect( 'SecretPassword1' );
        $this->control->connect( 'SecretPassword1' );
      } catch ( ConnectionException $e ) {
        error_log( "Sonic croaked" );
      }
      //TODO new Psonic\Search(new Psonic\Client('localhost', 1491, 30));
    }
  }

  private function disconnect() {
    if ( $this->control ) {
      if ( $this->ingested ) {
        $this->control->consolidate();
      }
      $this->control->disconnect();
      $this->control  = null;
      $this->ingested = false;
    }
    if ( $this->ingest ) {
      $this->ingest->disconnect();
      $this->ingest = null;
    }
  }

  /** Ingest multiple posts
   * @param array $query_args The query for the posts to ingest.
   * @return void
   */
  public function posts_ingest( $query_args ) {
    $query = new WP_Query( $query_args);
    while ( $query->have_posts() ) {
      $query->the_post();
      $this->post_ingest( $query->post );
    }
    wp_reset_postdata();
  }

  public function post_ingest( $post ) {
    if ( is_post_publicly_viewable( $post ) ) {
      $this->object_ingest( $post, 'title', $post->post_title );
      $this->object_ingest( $post, 'content', $post->post_content );
      $this->object_ingest( $post, 'excerpt', $post->post_excerpt );
      /* get custom-crafted SEO description items if any. */
      foreach ( self::SEO_DESCRIPTION_META_KEYS as $metakey ) {
        $metaval = get_post_meta( $post->ID, $metakey, true );
        if ( is_string( $metaval ) ) {
          $this->object_ingest( $post, $metakey, $metaval );
        }
      }
    } else {
      $this->objects_remove( $post );
    }
  }

  /** Ingest a single string into sonic, replacing an earlier object if need be.
   * This keeps hashes of the inserted objects in a meta_key called SUPER_SONIC_SEARCH_OBJECTS.
   *
   * @param WP_Post $post The post involved.
   * @param string $object The name of the object ('title', 'content', some meta_key).
   * @param string $content The text to ingest.
   * @return void
   */
  private function object_ingest( $post, $object, $content ) {
    $this->connect();
    $object       = $post->ID . ':' . $object;
    $objects      = get_post_meta( $post->ID, self::SUPER_SONIC_SEARCH_OBJECTS, true );
    $objects      = is_array( $objects ) ? $objects : [];
    $objectsDirty = false;
    $cleaned      = is_string( $content ) ? $content : '';
    $cleaned      = $this->cleanContent( $cleaned );
    $hash         = hash( 'sha1', $cleaned );
    if ( array_key_exists( $object, $objects ) && $objects[ $object ] !== $hash ) {
      /* different content already ingested, remove it. */
      $this->ingest->flusho( $this->collection, self::BUCKET, $object );
      $this->ingested = true;
      unset ( $objects [ $object ] );
      $objectsDirty = true;
    }
    if ( ! array_key_exists( $object, $objects ) || $objects [ $object ] !== $hash ) {
      if ( strlen( $cleaned ) > 0 ) {
        $objects[ $object ] = $hash;
        $this->ingest->push( $this->collection, self::BUCKET, $object, $content, $this->locale );
        $this->ingested = true;
        $objectsDirty   = true;
      }
    }
    if ( $objectsDirty ) {
      update_post_meta( $post->ID, self::SUPER_SONIC_SEARCH_OBJECTS, $objects );
    }
  }

  private function objects_remove( $post ) {
    $this->connect();
    $objects = get_post_meta( $post->ID, self::SUPER_SONIC_SEARCH_OBJECTS, true );
    $objects = is_array( $objects ) ? $objects : [];
    foreach ( $objects as $object => $hash ) {
      $this->ingest->flusho( $this->collection, self::BUCKET, $object );
      $this->ingested = true;
    }
    delete_post_meta( $post->ID, self::SUPER_SONIC_SEARCH_OBJECTS );
  }

  /** Retrieve an opaque id for a collection, based on site and subsite.
   * @return false|string
   */
  private function getCollectionName() {
    global $wpdb;
    $tag = get_option( 'siteurl' ) . $wpdb->prefix;
    return substr( md5( $tag ), 0, 12 );
  }

  /** Remove tags and punctuation. Normalize whitespace to single ' ' characters.
   * @param string $text Text string to prepare for ingestion.
   * @return array|string|string[]|null Cleaned string.
   */
  private function cleanContent( $text ) {
    $a = wp_strip_all_tags( $text, true );
    $b = preg_replace( '/[[:punct:]]/mSu', ' ', $a );
    return preg_replace( '/\s\s+/mSu', ' ', $b );
  }

  /** Sonic needs a three-letter ISO 639-3 language code. This gets it from the current locale.
   * @return string Language code like 'eng' 'fra' 'deu'.
   */
  private function getThreeLetterLanguageCode() {
    if ( $this->locale ) {
      return $this->locale;
    }
    require_once 'class-iso-language.php';
    $this->locale = ISO_Language::getLanguage( get_bloginfo( "language" ) );
    return $this->locale;
  }

  /** Remove everything about the present site / blog from Sonic.
   * @return void
   */
  public function removeAll() {
    $this->connect();
    $this->ingest->flushc( $this->collection );
    $this->control->consolidate();
    $this->ingested = false;
    delete_post_meta_by_key( self::SUPER_SONIC_SEARCH_OBJECTS );
  }
}
