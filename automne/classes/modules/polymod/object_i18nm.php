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
// $Id: object_i18nm.php,v 1.1.1.1 2008/11/26 17:12:06 sebastien Exp $

/**
  * Class CMS_object_i18nm
  *
  * represent a i18nm message
  *
  * @package CMS
  * @subpackage module
  * @author S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_object_i18nm extends CMS_grandFather
{
	/**
	  * Integer ID
	  * @var integer
	  * @access private
	  */
	protected $_ID;
	
	/**
	  * languages codes priority (used for missing languages)
	  * @var array
	  * @access private
	  */
	protected $_languageCodesPriority = array('fr','en');
	
	/**
	  * languages labels
	  * @var array(string "languageCode" => string "label")
	  * @access private
	  */
	protected $_languageLabels = array('fr' => 'Fran�ais','en' => 'English');
	
	/**
	  * all values by languageCode
	  * @var array	(string "languageCode" => string "value")
	  * @access private
	  */
	protected $_values = array();
	
	/**
	  * all values allready in DB
	  * @var array	(string "languageCode")
	  * @access private
	  */
	protected $_DBKnown = array();
	
	/**
	  * Constructor.
	  * initialize object.
	  *
	  * @param integer $id DB id
	  * @param array $dbValues DB values
	  * @return void
	  * @access public
	  */
	function __construct($id = 0, $dbValues=array())
	{
		static $i18nm;
		//load languages
		$this->getAvailableLanguages();
		if ($id) {
			if (!SensitiveIO::isPositiveInteger($id)) {
				$this->raiseError("Id is not a positive integer : ".$id);
				return;
			}
			if (!isset($i18nm[$id])) {
				if ($id && !$dbValues) {
					$sql = "
						select
							*
						from
							mod_object_i18nm
						where
							id_i18nm ='".$id."'
					";
					$q = new CMS_query($sql);
					if ($q->getNumRows()) {
						$this->_ID = $id;
						while ($arr = $q->getArray()) {
							$this->_values[$arr["code_i18nm"]] = $arr['value_i18nm'];
							$this->_DBKnown[] = $arr["code_i18nm"];
						}
					} else {
						$this->raiseError("Unknown ID :".$id);
						return;
					}
				} elseif($id && is_array($dbValues) && $dbValues) {
					$this->_ID = $id;
					foreach ($dbValues as $code => $value) {
						$this->_values[$code] = $value;
						$this->_DBKnown[] = $code;
					}
				}
				$i18nm[$id] = $this;
			} else {
				//$this = $GLOBALS["polyModule"]["i18nm"][$id];
				$this->_ID = $id;
				$this->_values = $i18nm[$id]->_values;
				$this->_DBKnown = $i18nm[$id]->_DBKnown;
			}
		}
	}
	
	/**
	  * Get object ID
	  *
	  * @return integer, the DB object ID
	  * @access public
	  */
	function getID()
	{
		return isset($this->_ID) ? $this->_ID : null;
	}
	
	/**
	  * Get available languages codes
	  *
	  * @return array, the available languages codes
	  * @access public
	  * @static
	  */
	function getAvailableLanguages() {
		static $availableLanguages, $languagesPriority;
		if (!is_array($availableLanguages)) {
			$availableLanguages = array();
			//check for polymod properly loaded
			$module =  (class_exists('CMS_polymod')) ? MOD_POLYMOD_CODENAME : '';
			//order by dateFormat to get fr in first place
			$languages = CMS_languagesCatalog::getAllLanguages($module,"dateFormat_lng");
			foreach ($languages as $language) {
				$availableLanguages[$language->getCode()] = $language->getLabel();
				$languagesPriority[] = $language->getCode();
			}
		}
		if(isset($this)) {
			$this->_languageLabels = $availableLanguages;
			$this->_languageCodesPriority = $languagesPriority;
		}
		return array_keys($availableLanguages);
		//$tmp = new CMS_object_i18nm();
		//return array_keys($tmp->_languageLabels);
	}
	
	/**
	  * Sets a value for a given language code.
	  *
	  * @param string $languageCode the language code of the value to set
	  * @param mixed $value the value to set
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function setValue($languageCode, $value)
	{
		if (strlen($languageCode) > 2) {
			$this->raiseError("Can't use a language code longuer than 2 caracters : ".$languageCode);
			return false;
		}
		$this->_values[$languageCode] = $value;
		return true;
	}
	
	/**
	  * get a value for a given language code if exist, else, return value by priority
	  *
	  * @param string $languageCode the language code of the value to get
	  * @param boolean $usePriority : use priority system if value is not found for given code
	  * @return string, the value
	  * @access public
	  */
	function getValue($languageCode = '', $usePriority = true)
	{
		if ($languageCode && isset($this->_values[$languageCode])) {
			return $this->_values[$languageCode];
		}
		if ($usePriority) {
			$return = "";
			foreach ($this->_languageCodesPriority as $priorityCode) {
				if (isset($this->_values[$priorityCode])) {
					return $this->_values[$priorityCode];
				}
			}
		}
		return "";
	}
	
	/**
	  * get HTML admin (used to enter object values in admin)
	  *
	  * @return string : the html admin
	  * @access public
	  */
	function getHTMLAdmin($prefixName, $textareaInput=false) {
		$html = '<table border="0" cellpadding="3" cellspacing="0" style="border-left:1px solid #4d4d4d; width:400px;">';
		$count = 0;
		foreach ($this->_languageLabels as $languageCode => $languageLabel) {
			$required = (!$count) ? '<span class="admin_text_alert">*</span> ':'';
			$input = (!$textareaInput) ? '<input type="text" size="30" name="'.$prefixName.$languageCode.'" class="admin_input_text" value="'.htmlspecialchars($this->getValue($languageCode, false)).'" />':'<textarea name="'.$prefixName.$languageCode.'" class="admin_long_textarea" cols="45" rows="2">'.htmlspecialchars($this->getValue($languageCode, false)).'</textarea>';
			$html .= '
			<tr>
				<td class="admin" align="right" style="width:80px;">'.$required.$languageLabel.'</td>
				<td class="admin">'.$input.'</td>
			</tr>';
			$count++;
		}
		$html .='
		<input type="hidden" name="'.$prefixName.'" value="'.$this->getID().'" />
		</table>';
		return $html;
	}
	
	/**
	  * Writes object into persistence (MySQL for now), along with base data.
	  *
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function writeToPersistence()
	{
		$valuesToSet = $this->_values;
		$ok = true;
		if (is_array($valuesToSet) && $valuesToSet) {
			//first update code allready known in DB
			if (is_array($this->_DBKnown) && $this->_DBKnown && $this->_ID) {
				foreach ($this->_DBKnown as $aKownCode) {
					$sql = "
						update
							mod_object_i18nm
						set
							value_i18nm='".SensitiveIO::sanitizeSQLString($this->_values[$aKownCode])."'
						where
							id_i18nm='".$this->_ID."'
							and code_i18nm='".$aKownCode."'
					";
					$q = new CMS_query($sql);
					if ($q->hasError()) {
						$this->raiseError("Can't update value for code : ".$aKownCode);
						$ok = false;
					} else {
						unset($valuesToSet[$aKownCode]);
					}
				}
			}
			//then, add the rest of the values
			if (is_array($valuesToSet) && $valuesToSet) {
				foreach ($valuesToSet as $code => $value) {
					//save data
					$sql_fields = "
						code_i18nm='".SensitiveIO::sanitizeSQLString($code)."',
						value_i18nm='".SensitiveIO::sanitizeSQLString($value)."'
					";
					if ($this->_ID) {
						$sql = "
							insert into
								mod_object_i18nm
							set
								id_i18nm='".$this->_ID."',
								".$sql_fields;
					} else {
						$sql = "
							insert into
								mod_object_i18nm
							set
								".$sql_fields;
					}
					$q = new CMS_query($sql);
					if ($q->hasError()) {
						$this->raiseError("Can't save object");
						$ok = false;
					} elseif (!$this->_ID) {
						$this->_ID = $q->getLastInsertedID();
					}
				}
			}
			unset($GLOBALS["polyModule"]["i18nm"][$this->_ID]);
			return $ok;
		} else {
			$this->raiseError("No values to write");
			return false;
		}
	}
	
	/**
	  * Destroy this object, in DB
	  *
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function destroy () {
		if ($this->_ID) {
			$sql = "delete from
						mod_object_i18nm
					where
						id_i18nm='".$this->_ID."'
			";
			$q = new CMS_query($sql);
			if ($q->hasError()) {
				$this->raiseError("Can't destroy object");
				return false;
			}
		}
		unset($this);
		return true;
	}
}
?>