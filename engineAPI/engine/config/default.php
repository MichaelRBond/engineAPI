<?php

$accessControl   = array();
$moduleFunctions = array();
$engineDB        = NULL;
$DEBUG           = NULL;
 
//URLS
global $engineVars;

// Your domain
$engineVars['server']     = "my.domain.com";

// stick your protocol in front ... 'http' or 'https' or 'ftp' or whatever
$engineVars['WVULSERVER'] = "http://".$engineVars['server'];

// If your engine applications don't use your apache document root as its 
// document root. Setting this is important for recursion and redirection. 
$engineVars['WEBROOT']    = $engineVars['WVULSERVER'] ."";

// your 'main' CSS directory. 
$engineVars['CSSURL']     = $engineVars['WEBROOT'] ."/css";

// your 'main' javascript directory
$engineVars['JAVAURL']    = $engineVars['WEBROOT'] ."/javascript";

// Where you copied engineIncludes too. 
$engineVars['engineInc']  = $engineVars['WEBROOT'] ."/engineIncludes";

//Support Pages
$engineVars['loginPage']    = $engineVars['engineInc'] ."/login.php"; 
$engineVars['logoutPage']   = $engineVars['engineInc'] ."/logout.php"; 
$engineVars['downloadPage'] = $engineVars['engineInc'] ."/download.php";

//javascripts
$engineVars['jquery']          = $engineVars['engineInc'] ."/jquery.js";
$engineVars['jqueryDate']      = $engineVars['engineInc'] ."/jquery.date.js";
$engineVars['jqueryCookie']    = $engineVars['engineInc'] ."/jquery.eikooc.js";
$engineVars['sortableTables']  = $engineVars['engineInc'] ."/sorttable.js"; 
$engineVars['tablesDragnDrop'] = $engineVars['engineInc'] ."/tablednd.js";
$engineVars['selectBoxJS']     = $engineVars['engineInc'] ."/engineSelectBoxes.js";
$engineVars['convert2TextJS']  = $engineVars['engineInc'] ."/convert2TextArea.js";
$engineVars['engineListObjJS'] = $engineVars['engineInc'] ."/engineListObj.js";
$engineVars['engineWYSIWYGJS'] = $engineVars['engineInc'] ."/engineWYSIWYG.js";
$engineVars['engineWYSIWYGJS'] = $engineVars['engineInc'] ."/engineWYSIWYG.js";
$engineVars['tiny_mce_JS']     = $engineVars['engineInc'] ."/wysiwyg/tiny_mce.js";

//images
$engineVars['imgDeleteIcon']        = $engineVars['engineInc'] ."/images/minusIcon.gif";
$engineVars['imgListRetractedIcon'] = $engineVars['engineInc'] ."/images/arrowRight.gif";
$engineVars['imgListExpandedIcon']  = $engineVars['engineInc'] ."/images/arrowDown.gif";

//Directories
// The EngineAPI directories should be OUTSIDE of your document root and
// NOT available to the public. 

// baseRoot is where the base for the website is. Everything else is 
//expected to be based in this directory. WVU likes to consider each 
//website/domain to be a "user" on the system and drops everything in 
///home/domain
$engineVars['baseRoot']      = "/home/ereserves";

// EngineAPI's base directory. ALl of engine's directories will be contained here
$engineVars['rootPHPDir']    = $engineVars['baseRoot'] ."/phpincludes/engineAPI";

// Where the engine templates are stored
$engineVars['tempDir']       = $engineVars['rootPHPDir'] ."/template";

// The directory that corrisponds to $engineVars['WEBROOT'], defined above
$engineVars['documentRoot']  = $engineVars['baseRoot'] ."/public_html";

// File listings. XML files that contain metadata about files so what 
// they can be stored without a mysql database.
$engineVars['fileListings']  = $engineVars['rootPHPDir'] ."/filelistings";

// phpMailing Include
$engineVars['emailInclude']  = $engineVars['rootPHPDir'] ."/phpmailer/phpmailer-fe.php";

// Engine Modules directory
$engineVars['modules']       = $engineVars['rootPHPDir'] ."/engine/modules";

// Access Control Modules
$engineVars['accessModules'] = $engineVars['rootPHPDir'] ."/engine/accessControl";

// Login Modules
$engineVars['loginModules']  = $engineVars['rootPHPDir'] ."/engine/login";

