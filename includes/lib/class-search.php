<?php

namespace OllieJones;

use Psonic\Client;
use Psonic\Exceptions\ConnectionException;
use Psonic\Ingest;
use Psonic\Control;
use Psonic\Search;
use WP_Post;
use WP_Query;

class Searcher {
  const BUCKET = 'wordpress';
  private $searcher;
  private $control;
  private $collection;
  private $locale;
  private $allterms = [];

  public function __construct() {
    $this->collection = $this->getCollectionName();
  }

  public function __destruct() {
    $this->disconnect();
  }

  /** Connect to Sonic's Control and Search channels.
   *
   * @return void
   */
  private function connect() {
    if ( ! $this->searcher || ! $this->control ) {
      $this->locale = $this->getThreeLetterLanguageCode();
      //TODO use a URI: sonic://:password@localhost:port?timeout=xxx
      $this->control  = new Control( new Client( 'localhost', 1491, 30 ) );
      $this->searcher = new Search( new Client( 'localhost', 1491, 30 ) );
      try {
        $this->searcher->connect( 'SecretPassword1' );
        $this->control->connect( 'SecretPassword1' );
      } catch ( ConnectionException $e ) {
        error_log( "Sonic croaked" );
      }
    }
  }

  private function disconnect() {
    if ( $this->control ) {
      $this->control->disconnect();
      $this->control = null;
    }
    if ( $this->searcher ) {
      $this->searcher->disconnect();
      $this->searcher = null;
    }
  }

  /** Do an expansive search on the provided search term.
   * @param string $searchterm One or more words for which to search.
   * @return void
   */
  public function search( $searchterm ) {
    $this->connect();
    $wordsInSearchTerm = array_filter( explode( ' ', $searchterm ) );

    /* put the words themselves into the list */
    $this->allterms = [];
    foreach ( $wordsInSearchTerm as $word ) {
      $this->addTerm( $word, 1 );
    }

    /* retrieve possible words based on the search term words. */
    $expansionWordCount = 0;
    foreach ( $wordsInSearchTerm as $term ) {
      $results            = $this->searcher->suggest(
        $this->collection,
        self::BUCKET,
        $term, /* this must only be one word, or it throws an exception */
        20,
        0,
        $this->locale );
      $expansionWordCount += count( $results );
      foreach ( $results as $result ) {
        $this->addTerm( $result, 0 );
      }
    }

    arsort( $this->allterms, SORT_NUMERIC );

    /* search for all the terms in the list */
    $resultset = [];
    foreach ( $this->allterms as $term => $termPriority ) {
      $results = $this->searcher->query(
        $this->collection,
        self::BUCKET,
        $term,
        100,
        0,
        $this->locale );

      /* Results are of the form postid:source
       * where source can be title, content, summary, seo, etc. */
      if ( is_array( $results ) ) {
        foreach ( $results as $result ) {
          if ( is_string( $result ) && strlen( $result ) > 1 ) {
            $splits = explode( ':', $result, 2 );
            if ( count( $splits ) === 2 ) {
              $id       = intval( $splits[0] );
              $source   = is_string( $splits[1] ) ? $splits[1] : '?';
              $priority = $termPriority;
              if ( $termPriority > 0 ) {
                /* original word, not expanded word */
                if ( $source === 'title' ) {
                  $priority += ( 3 + $expansionWordCount );
                } elseif ( $source !== 'content' ) {
                  $priority += ( 1 + $expansionWordCount );
                } else {
                  $priority += ( 2 + $expansionWordCount );
                }
              } else {
                $priority = 1;
              }
              if ( ! array_key_exists( $id, $resultset ) ) {
                $resultset [ $id ] = 0;
              }
              $resultset [ $id ] += $priority;
            }
          }
        }
      }
    }
    arsort( $resultset, SORT_NUMERIC );
    $k = $resultset;

    return null;  //TODO not done yet, this is a stub.
  }

  /** Retrieve an opaque id for a collection, based on site and subsite.
   *
   * @return false|string
   */
  private function getCollectionName() {
    global $wpdb;
    $tag = get_option( 'siteurl' ) . $wpdb->prefix;
    return substr( md5( $tag ), 0, 12 );
  }

  private function resultPriority( $termPriority, $source ) {
    return $termPriority;
  }

  /** Sonic needs a three-letter ISO 639-3 language code. This gets it from the current locale.
   *
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

  /** Add a term, setting its priority.
   * @param string $newterm
   * @param int $newPriority
   *
   * @return void
   */
  public function addTerm( $newterm, $newPriority ) {
    if ( is_string( $newterm ) ) {
      $existingPriority           = array_key_exists( $newterm, $this->allterms ) ? $this->allterms[ $newterm ] : PHP_INT_MIN;
      $this->allterms[ $newterm ] = max( $newPriority, $existingPriority );
    }
  }

}
