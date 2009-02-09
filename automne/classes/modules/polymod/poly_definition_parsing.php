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
// | Author: S?stien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
// +----------------------------------------------------------------------+
//
// $Id: poly_definition_parsing.php,v 1.3 2009/02/03 14:27:25 sebastien Exp $

/**
  * Class CMS_polymod_definition_parsing
  *
  * represent a polymod parsing : from a given string definition and some parameters, create HTML / PHP datas
  *
  * @package CMS
  * @subpackage polymod
  * @author S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_polymod_definition_parsing extends CMS_grandFather
{
	const OUTPUT_PHP = 1;
	const OUTPUT_RESULT = 2;
	const PARSE_MODE = 1;
	const CHECK_PARSING_MODE = 2;
	const BLOCK_PARAM_MODE = 4;
	
	/**
	  * The definition to parse
	  * @var string
	  * @access private
	  */
	protected $_definition;
	
	/**
	  * The definition parsed
	  * @var multidimensionnal array
	  * @access private
	  */
	protected $_definitionArray = array();
	
	/**
	  * All callbacks functions names for each tags
	  * @var array
	  * @access private
	  */
	protected $_tagsCallBack = array(
			'atm-search' 			=> '_searchTag',
			'atm-result' 			=> '_searchResultTag',
			'atm-noresult' 			=> '_searchNoResultTag',
			'atm-search-param' 		=> '_searchParamTag',
			'atm-search-page'		=> '_searchLimitTag',
			'atm-search-limit'		=> '_searchLimitTag',
			'atm-search-order'		=> '_searchOrderTag',
			'atm-loop' 				=> '_loopTag',
			'atm-if' 				=> '_ifTag',
			'atm-parameter'			=> '_parameterTag',
			'atm-function'			=> '_functionTag',
			'atm-setvar'			=> '_setvarTag',
			'atm-plugin'			=> '_pluginTag',
			'atm-plugin-view'		=> '_pluginViewTag',
			'atm-plugin-valid'		=> '_pluginValidTag',
			'atm-plugin-invalid'	=> '_pluginInvalidTag',
			'atm-rss'				=> '_RSSTag',
			'atm-rss-item'			=> '_RSSItemTag',
			'atm-rss-item-url'		=> '_RSSItemContentTag',
			'atm-rss-item-title'	=> '_RSSItemContentTag',
			'atm-rss-item-content'	=> '_RSSItemContentTag',
			'atm-rss-item-author'	=> '_RSSItemContentTag',
			'atm-rss-item-date'		=> '_RSSItemContentTag',
			'atm-rss-item-category'	=> '_RSSItemContentTag',
			'atm-rss-title'			=> '_RSSItemContentTag',
			'atm-form'				=> '_formTag',
			'atm-input'				=> '_inputTag',
			'atm-form-required'		=> '_formRequirementsTag',
			'atm-form-malformed'	=> '_formRequirementsTag',
			'atm-input-callback'    => '_inputCallback',
			'atm-xml'				=> '_xmlTag',
		);
	
	/**
	  * The parameters to parse
	  * @var array
	  * @access private
	  */
	protected $_parameters = array();
	
	/**
	  * Public treatment ?
	  * @var boolean
	  * @access private
	  */
	protected $_public = false;
	
	/**
	  * The definition parser object
	  * @var CMS_xml2Array
	  * @access private
	  */
	protected $_parser;
	
	/**
	  * Definition parsing error (only for test mode)
	  * @var string
	  * @access private
	  */
	protected $_parsingError = '';
	
	/**
	  * Definition treatment mode
	  * @var constant value (integer)
	  * @access private
	  */
	protected $_mode;
	
	/**
	  * All Definition block parameters (used in self::BLOCK_PARAM_MODE treatment mode)
	  * @var multidimentionnal array
	  * @access private
	  */
	protected $_blockParams;
	
	/**
	  * All Definition page header callback to add
	  * @var multidimentionnal array
	  * @access private
	  */
	protected $_headCallBack;
	
	/**
	  * Constructor.
	  * initialize object.
	  *
	  * @param string $definition the definition to parse
	  * @param boolean $parse : completely parse the given definition (default is true) or only replace values in it
	  * @param constant $mode : the current parsing mode in :
	  *		self::PARSE_MODE : Parse definition (default)
	  *		self::CHECK_PARSING_MODE : Check for definition parsing errors
	  *		self::BLOCK_PARAM_MODE : Get definition block parameters form
	  * @return void
	  * @access public
	  */
	function __construct($definition, $parse = true, $mode = self::PARSE_MODE) {
		if (!trim($definition)) {
			return;
		}
		$this->_mode = $mode;
		if ($parse || $mode == self::BLOCK_PARAM_MODE) {
			//parse definiton
			$this->_parser = new CMS_xml2Array($definition);
			$this->_definitionArray = $this->_parser->getParsedArray();
			//compute definition
			$this->_definition = $this->_computeTags($this->_definitionArray);
			if ($mode != self::BLOCK_PARAM_MODE) {
				//clean some useless codes
				$this->_definition = $this->_cleanComputedDefinition($this->_definition);
			}
		} else {
			$this->_definition = $this->preReplaceVars($definition, false, true);
		}
	}
	
	/**
	  * Get the parsing content
	  * only with self::PARSE_MODE
	  *
	  * @param constant $type : the content type to return in :
	  *  - self::OUTPUT_RESULT output evalued PHP result
	  *  - self::OUTPUT_PHP output valid PHP to execute
	  * @param array $parameters parameters to help parsing
	  		'public' : current public status
			'pageID' : current parsed page
			'itemID' : current item ID to work with
			'objectID' : current object type ID to work with
			'item' : current item to work with
			'module' : current module codename
			'block_attributes' : current block attributes values
	  		'language' : current language code
	  * @return string the PHP / HTML content parsed
	  * @access public
	  */
	function getContent($type = self::OUTPUT_RESULT, &$parameters) {
		if (!trim($this->_definition)) {
			return ;
		}
		//set parameters
		$this->_parameters = array_merge($this->_parameters, $parameters);
		//
		//Create all pre-execution variables with parameters values
		//
		$return = '';
		if (is_object($this->_parser)) {
			//init exported vars
			$languageObject = $blockAttributes = $pageID = $pluginSelection = $polyobjectsDefinitions = '';
			//load all poly objects for module
			if (!$this->_parameters['module']) {
				$this->raiseError("Missing valid module codename in parameters.");
			} else {
				$polyObjects = CMS_poly_object_catalog::getObjectsForModule($this->_parameters['module']);
				if (is_array($polyObjects) && $polyObjects) {
					foreach ($polyObjects as $polyObject) {
						$polyobjectsDefinitions .= 'if (!isset($object['.$polyObject->getID().'])) $object['.$polyObject->getID().'] = new CMS_poly_object('.$polyObject->getID().', 0, array(), $parameters[\'public\']);'."\n";
					}
				}
				$polyobjectsDefinitions .= '$parameters[\'module\'] = \''.$this->_parameters['module'].'\';'."\n";
			}
			$blockAttributes = $objectID = $pageID = $pluginSelection = $languageObject = $public = '';
			//set plugin selection if any
			if (isset($this->_parameters['selection'])) {
				$pluginSelection = '$parameters[\'selection\'] = '.var_export($this->_parameters['selection'], true).';'."\n";
			}
			//set pageID if any
			if (isset($this->_parameters['pageID']) && sensitiveIO::isPositiveInteger($this->_parameters['pageID'])) {
				$pageID = '$parameters[\'pageID\'] = \''.$this->_parameters['pageID'].'\';'."\n";
			}
			//set itemID if any
			if (isset($this->_parameters['itemID']) && sensitiveIO::isPositiveInteger($this->_parameters['itemID'])) {
				$pageID = '$parameters[\'itemID\'] = \''.$this->_parameters['itemID'].'\';'."\n";
			}
			//export block attributes
			if (isset($this->_parameters['block_attributes'])) {
				$blockAttributes = '$blockAttributes = '.CMS_polymod_definition_parsing::preReplaceVars(var_export($this->_parameters['block_attributes'], true), true).';'."\n";
			}
			//instanciate language if exists
			if (isset($this->_parameters['language'])) {
				$languageObject = 'if (!isset($cms_language) || (isset($cms_language) && $cms_language->getCode() != \''.$this->_parameters['language'].'\')) $cms_language = new CMS_language(\''.$this->_parameters['language'].'\');'."\n";
			}
			//instanciate objectID if exists
			if (isset($this->_parameters['objectID']) && sensitiveIO::isPositiveInteger($this->_parameters['objectID'])) {
				$objectID =  '$parameters[\'objectID\'] = '.$this->_parameters['objectID'].';'."\n";
			}
			//set public status
			if (isset($this->_parameters['public'])) {
				//if value exists here, use it
				$public = '$parameters[\'public\'] = '.($this->_parameters['public'] ? 'true' : 'false').';'."\n";
			} else {
				//else if it exists during execution, use it or force public status
				$public = '$parameters[\'public\'] = (isset($parameters[\'public\'])) ? $parameters[\'public\'] : true;'."\n";
			}
			$return = 
			'$content = "";'."\n".
			'$replace = "";'."\n".
			'if (!isset($objectDefinitions) || !is_array($objectDefinitions)) $objectDefinitions = array();'."\n".
			$blockAttributes.
			$objectID.
			$pageID.
			$pluginSelection.
			$languageObject.
			$public.
			'if (isset($parameters[\'item\'])) {$parameters[\'objectID\'] = $parameters[\'item\']->getObjectID();} elseif (isset($parameters[\'itemID\']) && sensitiveIO::isPositiveInteger($parameters[\'itemID\']) && !isset($parameters[\'objectID\'])) $parameters[\'objectID\'] = CMS_poly_object_catalog::getObjectDefinitionByID($parameters[\'itemID\']);'."\n".
			'if (!isset($object) || !is_array($object)) $object = array();'."\n".
			$polyobjectsDefinitions;
		}
		switch ($type) {
			case self::OUTPUT_PHP:
				//if header callback exists, add it to module useage for this page
				if (is_array($this->_headCallBack) && $this->_headCallBack) {
					if (sensitiveIO::isPositiveInteger($this->_parameters['pageID']) && $this->_parameters['module'] && $this->_parameters['language']) {
						//add language to callBack infos
						$this->_headCallBack['language'] = $this->_parameters['language'];
						$this->_headCallBack['headcode'] = $return;
						CMS_module::moduleUsage($this->_parameters['pageID'], $this->_parameters['module'], $this->_headCallBack);
					} else {
						$this->raiseError('Missing valid pageID or module codename or language code in parameters to use header callback.');
						return false;
					}
				}
				$return = 
				'<?php'."\n".
				'//Generated by : $Id: poly_definition_parsing.php,v 1.3 2009/02/03 14:27:25 sebastien Exp $'."\n".
				$return."\n".
				$this->_definition."\n".
				'echo CMS_polymod_definition_parsing::replaceVars($content, $replace);'."\n".
				'?>';
				$return = $this->indentPHP($return);
				return $return;
			break;
			case self::OUTPUT_RESULT:
				//then eval content
				if (is_object($this->_parser)) {
					if (is_object($this->_parameters['item'])) {
						//make object available
						$object[$this->_parameters['item']->getObjectID()] = &$this->_parameters['item'];
					}
					$this->_definition = $return.$this->_definition."\n".
					'return CMS_polymod_definition_parsing::replaceVars($content, $replace);';
					$return = eval($this->_definition);
				} else {
					if (!is_object($this->_parameters['item'])) {
						$this->raiseError('Missing valid item in parameters.');
						return false;
					}
					//make object available
					$object[$this->_parameters['item']->getObjectID()] = $this->_parameters['item'];
					$return = eval('return "'.$this->_definition.'";');
				}
				if (isset($ckeck) && $ckeck === false) {
					$this->raiseError('Can\'t eval content type to return : '.$this->_definition);
					return false;
				}
				return $return;
			break;
			default:
				$this->raiseError('Unknown content type to return : '.$type);
				return false;
			break;
		}
	}
	
	/**
	  * Return parsing errors founded during parsing test
	  * only in self::CHECK_PARSING_MODE
	  *
	  * @return string the parsing errors
	  * @access public
	  */
	function getParsingError() {
		return $this->_parsingError;
	}
	
	/**
	  * Return block params for current definition
	  * only in self::BLOCK_PARAM_MODE
	  *
	  * @return multidimentionnal array the block params
	  * @access public
	  */
	function getBlockParams() {
		return $this->_blockParams;
	}
	
	/**
	  * Compute recursively all parsed definition tags 
	  * and send them to callback methods (according to $this->_tagsCallBack)
	  *
	  * @param multidimentionnal array $definition : the reference of the definition to compute
	  * @param integer $level : the current level of recursion (default : 0)
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _computeTags(&$definition, $level = 0) {
		$code = '';
		if ($level == 0) {
			$code .= '$content .="';
		}
		if (is_array($definition) && is_array($definition[0])) {
			//loop on subtags
			foreach (array_keys($definition) as $key) {
				if (isset($definition[$key]['nodename']) && isset($this->_tagsCallBack[$definition[$key]['nodename']])) {
					if (method_exists($this, $this->_tagsCallBack[$definition[$key]['nodename']])) {
						$code .= '";'."\n";
						$code = CMS_polymod_definition_parsing::preReplaceVars($code);
						$code .= $this->{$this->_tagsCallBack[$definition[$key]['nodename']]}($definition[$key]);
						$code .= '$content .="';
					} else {
						$this->raiseError("Unknown compute callback method : ".$this->_tagsCallBack[$definition[$key]['nodename']]);
						return false;
					}
				} elseif (isset($definition[$key]['phpnode'])) {
					$code .= '";'."\n";
					$code = CMS_polymod_definition_parsing::preReplaceVars($code);
					$code .= 'eval(CMS_polymod_definition_parsing::replaceVars(\''.str_replace("'","\'",str_replace("\'","\\\'",CMS_polymod_definition_parsing::preReplaceVars($definition[$key]['phpnode'], false, false, false))).'\', $replace));'."\n";
					$code .= '$content .="';
				} elseif (isset($definition[$key]['childrens'])) {
					//compute subtags
					$childrens = $definition[$key]['childrens'];
					//append computed tags as code
					$xml = array($definition[$key]);
					$code .= str_replace('"', '\"', $this->_parser->toXML($xml, CMS_xml2Array::ARRAY2XML_START_TAG));
					$code .= $this->_computeTags($definition[$key]['childrens'], $level+1);
					$code .= str_replace('"', '\"', $this->_parser->toXML($xml, CMS_xml2Array::ARRAY2XML_END_TAG));
				} else {
					$xml = array($definition[$key]);
					$code .= str_replace('"', '\"', $this->_parser->toXML($xml));
				}
			}
		} else {
			if($this->_mode == self::CHECK_PARSING_MODE) {
				$this->_parsingError .= "\n".'Malformed definition to compute';
				return false;
			} else {
				$this->raiseError("Malformed definition to compute : ".print_r($definition, true));
				return false;
			}
		}
		if ($level == 0) {
			$code .= '";'."\n";
			$code = CMS_polymod_definition_parsing::preReplaceVars($code);
		}
		return $code;
	}
	
	/**
	  * Compute an atm-search tag
	  *
	  * @param array $tag : the reference atm-search tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _searchTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'what' => 'object', 
				'name' => 'alphanum', 
			))) {
			return;
		}
		$uniqueID = $this->getUniqueID();
		$objectID = substr($tag['attributes']['what'],9,-3);
		$return = '
		//SEARCH '.$tag['attributes']['name'].' TAG START '.$uniqueID.'
		$objectDefinition_'.$tag['attributes']['name'].' = \''.$objectID.'\';
		if (!isset($objectDefinitions[$objectDefinition_'.$tag['attributes']['name'].'])) {
			$objectDefinitions[$objectDefinition_'.$tag['attributes']['name'].'] = new CMS_poly_object_definition($objectDefinition_'.$tag['attributes']['name'].');
		}
		//public search ?'."\n";
		if (isset($tag['attributes']['public']) && ($tag['attributes']['public'] == 'true' || $tag['attributes']['public'] == 'false')) {
			$return .= '$public_'.$uniqueID.' = (!isset($public_search) || !$public_search) ? false : '.$tag['attributes']['public'].';'."\n";
		} else {
			$return .= '$public_'.$uniqueID.' = isset($public_search) ? $public_search : false;'."\n";
		}
		$return .= '//get search params
		$search_'.$tag['attributes']['name'].' = new CMS_object_search($objectDefinitions[$objectDefinition_'.$tag['attributes']['name'].'], $public_'.$uniqueID.');
		$launchSearch_'.$tag['attributes']['name'].' = true;
		//add search conditions if any
		';
		if ($this->_mode == self::BLOCK_PARAM_MODE) {
			$this->_blockParams['search'][ $tag['attributes']['name'] ][ 'searchType' ] = $objectID;
		}
		if (!$tag['childrens']) {
			if($this->_mode == self::CHECK_PARSING_MODE) {
				$this->_parsingError .= "\n"."Malformed atm-search tag : no children tags found";
				return;
			} else {
				$this->raiseError("Malformed atm-search tag : no children tags found");
				return;
			}
		}
		if (is_array($tag['childrens']) && $tag['childrens']) {
			$return .= $this->_computeTags($tag['childrens']).'
			';
		}
		$return .='//destroy search and results '.$tag['attributes']['name'].' objects
		unset($search_'.$tag['attributes']['name'].');
		unset($results_'.$tag['attributes']['name'].');
		//SEARCH '.$tag['attributes']['name'].' TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-result tag
	  *
	  * @param array $tag : the reference atm-result tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _searchResultTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'search' => 'alphanum', 
			))) {
			return;
		}
		if (isset($tag['attributes']['return']) && in_array($tag['attributes']['return'], array(CMS_object_search::POLYMOD_SEARCH_RETURN_IDS, CMS_object_search::POLYMOD_SEARCH_RETURN_OBJECTSLIGHT))) {
			$returnType = $tag['attributes']['return'];
		} elseif (isset($tag['attributes']['return']) && in_array($tag['attributes']['return'], array('POLYMOD_SEARCH_RETURN_IDS', 'POLYMOD_SEARCH_RETURN_OBJECTSLIGHT'))) {
			$returnType = constant('CMS_object_search::'.$tag['attributes']['return']);
		} else {
			$returnType = '';
		}
		$uniqueID = $this->getUniqueID();
		$return = '
		//RESULT '.$tag['attributes']['search'].' TAG START '.$uniqueID.'
		//launch search '.$tag['attributes']['search'].' if not already done
		if($launchSearch_'.$tag['attributes']['search'].' && !isset($results_'.$tag['attributes']['search'].')) {
			if (isset($search_'.$tag['attributes']['search'].')) {
				$results_'.$tag['attributes']['search'].' = $search_'.$tag['attributes']['search'].'->search('.$returnType.');
			} else {
				CMS_grandFather::raiseError("Malformed atm-result tag : can\'t use this tag outside of atm-search \"'.$tag['attributes']['search'].'\" tag ...");
				$results_'.$tag['attributes']['search'].' = array();
			}
		} elseif (!$launchSearch_'.$tag['attributes']['search'].') {
			$results_'.$tag['attributes']['search'].' = array();
		}
		if ($results_'.$tag['attributes']['search'].') {
			$object_'.$uniqueID.' = $object[$objectDefinition_'.$tag['attributes']['search'].']; //save previous object search if any
			$replace_'.$uniqueID.' = $replace; //save previous replace vars if any
			$count_'.$uniqueID.' = 0;
			$content_'.$uniqueID.' = $content; //save previous content var if any
			$maxPages_'.$uniqueID.' = $search_'.$tag['attributes']['search'].'->getMaxPages();
            $maxResults_'.$uniqueID.' = $search_'.$tag['attributes']['search'].'->getNumRows();';
			if ($returnType == CMS_object_search::POLYMOD_SEARCH_RETURN_IDS || $returnType == 'POLYMOD_SEARCH_RETURN_IDS') {
				$return .= '
				foreach ($results_'.$tag['attributes']['search'].' as $resultID_'.$tag['attributes']['search'].') {';
			} else {
				$return .= '
				foreach ($results_'.$tag['attributes']['search'].' as $object[$objectDefinition_'.$tag['attributes']['search'].']) {';
			}
				$return .= '
				$content = "";
				$replace["atm-search"] = array (
					"{resultid}" 	=> (isset($resultID_'.$tag['attributes']['search'].')) ? $resultID_'.$tag['attributes']['search'].' : $object[$objectDefinition_'.$tag['attributes']['search'].']->getID(),
					"{firstresult}" => (!$count_'.$uniqueID.') ? 1 : 0,
					"{lastresult}" 	=> ($count_'.$uniqueID.' == sizeof($results_'.$tag['attributes']['search'].')-1) ? 1 : 0,
					"{resultcount}" => ($count_'.$uniqueID.'+1),
					"{maxpages}"    => $maxPages_'.$uniqueID.',
					"{currentpage}" => ($search_'.$tag['attributes']['search'].'->getAttribute(\'page\')+1),
					"{maxresults}"  => $maxResults_'.$uniqueID.',
				);
				'.$this->_computeTags($tag['childrens']).'
				$count_'.$uniqueID.'++;
				//do all result vars replacement
				$content_'.$uniqueID.'.= CMS_polymod_definition_parsing::replaceVars($content, $replace);
			}
			$content = $content_'.$uniqueID.'; //retrieve previous content var if any
			$replace = $replace_'.$uniqueID.'; //retrieve previous replace vars if any
			$object[$objectDefinition_'.$tag['attributes']['search'].'] = $object_'.$uniqueID.'; //retrieve previous object search if any
		}
		//RESULT '.$tag['attributes']['search'].' TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-noresult tag
	  *
	  * @param array $tag : the reference atm-noresult tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _searchNoResultTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'search' => 'alphanum', 
			))) {
			return;
		}
		$uniqueID = $this->getUniqueID();
		$return = '
		//NO-RESULT '.$tag['attributes']['search'].' TAG START '.$uniqueID.'
		//launch search '.$tag['attributes']['search'].' if not already done
		if($launchSearch_'.$tag['attributes']['search'].' && !isset($results_'.$tag['attributes']['search'].')) {
			if (isset($search_'.$tag['attributes']['search'].')) {
				$results_'.$tag['attributes']['search'].' = $search_'.$tag['attributes']['search'].'->search();
			} else {
				CMS_grandFather::raiseError("Malformed atm-noresult tag : can\'t use this tag outside of atm-search \"'.$tag['attributes']['search'].'\" tag ...");
				$results_'.$tag['attributes']['search'].' = array();
			}
		} elseif (!$launchSearch_'.$tag['attributes']['search'].') {
			$results_'.$tag['attributes']['search'].' = array();
		}
		if (!$results_'.$tag['attributes']['search'].') {
			'.$this->_computeTags($tag['childrens']).'
		}
		//NO-RESULT '.$tag['attributes']['search'].' TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-search-param tag
	  *
	  * @param array $tag : the reference atm-search-param tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _searchParamTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'search' => 'alphanum', 
				'type' => 'paramType', 
				'value' => true, 
				'mandatory' => 'true|false', 
			))) {
			return;
		}
		$return = '';
		//if value came from block parameters
		if ($tag['attributes']['value'] == 'block') {
			$type = CMS_polymod_definition_parsing::preReplaceVars($tag['attributes']['type'], false, false, false);
			if ($this->_mode == self::BLOCK_PARAM_MODE) {
				$this->_blockParams['search'][ $tag['attributes']['search'] ][ $type ] = ($tag['attributes']['mandatory'] == 'true') ? true : false;
			}
			$uniqueID = $this->getUniqueID();
			$return .= '
			if ($blockAttributes[\'search\'][\''.$tag['attributes']['search'].'\'][\''.$type.'\']) {
				$values_'.$uniqueID.' = '.CMS_polymod_definition_parsing::preReplaceVars(var_export($tag['attributes'],true),true).';
				$values_'.$uniqueID.'[\'value\'] = $blockAttributes[\'search\'][\''.$tag['attributes']['search'].'\'][\''.$type.'\'];
				$launchSearch_'.$tag['attributes']['search'].' = (CMS_polymod_definition_parsing::addSearchCondition($search_'.$tag['attributes']['search'].', $values_'.$uniqueID.')) ? $launchSearch_'.$tag['attributes']['search'].' : false;
			} elseif ('.$tag['attributes']['mandatory'].' == true) {
				//search parameter is mandatory and no value found
				$launchSearch_'.$tag['attributes']['search'].' = false;
			}';
		} else {
			$return .= '$launchSearch_'.$tag['attributes']['search'].' = (CMS_polymod_definition_parsing::addSearchCondition($search_'.$tag['attributes']['search'].', '.CMS_polymod_definition_parsing::preReplaceVars(var_export($tag['attributes'],true),true).')) ? $launchSearch_'.$tag['attributes']['search'].' : false;'."\n";
		}
		return $return;
	}
	
	/**
	  * Compute an atm-search-page or atm-search-limit tag
	  *
	  * @param array $tag : the reference tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _searchLimitTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'search' => 'alphanum', 
				'value' => true, 
			))) {
			return;
		}
		//if value came from block parameters
		if ($tag['attributes']['value'] == 'block') {
			$type = ($tag['nodename'] == 'atm-search-limit') ? 'limit' : 'page';
			if ($this->_mode == self::BLOCK_PARAM_MODE) {
				$this->_blockParams['search'][ $tag['attributes']['search'] ][ $type ] = true;
			}
			$tag['attributes']['value'] = '".$blockAttributes[\'search\'][\''.$tag['attributes']['search'].'\'][\''.$type.'\']."';
		}
		if ($tag['nodename'] == 'atm-search-limit') {
			return '$search_'.$tag['attributes']['search'].'->setAttribute(\'itemsPerPage\', (int) CMS_polymod_definition_parsing::replaceVars("'.CMS_polymod_definition_parsing::preReplaceVars($tag['attributes']['value'], false, false).'", $replace));'."\n";
		} else {
			return '$search_'.$tag['attributes']['search'].'->setAttribute(\'page\', (int) (CMS_polymod_definition_parsing::replaceVars("'.CMS_polymod_definition_parsing::preReplaceVars($tag['attributes']['value'], false, false).'", $replace) -1 ));'."\n";
		}
	}
	
	/**
	  * Compute an atm-search-order tag
	  *
	  * @param array $tag : the reference atm-search-order tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _searchOrderTag(&$tag) {
		if ($tag['attributes']['type'] == 'random') {
			//force direction value.
			$tag['attributes']['direction'] = 'asc';
		}
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'type' => 'orderType', 
				'search' => 'alphanum', 
				'direction' => 'asc|desc|block|{.*}', 
			))) {
			return;
		}
		$type = CMS_polymod_definition_parsing::preReplaceVars($tag['attributes']['type'], false, false);
		//if direction value came from block parameters
		if ($tag['attributes']['direction'] == 'block') {
			if ($this->_mode == self::BLOCK_PARAM_MODE) {
				$this->_blockParams['search'][ $tag['attributes']['search'] ][ 'order' ][ $type ] = true;
			}
			//replace tag direction value by corresponding block parameter value
			$tag['attributes']['direction'] = '$blockAttributes[\'search\'][\''.$tag['attributes']['search'].'\'][\'order\'][\''.$type.'\']';
		}
		//if direction came from a var content
		elseif (substr($tag['attributes']['direction'],0,1) == '{' && substr($tag['attributes']['direction'],-1,1) == '}') {
			$tag['attributes']['direction'] = CMS_polymod_definition_parsing::preReplaceVars($tag['attributes']['direction'], false, false);
		}
		return '$search_'.$tag['attributes']['search'].'->addOrderCondition("'.$type.'", "'.$tag['attributes']['direction'].'"'.(isset($tag['attributes']['operator']) ? ', "'.$tag['attributes']['operator'].'"' : '').');'."\n";
	}
	
	/**
	  * add a search condition to a given CMS_object_search object
	  *
	  * @param CMS_object_search $search : the reference search object which need the condition
	  * @param array &tagAttributes : represent atm-search-param attributes
	  * @return boolean true on success, false on failure
	  * @access private
	  * @static
	  */
	function addSearchCondition(&$search, $tagAttributes) {
		global $cms_language;
		
		if (!isset($tagAttributes['type'])) {
			CMS_grandFather::raiseError("Malformed atm-search-param tag : missing 'type' attribute");
			return false;
		}
		if (!isset($tagAttributes['value'])) {
			CMS_grandFather::raiseError("Malformed atm-search-param tag : missing 'value' attribute");
			return false;
		}
		if (!isset($tagAttributes['mandatory'])) {
			CMS_grandFather::raiseError("Malformed atm-search-param tag : missing 'mandatory' attribute");
			return false;
		}
		if (isset($tagAttributes['value'])) {
			$searchConditionValue = $tagAttributes['value'];
		} else {
			CMS_grandFather::raiseError("Unknown value type : ".$tagAttributes['value']);
			return false;
		}
		//if no value for condition and condition is mandatory : return false
		if (!$searchConditionValue) {
			return $tagAttributes['mandatory'] == 'true' ? false : true;
		}
		if (is_scalar($tagAttributes['type']) && in_array($tagAttributes['type'], CMS_object_search::getStaticSearchConditionTypes()) 
			|| $tagAttributes['type'] == 'category' //deprecated
			) {
			if ($tagAttributes['type'] == 'publication date after' || $tagAttributes['type'] == 'publication date before') {
				//replace search condition value by corresponding cms_date object
				$date = new CMS_date();
				$date->setFormat($cms_language->getDateFormat());
				$date->setLocalizedDate($searchConditionValue);
				$searchConditionValue = $date;
			}
			$search->addWhereCondition($tagAttributes['type'], $searchConditionValue);
		} else {
			if (!sensitiveIO::isPositiveInteger($tagAttributes['type'])) {
				CMS_grandFather::raiseError("Malformed atm-search-param tag : attribute 'type' does not represent a valid object ".$tagAttributes['type']);
				return false;
			} else {
				$search->addWhereCondition($tagAttributes['type'], $searchConditionValue, (isset($tagAttributes['operator']) ? $tagAttributes['operator'] : false));
			}
		}
		return true;
	}
	
	/**
	  * Compute an atm-if tag
	  *
	  * @param array $tag : the reference atm-if tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _ifTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'what' => true, 
			))) {
			return;
		}
		$uniqueID = $this->getUniqueID();
		$return = '
		//IF TAG START '.$uniqueID.'
		$ifcondition = CMS_polymod_definition_parsing::replaceVars("'.CMS_polymod_definition_parsing::preReplaceVars($tag['attributes']["what"], false, false, array('CMS_polymod_definition_parsing', 'encloseWithPrepareVar')).'", $replace);
		if ($ifcondition) {
			$func = create_function("","return (".$ifcondition.");");
			if ($func()) {
				'.$this->_computeTags($tag['childrens']).'
			}
		}//IF TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-parameter tag
	  *
	  * @param array $tag : the reference atm-parameter tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _parameterTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'attribute' => true, 
			))) {
			return;
		}
		if (!$tag['attributes']["value"]) {
			if($this->_mode == self::CHECK_PARSING_MODE) {
				$this->_parsingError .= "\n"."Malformed atm-parameter tag : missing 'value' attribute";
				return;
			} else {
				$this->raiseError("Malformed atm-parameter tag : missing 'value' attribute");
				return;
			}
		}
		$uniqueID = $this->getUniqueID();
		$return = '
		//PARAMETER TAG START '.$uniqueID.'
		$content = preg_replace("#(<[^/][^>]*)>(.*)$#U",\'\1 '.$tag['attributes']["attribute"].'="'.$tag['attributes']["value"].'">\2\', $content);
		//PARAMETER TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-function tag
	  *
	  * @param array $tag : the reference atm-function tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _functionTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'function' => 'alphanum', 
			))) {
			return;
		}
		$uniqueID = $this->getUniqueID();
		$return = '
		//FUNCTION TAG START '.$uniqueID.'
		$parameters_'.$uniqueID.' = array (';
		foreach ($tag['attributes'] as $attributeName => $attributeValue) {
			if ($attributeName != 'object' && $attributeName != 'function') {
				$return .= '\''.$attributeName.'\' => CMS_polymod_definition_parsing::replaceVars("'.CMS_polymod_definition_parsing::preReplaceVars($attributeValue).'", $replace),';
			}
		}
		$return .= ');
		';
		$childrens = (isset($tag['childrens'])) ? $tag['childrens'] : null;
		if (isset($tag['attributes']["object"]) && $tag['attributes']["object"]) {
			$objects = CMS_polymod_definition_parsing::preReplaceVars($tag['attributes']["object"], false, true, false, true);
			$return .='
			$object_'.$uniqueID.' = &'.array_pop($objects).';
			if (method_exists($object_'.$uniqueID.', "'.$tag['attributes']["function"].'")) {
				$content .= CMS_polymod_definition_parsing::replaceVars($object_'.$uniqueID.'->'.$tag['attributes']["function"].'($parameters_'.$uniqueID.', '.CMS_polymod_definition_parsing::preReplaceVars(var_export($childrens ,true), true).'), $replace);
			} else {
				CMS_grandFather::raiseError("Malformed atm-function tag : can\'t found method '.$tag['attributes']["function"].' on object : ".get_class($object_'.$uniqueID.'));
			}';
		} else {
			$return .='
			if (method_exists(new CMS_poly_definition_functions(), "'.$tag['attributes']["function"].'")) {
				$content .= CMS_polymod_definition_parsing::replaceVars(CMS_poly_definition_functions::'.$tag['attributes']["function"].'($parameters_'.$uniqueID.', '.CMS_polymod_definition_parsing::preReplaceVars(var_export($childrens ,true), true).'), $replace);
			} else {
				CMS_grandFather::raiseError("Malformed atm-function tag : can\'t found method '.$tag['attributes']["function"].'in CMS_poly_definition_functions");
			}';
		}
		$return .='
		//FUNCTION TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-loop tag
	  *
	  * @param array $tag : the reference atm-loop tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _loopTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'on' => true, 
			))) {
			return;
		}
		$reverse = '';
		if (isset($tag['attributes']["reverse"]) && $tag['attributes']["reverse"] == 'true') {
			$reverse = '$loopcondition = array_reverse ( $loopcondition , true );';
		}
		$uniqueID = $this->getUniqueID();
		$return = '
		//LOOP TAG START '.$uniqueID.'
		$loopcondition = '.array_pop(CMS_polymod_definition_parsing::preReplaceVars($tag['attributes']['on'], false, false, false, true)).';
		if (is_array($loopcondition)) {
			$count_'.$uniqueID.' = 0;
			$replace_'.$uniqueID.' = $replace; //save previous replace vars if any
			$content_'.$uniqueID.' = $content; //save previous content var if any
			if (isset($key)) $key_'.$uniqueID.' = $key; //save previous key var if any
			$loopcondition_'.$uniqueID.' = $loopcondition; //save previous loopcondition var if any
			'.$reverse.'
			$maxloops_'.$uniqueID.' = sizeof($loopcondition);
			foreach (array_keys($loopcondition) as $key) {
				$content = "";
				$replace["atm-loop"] = array (
					"{firstloop}" 	=> (!$count_'.$uniqueID.') ? 1 : 0,
					"{lastloop}" 	=> ($count_'.$uniqueID.' == sizeof($loopcondition)-1) ? 1 : 0,
					"{loopcount}" 	=> ($count_'.$uniqueID.'+1),
					"{maxloops}" 	=> $maxloops_'.$uniqueID.'
				);
				'.$this->_computeTags($tag['childrens']).'
				$count_'.$uniqueID.'++;
				//do all result vars replacement
				$content_'.$uniqueID.'.= CMS_polymod_definition_parsing::replaceVars($content, $replace);
			}
			$content = $content_'.$uniqueID.'; //retrieve previous content var if any
			$replace = $replace_'.$uniqueID.'; //retrieve previous replace vars if any
			if (isset($key_'.$uniqueID.')) $key = $key_'.$uniqueID.'; //retrieve previous key vars if any
			$loopcondition = $loopcondition_'.$uniqueID.'; //retrieve previous loopcondition vars if any
		} else {
			CMS_grandFather::raiseError("Malformed atm-loop tag : can\'t loop on non array : ".$loopcondition);
		}//LOOP TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-setvar tag
	  *
	  * @param array $tag : the reference atm-setvar tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _setvarTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'varname' => 'alphanum', 
				'vartype' => 'request|session|var', 
			))) {
			return;
		}
		$uniqueID = $this->getUniqueID();
		$return = '
		//SETVAR TAG START '.$uniqueID;
		if ($tag['attributes']["vartype"] == 'request') {
			$return .= '
			$_REQUEST[\''.$tag['attributes']["varname"].'\'] = CMS_polymod_definition_parsing::replaceVars('.CMS_polymod_definition_parsing::preReplaceVars(var_export($tag['attributes']["value"],true),true).',$replace);';
		} elseif ($tag['attributes']["vartype"] == 'session') {
			$return .= '
			$_SESSION[\''.$tag['attributes']["varname"].'\'] = CMS_polymod_definition_parsing::replaceVars('.CMS_polymod_definition_parsing::preReplaceVars(var_export($tag['attributes']["value"],true),true).',$replace);';
		} else {
			$return .= '
			$'.$tag['attributes']["varname"].' = CMS_polymod_definition_parsing::replaceVars('.CMS_polymod_definition_parsing::preReplaceVars(var_export($tag['attributes']["value"],true),true).',$replace);';
		}
		$return .= '
		//SETVAR TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-plugin tag
	  *
	  * @param array $tag : the reference atm-plugin tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _pluginTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'language' => 'language', 
			))) {
			return;
		}
		//set language
		$this->_parameters['language'] = $tag['attributes']["language"];
		$uniqueID = $this->getUniqueID();
		//search for an atm-plugin-view tag in direct child tags
		foreach ($tag['childrens'] as $child) {
			if ($child['nodename'] == 'atm-plugin-view') {
				$this->_parameters['has-plugin-view'] = true;
			}
		}
		
		$return = '
		//PLUGIN TAG START '.$uniqueID.'
		if (!sensitiveIO::isPositiveInteger($parameters[\'itemID\']) || !sensitiveIO::isPositiveInteger($parameters[\'objectID\'])) {
			CMS_grandFather::raiseError(\'Error into atm-plugin tag : can\\\'t found object infos to use into : $parameters[\\\'itemID\\\'] and $parameters[\\\'objectID\\\']\');
		} else {
			//search needed object (need to search it for publications and rights purpose)
			if (!isset($objectDefinitions[$parameters[\'objectID\']])) {
				$objectDefinitions[$parameters[\'objectID\']] = new CMS_poly_object_definition($parameters[\'objectID\']);
			}
			$search_'.$uniqueID.' = new CMS_object_search($objectDefinitions[$parameters[\'objectID\']], $parameters[\'public\']);
			$search_'.$uniqueID.'->addWhereCondition(\'item\', $parameters[\'itemID\']);
			$results_'.$uniqueID.' = $search_'.$uniqueID.'->search();
			if (isset($results_'.$uniqueID.'[$parameters[\'itemID\']]) && is_object($results_'.$uniqueID.'[$parameters[\'itemID\']])) {
				$object[$parameters[\'objectID\']] = $results_'.$uniqueID.'[$parameters[\'itemID\']];
			} else {
				$object[$parameters[\'objectID\']] = new CMS_poly_object($parameters[\'objectID\'], 0, array(), $parameters[\'public\']);
			}
			'.$this->_computeTags($tag['childrens']).'
		}
		//PLUGIN TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-plugin-valid tag
	  *
	  * @param array $tag : the reference atm-plugin-valid tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _pluginValidTag(&$tag) {
		$uniqueID = $this->getUniqueID();
		$return = '
		//PLUGIN-VALID TAG START '.$uniqueID.'
		if ($object[$parameters[\'objectID\']]->isInUserSpace() && !isset($parameters[\'plugin-view\']) && !isset($parameters[\'has-plugin-view\']) ) {
			'.$this->_computeTags($tag['childrens']).'
		}
		//PLUGIN-VALID END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-plugin-invalid tag
	  *
	  * @param array $tag : the reference atm-plugin-invalid tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _pluginInvalidTag(&$tag) {
		$uniqueID = $this->getUniqueID();
		$return = '
		//PLUGIN-INVALID TAG START '.$uniqueID.'
		if (!$object[$parameters[\'objectID\']]->isInUserSpace()) {
			'.$this->_computeTags($tag['childrens']).'
		}
		//PLUGIN-INVALID END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-plugin-view tag
	  *
	  * @param array $tag : the reference atm-plugin-invalid tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _pluginViewTag(&$tag) {
		$uniqueID = $this->getUniqueID();
		$return = '
		//PLUGIN-VIEW TAG START '.$uniqueID.'
		if ($object[$parameters[\'objectID\']]->isInUserSpace() && isset($parameters[\'plugin-view\'])) {
			'.$this->_computeTags($tag['childrens']).'
		}
		//PLUGIN-VIEW END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-rss tag
	  *
	  * @param array $tag : the reference atm-rss tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _RSSTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'language' => 'language', 
			))) {
			return;
		}
		//set language
		$this->_parameters['language'] = $tag['attributes']["language"];
		$uniqueID = $this->getUniqueID();
		$return = '
		//RSS TAG START '.$uniqueID.'
		if (!sensitiveIO::isPositiveInteger($parameters[\'objectID\'])) {
			CMS_grandFather::raiseError(\'Error into atm-rss tag : can\\\'t found object infos to use into : $parameters[\\\'objectID\\\']\');
		} else {
			'.$this->_computeTags($tag['childrens']).'
		}
		//RSS TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-rss-item tag
	  *
	  * @param array $tag : the reference atm-rss-item tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _RSSItemTag(&$tag) {
		$uniqueID = $this->getUniqueID();
		$return = '
		//RSS-ITEM TAG START '.$uniqueID.'
		$content .= \'<item>
		<guid isPermaLink="false">object\'.$parameters[\'objectID\'].\'-\'.$object[$parameters[\'objectID\']]->getID().\'</guid>\';
		'.$this->_computeTags($tag['childrens']).'
		$content .= \'</item>\';
		//RSS-ITEM TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-rss-item-xxx tag
	  *
	  * @param array $tag : the reference atm-rss-item-xxx tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _RSSItemContentTag(&$tag) {
		$uniqueID = $this->getUniqueID();
		switch ($tag['nodename']) {
			case 'atm-rss-item-url':
				$rssTagName = 'link';
			break;
			case 'atm-rss-item-title':
				$rssTagName = 'title';
			break;
			case 'atm-rss-item-content':
				$rssTagName = 'description';
			break;
			case 'atm-rss-item-author':
				$rssTagName = 'author';
			break;
			case 'atm-rss-item-date':
				$rssTagName = 'pubDate';
			break;
			case 'atm-rss-item-category':
				$rssTagName = 'category';
			break;
			case 'atm-rss-title':
				$rssTagName = 'title';
			break;
		}
		$return = '
		//RSS-ITEM-'.strtoupper($rssTagName).' TAG START '.$uniqueID.'
		$content .= \'<'.$rssTagName.'>\';
		';
		if ($tag['nodename'] == 'atm-rss-item-content') {
			$return .= '$content .= \'<![CDATA[\';
			';
			$return .= $this->_computeTags($tag['childrens']);
			$return .= '
			$content .= \']]>\';';
		} else {
			$return .= '
			//save content
			$content_'.$uniqueID.' = $content;
			$content = \'\';
			'.$this->_computeTags($tag['childrens']).'
			//then remove tags from content and add it to old content
			$entities = array(\'&\' => \'&amp;\',\'>\' => \'&gt;\',\'<\' => \'&lt;\',);
			$content = $content_'.$uniqueID.'.str_replace(array_keys($entities),$entities,strip_tags(html_entity_decode($content)));';
		}
		$return .= '
		$content .= \'</'.$rssTagName.'>\';
		//RSS-ITEM-'.strtoupper($rssTagName).' TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-ajax tag
	  *
	  * @param array $tag : the reference atm-form tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _xmlTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'what' => true, 
			))) {
			return;
		}
		$uniqueID = $this->getUniqueID();
		//return code
		$return = '
		//AJAX TAG START '.$uniqueID.'
		'.$this->_computeTags($tag['childrens']).'
		//AJAX TAG END '.$uniqueID.'
		';
		//Ajax code
		$ajaxCode = '
		$xmlCondition = CMS_polymod_definition_parsing::replaceVars("'.CMS_polymod_definition_parsing::preReplaceVars($tag['attributes']["what"], false, false, array('CMS_polymod_definition_parsing', 'encloseWithPrepareVar')).'", $replace);
		if ($xmlCondition) {
			$func = create_function("","return (".$xmlCondition.");");
			if ($func()) {
				'.$return.'
				//output XML response
				header("Content-Type: text/xml");
				echo "<"."?xml version=\"1.0\" encoding=\"'.APPLICATION_DEFAULT_ENCODING.'\"?".">
				<response xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">
				<error>0</error>
				<errormessage/>
				<data><![CDATA[".CMS_polymod_definition_parsing::replaceVars($content, $replace)."]]></data>
				</response>";
				exit;
			}
		}';
		//do some cleaning on code and add reference to it into header callback
		$this->_headCallBack['ajax'][] = $this->indentPHP($this->_cleanComputedDefinition($ajaxCode));
		return $return;
	}
	
	/**
	  * Compute an atm-form tag
	  *
	  * @param array $tag : the reference atm-form tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _formTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'what' => 'object', 
				'name' => 'alphanum'
			))) {
			return;
		}
		$uniqueID = $this->getUniqueID();
		//add reference to this form to header callback
		$this->_headCallBack['form'][] = $tag['attributes']['name'];
		//form tag start
		$objectID = substr($tag['attributes']['what'],9,-3);
		$return = '
		//FORM TAG START '.$uniqueID.'
		$objectDefinition_'.$tag['attributes']['name'].' = \''.$objectID.'\';
		if (isset($polymodFormsItems[\''.$tag['attributes']['name'].'\'])) $object['.$objectID.'] = $polymodFormsItems[\''.$tag['attributes']['name'].'\'];
		$content .= \'<form name="'.$tag['attributes']['name'].'" action="\'.$_SERVER[\'SCRIPT_NAME\'].\'" method="post" enctype="multipart/form-data">
		<input type="hidden" name="cms_action" value="validate" />
		<input type="hidden" name="item" value="\'.$object['.$objectID.']->getID().\'" />
		<input type="hidden" name="polymod" value="\'.$parameters[\'module\'].\'" />
		<input type="hidden" name="object" value="'.$objectID.'" />
		<input type="hidden" name="formID" value="'.$tag['attributes']['name'].'" />\';
		//for forms we absolutely needs cms_user
		if (!is_object($cms_user)) {
			//initialize public user
			$cms_context = new CMS_context(DEFAULT_USER_LOGIN, DEFAULT_USER_PASSWORD);
			$cms_user = $cms_context->getUser();
		}
		$replace_'.$uniqueID.' = $replace; //save previous replace vars if any
		$replace["atm-form"] = array (
			"{filled}" 		=> (isset($polymodFormsError[\''.$tag['attributes']['name'].'\'][\'filled\'])) ? $polymodFormsError[\''.$tag['attributes']['name'].'\'][\'filled\'] : 0,
			"{required}"	=> (isset($polymodFormsError[\''.$tag['attributes']['name'].'\'][\'required\'])) ? 1 : 0,
			"{malformed}"	=> (isset($polymodFormsError[\''.$tag['attributes']['name'].'\'][\'malformed\'])) ? 1 : 0,
		);
		'.$this->_computeTags($tag['childrens']).'
		//do all result vars replacement
		$content_'.$uniqueID.'.= CMS_polymod_definition_parsing::replaceVars($content, $replace);
		$replace = $replace_'.$uniqueID.'; //retrieve previous replace vars if any
		$content .= \'</form>\';
		//FORM TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-form-required or atm-form-malformed tag
	  *
	  * @param array $tag : the reference atm-form-required or atm-form-malformed tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _formRequirementsTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'form' => 'alphanum'
			))) {
			return;
		}
		$tagType = ($tag['nodename'] == 'atm-form-required') ? 'required' : 'malformed';
		$uniqueID = $this->getUniqueID();
		$return = '
		//FORM '.strtoupper($tagType).' TAG START '.$uniqueID.'
		if (isset($polymodFormsError[\''.$tag['attributes']['form'].'\'][\''.$tagType.'\'])) {
			$object_'.$uniqueID.' = $object[$objectDefinition_'.$tag['attributes']['form'].']; //save previous object search if any
			$replace_'.$uniqueID.' = $replace; //save previous replace vars if any
			$count_'.$uniqueID.' = 0;
			$content_'.$uniqueID.' = $content; //save previous content var if any
			$max'.$tagType.' = sizeof($polymodFormsError[\''.$tag['attributes']['form'].'\'][\''.$tagType.'\']);
			foreach ($polymodFormsError[\''.$tag['attributes']['form'].'\'][\''.$tagType.'\'] as $'.$tagType.'FieldID) {
				$content = "";
				$replace["atm-form-'.$tagType.'"] = array (
					"{first'.$tagType.'}" => (!$count_'.$uniqueID.') ? 1 : 0,
					"{last'.$tagType.'}" 	=> ($count_'.$uniqueID.' == sizeof($polymodFormsError[\''.$tag['attributes']['form'].'\'][\''.$tagType.'\'])-1) ? 1 : 0,
					"{'.$tagType.'count}" => ($count_'.$uniqueID.'+1),
					"{max'.$tagType.'}" 	=> $max'.$tagType.',
					"{'.$tagType.'name}" 	=> $object_'.$uniqueID.'->objectValues($'.$tagType.'FieldID)->getFieldLabel($cms_language),
					"{'.$tagType.'field}" 	=> $'.$tagType.'FieldID,
				);
				'.$this->_computeTags($tag['childrens']).'
				$count_'.$uniqueID.'++;
				//do all result vars replacement
				$content_'.$uniqueID.'.= CMS_polymod_definition_parsing::replaceVars($content, $replace);
			}
			$content = $content_'.$uniqueID.'; //retrieve previous content var if any
			$replace = $replace_'.$uniqueID.'; //retrieve previous replace vars if any
		}
		//FORM '.strtoupper($tagType).' TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
	  * Compute an atm-input tag
	  *
	  * @param array $tag : the reference atm-input tag to compute
	  * @return string the PHP / HTML content computed
	  * @access private
	  */
	protected function _inputTag(&$tag) {
		//check tags requirements
		if (!$this->checkTagRequirements($tag, array(
				'field' => true,
				'form' => 'alphanum',
			))) {
			return;
		}
		$uniqueID = $this->getUniqueID();
		$fieldID = preg_replace ('#(.*\[)([0-9]+)(\]})$#U', '\2', $tag['attributes']["field"]);
		$return = '
		//INPUT TAG START '.$uniqueID.'
		$parameters_'.$uniqueID.' = array (';
		foreach ($tag['attributes'] as $attributeName => $attributeValue) {
			if ($attributeName != 'field') {
				$return .= '\''.$attributeName.'\' => CMS_polymod_definition_parsing::replaceVars("'.CMS_polymod_definition_parsing::preReplaceVars($attributeValue).'", $replace),';
			}
		}
		$return .= ');
		';
		$return .='
		if (method_exists($object[$objectDefinition_'.$tag['attributes']['form'].'], "getInput")) {
			$content .= CMS_polymod_definition_parsing::replaceVars($object[$objectDefinition_'.$tag['attributes']['form'].']->getInput('.$fieldID.', $cms_language, $parameters_'.$uniqueID.'), $replace);
		} else {
			CMS_grandFather::raiseError("Malformed atm-input tag : can\'t found method getInput on object : ".get_class($object[$objectDefinition_'.$tag['attributes']['form'].']));
		}';
		//check for tag callback content
		if ($tag['childrens']) {
			//callback code
			$inputCallback = $this->_computeTags($tag['childrens']);
			//add reference to this form to header callback
			$this->_headCallBack['formsCallback'][$tag['attributes']['form']][$fieldID] = $this->indentPHP($this->_cleanComputedDefinition($inputCallback));
		}
		$return .='
		//INPUT TAG END '.$uniqueID.'
		';
		return $return;
	}
	
	/**
      * Compute an atm-input-callback tag
      *
      * @param array $tag : the reference atm-input tag to compute
      * @return string the PHP / HTML content computed
      * @access private
      */
    function _inputCallback(&$tag) {
            //check tags requirements
            if (!$this->checkTagRequirements($tag, array(
                            'return' => '(valid|invalid)',
                    ))) {
                    return;
            }
            $uniqueID = $this->getUniqueID();
            if ($tag['attributes']['return'] == 'valid') {
                    $return = '
                    //INPUT-CALLBACK TAG START '.$uniqueID.'
                    return true;
                    //INPUT-CALLBACK TAG END '.$uniqueID.'
                    ';
            } else {
                    $return = '
                    //INPUT-CALLBACK TAG START '.$uniqueID.'
                    return false;
                    //INPUT-CALLBACK TAG END '.$uniqueID.'
                    ';
            }
            return $return;
    }
	
	/**
	  * Replace vars like {something}
	  *
	  * @param string $text : the text which need to be replaced
	  * @param array $replacement : optionnal replacement to do
	  * @return text : the text replaced
	  * @access public
	  * @static
	  */
	function replaceVars($text, $replacement) {
		//if no text => return
		if (!$text) {
			return '';
		}
		//first, optional replacement (for atm-loop and atm-search now)
		if (is_array($replacement) && $replacement) {
			$replace = array();
			//prepare replacement
			foreach ($replacement as $replacement) {
				$replace = array_merge($replacement, $replace);
			}
			//then replace variables in text
			$text = str_replace(array_keys($replace), $replace, $text);
		}
		return $text;
	}
	
	/**
	  * Replace vars like {object:field:type} or {var|session|request|page:name:type}. Called during definition compilation
	  *
	  * @param string $text : the text which need to be replaced
	  * @param boolean reverse : reverse single and double quotes useage (default is false : double quotes)
	  * @param array $optionalReplacement : optionnal replacement to do
	  * @param boolean $cleanNotMatches : remove vars without matches
	  * @param mixed $matchCallback : function name or array(object classname, object method) which represent a valid callback function to execute on matches
	  * @return text : the text replaced
	  * @access public
	  * @static
	  */
	function preReplaceVars($text, $reverse = false, $cleanNotMatches = false, $matchCallback = array('CMS_polymod_definition_parsing', 'encloseString'), $returnMatchedVarsArray = false) {
		//if no text => return
		if (!$text) {
			return '';
		}
		//second, check text for vars to replace if any
		if (preg_match_all("#{[^{}\n]+}#", $text, $matches)) {
			$matches = array_unique($matches[0]);
			//create replacement array
			if ($reverse) {
				//create replacement array
				$replace = array();
				//replace 'fieldID' value by corresponding fieldID
				$replace["#^\{.*\[([n0-9]+)\]\[\\\'fieldID\\\'\]\}$#U"] 				= '\1';
				//replace '{vartype:type:name}' value by corresponding var call
				$replace["#^\{(var|request|session)\:([^:]+):(.+)\}$#U"] 				= 'CMS_poly_definition_functions::getVarContent("\1", "\3", "\2", @$\3)';
				//replace '{page:id:type}' value by corresponding CMS_tree::getPageValue(id, type) call
				$replace["#^\{page\:([0-9]+)\:(.*?)\}$#U"]= 'CMS_tree::getPageValue("\1","\2")';
				//create the real object path to vars
				$replace["#\[\\\'fields\\\'\]\[([n0-9]+)\]\}?#"] 						= '->objectValues(\1)';
				$replace["#\[\\\'values\\\'\]\[([n0-9]+)\]\[\\\'([a-zA-Z]+)\\\'\]\}$#U"]= '->getValue(\'\1\',\'\2\')';
				$replace["#\[\\\'([a-zA-Z]+)\\\'\]\|?([^|}]*)\}$#U"] 					= '->getValue(\'\1\',\'\2\')';
				$replace["#^\{\[\\\'object([0-9]+)\\\'\]#U"] 							= '$object[\1]';
				$replace["#\[([n0-9]+)]}$#U"] 											= '[\1]';
			} else {
				$replace = array();
				//replace 'fieldID' value by corresponding fieldID
				$replace["#^\{.*\[([n0-9]+)\]\['fieldID'\]\}$#U"] 			= '\1';
				//replace '{vartype:type:name}' value by corresponding var call
				$replace["#^\{(var|request|session)\:([^:]+):(.+)\}$#U"] 	= 'CMS_poly_definition_functions::getVarContent("\1", "\3", "\2", @$\3)';
				//replace '{page:id:type}' value by corresponding CMS_tree::getPageValue(id, type) call
				$replace["#^\{page\:([0-9]+)\:(.*?)\}$#U"]= 'CMS_tree::getPageValue("\1","\2")';
				//create the real object path to vars
				$replace["#\['fields'\]\[([n0-9]+)\]\}?#"] 				= '->objectValues(\1)';
				$replace["#\['values'\]\[([n0-9]+)\]\['([a-zA-Z]+)'\]\}$#U"]= '->getValue(\'\1\',\'\2\')';
				$replace["#\['([a-zA-Z]+)'\]\|?\"\.([^|}]*)\.\"\}$#U"] 		= '->getValue(\'\1\',\2)';
				$replace["#\['([a-zA-Z]+)'\]\|?([^|}]*)\}$#U"] 				= '->getValue(\'\1\',\'\2\')';
				$replace["#^\{\['object([0-9]+)'\]#U"] 						= '$object[\1]';
				$replace["#\[([n0-9]+)]}$#U"] 								= '[\1]';
			}
			//replace the loop 'n' value by $key
			$replace["#(\(n\))#U"] 											= '($key)';
			$replace["#('n')#U"] 											= '$key';
			//replace {page:self:type}, {plugin:selection} values
			$replace["#^\{page:self:(.*?)\}$#U"]= 'CMS_tree::getPageValue($parameters[\'pageID\'],"\1")';
			$replace["#^\{plugin:selection\}$#U"]= '$parameters[\'selection\']';
			
			$matchesValues = preg_replace(array_keys($replace), $replace, $matches);
			
			//create vars conversion table
			$replace = array();
			foreach ($matches as $key => $match) {
				if ($match != $matchesValues[$key]) {
					$matchValue = $matchesValues[$key];
				} else {
					$matchValue = null;
				}
				//apply callback if any to value
				if (isset($matchValue)) {
					if ($matchCallback !== false) {
						if (is_array($matchCallback) && is_callable($matchCallback)) {
							$replace[$match] = call_user_func($matchCallback, $matchValue, $reverse);
						} elseif (function_exists($matchCallback)) {
							$replace[$match] = $matchCallback($matchValue, $reverse);
						} else {
							CMS_grandFather::raiseError("Unknown callback function : ".$matchCallback);
							return false;
						}
					} else {
						$replace[$match] = $matchValue;
					}
				} 
				//clean not matches if needed
				elseif ($cleanNotMatches) {
					$replace[$match] = '';
				}
			}
			//return matched vars if needed
			if ($returnMatchedVarsArray) {
				return $replace;
			} 
			//else replace vars in text
			else {
				//then replace variables in text and return it
				$text = str_replace(array_keys($replace), $replace, $text);
			}
		}
		return $text;
	}
	
	/**
	  * Enclose a given var with quotes or return count if var is an array
	  *
	  * @param mixed $var : the var to enclose
	  * @return mixed : the var enclosed
	  * @access public
	  * @static
	  */
	function prepareVar($var) {
		if (is_array($var)) {
			return sizeof($var);
		} else {
			return "'".str_replace("'","\'",str_replace("\'","\\\'",$var))."'"; 
		}
	}
	
	/**
	  * Enclose a given var with CMS_polymod_definition_parsing::prepareVar method
	  *
	  * @param mixed $var : the var to enclose
	  * @return mixed : the var enclosed
	  * @access public
	  * @static
	  */
	function encloseWithPrepareVar($var) {
		return CMS_polymod_definition_parsing::encloseString("CMS_polymod_definition_parsing::prepareVar(".$var.")",false);
	}
	
	/**
	  * Enclose a given string with double quotes or single quotes according to reverse value
	  *
	  * @param mixed $var : the var to enclose
	  * @param boolean $reverse : enclose with double or single quotes
	  * @return mixed : the var enclosed
	  * @access public
	  * @static
	  */
	function encloseString($var, $reverse) {
		return ($reverse) ? "'.".$var.".'" : '".'.$var.'."';
	}
	
	/**
	  * Return an unique ID
	  * formatted as id_rand where id is the number of unique ids queried and rand a 6 alphanumerical random characters string
	  *
	  * @return string the unique ID
	  * @access public
	  * @static
	  */
	function getUniqueID () {
		static $count;
		$count++;
		return ($count+1).'_'.substr(md5(mt_rand().microtime()),0,6);
	}
	
	/**
	  * Return well indented php code
	  *
	  * @param string $phpcode : php code to indent
	  * @return string indented php code
	  * @access public
	  * @static
	  */
	function indentPHP($phpcode) {
		$phparray = array_map('trim',explode("\n",$phpcode));
		$level = 0;
		foreach ($phparray as $linenb => $phpline) {
			//remove blank lines
			if ($phpline == '') {
				unset($phparray[$linenb]);
				continue;
			}
			//check for indent level down
			if (substr($phpline, 0, 1) == '}' || substr($phpline, 0, 1) == ')') {
				$level--;
			}
			//indent code
			$indent = str_replace(' ', "\t",sprintf("%".($level)."s",  ''));
			$phparray[$linenb] = $indent.$phpline;
			//check for indent level up
			if (substr($phpline, -1) == '{' || substr($phpline, -7) == 'array (') {
				$level++;
			}
		}
		return implode ("\n",$phparray);
	}
	
	/**
	  * Do some replacements to clean produced definition code
	  *
	  * @param string $definition : php code to clean
	  * @return string cleaned php code
	  * @access private
	  * @static
	  */
	protected function _cleanComputedDefinition($definition) {
		$replace = array(
			'$content .="";' => '',
			'("".' 	=> '(',
			'="".' 	=> '=',
			'.""' 	=> '',
			'\'\'.' => '',
			'.\'\'' => '',
		);
		$pregreplace = array(
			'#\$content .="\n\s+";#' => '',
		);
		return preg_replace(array_keys($pregreplace), $pregreplace, str_replace(array_keys($replace), $replace, $definition));
	}
	
	/**
	  * Check tags attributes requirements 
	  *
	  * @param array $tag : the reference tag to compute
	  * @param array $requirements : tag attributes requirements at the following format :
	  		array(string attributeName => mixed attributeType)
			With attributeType in :
			- boolean true : check only presence of an attribute value
			- alphanum : attribute value must be a simple alphanumeric value without special chars
	  		- language : attribute value must be a valid language code
			- orderType : attribute value must be a valid order type
			- valid PERL regular expression : attribute value must be mattch the regular expression
	  * @return string indented php code
	  * @access public
	  * @static
	  */
	function checkTagRequirements(&$tag, $requirements) {
		if (!is_array($requirements)) {
			$this->raiseError('Tag requirements must be an array');
			return false;
		}
		foreach ($requirements as $name => $requirementType) {
			//check parameter existence
			if (!isset($tag['attributes'][$name])) {
				if ($this->_mode == self::CHECK_PARSING_MODE) {
					$this->_parsingError .= "\n".'Malformed '.$tag['nodename'].' tag : missing \''.$name.'\' attribute';
					return false;
				} else {
					$this->raiseError('Malformed '.$tag['nodename'].' tag : missing \''.$name.'\' attribute');
					return false;
				}
			} elseif ($requirementType !== true) {//if any, check value requirement
				switch ($requirementType) {
					case 'alphanum' :
						if ($tag['attributes'][$name] != sensitiveIO::sanitizeAsciiString($tag['attributes'][$name])) {
							if ($this->_mode == self::CHECK_PARSING_MODE) {
								$this->_parsingError .= "\n".'Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute must only be composed with alphanumeric caracters (0-9a-z) : '.$tag['attributes'][$name];
								return false;
							} else {
								$this->raiseError('Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute must only be composed with alphanumeric caracters (0-9a-z) : '.$tag['attributes'][$name]);
								return false;
							}
						}
					break;
					case 'language' :
						$languages = CMS_languagesCatalog::getAllLanguages();
						if (!isset($languages[$tag['attributes'][$name]])) {
							if ($this->_mode == self::CHECK_PARSING_MODE) {
								$this->_parsingError .= "\n".'Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute must only be a valid language code : '.$tag['attributes'][$name];
								return false;
							} else {
								$this->raiseError('Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute must only be a valid language code : '.$tag['attributes'][$name]);
								return false;
							}
						}
					break;
					case 'object':
						if (!sensitiveIO::isPositiveInteger(substr($tag['attributes'][$name],9,-3))) {
							if ($this->_mode == self::CHECK_PARSING_MODE) {
								$this->_parsingError .= "\n".'Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute does not represent a valid object';
								return false;
							} else {
								$this->raiseError('Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute does not represent a valid object');
								return false;
							}
						}
					break;
					case 'paramType' :
						if (!in_array($tag['attributes'][$name], CMS_object_search::getStaticSearchConditionTypes()) && !sensitiveIO::isPositiveInteger($tag['attributes'][$name]) && substr($tag['attributes'][$name], -12) != "['fieldID']}") {
							if ($this->_mode == self::CHECK_PARSING_MODE) {
								$this->_parsingError .= "\n".'Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute, must be one of these values : '.implode(', ', CMS_object_search::getStaticSearchConditionTypes());
								return false;
							} else {
								$this->raiseError('Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute, must be one of these values : '.implode(', ', CMS_object_search::getStaticSearchConditionTypes()));
								return false;
							}
						}
					break;
					case 'orderType' :
						if (!in_array($tag['attributes'][$name], CMS_object_search::getStaticOrderConditionTypes()) && !sensitiveIO::isPositiveInteger($tag['attributes'][$name]) && substr($tag['attributes'][$name], -12) != "['fieldID']}") {
							if ($this->_mode == self::CHECK_PARSING_MODE) {
								$this->_parsingError .= "\n".'Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute, must be one of these values : '.implode(', ', CMS_object_search::getStaticOrderConditionTypes());
								return false;
							} else {
								$this->raiseError('Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute, must be one of these values : '.implode(', ', CMS_object_search::getStaticOrderConditionTypes()));
								return false;
							}
						}
					break;
					default: //check 
						if (!preg_match('#^'.$requirementType.'$#i', $tag['attributes'][$name])) {
							if ($this->_mode == self::CHECK_PARSING_MODE) {
								$this->_parsingError .= "\n".'Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute must match expression \''.$requirementType.'\' : '.$tag['attributes'][$name];
								return false;
							} else {
								$this->raiseError('Malformed '.$tag['nodename'].' tag : \''.$name.'\' attribute must match expression \''.$requirementType.'\' : '.$tag['attributes'][$name]);
								return false;
							}
						}
					break;
				}
			}
		}
		return true;
	}
}
?>