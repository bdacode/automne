<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Automne (TM)														  |
// +----------------------------------------------------------------------+
// | Copyright (c) 2000-2009 WS Interactive								  |
// +----------------------------------------------------------------------+
// | Automne is subject to version 2.0 or above of the GPL license.		  |
// | The license text is bundled with this package in the file			  |
// | LICENSE-GPL, and is available through the world-wide-web at		  |
// | http://www.gnu.org/copyleft/gpl.html.								  |
// +----------------------------------------------------------------------+
// | Author: S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
// +----------------------------------------------------------------------+
//
// $Id: filesManagement.php,v 1.1.1.1 2008/11/26 17:12:06 sebastien Exp $

/**
  * Class CMS_file
  *
  * This script aimed to manage files, directory and rights on server
  * Needs inherited classes to become more efficient
  *
  * @package CMS
  * @subpackage files
  * @author S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */
  
class CMS_file extends CMS_grandFather
{
	const FILE_SYSTEM = 1;
	const WEBROOT = 2;
	const TYPE_FILE = 0;
	const TYPE_DIRECTORY = 5;
	
	/**
	 * Contain full filename of the file or dir
	 * @var string
	 * @access public
	 */
	protected $_name = "";
	
	/**
	 * is the file or dir exists on the server
	 * @var boolean
	 * @access public
	 */
	protected $_exists = false;
	
	/**
	 * type of the file : file or dir
	 * @var integer : self::TYPE_FILE for file, self::TYPE_DIRECTORY for dir, false for undefined
	 * @access public
	 */
	protected $_type = false;
	
	/**
	 * file or dir perms
	 * @var string the octal current chmod value of the file/dir
	 * @access public
	 */
	protected $_perms = "";
	
	/**
	 * file content
	 * @var string the file content
	 * @access public
	 */
	protected $_content = "";
	
	/**
	 * directory of the file/dir
	 * @var string the directory of the file/dir
	 * @access public
	 */
	protected $_basedir = "";
	
	/**
	 * filename of the file (empty if current object is a directory)
	 * @var string the filename of the file
	 * @access public
	 */
	protected $_filename = "";
	
	/**
	 * Constructor
	 * 
	 * @param string $name, the full filename of the file or dir
	 * @param integer $from, the file path is : self::FILE_SYSTEM or self::WEBROOT
	 * @param integer $type, the type of the current object : self::TYPE_FILE for a file, self::TYPE_DIRECTORY for a dir, false for undefined
	 * @return void
	 */
	function __construct($name, $from=self::FILE_SYSTEM, $type=self::TYPE_FILE) {
		$this->_name = ($from==self::FILE_SYSTEM) ? $name : PATH_REALROOT_FS.$name;
		if ($this->_name) {
			if (@is_file($this->_name) && $type == self::TYPE_FILE) {
				$this->_type = self::TYPE_FILE;
				$this->_exists = true;
				$this->_perms = $this->getFilePerms($this->_name);
				$this->_basedir = dirname($this->_name);
				$this->_filename = basename($this->_name);
			} elseif (@is_dir($this->_name) && $type == self::TYPE_DIRECTORY) {
				$this->_type = self::TYPE_DIRECTORY;
				$this->_exists = true;
				$this->_perms = $this->getFilePerms($this->_name);
				$this->_basedir = $this->_name;
				$this->_filename = "";
			} else {
				$this->_exists = false;
				$this->_type = $type;
			}
		}
	}
	
	/**
	  * Gets the FS existence status of the file
	  *
	  * @return boolean true if file exists, false otherwise
	  * @access public
	  */
	function exists()
	{
		return $this->_exists;
	}
	
