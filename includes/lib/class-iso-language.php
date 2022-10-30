<?php

namespace OllieJones;

class ISO_language {
  private static $cache;
  private static $optionCache;

  /**
   * @param string $lang A language code from get_bloginfo( "language" ) or similar.
   * @return string An ISO693-3 three-letter language code like 'deu', 'fra', 'eng'.
   */
  public static function getLanguage( $lang ) {
    self::$cache = is_array( self::$cache ) ? self::$cache : [];
    if ( array_key_exists( $lang, self::$cache ) && is_string(self::$cache[ $lang ]) ) {
      return self::$cache[ $lang ];
    }
    self::$optionCache = self::$optionCache ?: get_option( 'super_sonic_search_iso693_3_cache', [] );
    if ( array_key_exists( $lang, self::$optionCache ) && is_string(self::$optionCache[ $lang ])) {
      self::$cache [ $lang ] = self::$optionCache[ $lang ];
      return self::$cache [ $lang ];
    }
    /* nothing in either cache, do this the hard way, loading a big mess of tables */
    require_once 'class-iso639p3.php';
    self::$optionCache[ $lang ] = Iso639p3::code( $lang );
    self::$cache[ $lang ]       = self::$optionCache[ $lang ];
    update_option( 'super-sonic-search-iso693-3-cache', self::$optionCache, false );
    return self::$cache[ $lang ];
  }
}
