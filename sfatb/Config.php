<?php
	/**
	 * This file contains the application configuration class
	 * @package SFATB
	 */
	/**
	 * Application configuration class
	 *
	 * Loads and parses XML configuration
	 * @package SFATB
	 */
	class Config {
		/**
		 * Associative multi-dimension array holding application configuration
		 * 
		 * Use this by calling <code>Config::$data[param]</code>
		 * @static
		 * @var array
		 */
		public static $data;
		
		/**
		 * Store configuration data internally by given XML structure
		 * 
		 * @static
		 * @param SimpleXmlElement the root element of XML configuration file
		 * opened by simplexml_load_file
		 */
		public static function load( $xml = null ) {
			if( $xml )
				self::$data = self::xml_to_array( $xml );
			else
				self::$data = array();
		}
		
		/**
		 * Load and parse language files to retrieve tranlsation data
		 * 
		 * @static
		 * @param string the language to load
		 * @return array the translations
		 */
		public static function load_language( $lang = 'en' ) {
			$base_dir = realpath( dirname( __FILE__ )."/../" );
			if( !is_file( "$base_dir/config/langs/$lang.xml" ) )
				$lang = 'en';
			$xml = simplexml_load_file( "$base_dir/config/langs/$lang.xml" );
			if( $xml )
				return self::xml_to_array( $xml );
			else
				return array();
		}
		
		/**
		 * Convert XML node structure to multi-dimension associative array
		 * 
		 * Recursive function that traverse the XML node structure and
		 * forms the target array of keys and values.
		 * @static
		 * @param SimpleXmlElement an element of the XML configuration file
		 * @return array the target associative array
		 */
		public static function xml_to_array( $xml ) {
			$result = array();
			$children = array();
			$same_name = false;
			foreach( $xml->children() as $elem )
				$children[] = $elem;
			foreach( $children as $key => $elem ) {
				$name = $elem->getName();
				if( $children[$key-1] && $name == $children[$key-1]->getName() )
					$same_name = true;
				if( $children[$key+1] && $name == $children[$key+1]->getName() )
					$same_name = true;
				if( $elem->children() )
					$val = self::xml_to_array( $elem );
				else
					$val = (string)$elem[0];
				if( $same_name )
					$result[] = $val;
				else
					$result[$name] = $val;
			}
			return $result;
		}
	}
?>