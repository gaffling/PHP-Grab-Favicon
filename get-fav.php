<?php
/*

Changelog:
  add icon size switch (--size)
  when embedding icon get proper mime type
  add logfile events
  unify to writeLog (there are a couple places where it needs reworking)
  added new API to .ini file
  debug logging creates a function stack, so function:function2:function3 shows the traversal.
  created API structure
    this should permit adding APIs in the future
  allow enable/disable individual apis
  added http connect timeout
  added dns timeout
  changed $debug to a boolean
  unified output to a single function for both console and html
  moved to a configuration array/structure
  simplified grap_favicon function, only the url is passed in, the other options are read from the configuration structure
  validate path
  validate capabilities
  individual api enable/disable
  updated help
  added more timeouts for PHP base functions
  added option to save without tld (amazon.ico instead of amazon.com.ico) (needs testing)
  improved efficiency and icon type detection
  show number of icons being searched for at start
  log file
    timestamp option
    append option

TO DO:
  add tenacious mode (try more than one API)
  add option to give a list of acceptable icon types
  blocklist of icons (for example if the apis return archive.org's icon)
    list of md5 hashes
    skipped if blocklist is empty or option is goven
    --blocklist=(list of hashes or file with hashes)
    --enableblocklist
    --disableblocklist
  optional query string/form support for options
    default will be disabled for security reasons
  save sub-folders
  more error checking
  should configuration structure be typed automatically (done at set)?
    
  
ISSUES:
  exif_imagetype's HTTP functions are primitive and will cause icon grabbing to fail when they shouldn't
    cvs.com
    fredmeyer.com
    
        
file_gets   12 / 19        11.98sec

    
  
PHP Grab Favicon
================

> This `PHP Favicon Grabber` use a given url, save a copy (if wished) and return the image path.

How it Works
------------

1. Check if the favicon already exists local or no save is wished, if so return path & filename
2. Else load URL and try to match the favicon location with regex
3. If we have a match the favicon link will be made absolute
4. If we have no favicon we try to get one in domain root
5. If there is still no favicon we randomly try google, faviconkit & favicongrabber API
6. If favicon should be saved try to load the favicon URL
7. If wished save the Favicon for the next time and return the path & filename

How to Use
----------

```PHP
$url = 'example.com';

echo '<img src="'.grap_favicon($grap_favicon).'">';
```

Todo
----
Optional split the download dir into several sub-dirs (MD5 segment of filename e.g. /af/cd/example.com.png) if there are a lot of favicons.

Infos about Favicon
-------------------
https://github.com/audreyr/favicon-cheat-sheet

###### Copyright 2019-2023 Igor Gaffling

*/
 
$time_start = microtime(true);

/*
**  If you wish get-fav.php to be able to process commands issued via a query string or
**  form submission on a webserver, change the setting below to true.
*/
define('ENABLE_WEB_INPUT', false);

/*
**  Project
*/
define('PROJECT_NAME', 'PHP Grab Favicon');
define('PROGRAM_NAME', 'get-fav');
define('PROGRAM_VERSION', '202305281757');
define('PROGRAM_COPYRIGHT', 'Copyright 2019-2023 Igor Gaffling');

/*  Defaults */
define('DEFAULT_ENABLE_APIS', true);
define('DEFAULT_USE_CURL', true);
define('DEFAULT_STORE', true);
define('DEFAULT_TRY_HOMEPAGE', true);
define('DEFAULT_OVERWRITE', false);
define('DEFAULT_ENABLE_BLOCKLIST', true);
define('DEFAULT_REMOVE_TLD', false);
define('DEFAULT_USE_LOAD_BUFFERING', true);
define('DEFAULT_LOG_PATHNAME', "get-fav.log");
define('DEFAULT_LOG_FILE_ENABLED', false);
define('DEFAULT_LOG_CONSOLE_ENABLED', true);
define('DEFAULT_LOG_APPEND', true);
define('DEFAULT_LOG_SEPARATOR', str_repeat("*", 80));
define('DEFAULT_LOG_TIMESTAMP_CONSOLE', false);
define('DEFAULT_LOG_TIMESTAMP_FILE', true);
define('DEFAULT_LOG_TIMESTAMP_FORMAT', "Y-m-d H:i:s");
define('DEFAULT_LOG_LEVEL_FILE', 255);
define('DEFAULT_LOG_LEVEL_CONSOLE', 31);
define('DEFAULT_SIZE', 16);
define('DEFAULT_MAXIMUM_REDIRECTS', 5);
define('DEFAULT_LOCAL_PATH', "./");
define('DEFAULT_HTTP_TIMEOUT', 60);
define('DEFAULT_HTTP_CONNECT_TIMEOUT', 30);
define('DEFAULT_DNS_TIMEOUT', 120);
define('DEFAULT_API_DATABASE', "get-fav-api.ini");
define('DEFAULT_USER_AGENT', "FaviconBot/1.0/");
define('DEFAULT_VALID_EXTENSIONS', "gif,webp,png,ico,bmp,svg,jpg");

/*  Ranges */
define('RANGE_HTTP_TIMEOUT_MINIMUM', 0);
define('RANGE_HTTP_TIMEOUT_MAXIMUM', 600);
define('RANGE_HTTP_CONNECT_TIMEOUT_MINIMUM', 0);
define('RANGE_HTTP_CONNECT_TIMEOUT_MAXIMUM', 600);
define('RANGE_DNS_TIMEOUT_MINIMUM', 0);
define('RANGE_DNS_TIMEOUT_MAXIMUM', 600);
define('RANGE_HTTP_REDIRECTS_MINIMUM', 0);
define('RANGE_HTTP_REDIRECTS_MAXIMUM', 50);
define('RANGE_ICON_SIZE_MINIMUM', 16);
define('RANGE_ICON_SIZE_MAXIMUM', 512);

/* Logging Levels */
define('TYPE_ALL', 1);
define('TYPE_NOTICE', 2);
define('TYPE_WARNING', 4);
define('TYPE_VERBOSE', 8);
define('TYPE_ERROR', 16);
define('TYPE_DEBUGGING', 32);
define('TYPE_TRACE', 64);

/*  Buffers */
define('BUFFER_SIZE', 128);

/*  HTML */
define('HTML_STYLE_ALL', "<MESSAGE>");
define('HTML_STYLE_NOTICE', "<b style=\"color:yellow;\"><MESSAGE></b>");
define('HTML_STYLE_WARNING', "<b style=\"color:red;\"><MESSAGE></b>");
define('HTML_STYLE_VERBOSE', "<MESSAGE>");
define('HTML_STYLE_ERROR', "<b style=\"color:red;\"><MESSAGE></b>");
define('HTML_STYLE_DEBUGGING', "<b style=\"color:red;\">#<MESSAGE>#</b>");
define('HTML_STYLE_TRACE', "<b style=\"color:red;\">#<MESSAGE>#</b>");
define('HTML_STYLE_ICON', "<img title=\"<TITLE>\" style=\"width:32px;padding-right:32px;\" src=\"<FILE>\">");
define('HTML_STYLE_TT', "<pre><MESSAGE></pre>");

/*  Special Values */
define('GOOGLE_DEFAULT_ICON_MD5', '3ca64f83fdcf25135d87e08af65e68c9');
define('URL_PATH_FAVICON', "favicon.ico");

/*  Config Types */
define('CONFIG_TYPE_SCALAR', 0);
define('CONFIG_TYPE_STRING', 1);
define('CONFIG_TYPE_PATH', 2);
define('CONFIG_TYPE_USERAGENT', 3);
define('CONFIG_TYPE_BOOLEAN', 4);
define('CONFIG_TYPE_SWITCH', 5);
define('CONFIG_TYPE_SWITCH_PAIR', 6);
define('CONFIG_TYPE_NUMERIC', 7);
define('CONFIG_TYPE_NUMERIC_SIGNED', 8);

/*
**  Initialize Arrays and Flags
*/
$blockList = array();
$URLList = array();
$apiList = array();
$internalData = array();
$configuration = array();
$capabilities = array();
$lastLoad = array();
$statistics = array();
$functiontrace = array();

$numberOfIconsToFetch = 0;
$numberOfIconsFetched = 0;
$flag_log_initialized = 0;
$log_handle = null;

setItem("project_name",PROJECT_NAME);
setItem("program_name",PROGRAM_NAME);
setItem("program_version",PROGRAM_VERSION);
setItem("banner",getItem("project_name") . " (" .getItem("program_name") . ") v" . getItem("program_version"));
    
if (file_exists(DEFAULT_API_DATABASE)) {
  $apiList = parse_ini_file(DEFAULT_API_DATABASE,true,INI_SCANNER_TYPED);
  setConfiguration("global","api_list",DEFAULT_API_DATABASE);
}

/*
**  Load APIs
**  addAPI($name,$url,$json,$enabled,$json_structure(),$display)
*/

if (empty($apiList)) {
  addAPI("faviconkit","https://api.faviconkit.com/<DOMAIN>/<SIZE>",false,DEFAULT_ENABLE_APIS);
  addAPI("favicongrabber","http://favicongrabber.com/api/grab/<DOMAIN>",true,DEFAULT_ENABLE_APIS,array("icons","0","src"));
  addAPI("google","http://www.google.com/s2/favicons?domain=<DOMAIN>",false,DEFAULT_ENABLE_APIS);
  setConfiguration("global","api_list","internal");
}

$display_name_API_list = getAPIList(true);
$display_API_list = getAPIList();


/*
**  Determine Capabilities of PHP installation
**  addCapability($scope,$capability,$value)
*/