	/**
	  * Set the content of the file
	  *
	  * @param string/array $content : the content to set
	  * @return boolean true if file exists, false otherwise
	  * @access public
	  */
	function setContent($content, $allowSetBlank=false) {
		if ($content || $allowSetBlank) {
			if (is_string($content)) {
				if ($this->_type===self::TYPE_FILE) {
					$this->_content=$content;
					return true;
				} elseif($this->_type==self::TYPE_DIRECTORY) {
					$this->raiseError("Current object is a directory. Can't set content");
					return false;
				} else {
					$this->raiseError("Current object type not set. Can't set content");
					return false;
				}
			} elseif (is_array($content)) {
				if ($this->_type===self::TYPE_FILE) {
					$this->_content=implode("\n",$content);
					return true;
				} elseif($this->_type==self::TYPE_DIRECTORY) {
					$this->raiseError("Current object is a directory. Can't set content");
					return false;
				} else {
					$this->raiseError("Current object type not set. Can't set content");
					return false;
				}
			} else {
				$this->raiseError("Only can set string and array content");
				return false;
			}
		} else {
			$this->raiseError("Try to set blank content");
			return false;
		}
	}
	
	/**
	  * Get the content of the file as string
	  *
	  * @return string the current content
	  * @access public
	  */
	function getContent()
	{
		if(!$this->_content) {
			$this->readContent();
		}
		return $this->_content;
	}
	
	/**
	  * Alias of getFilename
	  *
	  * @return string the current content
	  * @access public
	  */
	function getName($withPath = true)
	{
		return $this->getFilename($withPath);
	}
	
	/**
	  * Get the full filesystem filename of the file or dir
	  *
	  * @return string the current filename
	  * @access public
	  */
	function getFilename($withPath = true)
	{
		return ($withPath) ? $this->_name : basename($this->_name);
	}
	
	/**
	  * Get the file extension if any (lowercase)
	  *
	  * @return string the current file extension
	  * @access public
	  */
	function getExtension()
	{
		return strtolower(pathinfo($this->_name, PATHINFO_EXTENSION));
	}
	
	/**
	  * Get the file size
	  *
	  * @param string $unit : the unit to return the value, accept 'M', 'K', '' or false to get the better one (default : false)
	  * @return string the current file size (with unit if any)
	  * @access public
	  */
	function getFileSize($unit = false)
	{
		if (!$this->exists()) {
			return false;
		}
		$filesize = @filesize($this->_name);
		if ($filesize !== false && $filesize > 0) {
			//convert in KB or MB
			if (($unit === false && $filesize > 1048576) || $unit == 'M') {
				$filesize = round(($filesize/1048576),2).' M';
			} elseif (($unit === false && $filesize > 1024) || $unit == 'K') {
				$filesize = round(($filesize/1024),2).' K';
			}
		} else {
			$filesize = '0';
		}
		return $filesize;
	}
	
	/**
	  * Get the file icon if any
	  *
	  * @param integer $from, the file path is : self::FILE_SYSTEM or self::WEBROOT (default : file system)
	  * @return string the current file icon
	  * @access public
	  */
	function getFileIcon($from=self::FILE_SYSTEM) {
		$extension = $this->getExtension();
		if (!$extension) {
			return false;
		}
		if (file_exists(PATH_MODULES_FILES_FS.'/'.MOD_STANDARD_CODENAME.'/icons/'.$extension.'.gif')) {
			return (($from == self::FILE_SYSTEM) ? PATH_MODULES_FILES_FS : PATH_MODULES_FILES_WR).'/'.MOD_STANDARD_CODENAME.'/icons/'.$extension.'.gif';
		}
		return false;
	}
	
	/**
	  * Get the file path
	  *
	  * @param integer $from, the file path is : self::FILE_SYSTEM or self::WEBROOT (default : file system)
	  * @return string the current file path
	  * @access public
	  */
	function getFilePath($from=self::FILE_SYSTEM) {
		$this->_basedir = dirname($this->_name);
		if ($from == self::FILE_SYSTEM) {
			return $this->_basedir;
		} else {
			if (!APPLICATION_IS_WINDOWS) {
				return str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath($this->_basedir));
			} else {
				return str_replace(DIRECTORY_SEPARATOR, '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath($this->_basedir)));
			}
		}
	}
	
