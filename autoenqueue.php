<?php

/*
 * Name: Auto Enqueue
 * Description: This class allows you to easily enqueue CSS and JS files. You just copy the file in your CSS or JS folder
 * and it will be enqueued.
 * Author: Stefan Malic
 * Author URI: http://www.stefanmalic.com/
 * File version: 1.0.0
 * License: GNU/GPL
 */

Class AutoEnqueue {

	// Asset folder is a path to the folder where you keep your CSS and JS folders.
	// Asset folder link is a link to your asset folder.
	// And plugin version is your plugin/theme version.

	/*
	 * Here's the code I use to get these values:
	 * define( 'PLUGIN_ASSET_FOLDER', plugin_dir_path(__FILE__).'assets'.DIRECTORY_SEPARATOR );
	 * define( 'PLUGIN_ASSET_FOLDER_LINK', plugins_url( PLUGIN_FOLDER_NAME.'/assets/' ) );
	 * define( 'PLUGIN_VERSION', '1.0.0' );
	 */

	private $asset_folder = PLUGIN_ASSET_FOLDER;
	private $asset_folder_link = PLUGIN_ASSET_FOLDER_LINK;
	private $plugin_version = PLUGIN_VERSION;

	public function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'enqueue_all'));
	}

	function generate_file_handle($file) {
	// Remove the period from the file name
		$file = str_replace(".", "", $file);
	// Make the file name an MD5 hash
		$file = md5($file);
	// Get the file name length
		$fileLen = strlen($file);
	// Cut the file name(now an MD5 hash) into half
		$file = substr($file, 0, $fileLen/2);
		return $file;
	}

	function extract_file_name($file) {
	// Find the last slash
		$lastSlash = strrpos($file, '/');
		$lastSlash += 1;
		$fileLen = strlen($file);
	// Remove everything prior to the slash, including the slash
		$file = substr($file, $lastSlash, $fileLen);
		return $file;
	}

	function get_file_ext($file) {
	// Separate file extension from the file name
		$file = $this->extract_file_name($file);
	// Find the period
		$dotPosition = strpos($file, '.');
	// Get file name length
		$fileLen = strlen($file);
	// Increase the dotPosition by 1, so the substr() function can extract the extension without the period
		$dotPosition += 1;
		return substr( $file, $dotPosition, $fileLen );
	}

	function check_file_extension($file) {
	// if it's .css or .js, it's okay, otherwise return false
		$ext = $this->get_file_ext($file);
		if( $ext <> "css" && $ext <> "js" ) return false;
		else return true;
	}

	function get_file_list($folder, $files = null) {
	// fetches everything in the folder
		$all = scandir($folder);
	// Array in which we'll store file names
		if( $files == null || empty($files) )
			$files = array();

	// Remove the . and .. from the file/folder listing
		$all = array_values( array_diff( $all, array('..', '.') ) );
	// loops through every item. Add files to the file array, else loop through any sub-folders and do the same.
		for ($i=0; $i < count($all); $i++) {
			// Absolute path to file. This is necessary as without it is_dir() won't work
			if( $all[$i] == NULL ) continue;
			$path_to_file = $folder.DIRECTORY_SEPARATOR.$all[$i];
			// If the current item is a directory, loop through it.
			// If the dir has any files, merge them with the current $files array.
			// Recursion, fuck yeah.
			if( is_dir($path_to_file) ) $files = array_merge($files, $this->get_file_list($path_to_file, $files) );
			else {
				$folder_link = $this->get_folder_link($path_to_file);
				$files[] = $folder_link."/".$all[$i]; // else if the item is a file, add it to the file list
			}
		}
		return $files;
	}

	function get_folder_link($folder) {
		$asset_folder = $this->asset_folder;
		$asset_folder = str_replace(array("/", "\\"), "#", $asset_folder); // replace slashes with hashtags
		$folder = str_replace(array("/", "\\"), "#", $folder); // same here
		$folder = str_replace($asset_folder, '', $folder); // strip the entire path only to asset folders
		$folder = str_replace("##", "#", $folder); // remove duplicate hashtags

		if( strrpos($folder, "#") !== false ) $lastSlashPosition = strrpos($folder, "#");

		$folder = substr($folder, 0, $lastSlashPosition); // remove the file from the path to be left with the folder
		$folder = str_replace("#", "/", $folder); // revert back from #'s to /'s

		// return PLUGIN_ASSET_FOLDER_LINK.$folder;
		return $this->asset_folder_link.$folder;
	}

	function get_deps($file) {
		$ext = $this->get_file_ext($file);
		if( $ext == "css" )
			return false;
		else
			if( $ext == "js" )
				return "jquery";
			else return false;
	}

	function enqueue_script($file) {
	// Generate the file handle for this particular file
		$handle = $this->generate_file_handle($file);
	// Define dependecy array
		$deps = array();
	// Get dependencies for this file. If it's CSS, no dependencies, if it's JS, use jQuery as a dependency
		if( $this->get_deps($file) !== false ) $deps = array($this->get_deps($file));
	// Get extension
		$ext = $this->get_file_ext($file);
	// Finally, check the file extension and call the appropriate script
		if( $ext == "js" )
			wp_enqueue_script( $handle, $file, $deps, $this->plugin_version, false );
		if( $ext == "css" ) {
			wp_enqueue_style( $handle, $file, $deps, $this->plugin_version, 'all' );
		}
	}

	function enqueue_all() {
		// First deal with CSS
		$cssFolder = $this->asset_folder.'css';
		$cssFiles = $this->get_file_list($cssFolder);
		if( $cssFiles !== NULL ) {
			foreach ($cssFiles as $file) {
				$this->enqueue_script($file);
			}
		}

		// Now deal with JS
		$jsFolder = $this->asset_folder.'js';
		$jsFiles = $this->get_file_list($jsFolder);
		if( $jsFiles !== NULL ) {
			foreach ($jsFiles as $file) {
				$this->enqueue_script($file);
			}
		}
	}
}
$scripts = new AutoEnqueue;
