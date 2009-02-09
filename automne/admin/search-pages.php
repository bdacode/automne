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
// | Author: S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>	  |
// +----------------------------------------------------------------------+
//
// $Id: search-pages.php,v 1.2 2008/12/18 10:36:43 sebastien Exp $

/**
  * PHP page : return search pages results
  * 
  *
  * @package CMS
  * @subpackage admin
  * @author S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once($_SERVER["DOCUMENT_ROOT"]."/cms_rc_admin.php");

//load interface instance
$view = CMS_view::getInstance();
//set default display mode for this page
$view->setDisplayMode(CMS_view::SHOW_JSON);

$query = sensitiveIO::request('query', '', '');
$start = sensitiveIO::request('start', 'sensitiveIO::isPositiveInteger', 0);
$limit = sensitiveIO::request('limit', 'sensitiveIO::isPositiveInteger', 10);

if (!$query || strlen($query) < 3) {
	CMS_grandFather::raiseError('Missing query or query is too short : '.$query);
	$view->show();
}
//lauch search
$results = CMS_search::getSearch($query, $cms_user, false, false);
//pr($results);
$pages = array();
$count = 0;
if (isset($results['results']) && is_array($results['results'])) {
	foreach ($results['results'] as $result) {
		if ($count >= $start && sizeof($pages) < $limit) {
			$page = CMS_tree::getPageById($result);
			if ($page && !$page->hasError()) {
				$pages[] = array(
					'pageId' 	=> $page->getID(),
					'title' 	=> $page->getTitle().' ('.$page->getID().')',
					'status' 	=> $page->getStatus()->getHTML(true, $cms_user, MOD_STANDARD_CODENAME, $page->getID()),
					'lineage' 	=> CMS_tree::getLineage(APPLICATION_ROOT_PAGE_ID, $page->getID(), false),
				);
			} else {
				$results['nbresult']--;
			}
		}
	}
}
$return = array(
	'pages' 	=> $pages,
	'totalCount'=> $results['nbresult'],
);

$view->setContent($return);
$view->show();
?>