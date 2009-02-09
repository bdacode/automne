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
// $Id: page-properties.php,v 1.3 2009/02/09 10:01:43 sebastien Exp $

/**
  * PHP page : Load page properties window.
  * Used accross an Ajax request render page page properties window.
  * 
  * @package CMS
  * @subpackage admin
  * @author S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr>
  */

require_once($_SERVER["DOCUMENT_ROOT"]."/cms_rc_admin.php");

define("MESSAGE_WINDOW_TITLE", 129);
define("MESSAGE_TOOLBAR_HELP",1073);
define("MESSAGE_PAGE_FIELD_YES", 1082);
define("MESSAGE_PAGE_FIELD_NO", 1083);
define("MESSAGE_PAGE_LINKS_RELATIONS", 1405);
define('MESSAGE_PAGE_RESULTS_RELATIONS', 1417);
define('MESSAGE_PAGE_RESULTS_LINKS', 1418);
define("MESSAGE_PAGE_FIELD_TO", 1302);
define("MESSAGE_PAGE_FIELD_PAGE", 1303);
define("MESSAGE_PAGE_INFO_CLICK_TO_EDIT", 331);
define("MESSAGE_PAGE_FIELD_TITLE", 132);
define("MESSAGE_PAGE_FIELD_LINKTITLE", 133);
define("MESSAGE_PAGE_INFO_ID", 70);
define("MESSAGE_PAGE_INFO_URL", 1099);
define("MESSAGE_PAGE_INFO_TEMPLATE", 72);
define("MESSAGE_PAGE_INFO_WEBSITE", 1076);
define("MESSAGE_PAGE_INFO_LINKS_RELATIONS", 1405);
define("MESSAGE_PAGE_INFO_PRINT", 1077);
define("MESSAGE_PAGE_FIELD_REDIRECT", 1039);
define("MESSAGE_PAGE_INFO_REQUIRED_FIELD", 1239);
define("MESSAGE_PAGE_FIELD_FORCEURLREFRESH_COMMENT", 1317);
define("MESSAGE_PAGE_INFO_FORCEURLREFRESH", 1318);

define("MESSAGE_PAGE_FIELD_PUBDATE_BEG", 134);
define("MESSAGE_PAGE_FIELD_DATE_COMMENT", 148);
define("MESSAGE_PAGE_FIELD_PUBDATE_END", 135);
define("MESSAGE_PAGE_FIELD_REMINDERDELAY", 136);
define("MESSAGE_PAGE_FIELD_REMINDERDELAY_COMMENT", 150);
define("MESSAGE_PAGE_FIELD_REMINDERDATE", 137);
define("MESSAGE_PAGE_FIELD_REMINDERMESSAGE", 138);

define("MESSAGE_PAGE_FIELD_DESCRIPTION", 139);
define("MESSAGE_PAGE_FIELD_DESCRIPTION_COMMENT", 149);
define("MESSAGE_PAGE_FIELD_KEYWORDS", 140);
define("MESSAGE_PAGE_TITLE_BASEDATAS", 88);
define("MESSAGE_PAGE_TITLE_METATAGS", 1043);
define("MESSAGE_PAGE_TITLE_COMMONMETATAGS", 1041);
define("MESSAGE_PAGE_FIELD_CATEGORY", 1044);
define("MESSAGE_PAGE_FIELD_AUTHOR", 1033);
define("MESSAGE_PAGE_FIELD_REPLYTO", 1034);
define("MESSAGE_PAGE_FIELD_COPYRIGHT", 1035);
define("MESSAGE_PAGE_FIELD_LANGUAGE", 1036);
define("MESSAGE_PAGE_FIELD_ROBOTS", 1037);
define("MESSAGE_PAGE_FIELD_ROBOTS_COMMENT", 1042);
define("MESSAGE_PAGE_FIELD_PRAGMA", 1038);
define("MESSAGE_PAGE_FIELD_PRAGMA_COMMENTS", 1040);