	/**
	  * Read the content of the file
	  *
	  * @param string $returnAs : the content format to get : string / array (array of lines) / csv (explode csv file)
	  *  if requested file is a directory then return the content of the directory (always array in this case)
	  * @param string $array_map_function : the function to apply at all the content (only for a file content returned as an array). trim by default.
	  * @param array $csvargs : only for csv format : the csv file delimiter and enclosure chars (default : array('delimiter' => ';', 'enclosure' => '"', 'strict' => true))
	  * @return string/array the current content
	  * @access public
	  */
	function readContent($returnAs="string",$array_map_function="trim", $csvargs = array('delimiter' => ';', 'enclosure' => '"', 'strict' => true))
	{
		if ($this->_exists) {
			if ($this->_type===self::TYPE_FILE) {
				if ($returnAs=="string") {
					$this->_content=file_get_contents($this->_name);
					return $this->_content;
				} elseif($returnAs=="array") {
					$this->_content = file_get_contents($this->_name);
					if ($array_map_function && function_exists($array_map_function)) {
						$array = array_map($array_map_function,file($this->_name));
					} else {
						$array = file($this->_name);
					}
					return $array;
				} elseif($returnAs=="csv") {
					if (!isset($csvargs['delimiter'])) {
						$csvargs['delimiter'] = ';';
					}
					if (!isset($csvargs['enclosure'])) {
						$csvargs['enclosure'] = '"';
					}
					if (!isset($csvargs['strict'])) {
						$csvargs['strict'] = true;
					}
					$this->_content = file_get_contents($this->_name);
					$count = 0;
					$handle = @fopen($this->_name, 'rb');
					while (($data = fgetcsv($handle, 4096, $csvargs['delimiter'], $csvargs['enclosure'])) !== false) {
						if ($array_map_function && function_exists($array_map_function)) {
							$data = array_map($array_map_function,$data);
						}
						//at first line, get number of values/lines of CSV array
						if ($csvargs['strict']) {
							if (!$count) {
								$num = count($data);
							} 
							//check for number of values in current line (tolerance is one because last CSV value can be empty so PHP array is smaller)
							elseif (sizeof($data) != $num && (sizeof($data)+1) != $num && (sizeof($data)-1) != $num) {
								$this->raiseError("Invalid CSV content file : column count does not match : ".sizeof($data)." should be ".$num." at line ".($count+1));
								return false;
							}
						}
						$array[] = $data;
						$count++;
					}
					return $array;
				} else {
					$this->raiseError("Invalid method for reading content : ".$returnAs);
					return false;
				}
			} elseif ($this->_type===self::TYPE_DIRECTORY) {
				$directory = dir($this->_name);
				$array=array();
				while (false !== ($file = $directory->read())) {
					if ($file!='.' && $file!='..') {
						$array[]=$file;
					}
				}
				return $array;
			} else {
				$this->raiseError("Current object is a directory. Can't read content");
				return false;
			}
		} else {
			$this->raiseError("File ".$this->_name." does not exist. Please write to persistence first");
			return false;
		}
	}
	
	/**
	  * Writes the file into persistence (FS)
	  *
	  * @param boolean $allowWriteBlank : allow write of an empty file (default false)
	  * @param boolean $createFolder : allow the creation of file folder if not exists (default : false)
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function writeToPersistence($allowWriteBlank = false, $createFolder = false)  {
		if ($this->_type==self::TYPE_FILE) {
			if (!$allowWriteBlank && !$this->_content) {
				$this->raiseError("Try to write an empty file");
				return false;
			}
			if ($this->exists()) {
				if (!$this->makeWritable($this->_name)) {
					$this->raiseError("Can't write file ".$this->_name);
					return false;
				}
			}
			
			if (!$f = @fopen($this->_name,'wb')) {
				if ($createFolder && CMS_file::makeDir($this->_name)) {
					if (!$f = @fopen($this->_name,'wb')) {
						$this->raiseError("Can't open file for writing and can't create directory for: ".$this->_name);
						return false;
					}
				} else {
					$this->raiseError("Can't open file for writing file: ".$this->_name);
					return false;
				}
			}
			if (!@fwrite($f, $this->_content)) {
				$this->raiseError("Can't write file ".$this->_name);
				return false;
			} else {
				$this->_exists=true;
			}
			@fclose($f);
			$this->chmod(FILES_CHMOD);
			return true;
		} else {
			if (!@mkdir($this->_name)) {
				$this->raiseError("Can't write directory ".$this->_name);
				return false;
			} else {
				$this->_exists=true;
				$this->chmod(DIRS_CHMOD);
			}
			return true;
		}
	}
	
	/**
	 * function delete
	 * Delete the  file or folder (recursively)
	 * @return boolean true on success, false on failure
	 */
	function delete() {
		if (!$this->exists()) {
			return false;
		}
		return $this->deleteFile($this->_name);
	}
	
