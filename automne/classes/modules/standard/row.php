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
// | Author: Antoine Pouch <antoine.pouch@ws-interactive.fr> &            |
// | Author: S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
// +----------------------------------------------------------------------+
//
// $Id: row.php,v 1.2 2008/12/18 10:40:46 sebastien Exp $

/**
  * Class CMS_row
  *
  * represent a client space row which contains blocks.
  *
  * @package CMS
  * @subpackage module
  * @author Antoine Pouch <antoine.pouch@ws-interactive.fr> &
  * @author S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_row extends CMS_grandFather
{
	/**
	  * Messages
	  */
	const MESSAGE_DELETE_ROW_CONFIRM = 844;
	const MESSAGE_PAGE_BLOCK_SYNTAX_ERROR = 1295;
	const MESSAGE_PAGE_ROW_SYNTAX_ERROR = 1296;

	const MESSAGE_BUTTON_CLEAR = 1128;
	const MESSAGE_CLEAR_ROW_CONFIRM = 1129;
	/**
	  * DB id
	  * @var integer
	  * @access private
	  */
	protected $_id;

	/**
	  * XML Tag id
	  * @var string
	  * @access private
	  */
	protected $_tagID;

	/**
	  * Label of the row
	  * @var string
	  * @access private
	  */
	protected $_label;

	/**
	  * row groups this row belongs to
	  * @var CMS_stack
	  * @access private
	  */
	protected $_groups;

	/**
	  * Definition file (contains the blocks definition and HTML)
	  * @var string
	  * @access private
	  */
	protected $_definitionFile;

	/**
	  * Modules that will be called by row included in the template
	  * @var CMS_stack
	  * @access private
	  */
	protected $_modules;

	/**
	  * array of blocks XML tags found in the definition file
	  * @var array(CMS_XMLTag)
	  * @access private
	  */
	protected $_blocks = array();

	/**
	  * Image of the row
	  * @var string : image filename
	  * @access private
	  */
	protected $_image = 'nopicto.gif';

	/**
	  * array of templates Ids to filter row useage
	  * @var array()
	  * @access private
	  */
	protected $_tplfilter = array();

	/**
	  * Row description
	  * @var string
	  * @access private
	  */
	protected $_description = '';

	/**
	  * Does row useable by users
	  * @var boolean
	  * @access private
	  */
	protected $_useable;

	/**
	  * Constructor.
	  *
	  * @param integer $id the DB ID of the row
	  * @param string $tagID the XML Tag ID of the row (if instansiated from the tag)
	  * @return void
	  * @access public
	  */
	function __construct($id = 0, $tagID = false)
	{
		$this->_tagID = $tagID;
		$this->_modules = new CMS_stack();
		$this->_groups = new CMS_stack();
		if ($id) {
			if (!SensitiveIO::isPositiveInteger($id)) {
				$this->raiseError("Id is not a positive integer");
				return;
			}
			$sql = "
				select
					*
				from
					mod_standard_rows
				where
					id_row='$id'
			";
			$q = new CMS_query($sql);

			if ($q->getNumRows()) {
				$data = $q->getArray();
				$this->_id = $id;
				$this->_label = $data["label_row"];
				$this->_groups->setTextDefinition($data["groupsStack_row"]);
				$this->_definitionFile = $data["definitionFile_row"];
				$this->_modules->setTextDefinition($data["modulesStack_row"]);
				$this->_image = ($data["image_row"]) ? $data["image_row"] : $this->_image;
				$this->_useable = ($data["useable_row"]) ? true : false;
				$this->_description = $data["description_row"];
				$this->_tplfilter = trim($data["tplfilter_row"]) ? explode(';', $data["tplfilter_row"]) : array();

			} else {
				$this->raiseError("Unknown id :".$id);
			}
		}
	}

	/**
	  * Does the row participates to one or more client spaces ?
	  *
	  * @return boolean true if it's the case
	  * @access public
	  */
	function hasClientSpaces()
	{
		$sql = "
			select
				1
			from
				mod_standard_clientSpaces_edited
			where
				type_cs = '".$this->_id."'
		";
		$q = new CMS_query($sql);
		if ($q->getNumRows()>0) {
			return true;
		}
		$sql = "
			select
				1
			from
				mod_standard_clientSpaces_public
			where
				type_cs = '".$this->_id."'
		";
		$q = new CMS_query($sql);
		return ($q->getNumRows()>0) ? true:false;
	}

	/**
	  * Gets the DB ID.
	  *
	  * @return integer the DB id
	  * @access public
	  */
	function getID()
	{
		return $this->_id;
	}

	/**
	  * Gets the XML tag ID.
	  *
	  * @return integer the DB id
	  * @access public
	  */
	function getTagID()
	{
		return $this->_tagID;
	}

	/**
	  * Gets the label.
	  *
	  * @return string the label
	  * @access public
	  */
	function getLabel()
	{
		return $this->_label;
	}

	/**
	  * Gets the row image.
	  *
	  * @return string the row image filename
	  * @access public
	  */
	function getImage($from = CMS_file::WEBROOT) {
		if (!file_exists(PATH_TEMPLATES_ROWS_FS.'/images/'.$this->_image)) {
			$this->_image = 'nopicto.gif';
		}
		return ($from == CMS_file::FILE_SYSTEM) ? PATH_TEMPLATES_ROWS_FS.'/images/'.$this->_image : PATH_TEMPLATES_ROWS_WR.'/images/'.$this->_image;
	}

	/**
	  * Sets the image. Can be empty. Must have the gif, jpg, jpeg or png extension.
	  *
	  * @param string $image the image to set
	  * @return boolean true on success, false on failure.
	  * @access public
	  */
	function setImage($image = 'nopicto.gif')
	{
		if (!trim($image)) {
			$image = 'nopicto.gif';
		}
		$extension = substr($image, strrpos($image, ".") + 1);
		if (SensitiveIO::isInSet(strtolower($extension), array("jpg", "jpeg", "gif", "png"))) {
			$this->_image = $image;
			return true;
		} else {
			$this->_image = 'nopicto.gif';
			return true;
		}
	}

	/**
	  * Sets the label.
	  *
	  * @param string $label the label to set
	  * @return boolean true on success, false on failure to set it
	  * @access public
	  */
	function setLabel($label)
	{
		if ($label) {
			$this->_label = stripslashes($label);
			return true;
		} else {
			return false;
		}
	}

	/**
	  * Gets the description
	  *
	  * @return string The label
	  * @access public
	  */
	function getDescription()
	{
		return $this->_description;
	}

	/**
	  * Sets the description.
	  *
	  * @param string $label The label to set
	  * @return boolean true on success, false on failure.
	  * @access public
	  */
	function setDescription($description)
	{
		$this->_description = $description;
		return true;
	}

	/**
	  * Is the row useable by users ?
	  *
	  * @return boolean
	  * @access public
	  */
	function isUseable()
	{
		return $this->_useable;
	}

	/**
	  * Sets the usability
	  *
	  * @param boolean $usability The new usablility to set
	  * @return boolean true on success, false on failure.
	  * @access public
	  */
	function setUsability($usability)
	{
		$this->_useable = ($usability) ? true : false;
		return true;
	}

	/**
	  * Get the filtered templates for the row
	  *
	  * @return array(string) The filtered templates IDs
	  * @access public
	  */
	function getFilteredTemplates()
	{
		return $this->_tplfilter;
	}

	/**
	  * Set the filtered templates. Must be an array of templates Ids
	  *
	  * @param array $tplsFilter
	  * @return void
	  * @access public
	  */
	function setFilteredTemplates($tplsFilter)
	{
		if (!is_array($tplsFilter)) {
			$this->_raiseError('$tplsFilter must be an array of page Ids.');
			return false;
		}
		$this->_tplfilter = $tplsFilter;
	}

	/**
	  * Gets the definition file name.
	  *
	  * @return string the file name of the definition file, without any path indication.
	  * @access public
	  */
	function getDefinitionFileName()
	{
		return $this->_definitionFile;
	}

	/**
	  * Gets the definition as string data, taken from the definition file
	  *
	  * @return string the definition
	  * @access public
	  */
	function getDefinition()
	{
		if ($file = $this->getDefinitionFileName()) {
			$fp = fopen(PATH_TEMPLATES_ROWS_FS."/".$file, 'rb');
			if (is_resource($fp)) {
				$data = fread($fp, filesize (PATH_TEMPLATES_ROWS_FS."/".$file));
				fclose($fp);
				//check if rows use a polymod block, if so pass to module for variables conversion
				foreach ($this->getModules(false) as $moduleCodename) {
					if (CMS_modulesCatalog::isPolymod($moduleCodename)) {
						$module = CMS_modulesCatalog::getByCodename($moduleCodename);
						$data = $module->convertDefinitionString($data, true);
					}
				}

				return $data;
			}
		}
		return false;
	}

	/**
	  * Gets a block tag, taken from the definition file
	  *
	  * @param string $blockID the block id to get
	  * @return CMS_XMLTag the block tag
	  * @access public
	  */
	function getBlockTagById($blockID)
	{
		if (!$this->_blocks) {
			$modulesTreatment = new CMS_modulesTags(MODULE_TREATMENT_BLOCK_TAGS,RESOURCE_LOCATION_EDITION,$this);
			$this->_parseDefinitionFile($modulesTreatment);
		}
		if (!$this->_blocks) {
			$this->raiseError("No blocks tags founded in row definition");
			return false;
		}
		foreach ($this->_blocks as $block) {
			if ($block->getAttribute('id') == $blockID) {
				return $block;
			}
		}
		$this->raiseError("No block tag founded in row with id : ".$blockID);
		return false;
	}

	/**
	  * Gets the modules included in the template. Excludes the standard module.
	  *
	  * @return array(CMS_module) The modules present in the template via module client spaces
	  * @access public
	  */
	function getModules($outputObjects = true)
	{
		$elements = $this->_modules->getElements();

		$modules = array();
		foreach ($elements as $element) {
			$modules[] = ($outputObjects) ? CMS_modulesCatalog::getByCodename($element[0]) : $element[0];
		}
		return $modules;
	}

	/**
	  * Check the given user right on row
	  *
	  * @param CMS_profile_user $cms_user The user profile to check right
	  * @param integer $right, the right to check
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function hasUserRight(&$cms_user, $right = CLEARANCE_MODULE_EDIT) {
		if (!is_a($cms_user, 'CMS_profile_user')) {
			$this->raiseError("cms_user must be a valid CMS_profile_user");
			return false;
		}
		//useless
		$modulesRow = $this->getModules(false);
		foreach($modulesRow as $moduleCodename) {
			if (!$cms_user->hasModuleClearance($moduleCodename, $right)) {
				return false;
			}
		}
		$groups = $this->getGroups();
		if (is_array($groups) && $groups) {
			foreach($groups as $group){
				if ($cms_user->hasRowGroupsDenied($group)){
					return false;
				}
			}
		}
		return true;
	}

	/**
	  * Sets the definition from a string. Must write the definition to file and try to parse it
	  * The file must be in a specific directory : PATH_TEMPLATES_ROWS_FS (see constants from rc file)
	  *
	  * @param string $definition The definition
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function setDefinition($definition)
	{
		global $cms_language;
		$defXML = new CMS_DOMDocument();
		try {
			$defXML->loadXML($definition);
		} catch (DOMException $e) {
			return $cms_language->getMessage(self::MESSAGE_PAGE_ROW_SYNTAX_ERROR, array($e->getMessage()));
		}
		$blocks = $defXML->getElementsByTagName('block');
		$modules = array();
		foreach ($blocks as $block) {
			if ($block->hasAttribute("module")) {
				$modules[] = $block->getAttribute("module");
			} else {
				return $cms_language->getMessage(self::MESSAGE_PAGE_ROW_SYNTAX_ERROR, array($cms_language->getMessage(self::MESSAGE_PAGE_BLOCK_SYNTAX_ERROR)));
			}
		}
		$modules = array_unique($modules);
		$this->_modules->emptyStack();
		foreach ($modules as $module) {
			$this->_modules->add($module);
		}
		//check if rows use a polymod block, if so pass to module for variables conversion
		$rowConverted = false;
		foreach ($this->getModules(false) as $moduleCodename) {
			if (CMS_modulesCatalog::isPolymod($moduleCodename)) {
				$rowConverted = true;
				$module = CMS_modulesCatalog::getByCodename($moduleCodename);
				$definition = $module->convertDefinitionString($definition, false);
			}
		}
		if ($rowConverted) {
			//check definition parsing
			$parsing = new CMS_polymod_definition_parsing($definition, true, CMS_polymod_definition_parsing::CHECK_PARSING_MODE);
			$errors = $parsing->getParsingError();
			if ($errors) {
				return $cms_language->getMessage(self::MESSAGE_PAGE_ROW_SYNTAX_ERROR, array($errors));
			}
		}
		$filename = $this->getDefinitionFileName();
		if (!$filename) {
			//must write it to persistence to have its ID
			if (!$this->_id) {
				$this->writeToPersistence();
			}
			//build the filename
			$filename = "r".$this->_id."_".SensitiveIO::sanitizeAsciiString($this->_label).".xml";
		}
		$rowFile = new CMS_file(PATH_TEMPLATES_ROWS_FS."/".$filename);
		$rowFile->setContent($definition);
		$rowFile->writeToPersistence();
		$this->_definitionFile = $filename;
		return true;
	}

	/**
	  * Gets the data, using the specified visualization mode.
	  * The data is taken from the blocks and reintroduced into the definition file which may itself contain HTML instructions.
	  *
	  * @param CMS_language &$language The language of the administration frontend
	  * @param CMS_page &$page the page parsed
	  * @param CMS_clientSpace &$clientSpace the client space parsed
	  * @param integer $visualizationMode the visualization mode
      * @param boolean $templateHasPages, set to true will die access to up/down buttons
      * @param boolean $rowCanBeEdited determine we can edit it this page (Display form)
	  * @return string the data from the blocks and the definition file.
	  * @access public
	  */
	function getData(&$language, &$page, &$clientSpace, $visualizationMode, $templateHasPages=false, $rowCanBeEdited=true)
	{
		global $cms_user;
		$modulesTreatment = new CMS_modulesTags(MODULE_TREATMENT_BLOCK_TAGS, $visualizationMode, $this);
		$modulesTreatment->setTreatmentParameters(array("page" => $page, "language" => $language, "clientSpace" => $clientSpace));
		if (!$this->_parseDefinitionFile($modulesTreatment)) { //here we expect a false (otherwise, it is an error)
			$data = $modulesTreatment->treatContent();
			//if $visualizationMode is CLIENTSPACES_FORM and
            //no page uses the template calling this row, add the form here
			if ($visualizationMode == PAGE_VISUALMODE_CLIENTSPACES_FORM || $visualizationMode == PAGE_VISUALMODE_FORM) {
				//append atm-row class and row-id to all first level tags founded in row datas
				$domdocument = new CMS_DOMDocument();
				try {
					$domdocument->loadXML('<row>'.$data.'</row>');
				} catch (DOMException $e) {
					$this->raiseError('Parse error for row : '.$e->getMessage()." :\n".htmlspecialchars($data));
					return '';
				}
				$rowNodes = $domdocument->getElementsByTagName('row');
				if ($rowNodes->length == 1) {
					$rowXML = $rowNodes->item(0);
				}
				$elements = array();
				$rowId = 'row-'.$this->_tagID;
				foreach($rowXML->childNodes as $rowChildNode) {
					if (is_a($rowChildNode, 'DOMElement') && $rowChildNode->tagName != 'script') {
						if ($rowChildNode->hasAttribute('class')) {
							$rowChildNode->setAttribute('class', $rowChildNode->getAttribute('class').' '.$rowId);
						} else {
							$rowChildNode->setAttribute('class',$rowId);
						}
						if ($rowChildNode->hasAttribute('id')) {
							$elementId = $rowChildNode->getAttribute('id');
						} else {
							$elementId = 'el-'.md5(uniqid());
							$rowChildNode->setAttribute('id',$elementId);
						}
						$elements[] = $elementId;
					}
				}
				$data = CMS_DOMDocument::DOMElementToString($rowXML, true);
				//add row specification
				$data = '
				<script type="text/javascript">
					atmRowsDatas[\''.$rowId.'\'] = {
						id:					\''.$rowId.'\',
						template:			\''.$clientSpace->getTemplateID().'\',
						clientSpaceTagID:	\''.$clientSpace->getTagID().'\',
						rowTagID:			\''.$this->_tagID.'\',
						rowType:			\''.$this->_id.'\',
						label:				\''.sensitiveIO::sanitizeJSString($this->getLabel()).'\',
						userRight:			\''.$this->hasUserRight($cms_user).'\',
						visualMode:			\''.$visualizationMode.'\',
						document:			document,
						elements:			['.($elements ? '\''.implode('\',\'', $elements).'\'' : '').']
					};
				</script>
				'.$data;
			}
			return $data;
		} else {
			$this->raiseError('Can not use row template file '.$this->_definitionFile);
			return false;
		}
	}

	/**
	  * Parse the definition file to instanciate the _blocks attribute
	  *
	  * @param CMS_modulesTags $modulesTreatment tags object treatment
	  * @return string false on success, the parsing error string if any
	  * @access public
	  */
	protected function _parseDefinitionFile(&$modulesTreatment) {
		if ($this->_definitionFile) {
			$filename = PATH_TEMPLATES_ROWS_FS."/".$this->_definitionFile;
			$tplrow = new CMS_file(PATH_TEMPLATES_ROWS_FS."/".$this->_definitionFile);
			if (!$tplrow->exists()) {
				$this->raiseError('Can not found row template file '.PATH_TEMPLATES_ROWS_FS."/".$this->_definitionFile);
				return true;
			}
			$modulesTreatment->setDefinition($tplrow->readContent());
			$this->_blocks = $modulesTreatment->getTags(array('block'));
			if (is_array($this->_blocks)) {
				return false;
			} else {
				$this->raiseError("Malformed definition file : ".$this->_definitionFile."<br />".$modulesTreatment->getParsingError());
				return $modulesTreatment->getParsingError();
			}
		} else {
			$this->raiseError('Can not found row definition file ...');
			return true;
		}
	}

	/**
	  * Totally destroys the row from persistence, including its definition file.
	  *
	  * @return void
	  * @access public
	  */
	function destroy()
	{
		if ($this->_id) {
			$sql = "
				delete
				from
					mod_standard_rows
				where
					id_row='".$this->_id."'
			";
			$q = new CMS_query($sql);
		}
		if ($this->_definitionFile) {
			@unlink(PATH_TEMPLATES_ROWS_FS."/".$this->_definitionFile);
		}
		unset($this);
	}

	/**
	  * Gets the groups the row belongs to.
	  *
	  * @return array(string) The groups represented by their string in an array
	  * @access public
	  */
	function getGroups(){
		$groups_arrayed = $this->_groups->getElements();
		$groups = array();
		foreach ($groups_arrayed as $group_arrayed) {
			$groups[$group_arrayed[0]] = $group_arrayed[0];
		}
		return $groups;
	}

	/**
	  * Does the row has the specified group in its stack ?.
	  *
	  * @param string $group The group we want to test
	  * @return boolean true if the row belongs to that group, false otherwise
	  * @access public
	  */
	function belongsToGroup($group){
		$grps = $this->_groups->getElementsWithOneValue($group, 1);
		return (is_array($grps) && $grps);
	}

	/**
	  * Add a group to the list.
	  *
	  * @param string $group The group to add
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function addGroup($group){
		if ($group && $group == SensitiveIO::sanitizeSQLString($group)) {
			$groups = $this->getGroups();
			if (!in_array($group, $groups)) {
				$this->_groups->add($group);
			}
			return true;
		} else {
			$this->raiseError('Trying to set an empty group or which contains illegal characters');
			return false;
		}
	}

	/**
	  * Remove a group from the list.
	  *
	  * @param string $group The group to remove
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function delGroup($group){
		if ($group) {
			$this->_groups->del($group);
			return true;
		} else {
			$this->raiseError('Trying to remove an empty group');
			return false;
		}
	}

	/**
	  * Deletes all the groups stack
	  *
	  * @return void
	  * @access public
	  */
	function delAllGroups(){
		$this->_groups = new CMS_stack();
	}

	/**
	  * Writes the row into persistence (MySQL for now).
	  *
	  * @return boolean true on success, false on failure
	  * @access public
	  */
	function writeToPersistence()
	{
		$sql_fields = "
			label_row='".SensitiveIO::sanitizeSQLString($this->_label)."',
			definitionFile_row='".SensitiveIO::sanitizeSQLString($this->_definitionFile)."',
			modulesStack_row='".$this->_modules->getTextDefinition()."',
			groupsStack_row='".SensitiveIO::sanitizeSQLString($this->_groups->getTextDefinition())."',
			useable_row='".SensitiveIO::sanitizeSQLString($this->_useable)."',
			description_row='".SensitiveIO::sanitizeSQLString($this->_description)."',
			tplfilter_row='".SensitiveIO::sanitizeSQLString(implode(';',$this->_tplfilter))."',
			image_row='".SensitiveIO::sanitizeSQLString($this->_image)."'
		";

		if ($this->_id) {
			$sql = "
				update
					mod_standard_rows
				set
					".$sql_fields."
				where
					id_row='".$this->_id."'
			";
		} else {
			$sql = "
				insert into
					mod_standard_rows
				set
					".$sql_fields."
			";
		}
		//pr($sql);
		$q = new CMS_query($sql);
		if ($q->hasError()) {
			return false;
		} elseif (!$this->_id) {
			$this->_id = $q->getLastInsertedID();
		}
		return true;
	}
	
	function getJSonDescription($user, $cms_language, $withDefinition = false) {
		$hasClientSpaces = $this->hasClientSpaces();
		$description = sensitiveIO::ellipsis($this->getDescription(), 50);
		if ($description != $this->getDescription()) {
			$description = '<span ext:qtip="'.htmlspecialchars($this->getDescription()).'">'.$description.'</span>';
		}
		$description = $description ? $description.'<br />' : '';
		//append template definition if needed
		$definitionDatas = $withDefinition ? $this->getDefinition() : '';
		//templates filters
		$filteredTemplates = '';
		if ($this->getFilteredTemplates()) {
			foreach ($this->getFilteredTemplates() as $tplId) {
				$template = CMS_pageTemplatesCatalog::getByID($tplId);
				if (is_object($template) && !$template->hasError()) {
					$filteredTemplates .= ($filteredTemplates) ? ', ' : '';
					$filteredTemplates .= $template->getLabel();
				}
			}
		}
		$filtersInfos = '';
		$filtersInfos .= ($filteredTemplates) ? 'Aux mod�les : '.$filteredTemplates : '';
		$filtersInfos = ($filtersInfos) ? '<br />Usage restreint : <strong>'.$filtersInfos.'</strong>' : '';
		if ($user->hasAdminClearance(CLEARANCE_ADMINISTRATION_TEMPLATES)) {
			$edit = array(
				'url' 		=> 'row.php',
				'params'	=> array(
					'row' 		=> $this->getID()
				)
			);
		} else {
			$edit = false;
		}
		return array(
			'id'			=> $this->getID(),
			'label'			=> $this->getLabel(),
			'type'			=> 'Mod�le de Rang�e',
			'image'			=> $this->getImage(),
			'groups'		=> implode(', ', $this->getGroups()),
			'desc'			=> $this->getDescription(),
			'filter'		=> $this->getLabel().' '.implode(', ', $this->getGroups()),
			'tplfilter'		=> implode(',', $this->getFilteredTemplates()),
			'description'	=> 	'<div'.(!$this->isUseable() ? ' class="atm-inactive"' : '').'>'.
									'<img src="'.$this->getImage().'" style="float:left;margin-right:3px;width:70px;" />'.
									$description.
									'Groupes : <strong>'.implode(', ', $this->getGroups()).'</strong><br />'.
									'Actif : <strong>'.($this->isUseable() ? 'Oui':'Non').'</strong><br />'.
									'Employ� : <strong>'.($hasClientSpaces ? 'Oui':'Non').'</strong>'.($hasClientSpaces ? ' - <a href="#" onclick="Automne.view.search(\'row:'.$this->getID().'\');return false;">Voir</a>'.
									($user->hasAdminClearance(CLEARANCE_ADMINISTRATION_REGENERATEPAGES) ? ' / <a href="#" onclick="Automne.server.call(\'rows-controler.php\', \'\', {rowId:'.$this->getID().', action:\'regenerate\'});return false;">R�g�n�rer</a>' : '').' les pages.' : '').
									$filtersInfos.
									'<br class="x-form-clear" />'.
								'</div>',
			'activated'		=> $this->isUseable() ? true : false,
			'used'			=> $hasClientSpaces,
			'definition'	=> $definitionDatas,
			'edit'			=> $edit,
		);
	}
}
?>