define("MESSAGE_PAGE_UNPUBLISHED", 367);
define("MESSAGE_PAGE_UPDATE_NEXT_VALIDATION", 368);
define("MESSAGE_PAGE_LINK_LABEL_POINTING", 369);
define("MESSAGE_PAGE_IDENTIFIER_INFO", 370);
define("MESSAGE_PAGE_TEMPLATE_USED_INFO", 371);
define("MESSAGE_PAGE_BELONG_SITE", 372);
define("MESSAGE_PAGE_RELATION_BETWEEN_PAGE", 373);
define("MESSAGE_PAGE_PRINTABLE_VERSION", 374);
define("MESSAGE_PAGE_AUTOMATIC_REDIRECTION", 375);
define("MESSAGE_PAGE_DATE_START_PUBLICATION", 376);
define("MESSAGE_PAGE_DATE_END_PUBLICATION", 377);
define("MESSAGE_PAGE_DELAY_ALERT_MESSAGE", 378);
define("MESSAGE_PAGE_DATE_RECEPTION_ALERT_MESSAGE", 379);
define("MESSAGE_PAGE_ALERT_MESSAGE_INFO", 380);
define("MESSAGE_PAGE_TITLE_INFO", 381);
define("MESSAGE_PAGE_DESC_INFO", 382);
define("MESSAGE_PAGE_KEYWORD_INFO", 383);
define("MESSAGE_PAGE_CATEGORY_INFO", 384);
define("MESSAGE_PAGE_ROBOTS_INFO", 385);
define("MESSAGE_PAGE_BROWSER_DEFAULT_VALUE", 386);
define("MESSAGE_PAGE_AUTHOR_INFO", 387);
define("MESSAGE_PAGE_MAIL_INFO", 388);
define("MESSAGE_PAGE_COPYRIGHT_INFO", 389);
define("MESSAGE_PAGE_LANGUAGE_USED_INFO", 390);
define("MESSAGE_PAGE_BROWSER_CACHE_INFO", 391);
define("MESSAGE_PAGE_META_DATA", 398);
define("MESSAGE_PAGE_META_DATA_INFO", 392);
define("MESSAGE_PAGE_META_DATA_LABEL", 393);
define("MESSAGE_PAGE_SUBPAGES_LABEL", 394);
define("MESSAGE_PAGE_SUBPAGES_LIST_MESSAGE", 395);
define("MESSAGE_PAGE_DRAGANDDROP_MESSAGE", 396);
define("MESSAGE_PAGE_LOG_LABEL", 29);
define("MESSAGE_PAGE_TOOLBAR_HELP_INFO", 397);
define("MESSAGE_PAGE_MATCHING_TEMPLATE", 353);
define("MESSAGE_PAGE_UNMATCHING_TEMPLATE", 354);
define("MESSAGE_PAGE_PROPERTIES_LABEL", 8);
define("MESSAGE_PAGE_DATE_ALERT_LABEL", 1079);
define("MESSAGE_PAGE_SEARCH_ENGINE_LABEL", 1080);
define("MESSAGE_PAGE_ALIAS_LABEL", 399);


//load interface instance
$view = CMS_view::getInstance();
//set default display mode for this page
$view->setDisplayMode(CMS_view::SHOW_RAW);

/*
	$winId = (isset($_REQUEST['winId'])) ? $_REQUEST['winId'] : 'propertiesWindow';
	$currentPage = (isset($_REQUEST['currentPage']) && sensitiveIO::isPositiveInteger($_REQUEST['currentPage'])) ? $_REQUEST['currentPage'] : $cms_context->getPageID();
*/
$winId = sensitiveIO::request('winId', '', 'propertiesWindow');
$currentPage = sensitiveIO::request('currentPage', 'sensitiveIO::isPositiveInteger', $cms_context->getPageID());

//load page
$cms_page = CMS_tree::getPageByID($currentPage);
if ($cms_page->hasError()) {
	CMS_grandFather::raiseError('Selected page ('.$currentPage.') has error ...');
	$view->show();
}

//set editable status
if ($cms_user->hasPageClearance($cms_page->getID(), CLEARANCE_PAGE_EDIT)) {
	if ($cms_page->getLock() && $cms_page->getLock() != $cms_user->getUserId()) {
		$editable = false;
	} else {
		$editable = true;
		$cms_page->lock($cms_user);
	}
} else {
	$editable = false;
}

/***************************************\
*             PAGE PROPERTIES           *
\***************************************/

$pageId = $cms_page->getID();
$pageTitle = sensitiveIO::sanitizeJSString($cms_page->getTitle());
$pageLinkTitle = sensitiveIO::sanitizeJSString($cms_page->getLinkTitle());
$status = $cms_page->getStatus()->getHTML(false, $cms_user, MOD_STANDARD_CODENAME, $cms_page->getID());
$lineage = CMS_tree::getLineage($cms_user->getPageClearanceRoot($cms_page->getID()), $cms_page);