	/**
	 * function chmod
	 * Try to chmod this file / directory.
	 * @param string $right, the 3 or 4 octal numbers to set (775, 664, 0664, etc.)
	 * @return boolean true on success, false on failure
	 */
	function chmod($right)
	{
		if ($this->_exists) {
			//if ($this->_type===self::TYPE_FILE) {
				$right = (strlen($right) == 3) ? '0'.$right : $right;
				return @chmod($this->_name,octdec($right));
			/*} elseif($this->_type==self::TYPE_DIRECTORY) {
				return CMS_file::makeExecutable($this->_name);
			} else {
				$this->raiseError("Current object type not set. Can't chmod it");
				return false;
			}*/
		} else {
			$this->raiseError("Can't chmod file who does not exist : ".$this->_name);
			return false;
		}
	}
	
	// ****************************************************************
	// ** BELOW THIS POINT, ALL METHOD ARE STATIC                    **
	// ****************************************************************
	
	/**
	 * function getFilePerms
	 * get current file permissions
	 * @param string $file, the full filename of the file or dir
	 * @param string $type, type of the return : octal value or decimal value
	 * @return octal value of the file
	 * @static
	 */
	function getFilePerms($file, $type="octal") 
	{
		return ($type=="octal") ? @fileperms($file) : substr(sprintf('%o',@fileperms($file)), -4);
	}
	
