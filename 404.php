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
// $Id: 404.php,v 1.1.1.1 2008/11/26 17:12:05 sebastien Exp $

/**
  * Automne 404 error handler
  * @author S�bastien Pauchet <sebastien.pauchet@ws-interactive.fr> &
  */

// *************************************************************************
// ** REDIRECTION HANDLER. KEEP ALL THIS PHP CODE IN 404 ERROR DOCUMENT ! **
// **     YOU CAN DEFINE YOUR OWN ERROR PAGE WITH THE FILE /404.html      **
// *************************************************************************

require_once($_SERVER["DOCUMENT_ROOT"]."/cms_rc_frontend.php");
//parse requested URL to try to find a matching page
$redirectTo = '';
if ($_SERVER['REQUEST_URI'] && $_SERVER['REQUEST_URI'] != $_SERVER['SCRIPT_NAME']) {
	//extract pathinfo to get requested file basename
	$pathinfo = pathinfo($_SERVER['REQUEST_URI']);
	$basename = (isset($pathinfo['extension'])) ? substr($pathinfo['basename'], 0, -(1+strlen($pathinfo['extension']))) : $pathinfo['basename'];
	//if basename founded
	if ($basename) {
		//search page id in basename (declare matching patterns by order of research)
		$patterns[] = "#^([0-9]+)-#U"; // for request like id-page_title.php
		$patterns[] = "#^print-([0-9]+)-#U"; // for request like print-id-page_title.php
		$patterns[] = "#_([0-9]+)_$#U"; // for request like _id_id_.php
		$patterns[] = "#^([0-9]+)$#U"; // for request like id
		$count = 0;
		while(!preg_match($patterns[$count] , $basename, $requestedPageId) && $count+1 < sizeof($patterns)) {
			$count++;
		}
		if (isset($requestedPageId[1]) && sensitiveIO::IsPositiveInteger($requestedPageId[1])) {
			//try to instanciate the requested page
			$page = new CMS_page($requestedPageId[1]);
			if (!$page->hasError()) {
				//get page file
				$pageURL = $page->getURL( (substr($basename,0,5) == 'print' ? true : false) , false, PATH_RELATIVETO_FILESYSTEM);
				if (file_exists($pageURL)) {
					$redirectTo = $page->getURL( (substr($basename,0,5) == 'print' ? true : false));
				} else {
					//try to get direct html file
					$pageURL = $page->getHTMLURL( (substr($basename,0,5) == 'print' ? true : false) , false, PATH_RELATIVETO_FILESYSTEM);
					if (file_exists($pageURL)) {
						$redirectTo = $page->getHTMLURL( (substr($basename,0,5) == 'print' ? true : false));
					}
				}
			}
		}
	}
}
//do redirection to page if founded
if ($redirectTo) {
	header('HTTP/1.x 301 Moved Permanently', true, 301);
	header('Location: '.$redirectTo.($_SERVER['REDIRECT_QUERY_STRING'] ? '?'.$_SERVER['REDIRECT_QUERY_STRING'] : ''));
	exit;
}
//then if no page founded, display 404 error page
header('HTTP/1.x 404 Not Found', true, 404);
//Check if requested file is an image
$imagesExtensions = array('jpg', 'jepg', 'gif', 'png', 'ico');
if (isset($pathinfo['extension']) && in_array(strtolower($pathinfo['extension']), $imagesExtensions)) {
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/img/404.png')) {
		header('Content-Type: image/png');
		readfile($_SERVER['DOCUMENT_ROOT'].'/img/404.png');
		exit;
	}
}
//send an email if needed
if (ERROR404_EMAIL_ALERT && sensitiveIO::isValidEmail(APPLICATION_MAINTAINER_EMAIL)) {
	$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	$body ="A 404 Error occured on your website.\n";
	$body .="--------------------------------------\n\n";
	$body .='The requested file : '.CMS_websitesCatalog::getMainURL().$_SERVER['REQUEST_URI'].' was not found.'."\n";
	$body .='From (Referer) : '.$referer."\n\n";
	$body .='Date : '.date('r')."\n";
	$body .='User : '.$_SERVER['REMOTE_ADDR'].' ('.$_SERVER['HTTP_ACCEPT_LANGUAGE'].')'."\n";
	$body .='Browser : '.$_SERVER['HTTP_USER_AGENT']."\n\n";
	$body .='Host : '.$_SERVER['HTTP_HOST'].' '.$_SERVER['SERVER_ADDR']."\n\n";
	$body .='This email is automaticaly sent from your website. You can stop this sending with the parameter ERROR404 EMAIL ALERT.';
	
	$mail= new CMS_email();
	$mail->setSubject("404 Error in ".APPLICATION_LABEL);
	$mail->setBody($body);
	$mail->setEmailFrom(APPLICATION_MAINTAINER_EMAIL."<".APPLICATION_MAINTAINER_EMAIL.">");
	$mail->setEmailTo(APPLICATION_MAINTAINER_EMAIL);
	$mail->sendEmail();
}
//check for alternative 404 file and display it if any
if (file_exists($_SERVER['DOCUMENT_ROOT'].'/404.html')) {
	readfile($_SERVER['DOCUMENT_ROOT'].'/404.html');
	exit;
}
//or display default Automne 404 page ...
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>404 Not Found ...</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo APPLICATION_DEFAULT_ENCODING; ?>" />
	<meta name="author" content="WS Interactive" />
	<style type="text/css">
	body {
		background-color: #FFFFFF;
		margin:			0;
		font:			normal 12px Verdana,Arial,Helvetica,sans-serif;
	}
	#message {
		width:			300px;
		margin:			0 auto 0 auto;
		display: 		block;
		padding:		20px;
		border:			1px solid red;
		text-align:		center;
		background:		url(/automne/admin/img/logo_small.gif) top right no-repeat;
	}
	hr {
		border:			0px solid white;
		border-bottom:	1px solid red;
	}
	</style>
</head>
<body>
<br /><br />
<div id="message">
404 Not Found...<br />
Sorry, the page you requested was not found.<br /><br />
<a href="/">Back to the Home Page</a><br /><br />
<hr />
404 Non trouv� ...<br />
Nous ne trouvons pas la page que vous demandez.<br /><br />
<a href="/">Retour � l'accueil</a><br /><br />
</div>
</body>
</html>