//Page templates replacement
$pageTemplate = $cms_page->getTemplate();
//hack if page has no valid template attached
if (!is_a($pageTemplate, "CMS_pageTemplate")) {
	$pageTemplate = new CMS_pageTemplate();
}
$pageTplId = CMS_pageTemplatesCatalog::getTemplateIDForCloneID($pageTemplate->getID());
$pageTplLabel = $pageTemplate->getLabel();

//print
$print = ($cms_page->getPrintStatus()) ? $cms_language->getMessage(MESSAGE_PAGE_FIELD_YES):$cms_language->getMessage(MESSAGE_PAGE_FIELD_NO);

//page relations 
$linksFrom = CMS_linxesCatalog::searchRelations(CMS_linxesCatalog::PAGE_LINK_FROM, $cms_page->getID());
$linksTo = CMS_linxesCatalog::searchRelations(CMS_linxesCatalog::PAGE_LINK_TO, $cms_page->getID());

//page redirection
$redirectlink = $cms_page->getRedirectLink();
$redirectValue = '';
$module = MOD_STANDARD_CODENAME;
$visualmode = RESOURCE_DATA_LOCATION_EDITED;
if ($redirectlink->hasValidHREF()) {
	$redirect = $cms_language->getMessage(MESSAGE_PAGE_FIELD_YES).' '.$cms_language->getMessage(MESSAGE_PAGE_FIELD_TO).' : ';
	if ($redirectlink->getLinkType() == RESOURCE_LINK_TYPE_INTERNAL) {
		$redirectPage = new CMS_page($redirectlink->getInternalLink());
		if (!$redirectPage->hasError()) {
			$label = $cms_language->getMessage(MESSAGE_PAGE_FIELD_PAGE).' "'.$redirectPage->getTitle().'" ('.$redirectPage->getID().')';
		}
	} else {
		$label = $redirectlink->getExternalLink();
	}
	$redirectlink->setTarget('_blank');
	$redirect .= $redirectlink->getHTML($label, MOD_STANDARD_CODENAME, RESOURCE_DATA_LOCATION_EDITED, 'class="admin"', false);
	$redirectValue = $redirectlink->getTextDefinition();
} else {
	$redirect = $cms_language->getMessage(MESSAGE_PAGE_FIELD_NO);
}
//page URL
if ($cms_page->getURL()) {
	$pageUrl = '<a href="'.$cms_page->getURL().'" target="_blank">'.$cms_page->getURL().'</a>'.($cms_page->getRefreshURL() ? ' (<em>'.$cms_language->getMessage(MESSAGE_PAGE_UPDATE_NEXT_VALIDATION).'</em>)' : '');
} else {
	$pageUrl = '<em>'.$cms_language->getMessage(MESSAGE_PAGE_UNPUBLISHED).'</em>';
}
//mandatory 
$mandatory='<span class="atm-text-alert" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_REQUIRED_FIELD).'">*</span> ';