	/**
	 * function isDeletable
	 * is file deletable ?
	 * @param string $file, the full filename of the file or dir
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function isDeletable($f)
	{
		if (is_file($f)) {
			return is_writable(dirname($f));
		} elseif (is_dir($f)) {
			//todo
			//return (is_writable(dirname($f)) && count(files::scandir($f)) <= 2);
		}
	}
	
	/**
	 * function deleteFile
	 * Delete a file or folder (recursively)
	 * @param string $file, the full filename of the file or dir
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function deleteFile($file)
	{
		if (is_file($file)) {
			return @unlink($file);
		} elseif (@is_dir($file)) {
			return CMS_file::deltree($file, true);
		} else {
			CMS_grandFather::raiseError("Try to delete a file or dir who does not exists : ".$file);
			return false;
		}
	}
	
	/**
	 * function deltree (rm -rf)
	 * Delete a directory and all subdirectories and files (recursively)
	 * @param string $file, the full filename of the file or dir
	 * @param boolean $withDir, delete also the dir $file
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function deltree($dir, $withDir=false)
	{
		$current_dir = @opendir($dir);
		while($entryname = @readdir($current_dir)) {
			if (@is_dir($dir.'/'.$entryname) && ($entryname != '.' && $entryname!='..')) {
				if (!CMS_file::deltree($dir.'/'.$entryname,true)) {
					return false;
				}
			} elseif ($entryname != '.' and $entryname!='..') {
				if (!@unlink($dir.'/'.$entryname)) {
					CMS_file::makeWritable($dir.'/'.$entryname);
					if (!@unlink($dir.'/'.$entryname)) {
						return false;
					}
				}
			}
		}
		@closedir($current_dir);
		if ($withDir) {
			if (!@rmdir($dir)) {
				CMS_file::makeWritable($dir);
				if (!@rmdir($dir)) {
					return false;
				} else {
					return true;
				}
			} else {
				return true;
			}
		} else {
			return true;
		}
	}
	
	/**
	 * function deltreeSimulation (rm -rf)
	 * Simulate the delete a directory and all subdirectories and files (recursively)
	 * @param string $file, the full filename of the file or dir
	 * @param boolean $withDir, delete also the dir $file
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function deltreeSimulation($dir, $withDir=false)
	{
		$current_dir = @opendir($dir);
		while($entryname = @readdir($current_dir)) {
			if (@is_dir($dir.'/'.$entryname) && ($entryname != '.' && $entryname!='..')) {
				if (!CMS_file::deltreeSimulation($dir.'/'.$entryname,true)) {
					return false;
				}
			} elseif ($entryname != '.' and $entryname!='..') {
				if (!CMS_file::makeWritable($dir.'/'.$entryname)) {
					return false;
				}
			}
		}
		@closedir($current_dir);
		if ($withDir) {
			if (!CMS_file::makeWritable($dir)) {
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}
	
	/**
	 * function makeReadable
	 * Try to make a file readable if it's not the case (and executable for a dir)
	 * @param string $f, the full filename of the file or dir
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function makeReadable($f)
	{
		$chmodValue = (@is_dir($f)) ? 7:4;
		
		if (APPLICATION_IS_WINDOWS) {
			$chmodValue = 6;
		}
		if (is_readable($f)) {
			return true;
		} else {
			@chmod($fpath, octdec(FILES_CHMOD));
			if (@is_readable($f)) {
				return true;
			} else {
				@chmod($f,octdec('0'.$chmodValue.$chmodValue.'4'));
				if (@is_readable($f)) {
					return true;
				} else {
					@chmod($f,octdec('0'.$chmodValue.$chmodValue.$chmodValue));
					if (@is_readable($f)) {
						return true;
					} else {
						return false;
					}
				}
			}
		}
	}
	
	/**
	 * function makeWritable
	 * Try to make a file writable if it's not the case (and executable for a dir)
	 * @param string $f, the full filename of the file or dir
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function makeWritable($f)
	{
		if (@is_dir($f)) {
			return CMS_file::makeExecutable($f);
		}
		if (is_writable($f)) {
			return true;
		} else {
			@chmod($fpath, octdec(FILES_CHMOD));
			if (@is_writable($f)) {
				return true;
			} else {
				@chmod($f,octdec('0664'));
				if (@is_writable($f)) {
					return true;
				} else {
					@chmod($f,octdec('0666'));
					if (@is_writable($f)) {
						return true;
					} else {
						return false;
					}
				}
			}
		}
	}
	
	/**
	 * function makeExecutable
	 * Try to make a file executable if it's not the case
	 * @param string $f, the full filename of the file or dir
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function makeExecutable($f)
	{
		if (function_exists('is_executable') && @is_executable($f)) {
			return true;
		} elseif (!function_exists('is_executable')) {
			//assume we are on windows platform because this function does not exists before PHP5.0.0 (so files are always executable)
			return true;
		} else {
			@chmod($fpath, octdec(DIRS_CHMOD));
			if (CMS_file::fileIsExecutable($f)) {
				return true;
			} else {
				@chmod($f,octdec('0775'));
				if (CMS_file::fileIsExecutable($f)) {
					return true;
				} else {
					@chmod($f,octdec('0777'));
					if (CMS_file::fileIsExecutable($f)) {
						return true;
					} else {
						return false;
					}
				}
			}
		}
	}
	
	/**
	 * function fileIsExecutable
	 * Is file or dir executable (this function exists because php function is_executable does not work on directories)
	 * @param string $f, the full filename of the file or dir
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function fileIsExecutable($f) {
		if (@is_dir($f)) {
			return @file_exists($f."/.");
		} elseif (function_exists("is_executable")) {
			return @is_executable($f);
		} else {
			return true;
		}
	}
	
	/**
	 * function chmodFile
	 * Try to chmod a file (a dir is redirected to makeExecutable method).
	 * @param string $right, the 3 or 4 octal numbers to set (775, 664, 0664, etc.)
	 * @param string $file, the full filename of the file or dir
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function chmodFile($right,$file)
	{
		if (@is_dir($file)) {
			return CMS_file::makeExecutable($file);
		} elseif(@is_file($file)) {
			$right = (strlen($right) == 3) ? '0'.$right : $right;
			return @chmod($file,octdec($right));
		} else {
			CMS_grandFather::raiseError("Can't chmod file who does not exist : ".$file);
			return false;
		}
	}
	
	/**
	 * function makeDir
	 * Try to create a dir (and all parents if needed)
	 * @param string $f, the full filename of the file or dir
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function makeDir($f)
	{
		//create directories recursively 
		if (!@is_dir(dirname($f))) {
			CMS_file::makeDir(dirname($f));
		}
		if (!@file_exists($f)) {
			if (@mkdir($f) === false) {
				return false;
			}
			@chmod($f,fileperms(dirname($f)));
		}
		return true;
	}
	
	/**
	 * function copyTo
	 * Try to copy a file (and create all parents if needed)
	 * @param string $from, the full filename of the file to copy
	 * @param string $to, the full filename of the file copied
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function copyTo($from,$to)
	{
		if (@is_file($from)) {
			//check if parent directory exist else create it
			if (!@is_dir(dirname($to))) {
				CMS_file::makeDir(dirname($to));
			}
			//copy the file
			return @copy($from, $to);
		} else {
			//create directory
			return CMS_file::makeDir($to);
		}
	}
	
	/**
	 * function moveTo
	 * Try to move a file (and create all parents if needed)
	 * @param string $from, the full filename of the file to move
	 * @param string $to, the full filename of the file moved
	 * @return boolean true on success, false on failure
	 * @static
	 */
	function moveTo($from,$to)
	{
		if (@is_file($from)) {
			//check if parent directory exist else create it
			if (!@is_dir(dirname($to))) {
				CMS_file::makeDir(dirname($to));
			}
			//move the file (ie : rename it)
			return rename($from, $to);
		} else {
			//create directory
			return CMS_file::makeDir($to);
		}
	}
	