addCapability("php","console",(php_sapi_name() == "cli"));
addCapability("php","curl",function_exists('curl_version'));
addCapability("php","exif",function_exists('exif_imagetype'));
addCapability("php","get",function_exists('file_get_contents'));
addCapability("php","fileinfo",function_exists('finfo_open'));
addCapability("php","mimetype",function_exists('mime_content_type'));

/*  
** Set Configuration Defaults
** setConfiguration($scope,$option,$value,$default,$type)
*/

setConfiguration("global","debug",false);
setConfiguration("global","api",DEFAULT_ENABLE_APIS);
setConfiguration("global","blocklist",DEFAULT_ENABLE_BLOCKLIST);
setConfiguration("global","icon_size",DEFAULT_SIZE);
setConfiguration("global","valid_types",DEFAULT_VALID_EXTENSIONS);
setConfiguration("console","enabled",DEFAULT_LOG_CONSOLE_ENABLED);
setConfiguration("console","level",DEFAULT_LOG_LEVEL_CONSOLE);
setConfiguration("console","timestamp",DEFAULT_LOG_TIMESTAMP_CONSOLE);
setConfiguration("console","timestampformat",DEFAULT_LOG_TIMESTAMP_FORMAT);
setConfiguration("curl","enabled",getCapability("php","curl"));
setConfiguration("curl","verbose",false);
setConfiguration("curl","showprogress",false);
setConfiguration("files","local_path",DEFAULT_LOCAL_PATH);
setConfiguration("files","overwrite",DEFAULT_OVERWRITE);
setConfiguration("files","store",DEFAULT_STORE);
setConfiguration("files","remove_tld",DEFAULT_REMOVE_TLD);
setConfiguration("http","default_useragent",DEFAULT_USER_AGENT);
setConfiguration("http","dns_timeout",DEFAULT_DNS_TIMEOUT);
setConfiguration("http","http_timeout",DEFAULT_HTTP_TIMEOUT);
setConfiguration("http","http_timeout_connect",DEFAULT_HTTP_CONNECT_TIMEOUT);
setConfiguration("http","try_homepage",DEFAULT_TRY_HOMEPAGE);
setConfiguration("http","maximum_redirects",DEFAULT_MAXIMUM_REDIRECTS);
setConfiguration("http","use_buffering",DEFAULT_USE_LOAD_BUFFERING);
setConfiguration("logging","append",DEFAULT_LOG_APPEND);
setConfiguration("logging","enabled",DEFAULT_LOG_FILE_ENABLED);
setConfiguration("logging","level",DEFAULT_LOG_LEVEL_FILE);
setConfiguration("logging","pathname",DEFAULT_LOG_PATHNAME);
setConfiguration("logging","separator",DEFAULT_LOG_SEPARATOR);
setConfiguration("logging","timestamp",DEFAULT_LOG_TIMESTAMP_FILE);
setConfiguration("logging","timestampformat",DEFAULT_LOG_TIMESTAMP_FORMAT);
setConfiguration("mode","console",getCapability("php","console"));

/* Modify Configuration Depending on Other Options */
if (isset($_SERVER['SERVER_NAME'])) { setConfiguration("http","default_useragent",DEFAULT_USER_AGENT . " (+http://". $_SERVER['SERVER_NAME'] ."/)"); }
if (!DEFAULT_USE_CURL) { setConfiguration("curl","enabled",false); }

if (getConfiguration("mode","console")) { $script_name = basename(__FILE__); } else { $script_name = basename($_SERVER['PHP_SELF']); }

/* Command Line Options */
$shortopts  = "";
$shortopts  = "b::";
$shortopts  = "l::";
$shortopts  = "p::";
$shortopts  = "c::";
$shortopts .= "h?";
$shortopts .= "v";

$longopts  = array(
  "logfile::",
  "list::",
  "blocklist::",
  "path::",
  "config::",
  "configfile::",
  "user-agent::",
  "curl-timeout::",
  "http-timeout::",
  "connect-timeout::",
  "dns-timeout::",
  "enableapis::",
  "disableapis::",
  "loglevel::",
  "level::",
  "size::",
  "tryhomepage",
  "onlyuseapis",
  "disableallapis",
  "enableblocklist",
  "disableblocklist",
  "log",
  "nolog",
  "append",
  "noappend",
  "timestamp",
  "notimestamp",
  "showtimestamp",
  "hidetimestamp",
  "store",
  "nostore",
  "save",
  "nosave",
  "removetld",
  "noremovetld",
  "overwrite",
  "nooverwrite",
  "bufferhttp",
  "nobufferhttp",
  "skip",
  "nocurl",
  "curl-verbose",
  "consolemode",
  "noconsolemode",
  "debug",
  "help",
  "version",
  "ver",
);

$options = getopt($shortopts, $longopts);

if ((isset($options['v'])) || (isset($options['ver'])) || (isset($options['version']))) {
  echo PROJECT_NAME . " (" . PROGRAM_NAME . ") v" . PROGRAM_VERSION ."\n";
  echo PROGRAM_COPYRIGHT . "\n";
  exit;
}

if ((isset($options['help'])) || (isset($options['h'])) || (isset($options['?']))) {
  echo "Usage: $script_name (Switches)\n";
  echo "\n";
  echo "Available APIs: $display_API_list (" . getConfiguration("global","api_list","internal") . ")\n";
  echo "Lists can be separated with space, comma or semi-colon.\n";
  echo "\n";
  echo "--configfile=FILE           Pathname to read for configuration.\n";
  echo "--list=FILE/LIST            Pathname or a delimited list of URLs to check.\n";
  echo "--blocklist=FILE/LIST       Pathname or a delimited list of MD5 hashes to block.\n";
  echo "--logfile=FILE              Pathname for log file (default is " . DEFAULT_LOG_PATHNAME . ")\n";
  echo "--path=PATH                 Location to store icons (default is " . DEFAULT_LOCAL_PATH . ")\n";
  echo "--size=NUMBER               Try to get icon size (default is " . DEFAULT_SIZE . ")\n";
  echo "\n";
  echo "--tryhomepage               Try homepage first, then APIs. (default is " . showBoolean(DEFAULT_TRY_HOMEPAGE) . ")\n";
  echo "--onlyuseapis               Only use APIs.\n";
  echo "--disableapis               Don't use APIs.\n";
  echo "--enableblocklist           Enable blocklist. (default is ". showBoolean(DEFAULT_ENABLE_BLOCKLIST) . ")\n";
  echo "--disableblocklist          Disable blocklist.\n";
  echo "--store                     Store favicons locally. (default is ". showBoolean(DEFAULT_STORE) . ")\n";
  echo "--nostore                   Do not store favicons locally.\n";
  echo "--overwrite                 Overwrite local favicons. (default is ". showBoolean(DEFAULT_OVERWRITE) . ")\n";
  echo "--skip                      Skip local favicons.\n";
  echo "--removetld                 Remove top level domain from filename. (default is " . showBoolean(DEFAULT_REMOVE_TLD) . ")\n";
  echo "--noremovetld               Don't remove top level domain from filename.\n";
  echo "--consolemode               Force console output.\n";
  echo "--noconsolemode             Force HTML output.\n";
  echo "--debug                     Enable debug mode.\n";
  echo "--help                      This listing and exit.\n";
  echo "--version                   Show version and exit.\n";
  echo "\n";
  echo "Advanced:\n";
  echo "--user-agent=AGENT_STRING   Customize the user agent.\n";
  echo "--nocurl                    Disable cURL.\n";
  echo "--bufferhttp                Buffer HTTP page loading. (default is " . showBoolean(DEFAULT_USE_LOAD_BUFFERING) . ")\n";
  echo "--nobufferhttp              Disable HTTP page load buffering.\n";
  echo "--curl-verbose              Enable cURL verbose.\n";
  echo "--curl-progress             Enable cURL progress bar.\n";
  echo "--enableapis=FILE/LIST      Filename or a delimited list of APIs to enable.\n";
  echo "--disableapis=FILE/LIST     Filename or a delimited list of APIs to disable.\n";
  echo "--http-timeout=SECONDS      Set HTTP timeout. (default is " . DEFAULT_HTTP_TIMEOUT . ").\n";
  echo "--connect-timeout=SECONDS   Set HTTP connect timeout. (default is " . DEFAULT_HTTP_CONNECT_TIMEOUT . ").\n";
  echo "--dns-timeout=SECONDS       Set DNS lookup timeout. (default is " . DEFAULT_DNS_TIMEOUT . ").\n";
  echo "\n";
  echo "Logging:\n";
  echo "--log                       Enable debug logging. (default is " . showBoolean(DEFAULT_LOG_FILE_ENABLED) . ")\n";
  echo "--nolog                     Disable debug logging.\n";
  echo "--append                    Append debug log. (default is " . showBoolean(DEFAULT_LOG_APPEND) . ")\n";
  echo "--noappend                  Always overwrite debug log.\n";
  echo "--timestamp                 Enable debug log timestamps. (default is " . showBoolean(DEFAULT_LOG_TIMESTAMP_FILE) . ")\n";
  echo "--notimestamp               Do not show timestamps in debug log.\n";
  echo "--loglevel=NUMBER           Set debug logging level. (default is " . DEFAULT_LOG_LEVEL_FILE . ")\n";
  echo "\n";
  echo "Console:\n";
  echo "--level=NUMBER              Set debug logging level. (default is " . DEFAULT_LOG_LEVEL_CONSOLE . ")\n";
  echo "--showtimestamp             Enable debug log timestamps. (default is " . showBoolean(DEFAULT_LOG_TIMESTAMP_CONSOLE) . ")\n";
  echo "--hidetimestamp             Do not show timestamps in debug log.\n";
  exit;
}

/* 
**  Command Line Options
**
**  Aliased Options
*/