$propertiesTable = sensitiveIO::sanitizeJSString(
	'<table id="atm-properties-table" class="atm-table">
		<tr class="atm-odd" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_TITLE_INFO).'">'.$mandatory.$cms_language->getMessage(MESSAGE_PAGE_FIELD_TITLE).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorStringReq" atm:field="title" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$pageTitle.'</td>
		</tr>
		<tr class="atm-even" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_LINK_LABEL_POINTING).'">'.$mandatory.$cms_language->getMessage(MESSAGE_PAGE_FIELD_LINKTITLE).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorStringReq" atm:field="linkTitle" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$pageLinkTitle.'</td>
		</tr>
		<tr class="atm-odd" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_IDENTIFIER_INFO).'">'.$cms_language->getMessage(MESSAGE_PAGE_INFO_ID).'</th>
			<td>'.$cms_page->getID().'</td>
		</tr>
		<tr class="atm-even" height="36">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_FORCEURLREFRESH).'">'.$cms_language->getMessage(MESSAGE_PAGE_INFO_URL).'</th>
			<td'.($cms_page->getURL() && $editable ? ' class="atm-editable" atm:config="editorURL" atm:field="updateURL"' : '').'>'.$pageUrl.'</td>
		</tr>
		<tr class="atm-odd" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_TEMPLATE_USED_INFO).'">'.$cms_language->getMessage(MESSAGE_PAGE_INFO_TEMPLATE).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorSelectTpl" atm:field="template" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$pageTplLabel.'</td>
		</tr>
		<tr class="atm-even" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_BELONG_SITE).'">'.$cms_language->getMessage(MESSAGE_PAGE_INFO_WEBSITE).'</th>
			<td>'.$cms_page->getWebsite()->getLabel().'</td>
		</tr>
		<tr class="atm-odd" height="60">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_RELATION_BETWEEN_PAGE).'">'.$cms_language->getMessage(MESSAGE_PAGE_INFO_LINKS_RELATIONS).'</th>
			<td>
				<ul>
					<li><a href="'.PATH_ADMIN_WR.'/search.php?search='.CMS_search::SEARCH_TYPE_LINKFROM.':'.$cms_page->getID().'">'.$cms_language->getMessage(MESSAGE_PAGE_RESULTS_RELATIONS,array(count($linksTo)),MOD_STANDARD_CODENAME).'</a></li>
					<li><a href="'.PATH_ADMIN_WR.'/search.php?search='.CMS_search::SEARCH_TYPE_LINKTO.':'.$cms_page->getID().'">'.$cms_language->getMessage(MESSAGE_PAGE_RESULTS_LINKS,array(count($linksFrom)),MOD_STANDARD_CODENAME).'</a></li>
				</ul>
			</td>
		</tr>
		<tr class="atm-even" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_PRINTABLE_VERSION).'">'.$cms_language->getMessage(MESSAGE_PAGE_INFO_PRINT).'</th>
			<td>'.$print.'</td>
		</tr>
		<tr class="atm-odd">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_AUTOMATIC_REDIRECTION).'">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_REDIRECT).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorRedirect" atm:field="redirection" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$redirect.'</td>
		</tr>
	</table>'
);

/***************************************\
*         PAGE DATES & ALERTS           *
\***************************************/

$dateFormat = $cms_language->getDateFormat();
$pub_start = $cms_page->getPublicationDateStart();
$pub_end = $cms_page->getPublicationDateEnd();
$reminder_date = $cms_page->getReminderOn();
$date_mask = $cms_language->getDateFormatMask();
$pubStart = $pub_start->getLocalizedDate($dateFormat);
$pubEnd = $pub_end->getLocalizedDate($dateFormat);
$reminderPeriodicity = $cms_page->getReminderPeriodicity();
$reminderDate = $reminder_date->getLocalizedDate($dateFormat);
$reminderMessage = htmlspecialchars($cms_page->getReminderOnMessage());

$datesTable = sensitiveIO::sanitizeJSString(
	'<table id="atm-date-table" class="atm-table">
		<tr class="atm-odd" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_DATE_START_PUBLICATION).' '.$cms_language->getMessage(MESSAGE_PAGE_FIELD_DATE_COMMENT, array($date_mask)).'">'.$mandatory.$cms_language->getMessage(MESSAGE_PAGE_FIELD_PUBDATE_BEG).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorDateReq" atm:field="pubdatestart" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$pubStart.'</td>
		</tr>
		<tr class="atm-even" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_DATE_END_PUBLICATION).' '.$cms_language->getMessage(MESSAGE_PAGE_FIELD_DATE_COMMENT, array($date_mask)).'">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_PUBDATE_END).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorDate" atm:field="pubdateend" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$pubEnd.'</td>
		</tr>
		<tr class="atm-odd" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_DELAY_ALERT_MESSAGE).' '.$cms_language->getMessage(MESSAGE_PAGE_FIELD_REMINDERDELAY_COMMENT).'">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_REMINDERDELAY).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorInt" atm:field="reminderdelay" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$reminderPeriodicity.'</td>
		</tr>
		<tr class="atm-even" height="32">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_DATE_RECEPTION_ALERT_MESSAGE).'">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_REMINDERDATE).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorDate" atm:field="reminderdate" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$reminderDate.'</td>
		</tr>
		<tr class="atm-odd">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_ALERT_MESSAGE_INFO).'">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_REMINDERMESSAGE).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorTextarea" atm:field="remindertext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$reminderMessage.'</td>
		</tr>
	</table>'
);

/***************************************\
*            SEARCH ENGINES             *
\***************************************/
$description = htmlspecialchars($cms_page->getDescription());
$keywords = htmlspecialchars($cms_page->getKeywords());
$category = htmlspecialchars($cms_page->getCategory());
$robots = htmlspecialchars($cms_page->getRobots());