	/**
	 * function getFileList
	 * Get an entire listing of files
	 * @param string $file, the full filename of the file to get
	 * @return array (view CMS_archive::list_files to complete description)
	 * @static
	 */
	function getFileList ($file)
	{
		$return=array();
		if (strstr($file, "*")) {
			//use archive class list_files function because it is really efficient
			$tempArchive = new CMS_archive('temp');
			$return = $tempArchive->list_files($file);
		} elseif (@is_dir($file)) {
			$return[] =  array(
				'name' => $file,
				'name2' => $file,
				'type' => self::TYPE_DIRECTORY
			);
		} elseif (@is_file($file)) {
			$return[] =  array(
				'name' => $file,
				'name2' => $file,
				'type' => self::TYPE_FILE
			);
		}
		return $return;
	}
	
	/**
	 * function getParent
	 * Get the first parent dir who exists of a file
	 * @param string $file, the full filename of the file to get
	 * @return string : the parent dir filename
	 * @static
	 */
	function getParent($file) {
		if (@file_exists(dirname($file))) {
			return dirname($file);
		} else {
			return CMS_file::getParent(dirname($file));
		}
	}
	
	/**
	  * get temporary path
	  *
	  * @return string the temporary path
	  * @access public
	  * @static
	 */
	function getTmpPath() {
		$tmpPath = '';
		if (@is_dir(ini_get("session.save_path")) && is_object(@dir(ini_get("session.save_path")))) {
			$tmpPath = ini_get("session.save_path");
		} elseif(@is_dir(PATH_PHP_TMP) && is_object(@dir(PATH_PHP_TMP))) {
			$tmpPath = PATH_PHP_TMP;
		} elseif (@is_dir(PATH_TMP_FS) && is_object(@dir(PATH_TMP_FS))){
			$tmpPath = PATH_TMP_FS;
		} else {
			CMS_grandFather::raiseError('Can\'t found temporary path ...');
			return false;
		}
		if (!@is_writable($tmpPath)) {
			CMS_grandFather::raiseError('Can\'t write in temporary path : '.$tmpPath);
			return false;
		}
		return $tmpPath;
	}
	
	/**
	  * get mime type of a given file
	  *
	  * @param string $file : the file location to get mime type (relative to FS) or none to use method on current object
	  * @return string the mime type founded or false if file does not exists. application/octet-stream is returned if no type founded
	  * @access public
	  * @static
	  */
	function mimeContentType($file='') {
		if (!$file && isset($this)) {
			if ($this->exists() && $this->_type === self::TYPE_FILE) {
				$file = $this->_name;
			}
		}
		if (!$file) {
			return false;
		}
		$return = '';
		if (function_exists('exec') && !APPLICATION_IS_WINDOWS) {
			$return = trim(exec('file -bi ' . escapeshellarg($file)));
		}
		if (!$return && file_exists(PATH_AUTOMNE_MIMETYPE_FS)) {
			$ext=array_pop(explode('.',$file));
			foreach(file(PATH_AUTOMNE_MIMETYPE_FS) as $line) {
				if(preg_match('/^([^#]\S+)\s+.*'.$ext.'.*$/',$line,$m)) {
					$return = $m[1];
					break;
				}
			}
		}
		if (!$return && function_exists('mime_content_type')) {
			$return = mime_content_type($file);
		}
		return ($return) ? $return : 'application/octet-stream';
	}
	