if (isset($options['curl-timeout'])) { $options['http-timeout'] = $options['curl-timeout']; }
if (isset($options['p'])) { $options['path'] = $options['p']; } 
if (isset($options['l'])) { $options['list'] = $options['l']; }
if (isset($options['b'])) { $options['blocklist'] = $options['b']; }
if (isset($options['config'])) { $options['configfile'] = $options['config']; }
if (isset($options['c'])) { $options['configfile'] = $options['c']; }
if (isset($options['save'])) { $options['store'] = $options['save']; } 
if (isset($options['nosave'])) { $options['nostore'] = $options['nosave']; } 
if (isset($options['skip'])) { $options['nooverwrite'] = $options['skip']; } 


/*
**  Load Configuration File
*/

if (isset($options['configfile'])) {
  if (!is_null($options['configfile'])) {
    if (file_exists($options['configfile'])) {
      $configuration_from_file = parse_ini_file($options['configfile'],true,INI_SCANNER_RAW);
      if (isset($configuration_from_file)) {
        $configuration = array_replace_recursive($configuration, $configuration_from_file);
        unset($configuration_from_file);
        validateConfiguration();
      }
    }
  }
}


/*
**   Process Command Line Switches
*/
 
setConfiguration("console","level",(isset($options['level']))?$options['level']:null);
setConfiguration("console","timestamp",(isset($options['showtimestamp']))?$options['showtimestamp']:null,(isset($options['hidetimestamp']))?$options['hidetimestamp']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("curl","verbose",(isset($options['curl-verbose']))?$options['curl-verbose']:null,null,CONFIG_TYPE_SWITCH);
setConfiguration("curl","showprogress",(isset($options['curl-showprogress']))?$options['curl-showprogress']:null,null,CONFIG_TYPE_SWITCH);
setConfiguration("files","local_path",(isset($options['path']))?$options['path']:null,null,CONFIG_TYPE_PATH);
setConfiguration("files","store",(isset($options['store']))?$options['store']:null,(isset($options['nostore']))?$options['nostore']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("files","overwrite",(isset($options['overwrite']))?$options['overwrite']:null,(isset($options['nooverwrite']))?$options['nooverwrite']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("files","remove_tld",(isset($options['removetld']))?$options['removetld']:null,(isset($options['noremovetld']))?$options['noremovetld']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("global","blocklist",(isset($options['enableblocklist']))?$options['enableblocklist']:null,(isset($options['disableblocklist']))?$options['disableblocklist']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("global","debug",(isset($options['debug']))?$options['debug']:null,null,CONFIG_TYPE_SWITCH);
setConfiguration("global","icon_size",(isset($options['size']))?$options['size']:null);
setConfiguration("http","use_buffering",(isset($options['bufferhttp']))?$options['bufferhttp']:null,(isset($options['nobufferhttp']))?$options['nobufferhttp']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("http","try_homepage",(isset($options['tryhomepage']))?$options['tryhomepage']:null,(isset($options['onlyuseapis']))?$options['onlyuseapis']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("http","useragent",(isset($options['user-agent']))?$options['user-agent']:null,null,CONFIG_TYPE_USERAGENT);
setConfiguration("http","http_timeout",(isset($options['http-timeout']))?$options['http-timeout']:null);
setConfiguration("http","http_timeout_connect",(isset($options['connect-timeout']))?$options['connect-timeout']:null);
setConfiguration("http","dns_timeout",(isset($options['dns-timeout']))?$options['dns-timeout']:null);
setConfiguration("logging","append",(isset($options['append']))?$options['append']:null,(isset($options['noappend']))?$options['noappend']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("logging","enabled",(isset($options['log']))?$options['log']:null,(isset($options['nolog']))?$options['nolog']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("logging","level",(isset($options['loglevel']))?$options['loglevel']:null);
setConfiguration("logging","pathname",(isset($options['logfile']))?$options['logfile']:null,null);
setConfiguration("logging","timestamp",(isset($options['timestamp']))?$options['timestamp']:null,(isset($options['notimestamp']))?$options['notimestamp']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("mode","console",(isset($options['consolemode']))?$options['consolemode']:null,(isset($options['noconsolemode']))?$options['noconsolemode']:null,CONFIG_TYPE_SWITCH_PAIR);

if (isset($options['nocurl'])) { setConfiguration("curl","enabled",false); }
if (isset($options['disableallapis'])) { setConfiguration("global","api",false); }

validateConfiguration();

writeLog(getItem("banner"),TYPE_ALL);
# TO DO:
#   show log options
writeLog("Log Enabled",TYPE_DEBUGGING);
writeLog("Log Enabled",TYPE_TRACE);

/*  
**  Process Lists
*/

$URLList = loadList((isset($options['list']))?$options['list']:null);
$blockList = loadList((isset($options['blocklist']))?$options['blocklist']:null);
$enabledAPIList = loadList((isset($options['enableapis']))?$options['enableapis']:null);
$disabledAPIList = loadList((isset($options['disableapis']))?$options['disableapis']:null);

/*
**  Add To Blocklist
*/
writeLog("Building Blocklist",TYPE_DEBUGGING);
addBlocklist("3ca64f83fdcf25135d87e08af65e68c9");     //  Google Default Icon
addBlocklist("d0fefd1fde1699e90e96a5038457b061");     //  Internet Archive Default Icon

/*
**  Enable/Disable any APIs As Needed
*/

writeLog("Processing API Lists",TYPE_DEBUGGING);
if (getConfiguration("global","api")) {
  if (!empty($enabledAPIList)) {
    foreach ($enabledAPIList as $enableAPIname) {
      foreach ($apiList as &$APIrecord) {
        if (strcasecmp($APIrecord['name'], $enableAPIname) == 0) {
          if (!$APIrecord['enabled']) {
            $APIrecord['enabled'] = true;
          }
          break;
        }
      }
    }
  }
  if (!empty($disabledAPIList)) {
    foreach ($disabledAPIList as $disableAPIname) {
      foreach ($apiList as &$APIrecord) {
        if (strcasecmp($APIrecord['name'], $disableAPIname) == 0) {
          if ($APIrecord['enabled']) {
            $APIrecord['enabled'] = false;
          }
          break;
        }
      }    
    }
  }
} else {
  writeLog("Disabling All APIs",TYPE_DEBUGGING);
  foreach ($apiList as &$APIrecord) {
    if ($APIrecord['enabled']) {
      $APIrecord['enabled'] = false;
    }
  }
}

# Refresh Display List
$display_API_list = getAPIList();
if (is_null($display_API_list)) { $display_API_list = "none"; }

/* If test URLs is empty, setup testing list */
if (empty($URLList)) {
  writeLog("Using Test URLs",TYPE_DEBUGGING);
  $URLList = array(
    'http://aws.amazon.com',
    'http://www.apple.com',
    'http://www.dribbble.com',
    'http://www.github.com',
    'http://www.intercom.com',
    'http://www.indiehackers.com',
    'http://www.medium.com',
    'http://www.mailchimp.com',
    'http://www.netflix.com',
    'http://www.producthunt.com',
    'http://www.reddit.com',
    'http://www.slack.com',
    'http://www.soundcloud.com',
    'http://www.stackoverflow.com',
    'http://www.techcrunch.com',
    'http://www.trello.com',
    'http://www.vimeo.com',
    'https://www.whatsapp.com/',
    'https://www.gaffling.com/',
  );
}

/*  Set PHP User Agent/Timeouts if Required */
initializePHPAgent();
addStatistic("process_counter", 0);

if (!empty($URLList)) {
  $numberOfIconsToFetch = count($URLList);
  addStatistic("fetch", $numberOfIconsToFetch);  
  addStatistic("fetched", 0);
}

writeLog("Looking for $numberOfIconsToFetch Icons",TYPE_VERBOSE);

/*  Process List */
foreach ($URLList as $url) {
  $favicons[] = grap_favicon($url);
}

/*  Show Results */
foreach ($favicons as $favicon) {
  if (!empty($favicon)) { 
    if (file_exists($favicon)) {
      $numberOfIconsFetched++;
      incrementStatistic("fetched");
      $htmlMode = array(
        "html_template" => HTML_STYLE_ICON,
        "html_parameters" => array(
          "title" => $favicon,
          "file" => $favicon)
      );
      writeLog("Icon: $favicon",TYPE_VERBOSE,$htmlMode);
    }
  }
}

/*  Show Runtime and Statistics */
writeLog("Found " . getStatistic("fetch") . " Icons out of " . getStatistic("fetched") . " Requested",TYPE_NOTICE);
writeLog("Runtime: ".round(microtime(true)-$time_start,2)." second(s)",TYPE_NOTICE);
writeLog("Processing Complete",TYPE_NOTICE);

/*****************************************************
                FUNCTIONS
*****************************************************/
function grap_favicon($url) {
  incrementStatistic("process_counter");
  debugSection("grap_favicon(" . getStatistic("process_counter") . ")");
  
  // URL to lower case
	$url          = strtolower($url);
  
  //  Init Vars
  $consoleMode  = getConfiguration("mode","console");
  $save         = getConfiguration("files","store");
  $directory    = getConfiguration("files","local_path");
  $trySelf      = getConfiguration("http","try_homepage");
  $overwrite    = getConfiguration("files","overwrite");
  $removeTLD    = getConfiguration("files","remove_tld");
  $iconSize     = getConfiguration("global","icon_size");
  
  $api_name = null;
  $api_url = null;
  $api_json = false;
  $api_enabled = false;
  
  $filePath = null;
  setGlobal('redirect_count',0);
  setGlobal('redirect_url',null);

  if (!$consoleMode) {
    // avoid script runtime timeout
    $max_execution_time = ini_get("max_execution_time");
    set_time_limit(0); // 0 = no timelimit
  }

	// Get the Domain from the URL
  $parsed_url = parse_url($url);
  $core_domain = null;
  $protocol = "http";
  $domain = $url;
  $url_port = null;
  $url_user = null;
  $url_password = null;
  $url_path = null;
  
  if (!empty($parsed_url)) {
    $domain = $url;
    if (isset($parsed_url['scheme'])) { $protocol = $parsed_url['scheme']; };
    if (isset($parsed_url['host'])) { $domain = $parsed_url['host']; }
    if (isset($parsed_url['port'])) { $url_port = $parsed_url['port']; } 
    if (isset($parsed_url['user'])) { $url_user = $parsed_url['user']; } 
    if (isset($parsed_url['pass'])) { $url_password = $parsed_url['pass']; } 
    if (isset($parsed_url['path'])) { $url_path = $parsed_url['path']; } 
    
    // Check Domain
    $domainParts = explode('.', $domain);
    if(count($domainParts) >= 2) {
      $core_domain = $domainParts[count($domainParts)-2];
    }
    
    if(count($domainParts) == 3 and $domainParts[0]!='www') {
      // With Subdomain (if not www)
      $domain = $domainParts[0].'.'.
                $domainParts[count($domainParts)-2].'.'.$domainParts[count($domainParts)-1];
    } else if (count($domainParts) >= 2) {
      // Without Subdomain
      $domain = $domainParts[count($domainParts)-2].'.'.$domainParts[count($domainParts)-1];
    } else {
      // Without http(s)
      $domain = $url;
    }
  }
  
  writeLog("Processing $url, Domain: $domain using $protocol",TYPE_DEBUGGING);

  // If $trySelf == TRUE ONLY USE APIs
  if (isset($trySelf) && $trySelf == true) {
    
    // Try Direct Load
    if (empty($favicon)) {
      $favicon = addFavIconToURL($url);
      writeLog("Direct Load Attempt using '$favicon'",TYPE_DEBUGGING);
      $fileExtension = geticonextension($favicon,false);
      if (is_null($fileExtension)) {
        writeLog("Failed Direct Load Attempt using '$favicon'",TYPE_DEBUGGING);
        unset($favicon);
      }
    }
    
    if (empty($favicon)) {
      // Load Page
      
      $html = load($url);

      if (empty($html)) {
        writeLog("No data received",TYPE_WARNING);
      } else {
        writeLog("Examining Web Page for Icons",TYPE_DEBUGGING);
        writeLog("Attempting RegEx Match",TYPE_DEBUGGING);
        // Find Favicon with RegEx
        $regExPattern = '/((<link[^>]+rel=.(icon|shortcut\sicon|alternate\sicon)[^>]+>))/i';
        if (@preg_match($regExPattern, $html, $matchTag)) {
          writeLog("RegEx Initial Pattern Matched\n" . print_r($matchTag,true),TYPE_DEBUGGING);
          $regExPattern = '/href=(\'|\")(.*?)\1/i';
          if (isset($matchTag[1]) && @preg_match($regExPattern, $matchTag[1], $matchUrl)) {
            writeLog("RegEx Secondary Pattern Matched",TYPE_DEBUGGING);
            if (isset($matchUrl[2])) {
              writeLog("Found Match, Building Link",TYPE_DEBUGGING);
              // Build Favicon Link
              $favicon = rel2abs(trim($matchUrl[2]), $protocol . '://'.$domain.'/');
              writeLog("Located Icon in HTML as '$favicon'",TYPE_DEBUGGING);
              writeLog("Matched Icon as '$favicon'",TYPE_DEBUGGING);
            } else {
              writeLog("Failed To Find Match",TYPE_DEBUGGING);
            }
          } else {
            writeLog("RegEx Secondary Pattern Failed To Match",TYPE_DEBUGGING);
          }
        } else {
          writeLog("RegEx Initial Pattern Failed To Match",TYPE_DEBUGGING);
        }
      }

      // If there is no Match: Try if there is a Favicon in the Root of the Domain
      if (empty($favicon)) {
        $favicon = addFavIconToURL($protocol . '://'.$domain);
        writeLog("Attempting Direct Match using '$favicon'",TYPE_DEBUGGING);

        // Try to Load Favicon
        # if ( !@getimagesize($favicon) ) {
        # https://www.php.net/manual/en/function.getimagesize.php
        # Do not use getimagesize() to check that a given file is a valid image.
        $fileExtension = geticonextension($favicon,false);
        if (is_null($fileExtension)) {
          writeLog("Failed Direct Match using '$favicon'",TYPE_DEBUGGING);
          unset($favicon);
        }
      }
    } // END If $trySelf == TRUE ONLY USE APIs
  }

  // If nothing works: Get the Favicon from API
  if ((!isset($favicon)) || (empty($favicon))) {
    $api_count = getAPICount();
    writeLog("Falling Back to API, $api_count are defined and enabled",TYPE_DEBUGGING);
    
    if ($api_count > 0) {
      writeLog("Selecting API",TYPE_DEBUGGING);
      # TO DO:
      #   tenacious mode would try more than one
      $selectAPI = getRandomAPI();
      if (!is_null($selectAPI['name'])) {
        $api_display = $selectAPI['display'];
        $api_name = $selectAPI['name'];
        $api_url = $selectAPI['url'];
        $api_json = $selectAPI['json'];
        $api_enabled = $selectAPI['enabled'];
        $api_json_structure = $selectAPI['json_structure'];
        
        if ($api_enabled) {
          writeLog("Selected API: $api_name",TYPE_DEBUGGING);
          $favicon = getAPIurl($api_url,$domain,$iconSize);
          if ($api_json) {
            $echo = json_decode(load($favicon),true);
            if (!is_null($echo)) {
              $favicon = $echo;
              if (!empty($api_json_structure)) {
                # TO DO:
                # this could be better parsing
                foreach ($api_json_structure as $element) {
                  $favicon = $favicon[$element];
                }
              }  
            }            
          }
          writeLog("$api_name API Request: '$favicon'",TYPE_DEBUGGING);
        } else {
          writeLog("Selected API ($api_name) Is Disabled!",TYPE_WARNING);
        }
      } else {
        writeLog("Failed To Select API",TYPE_WARNING);
      }
    } else {
      writeLog("No APIs Available",TYPE_WARNING);
    }
  } // END If nothing works: Get the Favicon from API


  // If Favicon should be saved
  if ((isset($save)) && ($save == TRUE)) {
    unset($content);
    writeLog("Loading Icon To Store using '$favicon'",TYPE_DEBUGGING);

    //  Load Favicon
    $content = load($favicon);
    
    if (empty($content)) {
      writeLog("Failed to load favicon using '$favicon'",TYPE_DEBUGGING);
    } else {
      if (!is_null(getGlobal('redirect_url'))) { $favicon = getGlobal('redirect_url'); }
      if (!is_null($api_name)) {
        if ($api_name == "google") {
          if (md5($content) == GOOGLE_DEFAULT_ICON_MD5) {
            $domain = 'default'; // so we don't save a default icon for every domain again
            writeLog("Got Google Default Icon, Discarding",TYPE_DEBUGGING);
          }
        }
      }

      //  Get Type
      if (!empty($favicon)) {
        $fileExtension = geticonextension($favicon);
        if (is_null($fileExtension)) {
          writeLog("Icon Is Not Valid, Discarding '$favicon'",TYPE_DEBUGGING);
        } else {
          if ($removeTLD && !is_null($core_domain)) {
            $filePath = preg_replace('#\/\/#', '/', $directory.'/'.$core_domain.'.'.$fileExtension);
          } else {
            $filePath = preg_replace('#\/\/#', '/', $directory.'/'.$domain.'.'.$fileExtension);
          }

          //  If overwrite, delete it
          if (file_exists($filePath)) { if ($overwrite) { unlink($filePath); } }

          //  If file exists, skip
          if (file_exists($filePath)) {
            writeLog("Skipping Storing Icon as '$filePath'",TYPE_DEBUGGING);
          } else {
            // Write
            $fh = @fopen($filePath, 'wb');
            if ($fh) {
              fwrite($fh, $content);
              fclose($fh);
              writeLog("Stored Icon as '$filePath'",TYPE_DEBUGGING);
            } else {
              writeLog("Error Storing Icon as '$filePath'",TYPE_DEBUGGING);
            }
          }
        }
      }
    }
  } else {
    // Don't save Favicon local, only return Favicon URL
    $filePath = $favicon;
  }

  // FOR DEBUG ONLY
  if (debugMode()) { listIcons($filePath); }

    // reset script runtime timeout
  if (!$consoleMode) {
    set_time_limit($max_execution_time); // set it back to the old value
  }

  //  End Section
  debugSection();
  
  // Return Favicon Url
  return $filePath;

} // END MAIN Function


/*  Load URL */
function load($url) {
  debugSection("load");
  $content = null;
  $content_hash = null;
  $content_type = null;
  $http_code = null;
  $method = null;
  $previous_url = getGlobal('redirect_url');
  $protocol = parse_url($url,PHP_URL_SCHEME);
  $protocol_id = null;  
  $redirect = getGlobal('redirect_count');
  if (!isset($redirect)) { $redirect = 0; }
  $flag_skip_loadlastresult = false;
  
  //  If no protocol is it's probably a local file.
  if (is_null($protocol)) {
    $content = loadLocalFile($url);
  } else {
    if (getConfiguration("http","use_buffering")) {
      $lastLoadURL = getLastLoadResult('url');
      if (!is_null($lastLoadURL)) {
        if ($lastLoadURL == $url) {
          $content = getLastLoadResult('content');
          $flag_skip_loadlastresult = true;
        }
      }
    }
  }
  
  if (is_null($content)) {
    writeLog("loading: url='$url'",TYPE_TRACE);
    if (getConfiguration("curl","enabled")) {
      $method = "curl";
      writeLog("$method: Operation Timeout=" . getConfiguration("http","http_timeout") . ", Connection Timeout=" . getConfiguration("http","http_timeout_connect") . ", DNS Timeout=" . getConfiguration("http","dns_timeout"),TYPE_TRACE);
      $ch = curl_init($url);
      if (!is_null(getConfiguration("http","useragent"))) { curl_setopt($ch, CURLOPT_USERAGENT, getConfiguration("http","useragent")); }
      curl_setopt($ch, CURLOPT_VERBOSE, getConfiguration("curl","verbose"));
      curl_setopt($ch, CURLOPT_TIMEOUT, getConfiguration("http","http_timeout"));
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, getConfiguration("http","http_timeout_connect")); 
      curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, getConfiguration("http","dns_timeout")); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      if (getConfiguration("curl","showprogress")) { curl_setopt($ch, CURLOPT_NOPROGRESS, false); }
      $content = curl_exec($ch);
      $curl_response = curl_getinfo($ch);
      $content_type = $curl_response['content_type'];
      $http_code = $curl_response['http_code'];
      $curl_url = $curl_response['url'];
      $protocol = $curl_response['scheme'];
      $protocol_id = $curl_response['protocol'];
      writeLog("$method: Return Code=$http_code for '$url'",TYPE_TRACE);
      curl_close($ch);
      unset($ch);
      if (is_null($content)) { writeLog("$method: No content received '$url'",TYPE_TRACE); }
      if ($http_code == 301 || $http_code == 302 || $http_code == 307 || $http_code == 308)  {
        $redirect++;
        if ($redirect < getConfiguration("http","maximum_redirects"))
        {
          writeLog("$method: Redirecting to '$curl_url' from '$url'",TYPE_TRACE);
          setGlobal('redirect_count',$redirect);
          setGlobal('redirect_url',$curl_url);
          $content = load($curl_url);
          $flag_skip_loadlastresult = true;
        } else {
          writeLog("$method: Too many redirects ($redirect)",TYPE_TRACE);
        }
      }
    } else {
      $method = "stream";
      $context_options = array(
        'http' => array(
          'user_agent' => getConfiguration("http","useragent"),
          'timeout' => getConfiguration("http","http_timeout"),
        )
      );
      $context = stream_context_create($context_options);
      if (!getCapability("php","get")) {
        writeLog("$method: attempting to load '$url'",TYPE_TRACE);
        $fh = fopen($url, 'r', false, $context);
        if ($fh) {
          $content = '';
          while (!feof($fh)) {
            $content .= fread($fh, BUFFER_SIZE);
          }
          fclose($fh);
        } else {
          writeLog("Failed to open '$url'",TYPE_TRACE);
        }
      } else {
        $method = "stream/file_get_contents";
        writeLog("$method: attempting to load '$url'",TYPE_TRACE);
        $content = @file_get_contents($url, null, $context);
        if (is_null($content)) {
          writeLog("$method: No content received '$url'",TYPE_TRACE);
        } else {
          if (!is_null($http_response_header)) {
            $headers = implode("\n", $http_response_header);
            if (preg_match_all("/^HTTP.*\s+([0-9]+)/mi", $headers, $matches )) {
              $http_code = end($matches[1]);
            }
          }
          if (is_null($http_code)) { writeLog("$method: Return Code=NONE for '$url'",TYPE_TRACE); } else { writeLog("$method: Return Code=$http_code for '$url'",TYPE_TRACE); }
        }
      }
    }
    $content_type = getMIMEType($content);
    if (!$flag_skip_loadlastresult) { lastLoadResult($url,$method,$content_type,$http_code,$protocol,$protocol_id,$content); }
  } else {
    writeLog("Using buffered result for url='$url'",TYPE_TRACE);
  }
  debugSection();
  return $content;
}

/*  Load a Local File */
function loadLocalFile($pathname) {
  debugSection("loadLocalFile");
  $content = null;
  $content_type = null;
  if (is_null($pathname)) {
    writeLog("No pathname given",TYPE_TRACE);
  } else {
    if (file_exists($pathname)) {
      if (!getCapability("php","get")) {
        $fh = fopen($pathname, 'r', false);
        if ($fh) {
          $content = '';
          while (!feof($fh)) {
            $content .= fread($fh, BUFFER_SIZE); // Because filesize() will not work on URLS?
          }
          fclose($fh);
        } else {
          writeLog("Failed to open '$pathname'",TYPE_TRACE);
        }
      } else {
        $content = @file_get_contents($pathname, false);
      }
      if (is_null($content)) {
        writeLog("No content for '$pathname'",TYPE_TRACE);
      } else {
        $content_type = getMIMEType($content);
      }
      lastLoadResult($pathname,"file",$content_type,0,"file",0,$content);
    } else {
      writeLog("'$pathname' does not exist",TYPE_TRACE);
    }
  }
  debugSection();
  return $content;
}

/*  Get MIME Type based on data */
function getMIMEType($data) {
  $content_type = null;
  if (!is_null($data)) {
    if (is_null($content_type)) {
      if (getCapability("php","fileinfo")) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $content_type = $finfo->buffer($data);    
      }
    }
  }
  return $content_type;
}

/*  Get MIME Type from a File */
function getMIMETypeFromFile($pathname) {
  $content_type = null;
  if (!is_null($pathname)) {
    if (file_exists($pathname)) {
      if (is_null($content_type)) {
        if (getCapability("php","fileinfo")) {
          $finfo = new finfo(FILEINFO_MIME_TYPE);
          $content_type = $finfo->file($pathname);    
        }
      }      
      if (is_null($content_type)) {
        if (getCapability("php","mime_content_type")) {
          $content_type = mime_content_type($pathname);
        }
      }    
    }
  }
  return $content_type;
}

/* HELPER: Change URL from relative to absolute */
function rel2abs($rel, $base) {
	extract(parse_url($base));
	if (strpos( $rel,"//" ) === 0) return $scheme . ':' . $rel;
	if (parse_url( $rel, PHP_URL_SCHEME ) != '') return $rel;
	if ($rel[0] == '#' or $rel[0] == '?') return $base . $rel;
	$path = preg_replace( '#/[^/]*$#', '', $path);
	if ($rel[0] ==  '/') $path = '';
	$abs = $host . $path . "/" . $rel;
	$abs = preg_replace( "/(\/\.?\/)/", "/", $abs);
	$abs = preg_replace( "/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs);
	return $scheme . '://' . $abs;
}

/* Get Icon Extension / Verify Icon */
function geticonextension($url, $noFallback = false) {
  debugSection("geticonextension");
  $retval = null;
  if (!empty($url))
  {
    $content = load($url);
    if (!is_null($content)) {
      $content_type = getLastLoadResult("content_type");
      if (is_null($content_type)) {
        if (getCapability("php","exif")) {
          $phpUA = ini_get("user_agent");
          $timeout = ini_get("default_socket_timeout");
          writeLog("geticonextension(exif): url='$url', content-type: null, useragent=$phpUA, timeout=$timeout",TYPE_TRACE);
          $filetype = @exif_imagetype($url);
          if (!is_null($filetype)) {
            switch ($filetype) {
              case IMAGETYPE_GIF:
                $retval = "gif";
                break;
              case IMAGETYPE_JPEG:
                $retval = "jpg";
                break;
              case IMAGETYPE_PNG:
                $retval = "png";
                break;
              case IMAGETYPE_ICO:
                $retval = "ico";
                break;
              case IMAGETYPE_WEBP:
                $retval = "webp";
                break;
              case IMAGETYPE_BMP:
                $retval = "bmp";
                break;
              default:
                $retval = null;
                $noFallback = true;
            }
          }
        }
      } else {
        writeLog("geticonextension: url='$url', content-type=$content_type",TYPE_TRACE);
        switch ($content_type) {
          case "image/x-icon":
            $retval = "ico";
            break;
          case "image/vnd.microsoft.icon":
            $retval = "ico";
            break;
          case "image/webp":
            $retval = "webp";
            break;
          case "image/png":
            $retval = "png";
            break;
          case "image/jpeg":
            $retval = "jpg";
            break;
          case "image/gif":
            $retval = "gif";
            break;
          case "image/svg+xml":
            $retval = "svg";
            break;
          case "image/avif":
            $retval = "avif";
            break;
          case "image/apng":
            $retval = "apng";
            break;
          case "image/bmp":
            $retval = "bmp";
            break;
          case "image/tiff":
            $retval = "tif";
            break;
          default:
            $retval = null;
            $noFallback = true;
        }
      }
    }
    if (is_null($retval)) {
      if (!$noFallback) {
        writeLog("geticonextension: url='$url', attempting fallback",TYPE_TRACE);
        $extension = @preg_replace('/^.*\.([^.]+)$/D', '$1', $url);
        if (isValidType($extension)) {
          $retval = $extension;
        } else {
          $retval = null;
        }
      }
    }
  }
  debugSection();
  return $retval;
}

/*  Validate File Type */
function isValidType($type) {
  $retval = false;
  $extensionList = strtolower(getConfiguration("global","valid_types"));
  $type = strtolower($type);
  if (!is_null($extensionList)) {
    #   TO DO:
    #     is this an extension we are allowing?
  }
  return $retval;
}

/*  Adds the favicon path to the URL */
function addFavIconToURL($url) {
  if(strrev($url)[0]==='/') {
    // Already has slash
  } else {
    $url .= "/";
  }
  $url .= URL_PATH_FAVICON;
  return $url;
}

function validateicon($pathname, $removeIfInvalid = false) {
  debugSection("validateicon");
  # TO DO
  #   determine if the pathname is:
  #     a valid image file
  #     not in the blocklist
  #
  # if it is not a valid (or is blocked) and removeIfInvalid is true, delete it.
  debugSection();
  $retval = false;
}

/* Set Global Variable */
function setGlobal($variable,$value = null) {
  $GLOBALS[$variable] = $value;
}

/* Get Global Variable */
function getGlobal($variable) {
	return $GLOBALS[$variable];
}

/*  Set PHP's User Agent */
function initializePHPAgent() {
  $userAgent = getConfiguration("http","useragent");
  if (is_null($userAgent)) { $userAgent = getConfiguration("http","default_useragent"); }
  if (!is_null($userAgent)) { ini_set('user_agent', $userAgent); }
  if (ini_get("default_socket_timeout") > getConfiguration("http","http_timeout")) { ini_set("default_socket_timeout", getConfiguration("http","http_timeout")); }
}

/*  Show Boolean */
function showBoolean($value) {
  $retval = "null";
  if (isset($value)) {
    $value = setBoolean($value);
    if ($value) { $retval = "true"; } else { $retval = "false"; }
  }
  return $retval;
}

/*  Set/Convert To Boolean */
function setBoolean($value) {
  $retval = false;
  if (is_bool($value)) {
    $retval = $value;
  } elseif (is_numeric($value)) {
    if ($value > 0) { $retval = true; }
  } elseif (is_string($value)) {
    if ((strtolower($value) == "true") || (strtolower($value) == "yes") || (strtolower($value) == "enabled") || (strtolower($value) == "enable") || (strtolower($value) == "on")) {
      $retval = true;
    }
  } else {
    if (isset($value)) {
      $retval = true;
    }
  }
  return $retval;
}

/*  Set/Convert A Numeric Range */
function setRange($value,$min = null,$max = null) {
  if (is_numeric($value)) {
    if (!is_null($min)) { if ($value < $min) { $value = $min; } }
    if (!is_null($max)) { if ($value > $max) { $value = $max; } }
  }
  $value = intval($value);
  return $value;
}

/*
**  API Support Functions **
**
**    name      Name/ID for the API
**    url       URL for the API
**    json      Does API return JSON?
**    enabled   Is API enabled?
**    display   Display Name (cosmetic only), defaults to name
**
**    json_structure is an array of the expected json data turned.
**
**
*/
/* Add an API */
function addAPI($name,$url,$json = false,$enabled = true,$json_structure = array(),$display = null) {
  global $apiList;

  $entry = array();
  if (is_null($display)) { $display = $name; }
  $entry['display'] = $display;
  $entry['name'] = $name;
  $entry['url'] = $url;
  $entry['json'] = $json;
  $entry['enabled'] = $enabled;
  $entry['json_structure'] = $json_structure;

  array_push($apiList,$entry);
  refreshAPIList();
}

function getAPIList($displayName = false) {
  global $apiList;
  
  $retval = null;
  $counter = 0;
  
  if (!empty($apiList)) {
    foreach ($apiList as $item) {
      if ($displayName) {
        $api_name = $item['display'];
      } else {
        $api_name = $item['name'];
      }
      $api_enabled = $item['enabled'];
      if (!is_null($api_name)) {
        if (!is_null($retval)) {
          $retval .= ", ";
        }
        $retval .= $api_name;
        if (!$api_enabled) { $retval .= "*"; }
      }
    }
  }
  
  if (is_null($retval)) { if ($displayName) { $retval = "None"; } else { $retval = "none"; } }
  
  return $retval;
}

function refreshAPIList($isenabled = true) {
  global $apiList; 
  $count = 0;
  if (!empty($apiList)) {
    foreach ($apiList as $item) {
      $api_display = $item['display'];
      $api_name = $item['name'];
      $api_url = $item['url'];
      $api_json = $item['json'];
      $api_enabled = $item['enabled'];
      $api_json_structure = $item['json_structure'];
      if (!is_null($api_name)) {
        if ($isenabled) {
          if ($api_enabled) { $count++; }
        } else {
          $count++;
        }
      }
    }
  }
  setItem("api_count",$count);
}

function getAPICount($isenabled = true) {
  
  $api_count = getItem("api_count");
  
  if (!isset($api_count)) { 
    refreshAPIList($isenabled);
    $api_count = getItem("api_count");
  }

  return $api_count;
}

/* Return an API object */
function getAPI($name) {
  return lookupAPI('name',$name);
}

/* Select a Random API */
function getRandomAPI() {
  global $apiList;
  
  $api_count = getAPICount();
  $return_object = array();
  $return_object['name'] = null;
  $return_object['display'] = null;
  $return_object['url'] = null;
  $return_object['json'] = false;
  $return_object['enabled'] = false;
  $return_object['json_structure'] = array();
  
  $flag_selecting = true;
  
  if ($api_count > 0) {
    while ($flag_selecting) {
      $api_name = array_rand($apiList,1);
      $return_object = getAPI($api_name);
      if (!is_null($return_object['name'])) {
        if ($return_object['enabled']) {
          $flag_selecting = false;
        }
      }
    }
  }
  return $return_object;
}

/* Lookup API */
function lookupAPI($element,$value) {
  global $apiList;

  $return_object = array();
  $return_object['display'] = null;
  $return_object['name'] = null;
  $return_object['url'] = null;
  $return_object['json'] = false;
  $return_object['enabled'] = false;
  $return_object['json_structure'] = array();
  
  foreach ($apiList as $item) {
    if (!is_null($item['name'])) {
      if (strcasecmp($item[$element], $value) == 0) {
        $return_object = array();
        $return_object = $item;
        break;
      }
    }
  }
  return $return_object;
}

/*  Populate API URL */
function getAPIurl($url,$domain,$size = 0) {
  if ($size == 0) { $size = getConfiguration("global","icon_size"); }
  
  $processed_url = $url;
  $processed_url = str_replace("<DOMAIN>",$domain,$processed_url);
  $processed_url = str_replace("<SIZE>",$size,$processed_url);
  return $processed_url;
}

/*  Render HTML */
/*
**  Style is a string template.
**  Parameters is an array or a string.  if a string then 'message' is assumed.
**    message: Substitute <MESSAGE>
**    title:   Substitute <TITLE>
**    file:    Substitute <FILE>
*/

function renderHTML($style = null,$parameters = null) {
  $html = null;
  $message = null;
  $title = null;
  $file = null;
  if (!is_null($parameters)) {
    if (is_array($parameters)) {
      if (isset($parameters['message'])) {
        $message = $parameters['message'];
      }
      if (isset($parameters['title'])) {
        $title = $parameters['title'];
      }
      if (isset($parameters['file'])) {
        $file = $parameters['file'];
      }
    } else {
      $message = $parameters;
    }
    if (is_null($style)) {
      if (!is_null($message)) { $html = $message . "<br>"; }
    } else {
      $html = $style;
      if (!is_null($message)) { $html = str_replace("<MESSAGE>",$message,$html); }
      if (!is_null($title)) { $html = str_replace("<TITLE>",$title,$html); }
      if (!is_null($file)) { $html = str_replace("<FILE>",$file,$html); }
    }
  }
  return $html;
}

/*  List Icons with Base64 Encoding in HTML Mode */
function listIcons($filePath) {
  debugSection("listIcons");
  $consoleMode = getConfiguration("mode","console");    
  $html_mode = array();
  if (is_null($filePath)) {
    writeLog("No pathname given",TYPE_TRACE);
  } else {
    if (file_exists($filePath)) {
      $mimetype = getMIMETypeFromFile($filePath);
      if (is_null($mimetype)) {
        writeLog("Could not determine mimetype for '$filePath'",TYPE_ERROR);
      } else {
        if (!$consoleMode) {
          $encodedContent = getFileAsBase64($filePath);
          if (is_null($encodedContent)) {
            writeLog("Error loading '$filePath'",TYPE_ERROR);
          } else {
            $html = renderHTML(HTML_STYLE_WARNING,"Image");
            $html .= "<img style=\"width:32px;\" src=\"data:" . $mimetype . ";base64," . $encodedContent . "\>";
            $html .= "<hr size=\"1\">";
          }
          $html_mode = array(
            "html_output" => $html,
          );          
        }
        writeLog("'$mimetype' format file loaded from '$filePath'",TYPE_ALL,$html_mode);
      }  
    } else {
      writeLog("'$filePath' does not exist",TYPE_TRACE);
    }
  }
  debugSection();
}

/*  Load a File and Encode it in Base64 */
function getFileAsBase64($filePath) {
  debugSection("getFileAsBase64");
  $retval = null;
  if (is_null($filePath)) {
    writeLog("No pathname given",TYPE_TRACE);
  } else {
    if (file_exists($filePath)) {
      $content = null;
      if (!getCapability("php","get")) {
        $fh = @fopen($filePath, 'r');
        if ($fh) {
          while (!feof($fh)) {
            $content .= fread($fh, BUFFER_SIZE);
          }
          fclose($fh);
        } else {
          writeLog("Failed to open '$filePath'",TYPE_TRACE);
        }
      } else {
        $content = file_get_contents($filePath);
      }
      if (is_null($content)) {
        writeLog("No content for '$filePath'",TYPE_TRACE);
      } else {
        $retval = base64_encode($content); 
      }
    } else {
      writeLog("'$filePath' does not exist",TYPE_TRACE);
    }
  } 
  debugSection();
  return $retval;  
}

/*  See if the script is in Debug mode */
function debugMode() {
  $retval = false;
  $log_enabled = getConfiguration("logging","enabled");
  $log_level = getConfiguration("logging","level");
  $console_enabled = getConfiguration("console","enabled");
  $console_level = getConfiguration("console","level");
  $debug = getConfiguration("global","debug");
  if ($debug) { $retval = true; }
  if ($log_enabled) {
    if ($log_level & TYPE_DEBUGGING) { $retval = true; }
    if ($log_level & TYPE_TRACE) { $retval = true; }
  }
  if ($console_enabled) {
    if ($console_level & TYPE_DEBUGGING) { $retval = true; }
    if ($console_level & TYPE_TRACE) { $retval = true; }
  }
}

/*  Initialize Log File */
/*  Also acts to see if logging (console and/or file) is enabled */
function initializeLogFile() {
  $retval = false;
  $log_enabled = getConfiguration("logging","enabled");
  if ($log_enabled) {
    $log_opt_append = getConfiguration("logging","append");
    $log_pathname = getConfiguration("logging","pathname");
    $log_separator = getConfiguration("logging","separator");
    $flag_init = getGlobal("flag_log_initialized");
    if (!isset($flag_init)) { $flag_init = 0; }
    if ($flag_init) {
      $retval = true;
    } else {
      if (is_null($log_pathname)) {
        setConfiguration("logging","enabled",false);
      } else {
        $flag_open_append = false;
        $log_write_separator = false;
        if (file_exists($log_pathname)) {
          if ($log_opt_append) {
            $flag_open_append = true;
          }
        }
        if ($flag_open_append) {
          $log_handle = @fopen($log_pathname, 'a+');
          $log_write_separator = true;
        } else {
          $log_handle = @fopen($log_pathname, 'w+');
        }
        if (is_null($log_handle)) { 
          setConfiguration("logging","enabled",false);
        } else {
          setGlobal("flag_log_initialized",1);
          setGlobal("log_handle",$log_handle);
          $retval = true;
          if ($log_write_separator) {
            if (fwrite($log_handle, "$log_separator\n") === false) {
              setConfiguration("logging","enabled",false);
            } 
          }
        }
      }
    }
  }
  if (getConfiguration("logging","enabled")) { $retval = true; }
  if (getConfiguration("console","enabled")) { $retval = true; }
  return $retval;
}

/*  Write Log File */
/*
**  Message:  Output Text
**  Type:     Level
**  Special:  Array
**
**    Special is an array and provides overrides
**      html_template = renderHTML template
**      html_output = don't render output, use this string
**      html_parameters = array to give to renderHTML
**      no_html = suppress any HTML output for this message
**      no_file = suppress any logfile output for this message
**      no_console = suppress console output for this message
**
**/
function writeLog($message = null,$type = TYPE_ALL,$special = array()) {
  if (initializeLogFile()) {
    $console_enabled = getConfiguration("console","enabled");
    $console_level = getConfiguration("console","level");
    $console_opt_timestamp = getConfiguration("console","timestamp");
    $console_opt_timestamp_format = getConfiguration("console","timestampformat");
    $log_enabled = getConfiguration("logging","enabled");
    $log_level = getConfiguration("logging","level");
    $log_opt_timestamp = getConfiguration("logging","timestamp");
    $log_opt_timestamp_format = getConfiguration("logging","timestampformat");
    $log_handle = getGlobal("log_handle");
    $debug_section = getGlobal("debug_section");
    $consoleMode = getConfiguration("mode","console");      
    
    $flag_write_console = false;
    $flag_write_file = false;
    
    $string_module_file = null;
    $string_module_console = null;
    $string_timestamp_file = null;
    $string_timestamp_console = null;

    $html_parameters = array();
    $html_override = null;
    $flag_nohtml = false;
    $flag_nofile = false;
    $flag_noconsole = false;
    $end_line = null;
    $string_type = null;
    
    $now = time();
    
    //  Determine Prefix and/or HTML style
    if ($type & TYPE_ALL)
    {
      $html_style = HTML_STYLE_ALL;
    }
    if ($type & TYPE_NOTICE)
    {
      $html_style = HTML_STYLE_NOTICE;
    }
    if ($type & TYPE_WARNING)
    {
      $string_type = "[WARNING] ";
      $html_style = HTML_STYLE_WARNING;
    }
    if ($type & TYPE_VERBOSE)
    {
      $html_style = HTML_STYLE_VERBOSE;
    }
    if ($type & TYPE_ERROR)
    {
      $string_type = "[ERROR] ";
      $html_style = HTML_STYLE_ERROR;
    }
    if ($type & TYPE_DEBUGGING)
    {
      $string_type = "[DEBUG] ";
      $html_style = HTML_STYLE_DEBUGGING;
    }
    if ($type & TYPE_TRACE)
    {
      $string_type = "[TRACE] ";
      $html_style = HTML_STYLE_TRACE;
    }

    //  Process special directives
    if (!empty($special)) {
      if (isset($special['html_template'])) { $html_style = $special['html_template']; }
      if (isset($special['html_output'])) { $html_override = $special['html_output']; }
      if (isset($special['no_html'])) { $flag_nohtml = true; }
      if (isset($special['no_file'])) { $flag_nofile = true; }
      if (isset($special['no_console'])) { $flag_noconsole = true; }
      if (isset($special['html_parameters'])) { $html_parameters = $special['html_parameters']; }
    }

    //  Log File Preparation
    if ($log_enabled) {
      if ($log_level & $type) {
        $file_string_timestamp = null;
        if ($log_opt_timestamp) {
          if (!is_null($log_opt_timestamp_format)) { $file_string_timestamp = date($log_opt_timestamp_format,$now);}
        }
        if (!is_null($message)) {
          $flag_write_file = true;
        }
      }
    }

    //  Console Preparation
    if ($console_enabled) {
      if ($console_level & $type) {
        $console_string_timestamp = null;
        if ($console_opt_timestamp) {
          if (!is_null($console_opt_timestamp_format)) { $console_string_timestamp = date($console_opt_timestamp_format,$now);}
        }
        $flag_write_console = true;
      }
    }    
 
    // Process Special Directives
    if ($flag_write_file) {
      if ($flag_nofile) { $flag_write_file = false; }
    }
    if ($flag_write_console) { 
      if ($flag_noconsole) { if ($consoleMode) { $flag_write_console = false; } }
      if ($flag_nohtml) { if (!$consoleMode) { $flag_write_console = false; } }
    }    
    
    //  Write Log File
    if ($flag_write_file) {
      $string_file_line = $message;
      if (!is_null($debug_section)) { $string_file_line = "[" . $debug_section . "] " . $string_file_line; }
      if (!is_null($string_type)) { $string_file_line = $string_type . $string_file_line; }
      if (!is_null($file_string_timestamp)) { $string_file_line = $file_string_timestamp . " " . $string_file_line; }
      if (!is_null($log_handle)) {
        if (fwrite($log_handle, "$string_file_line\n") === false) {
          setConfiguration("logging","enabled",false);
        }
      }        
    }

    //  Write Console/HTML
    if ($flag_write_console) {
      $string_console_line = $message;
      if ($consoleMode) {
        if (!is_null($debug_section)) { $string_console_line = "[" . $debug_section . "] " . $string_console_line; }
        if (!is_null($string_type)) { $string_console_line = $string_type . $string_console_line; }
        if (!is_null($console_string_timestamp)) { $string_console_line = $console_string_timestamp . " " . $string_console_line; }
      } else {
        if (!is_null($html_override)) {
          $string_console_line = $html_override;
        } else {
          if (!is_null($debug_section)) { $string_console_line = "[" . renderHTML(HTML_STYLE_TT,$debug_section) . "] " . $string_console_line; }
          if (!is_null($string_type)) { $string_console_line = $string_type . $string_console_line; }
          if (!is_null($console_string_timestamp)) { $string_console_line = renderHTML(HTML_STYLE_TT,$console_string_timestamp) . " " . $string_console_line; }
          if (!isset($html_parameters['message'])) { $html_parameters['message'] = $string_console_line; }
          $string_console_line = renderHTML($html_style,$html_parameters);
        }
        $end_line = "<br>";
      }
      echo $string_console_line;
      if (!is_null($end_line)) { echo $end_line; }
      echo "\n";
    }
  }
}

/*  Debug Section Stack */
function debugSection($section = null) {
  global $functiontrace;
  
  if (is_null($section)) {
    array_pop($functiontrace);
  } else {
    array_push($functiontrace,$section);
  }
  if (empty($functiontrace)) {
    setGlobal("debug_section",null);
  } else {
    if (count($functiontrace) > 1) {
      setGlobal("debug_section",implode(":",$functiontrace));
    } else {
      setGlobal("debug_section",end($functiontrace));
    }
  }
}

/*
**  Configuration Controller
**
*/
function setConfiguration($scope = "global",$option,$value = null,$default = null,$type = CONFIG_TYPE_SCALAR) {
  global $configuration;
  $flag_fallback = true;
  $flag_handled = false;
  
  if (isset($value)) {
    switch ($type) {
      case CONFIG_TYPE_PATH:            /* Validate Path */
        if (isset($value)) { if (file_exists($value)) { $flag_fallback = false; } }
        break;
      case CONFIG_TYPE_BOOLEAN:         /* Validate Boolean */
        if (is_bool($value)) { $flag_fallback = false; }
        break;
      case CONFIG_TYPE_STRING:          /* Validate String */
        if (is_string($value)) { $flag_fallback = false; }
        break;
      case CONFIG_TYPE_NUMERIC:         /* Validate Numeric */
        if (is_numeric($value)) { $flag_fallback = false; }
        break;
      case CONFIG_TYPE_SWITCH:          /* Validate Switch */
        if (isset($value)) {
          $flag_fallback = false;
          $value = true;
        }
        break;
      case CONFIG_TYPE_SWITCH_PAIR:     /* Validate Switch Pair, $value = true, $default = false option */
        $flag_handled = true;
        if (isset($value)) {
          $flag_handled = true;
          $configuration[$scope][$option] = true;
        }
        if (isset($default)) {
          $flag_handled = true;
          $configuration[$scope][$option] = false;
        }
        break;
      case CONFIG_TYPE_USERAGENT:       /* Validate User Agent */
        if (is_null($default)) { $default = getConfiguration("http","default_useragent"); }
        if (isset($value)) {
          if (!is_null($value)) {
            $flag_fallback = false;
            if (strtolower($value) == "none") {
              $value = null;
            }
          }
        }
        break;
      default:                          /* Just see if it's set */
        if (isset($value)) { $flag_fallback = false; }
        break;
    }
  }
      
  if (!$flag_handled) {
    if ($flag_fallback) { $value = $default; }
    if (isset($value)) { $configuration[$scope][$option] = $value; }
  }
}

function getConfiguration($scope = "global",$option) {
  global $configuration;
  
  if (isset($configuration[$scope][$option])) { $value = $configuration[$scope][$option]; } else { $value = null; }
  
  return $value;
}

function validateConfigurationSetting($scope = "global",$option,$type = 0,$min = 0,$max = 0) {
  global $configuration;
  
  if (isset($configuration[$scope][$option])) {
    $value = $configuration[$scope][$option];
    switch ($type) {
      case CONFIG_TYPE_PATH:            /* Validate Path */
        if (isset($value)) {
          if (!file_exists($value)) {
            $value = null;
          }
        }
        if ((!isset($value)) || (is_null($value))) {
          if ($min == 0) {
            $min = DEFAULT_LOCAL_PATH;
          }
          $configuration[$scope][$option] = $min;
        }
        break;
      case CONFIG_TYPE_NUMERIC:         /* Validate Numeric */
        $value = setRange($value,$min,$max);
        if (is_numeric($value)) { $configuration[$scope][$option] = $value; }
        break;
      case CONFIG_TYPE_BOOLEAN:         /* Validate Boolean */
        if (!is_bool($value)) {
          $value = setBoolean($value);
          if (is_bool($value)) { $configuration[$scope][$option] = $value; }
        } 
        break;
    }
  }
}

/*  Validate Configuration */
function validateConfiguration() {
  debugSection("validateConfiguration");
  
  validateConfigurationSetting("global","debug",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","blocklist",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","api",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","icon_size",CONFIG_TYPE_NUMERIC,RANGE_ICON_SIZE_MINIMUM,RANGE_ICON_SIZE_MAXIMUM);
  validateConfigurationSetting("console","enabled",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("console","level",CONFIG_TYPE_NUMERIC,0,TYPE_ALL + TYPE_NOTICE + TYPE_WARNING + TYPE_VERBOSE + TYPE_ERROR + TYPE_DEBUGGING + TYPE_TRACE);
  validateConfigurationSetting("console","timestamp",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("curl","verbose",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("curl","showprogress",CONFIG_TYPE_BOOLEAN);  
  validateConfigurationSetting("files","local_path",CONFIG_TYPE_PATH,DEFAULT_LOCAL_PATH);
  validateConfigurationSetting("files","overwrite",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("files","store",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("files","remove_tld",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("http","dns_timeout",CONFIG_TYPE_NUMERIC,RANGE_DNS_TIMEOUT_MINIMUM,RANGE_DNS_TIMEOUT_MAXIMUM);
  validateConfigurationSetting("http","http_timeout",CONFIG_TYPE_NUMERIC,RANGE_HTTP_TIMEOUT_MINIMUM,RANGE_HTTP_TIMEOUT_MAXIMUM);
  validateConfigurationSetting("http","http_timeout_connect",CONFIG_TYPE_NUMERIC,RANGE_HTTP_CONNECT_TIMEOUT_MINIMUM,RANGE_HTTP_CONNECT_TIMEOUT_MAXIMUM);
  validateConfigurationSetting("http","maximum_redirects",CONFIG_TYPE_NUMERIC,RANGE_HTTP_REDIRECTS_MINIMUM,RANGE_HTTP_REDIRECTS_MAXIMUM);
  validateConfigurationSetting("http","try_homepage",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("http","use_buffering",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("logging","append",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("logging","enabled",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("logging","level",CONFIG_TYPE_NUMERIC,0,TYPE_ALL + TYPE_NOTICE + TYPE_WARNING + TYPE_VERBOSE + TYPE_ERROR + TYPE_DEBUGGING + TYPE_TRACE);
  validateConfigurationSetting("logging","timestamp",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("mode","console",CONFIG_TYPE_BOOLEAN);
  
  if (getConfiguration("logging","enabled")) {
    if (is_null(getConfiguration("logging","pathname"))) {
      writeLog("validateConfiguration: pathname is not set, disabling logging option",TYPE_TRACE);
      setConfiguration("logging","enabled",false);
    }
    if (getConfiguration("logging","timestamp")) {
      if (is_null(getConfiguration("logging","timestampformat"))) {
        writeLog("validateConfiguration: timestampformat is not set, disabling logging timestamp",TYPE_TRACE);
        setConfiguration("logging","timestamp",false);
      }
    }
    if (getConfiguration("logging","append")) {
      if (is_null(getConfiguration("logging","separator"))) {
        setConfiguration("logging","separator","* * *");
      }
    }
  }

  if (getConfiguration("console","enabled")) {
    if (getConfiguration("console","timestamp")) {
      if (is_null(getConfiguration("console","timestampformat"))) {
        writeLog("validateConfiguration: timestampformat is not set, disabling console timestamp",TYPE_TRACE);
        setConfiguration("console","timestamp",false);
      }
    }
  }
    
  if (getConfiguration("files","store")) {
    if (is_null(getConfiguration("files","local_path"))) {
        writeLog("validateConfiguration: local_path is not set, disabling store option",TYPE_TRACE);
        setConfiguration("files","store",false);
    } else {
      if (!file_exists(getConfiguration("files","local_path"))) {
        writeLog("validateConfiguration: local_path is invalid, disabling store option",TYPE_TRACE);
        setConfiguration("files","store",false);
      }
    }
  }
  
  if (getConfiguration("curl","enabled")) {
    if (!getCapability("php","curl")) {
      writeLog("validateConfiguration: curl is enabled but not supported, disabling",TYPE_TRACE);
      setConfiguration("curl","enabled",false);
    }
  }
  debugSection();
}

/*
** Capability Controller
*/
function addCapability($scope = "global", $capability,$value = false) {
  global $capabilities;
  
  $capabilities[$scope][$capability] = $value;
}

function getCapability($scope = "global", $capability) {
  global $capabilities;
  
  if (isset($capabilities[$scope][$capability])) { $value = $capabilities[$scope][$capability]; } else { $value = null; }
  
  return $value;
}

/*
** Blocklist Controller
*/
function addBlocklist($value) {
  global $blockList;
  
  if (isset($value)) {
    if (!searchBlocklist($value)) {
      array_push($blockList,strtolower($value));
    }
  }
}

function searchBlocklist($value) {
  global $blockList;

  $retval = false;
  if (isset($value)) {
    foreach ($blockList as $item) {
      if (strtolower($item) == strtolower($value)) {
        $retval = true;
        break;
      }
    }
  }
  return $retval;
}

/*
** List Controller
*/
function loadList($list) {
  $retval = array();
  if (isset($list)) {
    if (file_exists($list)) {
      $retval = file($list,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    } else {
      if (count($retval) == 0) {
        $retval = explode(",",str_replace(array(",",";"," "),",",$list));
      }
    }
  }
  return $retval;
}

/*
**  Internal Data Controller
*/
function setItem($name,$value = null) {
  global $internalData;
 
  $internalData[$name] = $value;
}

function getItem($name,$value = null) {
  global $internalData;

  if (isset($internalData[$name])) { $value = $internalData[$name]; } else { $value = null; }
  
  return $value;
}

/*
**  Statistics Controller
*/
function addStatistic($name,$value = null) {
  global $statistics;
  
  if (is_null($value)) { $statistics[$name] = 0; } else { $statistics[$name] = $value; }
}

function updateStatistic($name,$value) {
  global $statistics;
  
  if (isset($statistics[$name])) {
    if (!is_null($value)) { $statistics[$name] = $value; }
  }
}

function incrementStatistic($name,$value = 1) {
  global $statistics;
  
  if (isset($statistics[$name])) {
    if (!is_null($value)) { $statistics[$name] += $value; }
  }
}

function decrementStatistic($name,$value = 1) {
  global $statistics;
  
  if (isset($statistics[$name])) {
    if (!is_null($value)) { $statistics[$name] -= $value; }
  }
}

function getStatistic($name) {
  global $statistics;
  
  $retval = 0;
  
  if (isset($statistics[$name])) {
    $retval = $statistics[$name];
  }
  
  return $retval;
}

/*
**  Last Load Result Controller
*/
function lastLoadResult($url = null,$method = null,$content_type = null,$http_code = null,$scheme = null,$protocol = null,$content = null) {
  global $lastLoad;

  $lastLoad = array();
  $lastLoad['url'] = $url;
  $lastLoad['method'] = $method;
  $lastLoad['content_type'] = $content_type;
  $lastLoad['http_code'] = $http_code;
  $lastLoad['scheme'] = $scheme;
  $lastLoad['protocol'] = $protocol;
  $lastLoad['content'] = $content;
  $lastLoad['hash'] = md5($content);
}

function getLastLoadResult($element = null) {
  global $lastLoad;
  
  if (is_null($element)) {
    $return_data = $lastLoad;
  } else {
    $return_data = $lastLoad[$element];
  }
  return $return_data;
}