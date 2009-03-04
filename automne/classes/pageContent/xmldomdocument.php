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
// | Author: Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>      |
// +----------------------------------------------------------------------+
//
// $Id: xmldomdocument.php,v 1.2 2009/03/04 09:57:52 sebastien Exp $

/**
  * Class CMS_DOMDocument
  *
  * implement PHP class DOMDOcument. Allow usage of exceptions by load and loadXML method
  *
  * @package CMS
  * @subpackage page content
  * @author Sébastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

class CMS_DOMDocument extends DOMDocument {
	public function __construct($version = '1.0', $encoding = APPLICATION_DEFAULT_ENCODING) {
		parent::__construct($version, $encoding);
	}
	
	public function loadXML($source, $options = 0, $appendEncoding = true) {
		//append xml encoding and DTD if needed
		if ($appendEncoding) {
			$doctype = !APPLICATION_IS_WINDOWS ? '<!DOCTYPE automne SYSTEM "'.PATH_PACKAGES_FS.'/files/xhtml.ent">' : '<!DOCTYPE automne ['.file_get_contents(PATH_PACKAGES_FS.'/files/xhtml.ent').']>';
			$source = '<?xml version="1.0" encoding="'.APPLICATION_DEFAULT_ENCODING.'"?>
			'.$doctype.'
			'.$source;
			$options = ($options) ? $options | LIBXML_DTDLOAD : LIBXML_DTDLOAD;
		}
		set_error_handler (array('CMS_DOMDocument','XmlError'));
		$return = parent::loadXml($source, $options);
		restore_error_handler();
		return $return;
	}
	
	function XmlError($errno, $errstr, $errfile, $errline) {
		if ($errno==E_WARNING && (substr_count($errstr,"DOMDocument::loadXML()")>0)) {
			$error = substr($errstr, strlen('DOMDocument::loadXML() [<a href=\'function.DOMDocument-loadXML\'>function.DOMDocument-loadXML</a>]: '));
			throw new DOMException($error);
		} else {
			return false;
		}
	}
	
	public function DOMElementToString($domelement, $contentOnly = false) {
		if (!is_a($domelement, "DOMElement")) {
			CMS_grandFather::raiseError('Domelement is not a DOMElement instance');
			return false;
		}
		$output = '';
		//create DOMDocument
		$doc = new CMS_DOMDocument();
		if ($contentOnly) {
			$i = 0;
			while ( $node = $domelement->childNodes->item($i) ) {
				// import node
				$domNode = $doc->importNode($node, true);
				// append node
				$doc->appendChild($domNode);
				$i++;
			}
			$output = $doc->saveXML();
			//remove xml declaration
			$output = substr($output, strlen('<?xml version="1.0" encoding="'.APPLICATION_DEFAULT_ENCODING.'"?>')+1, -1);
		} else {
			// import node
			$domNode = $doc->importNode($domelement, true);
			// append node
			//$doc->appendChild($domNode);
			$output = $doc->saveXML($domNode);
		}
		return $output;
	}
}
?>