	/**
	  * return the max uploadable file size
	  *
	  * @param string $unit : the unit to return the value, accept 'M' or 'K' (default : 'M')
	  * @return string the max uploadable file size
	  * @access public
	  * @static
	  */
	function getMaxUploadFileSize($unit = 'M') {
		$max = (int) (ini_get("upload_max_filesize") < ini_get("post_max_size")) ? ini_get("upload_max_filesize") : ini_get("post_max_size");
		if ($unit == 'M') {
			return substr($max, 0, -1);
		} elseif($unit == 'K') {
			return $max * 1024;
		}
	}
	
	/**
	  * Send a group of files to client (ie : JS or CSS files)
	  * Provide coherent user caching infos (1 month) for files and allow gzip when possible
	  *
	  * @param array $files : array of files path to send to client (FS relative)
	  * @param string $contentType : the content type to send to client (default : text/html)
	  * @return void
	  * @access public
	  * @static
	  */
	static function sendFiles($files, $contentType = 'text/html') {
		//check for the closest last modification date
		$lastdate = '';
		foreach ($files as $file) {
			if (file_exists($file)) {
				$lastdate = (filemtime($file) > $lastdate) ? filemtime($file) : $lastdate;
			}
		}
		if (file_exists($_SERVER['SCRIPT_FILENAME'])) {
			$lastdate = (filemtime($_SERVER['SCRIPT_FILENAME']) > $lastdate) ? filemtime($_SERVER['SCRIPT_FILENAME']) : $lastdate;
		}
		//check If-Modified-Since header if exists then return a 304 if needed
		if (isset($_SERVER['IF-MODIFIED-SINCE']) && ($ifModifiedSince = strtotime($_SERVER['IF-MODIFIED-SINCE']))) {
			if ($lastdate <= $ifModifiedSince) {
				header('HTTP/1.1 304 Not Modified');
				header('Content-Type: '.$contentType);
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastdate) . ' GMT');
				header('Expires: ' . gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT'); //30 days
				header('Cache-Control: private, max-age=2592000');
				header('Pragma: private');
				exit;
			}
		}
		//Cache options
		$frontendOptions = array(
			'lifetime' => null, // cache never expire
			'caching' => true
		);
		$backendOptions = array(
			'cache_dir' => PATH_MAIN_FS.'/cache/', // Directory where to put the cache files
			'cache_file_umask' => octdec(FILES_CHMOD),
			'hashed_directory_umask' => octdec(DIRS_CHMOD),
		);
		// getting a Zend_Cache_Core object
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		$compress = 'ob_gzhandler' != ini_get('output_handler') && extension_loaded( 'zlib' ) && !ini_get('zlib.output_compression');
		//create cache id from files, compression status and last time files access
		$id = md5(implode(',',$files).'-'.$compress.'-'.$lastdate);
		$datas = '';
		if (!($datas = $cache->load($id))) {
			// datas cache missing so create it
			foreach ($files as $file) {
				if (file_exists($file)) {
					$datas .= file_get_contents($file);
				}
			}
			//minimize JS files if needed
			if (!SYSTEM_DEBUG && $contentType == 'text/javascript') {
				$datas = JSMin::minify($datas);
			}
			//minimize CSS files if needed
			if (!SYSTEM_DEBUG && $contentType == 'text/css') {
				$datas = cssmin::minify($datas);
			}
			//compres data if needed
			if ($compress) {
				$datas = gzencode($datas, 9);
			}
			$cache->save($datas, $id);
		}
		//send headers
		header('Content-Type: '.$contentType);
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastdate) . ' GMT');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT'); //30 days
		header('Cache-Control: private, max-age=2592000');
		header('Pragma: private');
		//send gzip header if needed
		if ($compress) {
			header("Content-Encoding: gzip");
		}
		//send content
		echo $datas;
		exit;
	}
}
?>