$searchEngineTable = sensitiveIO::sanitizeJSString(
	'<table id="atm-search-table" class="atm-table">
		<tr class="atm-odd">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_DESC_INFO).'">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_DESCRIPTION).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorTextarea" atm:field="descriptiontext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$description.'</td>
		</tr>
		<tr class="atm-even">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_KEYWORD_INFO).'">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_KEYWORDS).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorTextarea" atm:field="keywordstext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$keywords.'</td>
		</tr>
		<tr class="atm-odd">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_CATEGORY_INFO).'">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_CATEGORY).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorTextarea" atm:field="categorytext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$category.'</td>
		</tr>
		<tr class="atm-even">
			<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_ROBOTS_INFO).' '.$cms_language->getMessage(MESSAGE_PAGE_FIELD_ROBOTS_COMMENT).'" height="32">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_ROBOTS).'</th>
			<td'.($editable ? ' class="atm-editable" atm:config="editorString" atm:field="robotstext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$robots.'</td>
		</tr>
	</table>'
);

/***************************************\
*              META-DATAS               *
\***************************************/
if (!NO_PAGES_EXTENDED_META_TAGS) {
	$author = htmlspecialchars($cms_page->getAuthor());
	$replyTo = htmlspecialchars($cms_page->getReplyto());
	$copyright = htmlspecialchars($cms_page->getCopyright());
}
$language = CMS_languagesCatalog::getByCode($cms_page->getLanguage());
$pageLanguage = htmlspecialchars($language->getLabel());
$languageValue = htmlspecialchars($language->getCode());

$pragma = ($cms_page->getPragma() != '') ? $cms_language->getMessage(MESSAGE_PAGE_FIELD_PRAGMA_COMMENTS) : $cms_language->getMessage(MESSAGE_PAGE_BROWSER_DEFAULT_VALUE);
$pragmaValue = ($cms_page->getPragma() != '') ? 1 : 0;

$languages = CMS_languagesCatalog::getAllLanguages();
$languagesDatas = array();
foreach ($languages as $aLanguage) {
	$languagesDatas[] = array($aLanguage->getCode(), $aLanguage->getLabel());
}
$languagesDatas = sensitiveIO::jsonEncode($languagesDatas);

$meta = 'TODO';

$metaDatasTable = 
'<table id="atm-meta-table" class="atm-table">';
if (!NO_PAGES_EXTENDED_META_TAGS) {
	$metaDatasTable .='
	<tr class="atm-odd">
		<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_AUTHOR_INFO).'" height="32">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_AUTHOR).'</th>
		<td'.($editable ? ' class="atm-editable" atm:config="editorString" atm:field="authortext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$author.'</td>
	</tr>
	<tr class="atm-even">
		<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_MAIL_INFO).'" height="32">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_REPLYTO).'</th>
		<td'.($editable ? ' class="atm-editable" atm:config="editorString" atm:field="replytotext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$replyTo.'</td>
	</tr>
	<tr class="atm-odd">
		<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_COPYRIGHT_INFO).'" height="32">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_COPYRIGHT).'</th>
		<td'.($editable ? ' class="atm-editable" atm:config="editorString" atm:field="copyrighttext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$copyright.'</td>
	</tr>';
}
$metaDatasTable .='
	<tr class="atm-even">
		<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_LANGUAGE_USED_INFO).'" height="32">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_LANGUAGE).'</th>
		<td'.($editable ? ' class="atm-editable" atm:config="editorSelectLanguage" atm:field="language" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$pageLanguage.'</td>
	</tr>
	<tr class="atm-odd">
		<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_BROWSER_CACHE_INFO).'" height="32">'.$cms_language->getMessage(MESSAGE_PAGE_FIELD_PRAGMA).'</th>
		<td'.($editable ? ' class="atm-editable" atm:config="editorPragma" atm:field="pragmatext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$pragma.'</td>
	</tr>
	<tr class="atm-odd">
		<th ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_META_DATA_INFO).'">'.$cms_language->getMessage(MESSAGE_PAGE_META_DATA_LABEL).'</th>
		<td'.(($editable && $cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_EDITVALIDATEALL)) ? ' class="atm-editable" atm:config="editorTextarea" atm:field="metatext" ext:qtip="'.$cms_language->getMessage(MESSAGE_PAGE_INFO_CLICK_TO_EDIT).'"' : '').'>'.$meta.'</td>
	</tr>
</table>';
$metaDatasTable = sensitiveIO::sanitizeJSString($metaDatasTable);