// RSS Templates
$engineVars['rssDir']        = $engineVars['tempDir'] ."/rss";
$engineVars['magpieDir']     = $engineVars['baseRoot'] ."/phpincludes/magpie";

// External URLs
// External URLs is an array of urls that can be used in applications.
// WVU sets up proxy servers here. 
$engineVars['externalURLs']['proxy1'] = "http://proxy1.com/";
$engineVars['externalURLs']['proxy2'] = "http://proxy2.com";

//RSS Files
// Individual RSS files. The RSS system is due for a major overhaul, and this 
// section will change
$engineVars['rss2.0'] = $engineVars['rssDir'] ."/rss20.xml";

//Behavior
$engineVars['stripCarriageReturns'] = FALSE;

//Logging
$engineVars['log']   = TRUE;
$engineVars['logDB'] = "engineCMS";

//Access Control Test, Site wide default
// Turns access controls lists on and off
$engineVars['accessExistsTest'] = TRUE;

//Explode & Implode string delimiter
$engineVars['delim'] = "%|%engineCMSDelim%|%";

//Internal Email address Regex's
// regex that defines if an email is internal or external
$engineVars['internalEmails'] = array();
$engineVars['internalEmails']['wvu'] = '/.*wvu.edu$/';

//$onCampus represents the IP ranges that are considered to be "on site"
// FOR WVU Libraries it is important to know if someone's IP address 
// is "on" or "off" campus ... onCampus is an array that lets you define a range of
// IPs that should be included or excluded
$engineVars['onCampus'] = array();
$engineVars['onCampus'][] = "157.182.0-252.*";
$engineVars['onCampus'][] = "72.50.128-161.*";
$engineVars['onCampus'][] = "72.50.180-185.*";
// Temp
$engineVars['onCampus'][] = "192.168.171.1";

//MySQL Information
$engineVars['mysql']['server']   = "localhost";
$engineVars['mysql']['port']     = "3306";
//User with permissions to engineCMS database
$engineVars['mysql']['username'] = "dbUsername";
$engineVars['mysql']['password'] = "dbPassword";

//Active Directory (ldap?) Information
// As many active directories/ldaps can be defined as needed here. 
// WVU Libraries Staff
$engineVars['domains']['wvulibs']['ldapServer'] = "ldap://your.ad.PDC.com";
$engineVars['domains']['wvulibs']['ldapDomain'] = "your.ad.domain.com";
$engineVars['domains']['wvulibs']['dn']         = "DC=your,DC=ad,DC=PDC,DC=com";
$engineVars['domains']['wvulibs']['filter']     = "(|(sAMAccountName=%USERNAME%))";
$engineVars['domains']['wvulibs']['attributes'] = array("memberof","displayname");

// HTML stuff
// These are just default values. These can be over ridden using local variables, or in 
// the filetemplate engine call
$engineVars['oddColor']  = "#bfbfbf";
$engineVars['evenColor'] = "#f2f2f2";
$engineVars['oddClass']  = "evenClass";
$engineVars['evenClass'] = "oddClass";

// Email Stuff
$engineVars['emailSender']['recipient']               = "recipient";
$engineVars['emailSender']['sender']                  = "sender";
$engineVars['emailSender']['cc']                      = "cc";
$engineVars['emailSender']['bcc']                     = "bcc";
$engineVars['emailSender']['subject']                 = "subject";
$engineVars['emailSender']['redirectOnFail']          = "redirectOnFail";
$engineVars['emailSender']['redirect']                = "redirect";
$engineVars['emailSender']['required']                = "required";
$engineVars['emailSender']['return_link_url']         = "return_link_url";
$engineVars['emailSender']['return_link_title']       = "return_link_title";
$engineVars['emailSender']['missing_fields_redirect'] = "missing_fields_redirect";
$engineVars['emailSender']['replyEmailOnFail']        = "replyEmailOnFail";
$engineVars['emailSender']['replyEmailOnSuccess']     = "replyEmailOnSuccess";

//Template calls

// Default Template set, can be set locally in localVars['']
$engineVars['templateDefault'] = "default";

// Called before page content
$engineVars['templateHeader'] = $engineVars['tempDir'] ."/". $engineVars['templateDefault'] ."/templateHeader.php"; 

// Called after page content
$engineVars['templateFooter'] = $engineVars['tempDir'] ."/". $engineVars['templateDefault'] ."/templateFooter.php"; 
?>