/***************************************\
*               SUB-PAGES               *
\***************************************/
$siblings = '';
if (CMS_tree::hasSiblings($cms_page)) {
	$siblings = ", {
					title:	'".$cms_language->getMessage(MESSAGE_PAGE_SUBPAGES_LABEL)."',
					xtype:	'atmPanel',
					id:		'subPagesPanel',
					autoLoad:		{
						url:		'tree.php',
						params:		{
							winId:		'subPagesPanel',
							root:		'$pageId',
							showRoot:	false,
							maxlevel:	1,
							hideMenu:	true,
							window:		false,
							heading:	'".$cms_language->getJSMessage(MESSAGE_PAGE_SUBPAGES_LIST_MESSAGE)." ".sensitiveIO::sanitizeJSString($cms_page->getTitle()).".".($cms_user->hasPageClearance($cms_page->getID(), CLEARANCE_PAGE_EDIT) ? ' '.$cms_language->getJSMessage(MESSAGE_PAGE_DRAGANDDROP_MESSAGE) : '')."',
							enableDD:	".($cms_user->hasPageClearance($cms_page->getID(), CLEARANCE_PAGE_EDIT) ? 'true' : 'false')."
						},
						nocache:	true,
						scope:		this
					}
	            }";
}

/***************************************\
*              PAGE LOGS                *
\***************************************/
$logs = '';
if ($cms_user->hasAdminClearance(CLEARANCE_ADMINISTRATION_VIEWLOG)) {
	$logs = ", {
					title:	'".$cms_language->getMessage(MESSAGE_PAGE_LOG_LABEL)."',
					xtype:	'atmPanel',
					layout:	'fit',
					id:		'logPanel',
					autoLoad:		{
						url:		'page-logs.php',
						params:		{
							winId:		'logPanel',
							currentPage:'$pageId',
							action:		'view'
						},
						nocache:	true,
						scope:		this
					}
	            }";
}

$jscontent = <<<END
	var propertiesWindow = Ext.getCmp('{$winId}');
	//set window title
	propertiesWindow.setTitle('{$cms_language->getJSMessage(MESSAGE_WINDOW_TITLE)} \'{$pageTitle}\'');
	//set window icon
	propertiesWindow.setIconClass('atm-pic-edit');
	//set help button on top of page
	propertiesWindow.tools['help'].show();
	//add a tooltip on button
	var propertiesTip = new Ext.ToolTip({
		target: 		propertiesWindow.tools['help'],
		title: 			'{$cms_language->getJsMessage(MESSAGE_TOOLBAR_HELP)}',
		html: 			'{$cms_language->getJsMessage(MESSAGE_PAGE_TOOLBAR_HELP_INFO)}',
		dismissDelay:	0
    });
	//generic update fields configuration
	var fieldUpdateConfig = {
		url:				'page-controler.php',
		params: 			{currentPage:'{$pageId}'}
	};
	//unlock page just before window close
	propertiesWindow.on('beforeclose', function() {
		//send server call
		Automne.server.call({
			url:				'page-controler.php',
			params: 			{
				currentPage:		'{$pageId}',
				action:				'unlock'
			}
		});
		return true;
	});
END;
if ($editable) {
	$jscontent .= <<<END
	//close editor on window close
	propertiesWindow.on('close', Automne.utils.deleteEditor);
	
	/***************************************\
	*             EDITORS                   *
	\***************************************/
	
	//set conf for required text editor
	Ext.StoreMgr.add('editorStringReq', {
		xtype:				'textfield',
		allowBlank: 		false,
		maxLength:			255,
		selectOnFocus:		true,
		updateConfig:		fieldUpdateConfig,
		autosize:			'width'
	});
	//set conf for required text editor
	Ext.StoreMgr.add('editorString', {
		xtype:				'textfield',
		allowBlank: 		true,
		maxLength:			255,
		selectOnFocus:		true,
		updateConfig:		fieldUpdateConfig,
		autosize:			'width'
	});
	//set conf for url update editor
	Ext.StoreMgr.add('editorURL', {
		xtype:				'checkbox',
		allowBlank: 		false,
		maxLength:			255,
		selectOnFocus:		true,
		value:				{$cms_page->getRefreshURL()},
		boxLabel:			'{$cms_language->getJSMessage(MESSAGE_PAGE_FIELD_FORCEURLREFRESH_COMMENT)}',
		renderer:			function (field, el) {
			return '<a href="{$cms_page->getURL()}" target="_blank">{$cms_page->getURL()}</a><input type="hidden" value="'+ field.getValue() +'" />' + (field.getValue() ? ' (<em>{$cms_language->getJSMessage(MESSAGE_PAGE_UPDATE_NEXT_VALIDATION)}</em>)' : '');
		},
		updateConfig:		fieldUpdateConfig
	});
	//set conf for pragma update editor
	Ext.StoreMgr.add('editorPragma', {
		xtype:				'checkbox',
		allowBlank: 		false,
		maxLength:			255,
		selectOnFocus:		true,
		value:				{$pragmaValue},
		boxLabel:			'{$cms_language->getJSMessage(MESSAGE_PAGE_FIELD_PRAGMA_COMMENTS)}',
		renderer:			function (field, el) {
			return '<input type="hidden" value="'+ field.getValue() +'" />' + (field.getValue() ? '{$cms_language->getJsMessage(MESSAGE_PAGE_FIELD_PRAGMA_COMMENTS)}' : '{$cms_language->getJsMessage(MESSAGE_PAGE_BROWSER_DEFAULT_VALUE)}');
		},
		updateConfig:		fieldUpdateConfig
	});
	//set conf for template switching
	Ext.StoreMgr.add('editorSelectTpl', {
		xtype:				'combo',
		forceSelection:		true,
		mode:				'remote',
		valueField:			'id',
		displayField:		'label',
		value:				'{$pageTplLabel}',
		triggerAction: 		'all',
		store:				new Automne.JsonStore({
			url: 			'page-templates-datas.php',
			baseParams:		{
				template: 		{$pageTplId},
				page:			$pageId
			},
			root: 			'results',
			fields: 		['id', 'label', 'image', 'groups', 'compatible', 'description'],
			prepareData: 	function(data){
		    	data.qtip = Ext.util.Format.htmlEncode(data.description);
				data.cls = data.compatible ? '' : 'atm-red';
				return data;
			}
		}),
		renderer:			function(field) {
			var value = (field.store.getAt(field.store.find(field.valueField, field.getValue()))) ? field.store.getAt(field.store.find(field.valueField, field.getValue())).get(field.displayField) : field.getValue();
			return '<input type="hidden" value="'+ field.getValue() +'" />' + value;
		},
		allowBlank: 		false,
		selectOnFocus:		true,
		editable:			false,
		tpl: 				'<tpl for="."><div ext:qtip="{qtip}" class="x-combo-list-item {cls}">{label}</div></tpl>',
    	updateConfig:		fieldUpdateConfig,
		autosize:			'height'
	});
	//set conf for language switching
	Ext.StoreMgr.add('editorSelectLanguage', {
		xtype:				'combo',
		forceSelection:		true,
		mode:				'local',
		valueField:			'id',
		displayField:		'name',
		value:				'{$languageValue}',
		triggerAction: 		'all',
		store:				new Ext.data.SimpleStore({
		    fields: 	['id', 'name'],
		    data : 		{$languagesDatas}
		}),
		renderer:			function(field) {
			var value = (field.store.getAt(field.store.find(field.valueField, field.getValue()))) ? field.store.getAt(field.store.find(field.valueField, field.getValue())).get(field.displayField) : field.getValue();
			return '<input type="hidden" value="'+ field.getValue() +'" />' + value;
		},
		allowBlank: 		false,
		selectOnFocus:		true,
		editable:			false,
		updateConfig:		fieldUpdateConfig,
		autosize:			'height'
	});
	//set conf for page redirection
	Ext.StoreMgr.add('editorRedirect', {
		xtype:				'linkfield',
		selectOnFocus:		true,
		value:				'{$redirectValue}',
		updateConfig:		fieldUpdateConfig,
		height:				70,
		allowBlur:			true,
		renderer:			function (field, el) {
			field.getComputedValue(el);
			return '';
		},
		linkConfig: {
			admin: 				true,				// Link has label ?
			label: 				false,				// Link has label ?
			internal: 			true,				// Link can target an Automne page ?
			external: 			true,				// Link can target an external resource ?
			file: 				false,				// Link can target a file ?
			destination:		false,				// Can select a destination for the link ?
			currentPage:		'{$pageId}',		// Current page to open tree
			module:				'{$module}', 
			visualmode:			'{$visualmode}'
		}
	});
	var dateRenderer = function (field) {
		var value = field.getValue();
		return value ? value.dateFormat('{$dateFormat}') : '';
	};
	//set conf for required dates
	Ext.StoreMgr.add('editorDateReq', {
		xtype:				'datefield',
		allowBlank: 		false,
		format:				'{$dateFormat}',
		renderer:			dateRenderer,
		updater:			dateRenderer,
		selectOnFocus:		true,
		updateConfig:		fieldUpdateConfig,
		width:				100
	});
	//set conf for non-required dates
	Ext.StoreMgr.add('editorDate', {
		xtype:				'datefield',
		allowBlank: 		true,
		format:				'{$dateFormat}',
		renderer:			dateRenderer,
		updater:			dateRenderer,
		selectOnFocus:		true,
		updateConfig:		fieldUpdateConfig,
		width:				100
	});
	//set conf for non-required integer
	Ext.StoreMgr.add('editorInt', {
		xtype:				'numberfield',
		allowBlank: 		true,
		selectOnFocus:		true,
		updateConfig:		fieldUpdateConfig
	});
	//set conf for non-required text area
	Ext.StoreMgr.add('editorTextarea', {
		xtype:				'textarea',
		allowBlank: 		true,
		selectOnFocus:		true,
		updateConfig:		fieldUpdateConfig
	});
END;
}
$jscontent .= <<<END
	//create center panel
	var center = new Ext.TabPanel({
        activeTab: 			0,
        id:					'pagePropPanel',
		region:				'center',
		plain:				true,
        enableTabScroll:	true,
		defaults:			{
			autoScroll: true,
			listeners:{
				'activate':{
					fn:function(panel) {
						//set all editors on panel by class name
						Ext.select('table .atm-editable', true, panel.id).each(function(el){
							el.on('click', function(el) {
								if(this.getAttributeNS('atm', 'config')) Automne.utils.editor(this);
							}, el);
						});
						//delete editor on scroll
						Ext.EventManager.on(panel.body, 'scroll', Automne.utils.deleteEditor, this);
					},
					single:true,
					scope:this
				}, 
				'deactivate':{
					fn:		Automne.utils.deleteEditor,
					scope:	this
				}
			}
		},
        items:[{
				title:	'{$cms_language->getJsMessage(MESSAGE_PAGE_PROPERTIES_LABEL)}',
				id:		'propertiesPanel',
				html:	'{$propertiesTable}'
			},{
				title:	'{$cms_language->getJsMessage(MESSAGE_PAGE_DATE_ALERT_LABEL)}',
				id:		'datesPanel',
				html: 	'{$datesTable}'
            },{
				title:	'{$cms_language->getJsMessage(MESSAGE_PAGE_SEARCH_ENGINE_LABEL)}',
				id:		'searchEnginePanel',
				html:	'{$searchEngineTable}'
            },{
				title:	'{$cms_language->getJsMessage(MESSAGE_PAGE_META_DATA)}',
				id:		'metaPanel',
				html:	'{$metaDatasTable}'
            }/*,{
				title:	'{$cms_language->getJsMessage(MESSAGE_PAGE_ALIAS_LABEL)}',
				id:		'aliasPanel',
				html:	'TODOV4'
            }*/
			{$siblings}
			{$logs}
        ]
    });
	// Panel for the north
	var top = new Ext.BoxComponent({
		region:			'north',
		el: 			'north',
		/*style:			'padding:5px;',*/
		autoHeight: 	true
	});
	propertiesWindow.add(top);
	propertiesWindow.add(center);
	//redo windows layout
	propertiesWindow.doLayout();
END;
$view->addJavascript($jscontent);

//create page lineage
$lineageTitle = '';
if (is_array($lineage) && sizeof($lineage)) {
	foreach ($lineage as $ancestor) {
		if ($ancestor->getID() != $cms_page->getID()) {
			$lineageTitle .= '&nbsp;/&nbsp;<a href="#" onclick="Automne.utils.getPageById('.$ancestor->getID().');Ext.getCmp(\''.$winId.'\').close();">'.htmlspecialchars($ancestor->getTitle()).'</a>';
		} else {
			$lineageTitle .= '&nbsp;/&nbsp;'.htmlspecialchars($ancestor->getTitle());
		}
	}
}

$content = '
<div id="north">
	<div id="pageTitle">
	'.$status.'
	<span class="title">'.$cms_page->getTitle().'</span>
	</div>
	<div id="breadcrumbs">'.$lineageTitle.'</div>
</div>';

//send content
$view->setContent($content);

$view->show();
?>