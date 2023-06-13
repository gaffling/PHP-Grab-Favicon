<?php
/*

# getimagesize should only be done with a *valid image*
# list($width, $height, $type, $attr) = getimagesize("img/flag.jpg");  

    
-----------------------------
CHANGELOG

NOTE: Minor bug fixing is occuring on a continual basis and not noted here)
-----------------------------
  convertRelativeToAbsolute now has one return path making for easier debugging.
  Tightened up domain parsing and regex code;
    Domain parsing should be a bit better now
    RegEx portion pays more attention to HTTP responses and verifies that the content is html before proceeding.
    If RegEx has a hit, the URL will now retain the port if non-standard
  Fixed an issue where the log could be initialized too soon and not honor some settings
  Image identification fallbacks added to local file loading
  Added new 'extensions' section to the ini, it's mostly for testing but could be used if something isn't working right.
    They are all simple boolean values (true or false), the list is:
      curl, exif, get, put, mbstring, fileinfo, mimetype, gd, imagemagick, gmagick, hrtime
      If an extension is listed as true in this section but is not loaded or available, it will change to false.
  Added --sites as an alternate to --list
  Added raw datacheck for most common icon formats
  Added a "confidence" level, not used yet
  Initial work for processing parameters in HTML mode created (completely untested)
  Added --checklocal/--nochecklocal/--storeifnew (requires --checklocal and --store)
    These do not do anything yet.
  Added --showconfig/noshowconfig to show running configuration options
  Added --showconfigonly (implies --showconfig), shows running configuration and exits.
  Added --silent (console mode only)
  Refined HTTP Response Parsing
  PHP .ini values are now in defines so if something changes down the road it's easier to update
  Added more parameter checking
  Added major/minor to version
  Added --apiconfigfile=PATHNAME to load API Definitions
  Loading of 'same folder' API and config file can be controlled in the special runtime defines section.  Default is OFF
    They can still be overridden by a command switch
  API: Updated favicongrabber's built-in definition
  API: Added iconforce to built-in definition
  Added more to the capabilities structure
  If exif is used and content-type will be looked up using the image_type_to_mime_type function
  Capability checking is more thorough and accurate.  ("exif" requires "mbstring" etc)
  (In Progress) Preparing to make alerations to program flow, see CHANGES section below.
  Added HTTP Code parser (lookupHTTPResponse), returns an array:
      ok (boolean)
      code (string) [will never be null even if the http code was]
      description (string) [will never be null]
  rewrote API JSON parser (change to API .ini format)
  added --allowoctetstream / --disallowoctetstream to block potentially invalid icons
    NOTE: If the more advanced capabilities are not available (curl/mime & content type identification) this may block good data if set to disallow
  Some HTTP error handling added
  if VERBOSE, the PHP version will be logged
  if VERBOSE & Debug capabilities and options will be logged
  minor changes for PHP 8.2 compatibility
  added tenacious mode will try all APIs until it gets a successful result
  added precision timers for internal use
  it will now warn if, due to the PHP configuration, some functions that identify formats are not available that results may not be that great
  --debug option no longer influences log output but instead enables debug code
  you can now specify what icon types are acceptable (careful)
  added a new internal structure that tracks all processed urls
    fields: url, favicon, icontype, method, local, saved, overwrite, elapsed, hash, tries
      technically $favicons[] = grap_favicon($url); is not really necessary
      you can get the record with getProcessEntry('field','value to match');
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

-----------------------------
TO DO:
-------------------------------------
  Option to be more careful about chopping off subdomains
  HTML rendering overhaul
    create a master CSS (still all in one "document")
    create more formatting variables
  Add config for temporary image file use
      script will need read/write permissions
  Add functions to get image width/height (and verify type)
      Resource paths:
        raw data, file, url
      Data paths:
        exif, gd, imagemagick, gmagick and a fallback
  Add option to actually check icon on disk's size/type? (post save)
  Change: RegEx search should have simliar path to the new json parser as there can be multiple formats/icons defined
  Add another fallback:
    try to identify file (if needed download a temporary file), return mime type using fopen/fread/file_get_contents
  Update HTTP Mode
    parameters via query string or GET/POST
    show icons, if it has a protocol it should use "img src" to the URL not do a base64.
  Add option for APIs for a delay between requests?
  Add Blocklist for Icons
    list of md5 hashes
      skipped if blocklist is empty or option is goven
        --blocklist=(list of hashes or file with hashes)
        --enableblocklist
        --disableblocklist
  Add save sub-folders
    md5 hash or by alpha
  Add more error checking
  should configuration structure be typed automatically (done at set)?


-----------------------------
ISSUES:
-----------------------------
  medium.ico is often octet-stream and doesn't seem to work:
    md5 989fc29016f391122a2e735ef8f7ad31
    added an option to disallow octet-streams
    

-----------------------------
CHANGES
-----------------------------
Should optimize processing a bit, right now things are reloaded when they don't need to be.
  
  Flow:
  
  1. Check if icon is on disk
      if it is, get the md5hash and type
        if type is not in the 'accepted' list or hash is in the blocklist, mark it as 'wanted'
      if the icon is not present, mark it as 'wanted'
     NOTE: This will only work if:
          1. icons are named how the current settings name things.
          2. The hash/subfolder option hasn't been used.
     (if checklocal is not enabled, it will always be marked as wanted)     
  2. If wanted, try to get favicon
  3. try different methods to get the favicon, stop when the following is true:
      icon is a type that is in the accept list
      icon is not in the blocklist
  4. When saving, if the new icon hash is the same as the old, skip
  5. HTML Mode:
        can show the icon either locally (base64) or with a reference
       Console:
        if save is enabled, it shows the save pathname.
        if save is not enabled, it shows the url for the icon in the format of "domain:url"
  6. Option to output a text file with the urls for the icons
        preformat for curl or wget?
        
  
  Notes:
    the internal structure will need 'status' values, maybe more than one
        wanted
        found
        
    Seperate bool:    
        accepted

        
    perhaps wrapper it so code can be:
        if (siteIcon($url) == WANTED) {

    
  
PHP Grab Favicon
================

> This `PHP Favicon Grabber` use a given url, save a copy (if desired) and return the image path.

Requirements
------------
You'll need an installed and configured PHP.

The following extensions are recommended:
* curl
* fileinfo
* mbstring (required by exif)
* exif

While they are not required, the script will not be as effective.

The script has been tested with PHP 5.6, 7.4, 8.1 and 8.2.  
PHP 7.4 and later is recommended due to some improvements in identifying content-types.

For accurate logging timestamps, it is recommended that your PHP installation has the [Date] section of PHP.ini correctly set.


How it Works
------------

1. When invoked, the script will detect what features are available from your PHP installation.
2. Check if the favicon already exists local or no local copy is desired, if so return path & filename
3. Else load URL and try to match the favicon location with regex
4. If we have a match the favicon link see if it's valid
5. If we have no favicon we try to get one in domain root
6. If there is still no favicon we attempt to get one using a random API
7. If favicon should be saved try to load the favicon URL
8. If a local copy is desired, it will be saved and the pathname will be shown


API
----------
The following API's are supported "out of the box".

* FavIconKit        https://faviconkit.com/
* FavIconGrabber    https://favicongrabber.com/
* Google            https://www.google.com
* Icon Horse        https://icon.horse/

APIs can be customized, please check the documentation for more information.



How To Use
----------
The script can also use a configuration file, for more information please read `CONFIGURATION.md`
These are the most common command line options, for a full list, invoke with `--help` or view `switches.txt`

**Usage:** `get-fav.php` _(Switches)_

  List of URLs:
    --list=File or List of URLs.
    --list=mysites.txt
    --list=http://github.com,https://microsoft.com,http://www.google.com
    
    If using a file, it just needs to be a regular text file with a URL on each line.
    
  
  Location to Store Icons:
    --path=Absolute or Relative Path (must have write access)
    --path=./
    --path=icons
    --path=/myfolder/icons/
    
  Option to save icons:
    --store
    
    (this shouldn't be required as the default is true)
    


About Favicons 
-------------------
A Wikipedia article can be accessed at https://en.wikipedia.org/wiki/Favicon
Audrey Roy Greenfeld has written an excellent guide to favicons for both website administrators and users alike. It can be accessed on github at https://github.com/audreyr/favicon-cheat-sheet


###### Copyright 2019-2023 Igor Gaffling

*/
 
$time_start = microtime(true);

/*  RUNTIME OPTIONS 
**
**  If you wish get-fav.php to be able to process commands issued via a query string or
**  form submission on a webserver, change the setting below to true.
*/
define('ENABLE_WEB_INPUT', false);
/*
**
**  If these are true:
**      If get-fav-api.ini is in the same folder it will be used for API configuration automatically
**      If get-fav.ini is in the same folder it will be used as the config automatically
**
*/
define('ENABLE_SAME_FOLDER_API_INI',false);
define('ENABLE_SAME_FOLDER_INI',false);

/*
**  Project
*/
define('PROJECT_NAME', 'PHP Grab Favicon');
define('PROGRAM_NAME', 'get-fav');
define('PROGRAM_MAJOR_VERSION', 1);
define('PROGRAM_MINOR_VERSION', 2);
define('PROGRAM_BUILD', '202306131222');
define('PROGRAM_COPYRIGHT', 'Copyright 2019-2023 Igor Gaffling');

/*  Debug */
define('DEFAULT_DEBUG_DUMP_FILE', "get-fav-debug.log");
define('DEFAULT_DEBUG_DUMP_STRUCTURES', true);

/*  Defaults */

define('DEFAULT_ENABLE_APIS', true);
define('DEFAULT_USE_CURL', true);
define('DEFAULT_STORE', true);
define('DEFAULT_STORE_IF_NEW', false);
define('DEFAULT_TRY_HOMEPAGE', true);
define('DEFAULT_OVERWRITE', false);
define('DEFAULT_ENABLE_BLOCKLIST', true);
define('DEFAULT_TENACIOUS', false); 
define('DEFAULT_CHECKLOCAL', false);
define('DEFAULT_REMOVE_TLD', false);
define('DEFAULT_ALLOW_OCTET_STREAM', false);
define('DEFAULT_ALLOW_OCTET_STREAM_IF_FILEINFO_OR_MIMETYPE', true);
define('DEFAULT_USE_LOAD_BUFFERING', true);
define('DEFAULT_LOG_PATHNAME', "get-fav.log");
define('DEFAULT_LOG_FILE_ENABLED', false);
define('DEFAULT_LOG_CONSOLE_ENABLED', true);
define('DEFAULT_LOG_APPEND', true);
define('DEFAULT_LOG_SEPARATOR', str_repeat("*", 80));
define('DEFAULT_LOG_SHORT_SEPARATOR', "* * *");
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
define('DEFAULT_INI_FILE', "get-fav.ini");
define('DEFAULT_USER_AGENT', "FaviconBot/1.0/");
define('DEFAULT_VALID_EXTENSIONS', "gif,webp,png,ico,bmp,svg,jpg");
define('DEFAULT_SHOW_RUNNING_CONFIGURATION', true);
define('DEFAULT_PROTOCOL_IS_HTTPS', true);
define('DEFAULT_FALLBACK_IF_HTTPS_NOT_AVAILABLE', true);
define('DEFAULT_ACCEPTABLE_DATA_CONFIDENCE', 75);

/*  Data Confidence */
define('CONFIDENCE_CERTAIN', 95);
define('CONFIDENCE_HIGH', 80);
define('CONFIDENCE_MEDIUM', 60);
define('CONFIDENCE_LOW', 40);
define('CONFIDENCE_UNCERTAIN', 0);

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
define('RANGE_HTTP_RESPONSE_MINIMUM', 100);
define('RANGE_HTTP_RESPONSE_MAXIMUM', 523);

/* Logging Levels */
define('TYPE_ALL', 1);
define('TYPE_NOTICE', 2);
define('TYPE_WARNING', 4);
define('TYPE_VERBOSE', 8);
define('TYPE_ERROR', 16);
define('TYPE_DEBUGGING', 32);
define('TYPE_TRACE', 64);
define('TYPE_SPECIAL', 128);

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

/*  HTTP Response Types */
define('HTTP_RESPONSE_TYPE_NONE', 0);
define('HTTP_RESPONSE_TYPE_INFORMATIONAL', 1);
define('HTTP_RESPONSE_TYPE_SUCCESS', 2);
define('HTTP_RESPONSE_TYPE_REDIRECT', 3);
define('HTTP_RESPONSE_TYPE_CLIENT_ERROR', 4);
define('HTTP_RESPONSE_TYPE_SERVER_ERROR', 5);

/*  Timer Types */
define('TIME_TYPE_ANY', 0);
define('TIME_TYPE_STANDARD', 1);
define('TIME_TYPE_MICROTIME', 2);
define('TIME_TYPE_HRTIME', 3);

/*  States */
define('STATE_WANTED', 1);
define('STATE_FOUND', 2);

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

/*  PHP INI Entries */
define('PHP_OPTION_ALLOW_URL_FOPEN', "allow_url_fopen");
define('PHP_OPTION_MAX_EXECUTION_TIME', "max_execution_time");
define('PHP_OPTION_USER_AGENT', "user_agent");
define('PHP_OPTION_DEFAULT_SOCKET_TIMEOUT', "default_socket_timeout");

/*  Image Format Markers */
define('MAGIC_BMP', "BM");
define('MAGIC_GIF_STRING87', "GIF87a");
define('MAGIC_GIF_STRING89', "GIF89a");
define('MAGIC_JPG', 3774863615);
define('MAGIC_JPG_STRING', "JFIF");
define('MAGIC_PNG', 727905341920923785);
define('MAGIC_RIFF_STRING', "RIFF");
define('MAGIC_WEBP_STRING', "WEBP");
define('MAGIC_VP8_STRING', "VP8");

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
$processed = array();
$timers = array();

$flag_log_initialized = 0;
$log_handle = null;


/* Start Initializing */
startTimer("program");
setItem("suppress_logfile", true);
setItem("project_name",PROJECT_NAME);
setItem("program_name",PROGRAM_NAME);
setItem("program_version",PROGRAM_MAJOR_VERSION . "." . PROGRAM_MINOR_VERSION . " (" . PROGRAM_BUILD . ")");
setItem("banner",getItem("project_name") . " (" .getItem("program_name") . ") v" . getItem("program_version"));
setItem("copyright",PROGRAM_COPYRIGHT);
setItem("configuration","defaults");

/*  Populate capabilities structure to determine what functions can be used */
determineCapabilities();

/*  
** Set Configuration Defaults
** setConfiguration($scope,$option,$value,$default,$type)
*/

if (getConfiguration("extensions","fileinfo") || getConfiguration("extensions","mimetype")) {
  setConfiguration("global","allow_octet_stream",DEFAULT_ALLOW_OCTET_STREAM_IF_FILEINFO_OR_MIMETYPE);
} else {
  setConfiguration("global","allow_octet_stream",DEFAULT_ALLOW_OCTET_STREAM);
}

setConfiguration("global","debug",false);
setConfiguration("global","acceptable_data_confidence",DEFAULT_ACCEPTABLE_DATA_CONFIDENCE);
setConfiguration("global","api",DEFAULT_ENABLE_APIS);
setConfiguration("global","blocklist",DEFAULT_ENABLE_BLOCKLIST);
setConfiguration("global","icon_size",DEFAULT_SIZE);
setConfiguration("global","tenacious",DEFAULT_TENACIOUS);
setConfiguration("global","showconfig",DEFAULT_SHOW_RUNNING_CONFIGURATION);
setConfiguration("debug","dump_file",DEFAULT_DEBUG_DUMP_FILE);
setConfiguration("debug","dump_structures",DEFAULT_DEBUG_DUMP_STRUCTURES);
setConfiguration("console","enabled",DEFAULT_LOG_CONSOLE_ENABLED);
setConfiguration("console","level",DEFAULT_LOG_LEVEL_CONSOLE);
setConfiguration("console","timestamp",DEFAULT_LOG_TIMESTAMP_CONSOLE);
setConfiguration("console","timestampformat",DEFAULT_LOG_TIMESTAMP_FORMAT);
setConfiguration("curl","enabled",getConfiguration("extensions","curl"));
setConfiguration("curl","verbose",false);
setConfiguration("curl","showprogress",false);
setConfiguration("files","check_local",DEFAULT_CHECKLOCAL);
setConfiguration("files","local_path",DEFAULT_LOCAL_PATH);
setConfiguration("files","overwrite",DEFAULT_OVERWRITE);
setConfiguration("files","store",DEFAULT_STORE);
setConfiguration("files","store_if_new",DEFAULT_STORE_IF_NEW);
setConfiguration("files","remove_tld",DEFAULT_REMOVE_TLD);
setConfiguration("http","default_useragent",DEFAULT_USER_AGENT);
setConfiguration("http","dns_timeout",DEFAULT_DNS_TIMEOUT);
setConfiguration("http","http_timeout",DEFAULT_HTTP_TIMEOUT);
setConfiguration("http","http_timeout_connect",DEFAULT_HTTP_CONNECT_TIMEOUT);
setConfiguration("http","try_homepage",DEFAULT_TRY_HOMEPAGE);
setConfiguration("http","maximum_redirects",DEFAULT_MAXIMUM_REDIRECTS);
setConfiguration("http","use_buffering",DEFAULT_USE_LOAD_BUFFERING);
setConfiguration("http","default_protocol_https",DEFAULT_PROTOCOL_IS_HTTPS);
setConfiguration("http","default_protocol_fallback",DEFAULT_FALLBACK_IF_HTTPS_NOT_AVAILABLE);
setConfiguration("logging","append",DEFAULT_LOG_APPEND);
setConfiguration("logging","enabled",DEFAULT_LOG_FILE_ENABLED);
setConfiguration("logging","level",DEFAULT_LOG_LEVEL_FILE);
setConfiguration("logging","pathname",DEFAULT_LOG_PATHNAME);
setConfiguration("logging","separator",DEFAULT_LOG_SEPARATOR);
setConfiguration("logging","short_separator",DEFAULT_LOG_SHORT_SEPARATOR);
setConfiguration("logging","timestamp",DEFAULT_LOG_TIMESTAMP_FILE);
setConfiguration("logging","timestampformat",DEFAULT_LOG_TIMESTAMP_FORMAT);
setConfiguration("mode","console",getCapability("php","console"));

/* Modify Configuration Depending on Other Options */
if (isset($_SERVER['SERVER_NAME'])) { setConfiguration("http","default_useragent",DEFAULT_USER_AGENT . " (+http://". $_SERVER['SERVER_NAME'] ."/)"); }
if (!DEFAULT_USE_CURL) { setConfiguration("curl","enabled",false); }

/*  Ensure Initial Configuration Is Setup Properly */
validateConfiguration();

/*  Special Config Load Options */
if (defined("ENABLE_SAME_FOLDER_API_INI")) {
  if (ENABLE_SAME_FOLDER_API_INI) {
    if (defined("DEFAULT_API_DATABASE")) {
      if (is_string(DEFAULT_API_DATABASE)) {
        if (file_exists(DEFAULT_API_DATABASE)) {
          $apiList = parse_ini_file(DEFAULT_API_DATABASE,true,INI_SCANNER_TYPED);
          setConfiguration("global","api_list",DEFAULT_API_DATABASE);
        }
      }
    }
  }
}

if (defined("ENABLE_SAME_FOLDER_INI")) {
  if (ENABLE_SAME_FOLDER_INI) {
    if (defined("DEFAULT_INI_FILE")) {
      if (is_string(DEFAULT_INI_FILE)) {
        if (file_exists(DEFAULT_INI_FILE)) {
          $configuration_from_file = parse_ini_file(DEFAULT_INI_FILE,true,INI_SCANNER_RAW);
          if (isset($configuration_from_file)) {
            if (is_array($configuration_from_file)) {
              $configuration = array_replace_recursive($configuration, $configuration_from_file);
              setItem("configuration",DEFAULT_INI_FILE);
              unset($configuration_from_file);
              validateConfiguration();
            }
          }
        }
      }
    }
  }
}


/*
**  If no APIs have been loaded, add Default APIs
**  addAPI($name,$url,$json,$enabled,$json_structure(),$display)
*/

if (empty($apiList)) { loadDefaultAPIs(); }

$display_name_API_list = getAPIList(true);
$display_API_list = getAPIList();

/*  Override Options for command line or HTTP */
if (getConfiguration("mode","console")) { 
  /*  Command Line Options */
  $script_name = basename(__FILE__);
  
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
    "sites::",
    "blocklist::",
    "path::",
    "config::",
    "configfile::",
    "validtypes::",
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
    "apiconfig::",
    "apiconfigfile::",
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
    "storeifnew",
    "checklocal",
    "nochecklocal",
    "save",
    "nosave",
    "saveifnew",
    "removetld",
    "noremovetld",
    "overwrite",
    "nooverwrite",
    "bufferhttp",
    "nobufferhttp",
    "allowoctetstream",
    "disallowoctetstream",
    "skip",
    "nocurl",
    "curl-verbose",
    "consolemode",
    "noconsolemode",
    "tenacious",
    "notenacious",
    "showconfig",
    "noshowconfig",
    "debug",
    "silent",
    "showconfigonly",
    "help",
    "version",
    "ver",
  );

  $options = getopt($shortopts, $longopts);

  /*  Process Special Modes, These All Exit! */
  /*  Show Version & Exit */
  if ((isset($options['v'])) || (isset($options['ver'])) || (isset($options['version']))) {
    echo getItem("banner") . "\n";
    echo getItem("copyright") . "\n";
    exit;
  }

  /*  Show Help & Exit */
  if ((isset($options['help'])) || (isset($options['h'])) || (isset($options['?']))) {
    echo "Usage: $script_name (Switches)\n";
    echo "\n";
    echo "Available APIs: $display_API_list (" . getConfiguration("global","api_list","internal") . ")\n";
    echo "Lists can be separated with space, comma or semi-colon.\n";
    echo "\n";
    echo "--configfile=FILE           Pathname to read for configuration.\n";
    echo "--apiconfigfile=FILE        Pathname to read for APIs.\n";
    echo "--list=FILE/LIST            Pathname or a delimited list of URLs to check.\n";
    echo "--blocklist=FILE/LIST       Pathname or a delimited list of MD5 hashes to block.\n";
    echo "--validtypes=FILE/LIST      Valid icon types (default is " . DEFAULT_VALID_EXTENSIONS . ")\n";
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
    echo "--checklocal                Check local icons. (default is ". showBoolean(DEFAULT_CHECKLOCAL) . ")\n";
    echo "--nochecklocal              Do not check local icons.\n";
    echo "--storeifnew                Store favicon if new (default is ". showBoolean(DEFAULT_STORE_IF_NEW) . ")\n";
    echo "--overwrite                 Overwrite local favicons. (default is ". showBoolean(DEFAULT_OVERWRITE) . ")\n";
    echo "--skip                      Skip local favicons.\n";
    echo "--removetld                 Remove top level domain from filename. (default is " . showBoolean(DEFAULT_REMOVE_TLD) . ")\n";
    echo "--noremovetld               Don't remove top level domain from filename.\n";
    echo "--tenacious                 Try all enabled APIs until success. (default is " . showBoolean(DEFAULT_TENACIOUS) . ")\n";
    echo "--notenacious               Try a random API.\n";
    echo "--showconfig                Show running configuration. (default is " . showBoolean(DEFAULT_SHOW_RUNNING_CONFIGURATION) . ")\n";
    echo "--noshowconfig              Skip showing running configuration.\n";
    echo "--allowoctetstream          Allow MimeType 'application/octet-stream'. (default is " . showBoolean(DEFAULT_ALLOW_OCTET_STREAM) . ")\n";
    echo "--disallowoctetstream       Block MimeType 'application/octet-stream' for icons.\n";
    echo "--consolemode               Force console output.\n";
    echo "--noconsolemode             Force HTML output.\n";
    echo "--debug                     Enable debug mode.\n";
    echo "--silent                    Disable console output (Ignored in HTML mode.)\n";
    echo "--help                      This listing and exit.\n";
    echo "--showconfigonly            Show running configuration and exit. (Assumes --showconfig).\n";
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
  **  Aliased Options
  */

  if (isset($options['curl-timeout'])) { $options['http-timeout'] = $options['curl-timeout']; }
  if (isset($options['p'])) { $options['path'] = $options['p']; } 
  if (isset($options['l'])) { $options['list'] = $options['l']; }
  if (isset($options['b'])) { $options['blocklist'] = $options['b']; }
  if (isset($options['config'])) { $options['configfile'] = $options['config']; }
  if (isset($options['apiconfig'])) { $options['apiconfigfile'] = $options['apiconfig']; }
  if (isset($options['c'])) { $options['configfile'] = $options['c']; }
  if (isset($options['sites'])) { $options['list'] = $options['sites']; } 
  if (isset($options['save'])) { $options['store'] = $options['save']; } 
  if (isset($options['saveifnew'])) { $options['storeifnew'] = $options['saveifnew']; } 
  if (isset($options['nosave'])) { $options['nostore'] = $options['nosave']; } 
  if (isset($options['skip'])) { $options['nooverwrite'] = $options['skip']; } 


  /*
  **  Load Configuration File
  **
  **  The way the config file works is it's parsed by PHP and merged, directly, with the configuration array structure.
  **  This is why calling validateConfiguration() is important.
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
  **  Load API Config
  */
  if (isset($options['apiconfigfile'])) {
    if (!is_null($options['apiconfigfile'])) {
      if (file_exists($options['apiconfigfile'])) {
        $apiList = parse_ini_file($options['apiconfigfile'],true,INI_SCANNER_TYPED);
        if (empty($apiList)) {
          loadDefaultAPIs();
        } else {
          setConfiguration("global","api_list",$options['apiconfigfile']);
        }
        $display_name_API_list = getAPIList(true);
        $display_API_list = getAPIList();
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
  setConfiguration("files","check_local",(isset($options['checklocal']))?$options['checklocal']:null,(isset($options['nochecklocal']))?$options['nochecklocal']:null,CONFIG_TYPE_SWITCH_PAIR);
  setConfiguration("files","local_path",(isset($options['path']))?$options['path']:null,null,CONFIG_TYPE_PATH);
  setConfiguration("files","store",(isset($options['store']))?$options['store']:null,(isset($options['nostore']))?$options['nostore']:null,CONFIG_TYPE_SWITCH_PAIR);
  setConfiguration("files","store_if_new",(isset($options['storeifnew']))?$options['storeifnew']:null,null,CONFIG_TYPE_SWITCH);
  setConfiguration("files","overwrite",(isset($options['overwrite']))?$options['overwrite']:null,(isset($options['nooverwrite']))?$options['nooverwrite']:null,CONFIG_TYPE_SWITCH_PAIR);
  setConfiguration("files","remove_tld",(isset($options['removetld']))?$options['removetld']:null,(isset($options['noremovetld']))?$options['noremovetld']:null,CONFIG_TYPE_SWITCH_PAIR);
  setConfiguration("global","allow_octet_stream",(isset($options['allowoctetstream']))?$options['allowoctetstream']:null,(isset($options['disallowoctetstream']))?$options['disallowoctetstream']:null,CONFIG_TYPE_SWITCH_PAIR);
  setConfiguration("global","blocklist",(isset($options['enableblocklist']))?$options['enableblocklist']:null,(isset($options['disableblocklist']))?$options['disableblocklist']:null,CONFIG_TYPE_SWITCH_PAIR);
  setConfiguration("global","debug",(isset($options['debug']))?$options['debug']:null,null,CONFIG_TYPE_SWITCH);
  setConfiguration("global","icon_size",(isset($options['size']))?$options['size']:null);
  setConfiguration("global","showconfig",(isset($options['showconfig']))?$options['showconfig']:null,(isset($options['noshowconfig']))?$options['noshowconfig']:null,CONFIG_TYPE_SWITCH_PAIR);
  setConfiguration("global","tenacious",(isset($options['tenacious']))?$options['tenacious']:null,(isset($options['notenacious']))?$options['notenacious']:null,CONFIG_TYPE_SWITCH_PAIR);
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
  if (isset($options['silent'])) { if (getConfiguration("mode","console")) { setConfiguration("console","enabled",false); } }

  /*  Configuration Validate */
  validateConfiguration();
} else {
  /*  HTML Mode */
  $script_name = basename($_SERVER['PHP_SELF']);
  
  if (defined("ENABLE_WEB_INPUT")) {
    if (ENABLE_WEB_INPUT) {
      
      var_dump($_GET);
      echo "***\n";
      var_dump($_POST);
      echo "***\n";
      
      /*  Configuration Validate */
      validateConfiguration();  
    }
  }
}

/*
**
**  Begin Logging
**    Console and/or File
**
*/

setItem("suppress_logfile", false);
writeLog(getItem("banner"),TYPE_ALL);
writeLog(getItem("copyright"),TYPE_ALL);
writeLog("PHP Version: " . getCapability("php","version"),TYPE_VERBOSE);
if (debugMode()) { writeLog("Running Debug Mode",TYPE_VERBOSE); }
if (isset($options['silent'])) { if (getConfiguration("mode","console")) { writeLog("Console Output Has Been Disabled",TYPE_NOTICE); } }

/*  Warn If Capabilities Are Too Weak or Broken */
if ((!getConfiguration("extensions","exif")) && (!getConfiguration("extensions","fileinfo")) && (!getConfiguration("extensions","mimetype"))) { writeLog("Your configuration or PHP installation is reporting exif, fileinfo and mimetype as unavailable, results will be impaired.",TYPE_WARNING); }
if ((!getConfiguration("extensions","curl")) && (!getConfiguration("extensions","get"))) { if (!ini_get(PHP_OPTION_ALLOW_URL_FOPEN)) { writeLog("Your configuration or PHP installation does not have cURL or file_get_contents avaialble and the PHP option '" . PHP_OPTION_ALLOW_URL_FOPEN . "' is disabled, web access is essentially disabled.",TYPE_WARNING); } }
            
/*  
**  Process Lists
*/

$URLList = loadList((isset($options['list']))?$options['list']:null);
$blockList = loadList((isset($options['blocklist']))?$options['blocklist']:null);
$enabledAPIList = loadList((isset($options['enableapis']))?$options['enableapis']:null);
$disabledAPIList = loadList((isset($options['disableapis']))?$options['disableapis']:null);

if (isset($options['validtypes'])) { setConfiguration("global","valid_types",loadList($options['validtypes'])); }
if (empty(getConfiguration("global","valid_types"))) { setConfiguration("global","valid_types",loadList(DEFAULT_VALID_EXTENSIONS)); }

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

/* Validate URL List */
writeLog("Validating URL List",TYPE_DEBUGGING);
validateURLList();

/*  Set PHP User Agent/Timeouts if Required */
writeLog("Setting PHP User Agent and Timeouts",TYPE_DEBUGGING);
initializePHPAgent();

/*  Final Configuration Validate */
validateConfiguration();

/*  Show Capabilities */
if (debugMode()) {
  writeLog(getConfiguration("logging","short_separator"),TYPE_VERBOSE);
  showCapabilities(TYPE_VERBOSE);
  writeLog(getConfiguration("logging","short_separator"),TYPE_VERBOSE);
}

/*  Show Configuration Options */
$flag_show_config = getConfiguration("global","showconfig");
if ((isset($options['showconfigonly'])) && (!$flag_show_config)) {
  writeLog("Show configuration setting of " . showBoolean(getConfiguration("global","showconfig")) . " ignored due to --showconfigonly option",TYPE_NOTICE);
  $flag_show_config = true;
}
   
if ($flag_show_config) {
  writeLog("All Lists Loaded:",TYPE_VERBOSE);
  writeLog("-> APIs Loaded: $display_API_list (Source: " . getConfiguration("global","api_list") . ")",TYPE_VERBOSE);
  writeLog("-> Valid Icon Types: " . getValidTypes(),TYPE_VERBOSE);
  writeLog(getConfiguration("logging","short_separator"),TYPE_VERBOSE);
  writeLog("Options:",TYPE_VERBOSE);
  writeLog("-> Local Path: '" . getConfiguration("files","local_path") . "'",TYPE_VERBOSE);
  writeLog("-> Global Settings: Icon Size: " . getConfiguration("global","icon_size") . ", Tenacious? " . showBoolean(getConfiguration("global","tenacious")) . ", Allow Octet-Stream? " . showBoolean(getConfiguration("global","allow_octet_stream")),TYPE_VERBOSE);
  writeLog("-> File Settings: Save? " . showBoolean(getConfiguration("files","store")) . ", Overwrite? " . showBoolean(getConfiguration("files","overwrite")) . ", Remove TLD? " . showBoolean(getConfiguration("files","remove_tld")) . ", Check Icons? " . showBoolean(getConfiguration("files","check_local")) . ", Store if New? " . showBoolean(getConfiguration("files","store_if_new")),TYPE_VERBOSE);
  writeLog("-> HTTP Settings: Use Buffering? " . showBoolean(getConfiguration("http","use_buffering")) . ", Timeout? " . getConfiguration("http","http_timeout") . ", Connect Timeout? " . getConfiguration("http","http_timeout_connect") . ", DNS Timeout? " . getConfiguration("http","dns_timeout") . ", Try Homepage? " . showBoolean(getConfiguration("http","try_homepage")),TYPE_VERBOSE);
  if (getConfiguration("curl","enabled")) { writeLog("-> cURL is Enabled",TYPE_VERBOSE); } else { writeLog("-> cURL is Disabled",TYPE_VERBOSE); }
  if (getConfiguration("global","api")) { writeLog("-> API Use is Enabled",TYPE_VERBOSE); } else { writeLog("-> API Use is Disabled",TYPE_VERBOSE); }
  if (isset($options['showconfigonly'])) { 
    writeLog("Exiting due to --showconfigonly option",TYPE_NOTICE);
    exit;
  } else {
    writeLog(getConfiguration("logging","short_separator"),TYPE_VERBOSE);
  }
}

/*  Do the Thing */
addStatistic("process_counter", 0);
if (empty($URLList)) {
  addStatistic("fetch", 0);  
  addStatistic("fetched", 0);
} else {
  addStatistic("fetch", count($URLList));  
  addStatistic("fetched", 0);
  
  writeLog("Looking for " . getStatistic("fetch") . " Icons",TYPE_VERBOSE);

  /*  Process List */
  foreach ($URLList as $url) {
    $favicons[] = grap_favicon($url);
  }

  /*  Show Results */
  foreach ($favicons as $favicon) {
    if (!empty($favicon)) { 
      if (file_exists($favicon)) {
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
}

$elapsedTime = stopTimer("program",true);
if ($elapsedTime > 0) { $elapsedTime = round($elapsedTime,2); }

/*  Show Runtime and Statistics */
if (getStatistic("fetch") > 0) {
  writeLog("Found " . getStatistic("fetched") . " Icons out of " . getStatistic("fetch") . " Requested",TYPE_NOTICE);
  if ($elapsedTime > 0) { writeLog("Runtime: $elapsedTime second(s)",TYPE_NOTICE); }
  writeLog("Processing Complete",TYPE_NOTICE);
} else {
  writeLog("No URLs were provided",TYPE_NOTICE);
}

/*  Debug Dumps */
debugDumpStructures();


/*****************************************************
                FUNCTIONS
*****************************************************/
function grap_favicon($url) {
  incrementStatistic("process_counter");
  $instanceName = "grap_favicon(" . getStatistic("process_counter") . ")";
  debugSection($instanceName);
  startTimer($instanceName);
  
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
  $gotMethod    = null;
  $attemptCount = 0;
  
  $filePath = null;
  setGlobal('redirect_count',0);
  setGlobal('redirect_url',null);

  if (!$consoleMode) {
    // avoid script runtime timeout
    $max_execution_time = ini_get(PHP_OPTION_MAX_EXECUTION_TIME);
    if ($max_execution_time > 0) { set_time_limit(0);  }
  }
  
  if (addProcessEntry($url)) {
    writeLog("Starting Processing For: '$url'",TYPE_DEBUGGING);

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
      if (empty($domainParts)) {
        $core_domain = $domain;
      } else {
        if (count($domainParts) >= 2) {
          $www_key = array_search("www",$domainParts);
          if ($www_key !== false) {
            unset($domainParts[$www_key]);
            $domain = implode('.', $domainParts);
          }
          $temp = $domainParts;
          array_pop($temp);
          $core_domain = implode('.', $temp);
        } else {
          $core_domain = $domain;
        }
      }
    }

    
    # TO DO:
    #   if option is enabled:
    #     check if icon is on disk
    #       get content type
    #       get hash
    #       test against icon and block lists
    #   
    writeLog(showValue("url",$url) . ", " . showValue("protocol",$protocol) . ", " . showValue("domain",$domain) . ", " . showValue("core",$core_domain),TYPE_DEBUGGING);

    if ($trySelf) {
      $method = "direct";
      
      // Try Direct Load
      if (isIconWanted($url)) {
        $favicon = addFavIconToURL($url);
        writeLog("Direct Load Attempt using '$favicon'",TYPE_DEBUGGING);
        $attemptCount++;
        $flag_accepted = checkIconAcceptance($favicon);
        if ($flag_accepted) {
          updateProcessEntry($url,"accepted",true);
          updateProcessEntry($url,"state",STATE_FOUND);
          writeLog("Direct Load Attempt using '$favicon' succeeded",TYPE_DEBUGGING);
        } else {
          updateProcessEntry($url,"accepted",false);
          updateProcessEntry($url,"state",STATE_WANTED);
          writeLog("Direct Load Attempt using '$favicon' failed",TYPE_DEBUGGING);
          unset($favicon);
        }
      }
      
      # TO DO:
      #   Change to use isIconWanted
      if (empty($favicon)) {
        // Load Page
        $content = load($url);
        if (isLastLoadResultValid()) {
          $http_code = getLastLoadResult("http_code");
          $RequestData = lookupHTTPResponse($http_code);
          if ($RequestData['ok']) {
            $content_type = getLastLoadResult("content_type");
            if (is_null($content_type)) {
              writeLog("url='$url', last load contains content_type: null",TYPE_TRACE);
            } else {
              if (is_string($content_type)) {
                writeLog("url='$url', last load contains content_type: $content_type)",TYPE_TRACE);
              } else {
                writeLog("url='$url', last load contains content_type: " . gettype($content_type),TYPE_TRACE);
              }
            }
          } else {
            writeLog("url='$url', last load result returned an error=" . $RequestData['code'] . " (" . $RequestData['description'] . ")",TYPE_TRACE);
            if (is_null($http_code)) {
              $content_type = getLastLoadResult("content_type");
              if (is_null($content_type)) {
                writeLog("no http code or content_type returned from server",TYPE_TRACE);
              } else {
                writeLog("no http code returned from server",TYPE_TRACE);
              }
            } else {
              $content = null;
              writeLog("server response was " . $RequestData['code'] . " " . $RequestData['description'],TYPE_TRACE);
            }
          }
        } else {
          writeLog("url='$url', last load result was invalid",TYPE_TRACE);
        }
        if (is_null($content)) {
          writeLog("No data received",TYPE_DEBUGGING);
        } else {
          if (is_null($content_type)) {
            if (isHTML($content)) { $content_type = "text/html"; }
          } else {
            if ($content_type == "text/plain") { if (isHTML($content)) { $content_type = "text/html"; } }
          }
          if (is_null($content_type)) { 
            writeLog("Unable to identify content type",TYPE_TRACE);
          } elseif ($content_type == "text/html") {
            $method = "regex";
            writeLog("Examining Web Page for Icons",TYPE_DEBUGGING);
            writeLog("Attempting RegEx Match",TYPE_DEBUGGING);
            $regExPattern = '/((<link[^>]+rel=.(icon|shortcut\sicon|alternate\sicon)[^>]+>))/i';
            if (@preg_match($regExPattern, $content, $matchTag)) {
              writeLog("RegEx Initial Pattern Matched",TYPE_DEBUGGING);
              writeLog(print_r($matchTag,true),TYPE_SPECIAL);
              $regExPattern = '/href=(\'|\")(.*?)\1/i';
              if (isset($matchTag[0]) && @preg_match($regExPattern, $matchTag[0], $matchUrl)) {
                writeLog("RegEx Secondary Pattern Matched",TYPE_DEBUGGING);
                writeLog(print_r($matchUrl,true),TYPE_SPECIAL);
                if (isset($matchUrl[2])) {
                  writeLog("Found Match, Building Link",TYPE_DEBUGGING);
                  $favicon = convertRelativeToAbsolute(trim($matchUrl[2]), $protocol . '://'.$domain.'/', $url_port);
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
          } else {
            writeLog("RegEx Skipped, Did Not Receive HTML",TYPE_DEBUGGING);
          }
        }

        // If there is no Match: Try if there is a Favicon in the Root of the Domain
        if (empty($favicon)) {
          $method = "direct/root";
          $favicon = addFavIconToURL($protocol . '://'.$domain);
          writeLog("Attempting Direct Match using '$favicon'",TYPE_DEBUGGING);

          // Try to Load Favicon
          # if ( !@getimagesize($favicon) ) {
          # https://www.php.net/manual/en/function.getimagesize.php
          # Do not use getimagesize() to check that a given file is a valid image.
          $attemptCount++;
          $fileExtension = getIconExtension($favicon,false);
          if (!$fileExtension['valid']) {
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
        $selectedAPIList = array();
        $api_attempt = 0;
        $api_max_attempts = 0;
        
        if (($api_count > 1) && (getConfiguration("global","tenacious"))) {
          writeLog("Randomly Trying Up To $api_count APIs",TYPE_DEBUGGING);
          $selectedAPIList = getAPIIndex();
          if (!empty($selectedAPIList)) {
            shuffle($selectedAPIList);
          }
          $api_max_attempts = count($selectedAPIList);
        }
        # If tenacious doesn't get a list (or there's just one api) fallback to regular
        if (empty($selectedAPIList)) {
          writeLog("Selecting API",TYPE_DEBUGGING);
          $api_max_attempts = 1;
          array_push($selectedAPIList,getRandomAPIName());
        }
        foreach ($selectedAPIList as $selectedAPI) {
          $method = "api";
          $api_valid = true;
          $flag_accepted = false;
          
          writeLog("Attempting To Load API Record: '$selectedAPI'",TYPE_SPECIAL);
          $selectAPI = getAPI($selectedAPI);
          
          if (isset($selectAPI['display'])) { $api_display = $selectAPI['display']; } else { $api_display = null; }
          if (isset($selectAPI['name'])) { $api_name = $selectAPI['name']; } else { $api_name = null; }
          if (isset($selectAPI['url'])) { $api_url = $selectAPI['url']; } else { $api_url = null; }
          if (isset($selectAPI['json'])) { $api_json = $selectAPI['json']; } else { $api_json = null; }
          if (isset($selectAPI['enabled'])) { $api_enabled = $selectAPI['enabled']; } else { $api_enabled = null; }
          if (isset($selectAPI['json_structure'])) { $api_json_structure = $selectAPI['json_structure']; } else { $api_json_structure = null; }
          if (isset($selectAPI['apikey'])) { $api_key = $selectAPI['apikey']; } else { $api_key = null; }
          
          if ((is_null($api_name)) || (is_null($api_url)) || (is_null($api_enabled)) || (is_null($api_json))) { $api_valid = false; }
          
          if ($api_valid) {
            $api_attempt++;
            $favicon = null;
            if ($api_enabled) {
              $method = "api:$api_name";
              if (is_null($api_display)) { $api_display = $api_name; }
              $attemptCount++;
              if ($api_max_attempts > 1) { $section_prefix = "$api_attempt: "; } else { $section_prefix = "API: ";  }
              writeLog($section_prefix . "$method: Selected '$api_display'",TYPE_DEBUGGING);
              $api_request = getAPIurl($api_url,$domain,$iconSize,$api_key);
              if (isset($api_request)) {
                if ($api_json) {
                  writeLog($section_prefix . "$method: Getting JSON Data for '$api_request'",TYPE_DEBUGGING);
                  $api_data = load($api_request);
                  if (isLastLoadResultValid()) {
                    $http_code = getLastLoadResult("http_code");
                    $RequestData = lookupHTTPResponse($http_code);
                    if (!$RequestData['ok']) {
                      writeLog($section_prefix . "$method: API returned an error=" . $RequestData['code'] . " (" . $RequestData['description'] . ")",TYPE_TRACE);
                      $api_data = null;
                    }
                  } else {
                    $api_data = null;
                  }
                  if (is_null($api_data)) {
                    $json_data = null;
                  } else {
                    $json_data = json_decode($api_data,true);
                  }
                  if (!is_null($json_data)) {
                    if (!empty($api_json_structure)) {
                      $api_data_error = null;
                      if (isset($api_json_structure['error'])) {
                        if (isset($json_data['error'])) { $api_data_error = $api_json_structure['error']; }
                      }
                      if (!is_null($api_data_error)) {
                        if (is_array($api_data_error)) { $api_data_error = json_encode($api_data_error); }
                        writeLog($section_prefix . "$method: API Error: '$api_data_error'",TYPE_TRACE);
                      } else {
                        $icon_counter = 0;
                        if (isset($api_json_structure['icons'])) {
                          $icon_block = $json_data['icons'];
                        } else {
                          $icon_block = $json_data;
                        }
                        if (is_array($icon_block)) {
                          writeLog($section_prefix . "$method: API Returned " . count($icon_block) . " records",TYPE_TRACE);
                          foreach ($icon_block as $icon) {
                            $icon_counter++;
                            $api_temp = null;
                            $api_data_link = null;
                            $api_data_type = null;
                            $api_data_size = null;
                            $link_extension = null;
                            $flag_accepted = false;
                            $flag_size_ok = true;
                            if (isset($api_json_structure['link'])) { if (isset($icon[$api_json_structure['link']])) { $api_data_link = $icon[$api_json_structure['link']]; } }
                            if (!is_string($api_data_link)) { $api_data_link = null; }
                            
                            if (!is_null($api_data_link)) {
                              if (isset($api_json_structure['size'])) { if (isset($icon[$api_json_structure['size']])) { $api_data_size = $icon[$api_json_structure['size']]; } }
                              if (isset($api_json_structure['mime'])) { if (isset($icon[$api_json_structure['mime']])) { $api_data_type = $icon[$api_json_structure['mime']]; } }
                              if (isset($api_json_structure['sizeWxH'])) { if (isset($icon[$api_json_structure['sizeWxH']])) { list($api_data_size,$api_discard) = explode("x",$icon[$api_json_structure['sizeWxH']]); } }
                              if (!is_string($api_data_type)) { $api_data_type = null; }
                              if (!is_numeric($api_data_size)) { $api_data_size = null; }
                  
                              if (!is_null($api_data_type)) {
                                if (!is_null($api_temp)) { $api_temp .= ", "; }
                                $api_temp .= "type=$api_data_type";
                              }
                              if (!is_null($api_data_size)) {
                                if (!is_null($api_temp)) { $api_temp .= ", "; }
                                $api_temp .= "size=$api_data_size"; 
                              }
                              if (is_null($api_temp)) {
                                writeLog($section_prefix . "$method: $icon_counter: $api_data_link",TYPE_TRACE);
                              } else {
                                writeLog($section_prefix . "$method: $icon_counter: $api_data_link ($api_temp)",TYPE_TRACE);
                              }
                              if (!is_null($api_data_size)) {
                                if (($iconSize > 0) && ($api_data_size > 0)) {
                                  if ($api_data_size < $iconSize) {
                                    $flag_size_ok = false;
                                  }
                                }
                              } 
                              if ($flag_size_ok) {
                                if (checkIconAcceptance($api_data_link)) {
                                  $favicon = $api_data_link;
                                  $flag_accepted = true;
                                  break;
                                } else {
                                  unset($favicon);
                                }
                              } else {
                                if ($iconSize > 0) {
                                  writeLog($section_prefix . "$method: $icon_counter: Icon is below requested size, rejected",TYPE_TRACE);
                                }
                              }
                            }
                          }
                        } else {
                          writeLog($section_prefix . "$method: JSON API Data Block Is Not An Array",TYPE_TRACE);
                        }
                      }
                    } else {
                      writeLog($section_prefix . "$method: JSON API Does Not Have Structure Defined, Attempting to Parse",TYPE_DEBUGGING);
                      //  Attempt Simple Parsing
                      if (is_string($json_data)) { 
                        $favicon = $json_data;
                      } elseif (is_array($json_data)) {
                        if (isset($json_data[0])) {
                          $favicon = $json_data[0];
                        }
                      }
                    }
                  } else {
                    if (is_null($api_data)) { 
                      writeLog($section_prefix . "$method: No JSON Data Returned",TYPE_DEBUGGING);
                    } else {
                      writeLog($section_prefix . "$method: Unable to Decode JSON Data",TYPE_DEBUGGING);
                    }
                    unset($favicon);
                  }
                } else {
                  //  Not a JSON Based API so the response should be the direct favicon URL
                  $favicon = $api_request;
                }
              } else { 
                writeLog($section_prefix . "$method: Could not create API Request",TYPE_DEBUGGING);
                unset($favicon);
              }
              if (isset($favicon)) {
                if (!is_null($favicon)) {
                  if (!$flag_accepted) {
                    writeLog($section_prefix . "$method: Checking Icon Acceptance for '$favicon'",TYPE_DEBUGGING);
                    if (checkIconAcceptance($favicon)) { $flag_accepted = true; }
                  }
                  if ($flag_accepted) {
                    writeLog($section_prefix . "$method: Request URL '$favicon' was successful",TYPE_DEBUGGING);  
                    break;
                  } else {
                    writeLog($section_prefix . "$method: Request URL '$favicon' did not return a valid icon or it was rejected",TYPE_DEBUGGING);
                    unset($favicon);
                  }
                } else {
                  writeLog($section_prefix . "$method: Icon URL is Null",TYPE_DEBUGGING);
                }
              }
            } else {
              writeLog($section_prefix . "$method: API Is Disabled!",TYPE_WARNING);
            }
          } else {
            writeLog($section_prefix . "Selected API Is Invalid!",TYPE_WARNING);
          }
        }
      } else {
        writeLog("No APIs Available",TYPE_WARNING);
      }
    } // END If nothing works: Get the Favicon from API

    //  Update Status
    if (isset($favicon)) {
      writeLog("Found Icon at '$favicon'",TYPE_DEBUGGING);
      
      updateProcessEntry($url,"favicon",$favicon);
      updateProcessEntry($url,"method",$method);
      updateProcessEntry($url,"tries",$attemptCount);
    
      if ($save) {
        // should it check to see if it's resident?
        unset($content);
        writeLog("Loading Icon To Store using '$favicon'",TYPE_DEBUGGING);

        //  Load Favicon
        $content = load($favicon);
        
        if (!isset($content)) {
          writeLog("Failed to load favicon using '$favicon'",TYPE_DEBUGGING);
        } else {
          $content_hash = md5($content);
          if (!is_null(getGlobal('redirect_url'))) { $favicon = getGlobal('redirect_url'); }
          # TO DO: 
          #   This needs to be done in the API processing section
          if (isset($api_name)) {
            if ($api_name == "google") {
              if ($content_hash == GOOGLE_DEFAULT_ICON_MD5) {
                $domain = 'default'; // so we don't save a default icon for every domain again
                writeLog("Got Google Default Icon",TYPE_DEBUGGING);
              }
            }
          }

          //  Get Type
          if (!empty($favicon)) {
            $fileData = getIconExtension($favicon);
            if (!$fileData['valid']) {
              writeLog("Icon Is Not Valid, Discarding '$favicon'",TYPE_DEBUGGING);
            } else {
              $fileExtension = $fileData['extension'];
              $fileContentType = $fileData['content_type'];
              $fileMethod = $fileData['method'];
              $fileConfidence = $fileData['confidence'];
              updateProcessEntry($url,"icontype",$fileContentType);
              updateProcessEntry($url,"hash",$content_hash);
              updateProcessEntry($url,"confidence",$fileConfidence);
              
              writeLog("Icon Is Valid ('$favicon'), ext=$fileExtension, type=$fileContentType, id_method=$fileMethod, method=$method",TYPE_DEBUGGING);

              # TO DO:
              #   handle subdirectories
              #   use getCapability("os","directory_separator")
              if ($removeTLD && !is_null($core_domain)) {
                $filePath = preg_replace('#\/\/#', '/', $directory.'/'.$core_domain.'.'.$fileExtension);
              } else {
                $filePath = preg_replace('#\/\/#', '/', $directory.'/'.$domain.'.'.$fileExtension);
              }
              updateProcessEntry($url,"local",$filePath);

              //  If overwrite, delete it
              if (file_exists($filePath)) {
                if ($overwrite) {
                  updateProcessEntry($url,"overwrite",true);
                  unlink($filePath);
                }
              }

              //  If file exists, skip
              if (file_exists($filePath)) {
                updateProcessEntry($url,"saved",false);
                writeLog("Skipping Storing Icon as '$filePath'",TYPE_DEBUGGING);
              } else {
                // Write
                $fh = @fopen($filePath, 'wb');
                if ($fh) {
                  fwrite($fh, $content);
                  fclose($fh);
                  writeLog("Stored Icon as '$filePath'",TYPE_DEBUGGING);
                  updateProcessEntry($url,"saved",true);
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
    } else {
      writeLog("Did not find icon for '$url'",TYPE_DEBUGGING);
    }

    // reset script runtime timeout
    if (!$consoleMode) { set_time_limit($max_execution_time); }

    //  Update Elapsed Time
    updateProcessEntry($url,"elapsed",stopTimer($instanceName,true));
  } else {
    writeLog("Skipping Processing For: '$url'",TYPE_DEBUGGING);
  }

  //  End Section
  debugSection();
  
  // Return Favicon Url
  return $filePath;

} // END MAIN Function

/*  Lookup HTTP Code */
/*  Some of these are unofficial but get-fav may run into them */
function lookupHTTPResponse($code = null) {
  $status_ok = false;
  $description = null;
  $http_code = "(null)";
  $response_type = HTTP_RESPONSE_TYPE_NONE;

  if (!is_null($code)) {
    if (is_numeric($code)) {
      if ($code >= RANGE_HTTP_RESPONSE_MINIMUM && $code <= RANGE_HTTP_RESPONSE_MAXIMUM) {
        $http_code = $code;
        
        if ($code >= 100 && $code < 200) { $response_type = HTTP_RESPONSE_TYPE_INFORMATIONAL; }
        if ($code >= 200 && $code < 300) { $response_type = HTTP_RESPONSE_TYPE_SUCCESS; }
        if ($code >= 300 && $code < 400) { $response_type = HTTP_RESPONSE_TYPE_REDIRECT; }
        if ($code >= 400 && $code < 500) { $response_type = HTTP_RESPONSE_TYPE_CLIENT_ERROR; }
        if ($code >= 500 && $code < 600) { $response_type = HTTP_RESPONSE_TYPE_SERVER_ERROR; }
        
        if ($response_type == HTTP_RESPONSE_TYPE_INFORMATIONAL) { $status_ok = true; }
        if ($response_type == HTTP_RESPONSE_TYPE_SUCCESS) { $status_ok = true; }
        if ($response_type == HTTP_RESPONSE_TYPE_REDIRECT) { $status_ok = true; }

        switch ($code) {
          case 100: $description = "Continue"; break;
          case 101: $description = "Switching Protocols"; break;
          case 102: $description = "Processing (WebDAV)"; break;
          case 103: $description = "Early Hints"; break;
          case 200: $description = "OK"; break;
          case 201: $description = "Created"; break;
          case 202: $description = "Accepted"; break;
          case 203: $description = "Non-Authoritative Information"; break;
          case 204: $description = "No Content"; break;
          case 205: $description = "Reset Content"; break;
          case 206: $description = "Partial Content"; break;
          case 207: $description = "Multi-Status (WebDAV)"; break;
          case 208: $description = "Already Reported (WebDAV)"; break;
          case 226: $description = "IM Used (HTTP Delta encoding)"; break;
          case 300: $description = "Multiple Choices"; break;
          case 301: $description = "Moved Permanently"; break;
          case 302: $description = "Found"; break;
          case 303: $description = "See Other"; break;
          case 304: $description = "Not Modified"; break;
          case 305: $description = "Use Proxy"; break;
          case 306: $description = "Switch Proxy"; break;
          case 307: $description = "Temporary Redirect"; break;
          case 308: $description = "Permanent Redirect"; break;
          case 400: $description = "Bad Request"; break;
          case 401: $description = "Unauthorized"; break;
          case 402: $description = "Payment Required"; break;
          case 403: $description = "Forbidden"; break;
          case 404: $description = "Not Found"; break;
          case 405: $description = "Method Not Allowed"; break;
          case 406: $description = "Not Acceptable"; break;
          case 407: $description = "Proxy Authentication Required"; break;
          case 408: $description = "Request Time-out"; break;
          case 409: $description = "Conflict"; break;
          case 410: $description = "Gone"; break;
          case 411: $description = "Length Required"; break;
          case 412: $description = "Precondition Failed"; break;
          case 413: $description = "Payload Too Large"; break;
          case 414: $description = "URI Too Long"; break;
          case 415: $description = "Unsupported Media Type"; break;
          case 416: $description = "Range Not Satisfiable"; break;
          case 417: $description = "Expectation Failed"; break;
          case 418: $description = "I'm a teapot"; break;
          case 421: $description = "Misdirected Request"; break;
          case 422: $description = "Unprocessable Content (WebDAV)"; break;
          case 423: $description = "Locked (WebDAV)"; break;
          case 424: $description = "Failed Dependency (WebDAV)"; break;
          case 425: $description = "Too Early"; break;
          case 426: $description = "Upgrade Required"; break;
          case 428: $description = "Precondition Required"; break;
          case 429: $description = "Too Many Requests"; break;
          case 431: $description = "Request Header Fields Too Large"; break;
          case 444: $description = "No Response"; break;
          case 451: $description = "Unavailable For Legal Reasons"; break;
          case 500: $description = "Internal Server Error"; break;
          case 501: $description = "Not Implemented"; break;
          case 502: $description = "Bad Gateway"; break;
          case 503: $description = "Service Unavailable"; break;
          case 504: $description = "Gateway Timeout"; break;
          case 505: $description = "HTTP Version Not Supported"; break;
          case 506: $description = "Variant Also Negotiates"; break;
          case 507: $description = "Insufficient Storage (WebDAV)"; break;
          case 508: $description = "Loop Detected (WebDAV)"; break;
          case 509: $description = "Bandwidth Limit Exceeded"; break;
          case 510: $description = "Not Extended"; break;
          case 511: $description = "Network Authentication Required"; break;
          case 521: $description = "Web Server Is Down"; break;
          case 522: $description = "Connection Timed Out"; break;
          case 523: $description = "Origin Is Unreachable"; break;
          default:
            $description = "Unknown";
        }
      } else {
        $description = "Invalid Response; Out of Range ($code)";
        $http_code = "(out of range)";
      }
    } else {
      $description = "Response Not Numeric";
      $http_code = "(invalid)";
    }
  } else {
    $description = "Response Is Null";
    $http_code = "(null)";
  }
  $retval = array(
    "ok" => $status_ok,
    "description" => $description,
    "code" => $http_code,
    "type" => $response_type,
  );
  return $retval;
}

/*  Adds the favicon path to the URL */
function addFavIconToURL($url) {
  debugSection("addFavIconToURL");
  if (is_string($url)) {
    if (strlen($url) > 0) {
      $temp = strrev($url);
      if (substr($temp,0,1) != "/") { $url .= "/"; }
      $url .= URL_PATH_FAVICON;
    } else {
      writeLog("URL is " . strlen($url) . ") bytes long!",TYPE_TRACE);
    }
  } else {
    writeLog("URL is type (" . gettype($url) . ") instead of string!",TYPE_TRACE);
  }
  debugSection();
  return $url;
}

/*  Load URL */
function load($url) {
  debugSection("load");
  $content = null;
  writeLog(showValue("url",$url),TYPE_TRACE);
  if (!is_string($url)) {
    if (debugMode()) {
      writeLog("URL requested for load is type (" . gettype($url) . ") instead of string!",TYPE_ERROR);
      writeLog(print_r($url),TYPE_ERROR);
    }
  } else {
    $content_hash = null;
    $content_type = null;
    $content_type_extended = null;
    $http_code = null;
    $method = null;
    $previous_url = getGlobal('redirect_url');
    $protocol = parse_url($url,PHP_URL_SCHEME);
    $protocol_id = null;  
    $redirect_url = null;
    $redirect = getGlobal('redirect_count');
    if (!isset($redirect)) { $redirect = 0; }
    $flag_skip_loadlastresult = false;
    //  If no protocol is it's probably a local file.
    if (is_null($protocol)) {
      $content = loadLocalFile($url);
    } else {
      if (getConfiguration("http","use_buffering")) {
        if (isLastLoadResultValid()) {
          $lastLoadURL = getLastLoadResult('url');
          if (!is_null($lastLoadURL)) {
            if ($lastLoadURL == $url) {
              $content = getLastLoadResult('content');
              if (!is_null($content)) {
                $flag_skip_loadlastresult = true;
              }
            }
          }
        }
      }
    }
    if (is_null($content)) {
      writeLog("loading: " . showValue("url",$url),TYPE_TRACE);
      if (getConfiguration("curl","enabled")) {
        $method = "curl";
        writeLog("$method: " . showValue("Operation Timeout",getConfiguration("http","http_timeout")) . ", " . showValue("Connection Timeout",getConfiguration("http","http_timeout_connect")) . ", " . showValue("DNS Timeout",getConfiguration("http","dns_timeout")),TYPE_TRACE);
        $ch = curl_init($url);
        if (isset($ch)) {
          if (!is_null(getConfiguration("http","useragent"))) { curl_setopt($ch, CURLOPT_USERAGENT, getConfiguration("http","useragent")); }
          curl_setopt($ch, CURLOPT_VERBOSE, getConfiguration("curl","verbose"));
          curl_setopt($ch, CURLOPT_TIMEOUT, getConfiguration("http","http_timeout"));
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, getConfiguration("http","http_timeout_connect")); 
          curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, getConfiguration("http","dns_timeout")); 
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          if (getConfiguration("curl","showprogress")) { curl_setopt($ch, CURLOPT_NOPROGRESS, false); }
          $protocol = null;
          $protocol_id = null;
          $content = curl_exec($ch);
          $curl_response = curl_getinfo($ch);
          if (!empty($curl_response)) {
            if (isset($curl_response['content_type'])) { $content_type = $curl_response['content_type']; }
            if (isset($curl_response['http_code'])) { $http_code = $curl_response['http_code']; }
            if (isset($curl_response['url'])) { $redirect_url = $curl_response['url']; }
            if (isset($curl_response['scheme'])) { $protocol = $curl_response['scheme']; }
            if (isset($curl_response['protocol'])) { $protocol_id = $curl_response['protocol']; }
            if (is_null($protocol)) { $protocol = parse_url($url,PHP_URL_SCHEME); }
            curl_close($ch);
            unset($ch);
          } else {
            writeLog("$method: No response from cURL",TYPE_TRACE);
          }
        } else {
          writeLog("$method: Error initializing cURL",TYPE_TRACE);
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
        if (!getConfiguration("extensions","get")) {
          writeLog("$method: attempting to load '$url'",TYPE_TRACE);
          if (ini_get(PHP_OPTION_ALLOW_URL_FOPEN)) {
            $fh = @fopen($url, 'r', false, $context);
            if ($fh) {
              $content = "";
              while (!feof($fh)) {
                $content .= fread($fh, BUFFER_SIZE);
              }
              fclose($fh);
            } else {
              writeLog("Failed to open '$url'",TYPE_TRACE);
            }
          } else {
            writeLog("$method: attempting to load '$url' but PHP option '" . PHP_OPTION_ALLOW_URL_FOPEN . "' is set to false",TYPE_ERROR);
          }
        } else {
          $method .= ":file_get_contents";
          writeLog("$method: attempting to load '$url'",TYPE_TRACE);
          $content = @file_get_contents($url, null, $context);
        }
        if (is_null($content)) {
          writeLog("$method: No content received '$url'",TYPE_TRACE);
        }
        if (!is_null($http_response_header)) {
          $headers = implode("\n", $http_response_header);
          # TO DO:
          # this needs better coding
          #   are redirects supported?
          #     what response header has the url to try?
          /* NOTE: $http_response_header is a special variable (array) that PHP provides */
          if (preg_match_all("/^HTTP.*\s+([0-9]+)/mi", $headers, $matches )) {
            $http_code = end($matches[1]);
          }
        }
      }
      
      # Common
      $RequestData = lookupHTTPResponse($http_code);
      writeLog("$method: Return Code=" . $RequestData['code'] . " (" . $RequestData['description'] . ") for '$url', OK? (" . showBoolean($RequestData['ok']) . "), " . showValue("content_type",$content_type),TYPE_TRACE);
      if (is_null($http_code)) {
        writeLog("$method: No HTTP return code received for '$url'",TYPE_TRACE);
      } else {
        if ($RequestData['type'] == HTTP_RESPONSE_TYPE_REDIRECT) {
          if (!is_null($redirect_url)) {
            $redirect++;
            if ($redirect < getConfiguration("http","maximum_redirects"))
            {
              writeLog("$method: Redirecting to '$redirect_url' from '$url' (# $redirect)",TYPE_TRACE);
              setGlobal('redirect_count',$redirect);
              setGlobal('redirect_url',$redirect_url);
              $content = load($redirect_url);
              $flag_skip_loadlastresult = true;
            } else {
              writeLog("$method: Too many redirects ($redirect)",TYPE_TRACE);
            }
          } else {
            writeLog("$method: Got redirect response from server but no URL provided",TYPE_TRACE);
          }
        } elseif ($RequestData['type'] == HTTP_RESPONSE_TYPE_CLIENT_ERROR) {
          writeLog("Server for '$url' Responded With '" . $RequestData['description'] . "' (" . $RequestData['code'] . ")",TYPE_WARNING);
        } elseif ($RequestData['type'] == HTTP_RESPONSE_TYPE_SERVER_ERROR) {
          writeLog("Server for '$url' Responded With '" . $RequestData['description'] . "' (" . $RequestData['code'] . ")",TYPE_ERROR);
        }
      }        
      if (is_null($content)) {
        writeLog("$method: No content received '$url'",TYPE_TRACE);
        $content_type = null;
      } else {
        if (!is_null($content_type)) { if (!is_string($content_type)) { writeLog("$method: content_type is not a string (type=" . gettype($content_type) . ")" ,TYPE_TRACE); $content_type = null; }  }
        if (is_null($content_type)) {
          writeLog("$method: content_type is reported null, attempting to determine",TYPE_TRACE);
        } else {
          list($content_type,$content_type_extended) = explode(";",$content_type);
          $content_type = trim($content_type);
          writeLog("$method: content_type is reported as $content_type, attempting to verify",TYPE_TRACE);
        }
        $content_type = getMIMEType($content);
      }
      if (!$flag_skip_loadlastresult) { lastLoadResult($url,$method,$content_type,$http_code,$protocol,$protocol_id,$content); }
    } else {
      writeLog("Using buffered result for " . showValue("url",$url),TYPE_TRACE);
    }
  }
  debugSection();
  return $content;
}

/*  Load a Local File */
function loadLocalFile($pathname) {
  debugSection("loadLocalFile");
  $content = null;
  $content_type = null;
  $protocol = null;
  writeLog(showValue("pathname",$pathname),TYPE_TRACE);
  if (!is_string($pathname)) {
    if (debugMode()) {
      writeLog("Pathname requested for load is type (" . gettype($pathname) . ") instead of string!",TYPE_ERROR);
      writeLog(print_r($pathname),TYPE_ERROR);
    }
  } else {
    $protocol = parse_url($pathname,PHP_URL_SCHEME);
    if (!is_null($protocol)) { writeLog("Possible URL Request ($protocol)",TYPE_TRACE); }
    if (is_null($pathname)) {
      writeLog("No pathname given",TYPE_TRACE);
    } else {
      if (file_exists($pathname)) {
        if (!getConfigurationty("extensions","get")) {
          $fh = fopen($pathname, 'r', false);
          if ($fh) {
            $content = "";
            while (!feof($fh)) {
              $content .= fread($fh, BUFFER_SIZE);
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
  }
  debugSection();
  return $content;
}

/*  Get MIME Type based on data */
function getMIMEType($data) {
  debugSection("getMIMEType");
  $content_type = null;
  if (!is_null($data)) {
    if (is_null($content_type)) {
      if (getConfiguration("extensions","fileinfo")) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $content_type = $finfo->buffer($data);    
      }
    }
  }
  debugSection();
  return $content_type;
}

/*  Get MIME Type from URL */
function getMIMETypeFromURL($url) {
  debugSection("getMIMETypeFromURL");
  $content_type = null;
  $content = null;
  if (!is_null($url)) {
    if (is_string($url)) {
      if (!getConfiguration("extensions","get")) {
        $fh = @fopen($url, 'r', false);
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
        $content = @file_get_contents($url, false);
      }
      if (!is_null($content)) {
        if (is_null($content_type)) {
          $content_type = getMIMEType($content);
        }
      }         
    }
  }
  debugSection();
  return $content_type;  
}

/*  Get MIME Type from a File */
function getMIMETypeFromFile($pathname) {
  debugSection("getMIMETypeFromFile");
  $content_type = null;
  $confidence = 0;
  if (is_string($pathname)) {
    if (file_exists($pathname)) {
      if (is_null($content_type)) {
        if (getConfiguration("extensions","fileinfo")) {
          $finfo = new finfo(FILEINFO_MIME_TYPE);
          $content_type = $finfo->file($pathname);
          if (!is_null($content_type)) { $confidence = CONFIDENCE_CERTAIN; }
          $method = "fileinfo";
        }
      }      
      if (is_null($content_type)) {
        if (getConfiguration("extensions","mime_content_type")) {
          $content_type = mime_content_type($pathname);
          if (!is_null($content_type)) { $confidence = CONFIDENCE_HIGH; }
          $method = "mimetype";
        }
      }    
      if (is_null($content_type)) {
        if (getConfiguration("extensions","exif")) {
          $fileCheck = getMIMETypeUsingEXIF($pathname);
          $content_type = $fileCheck['content_type'];
          $confidence = $fileCheck['confidence'];
          $method = $fileCheck['method'];
        }
      }
      if (is_null($content_type)) {
        if (!getConfiguration("extensions","get")) {
          $fh = fopen($pathname, 'r', false);
          if ($fh) {
            $buffer = '';
            while (!feof($fh)) {
              $buffer .= fread($fh, BUFFER_SIZE);
            }
            fclose($fh);
          } else {
            writeLog("Failed to open '$pathname'",TYPE_TRACE);
          }
        } else {
          $buffer = @file_get_contents($pathname, false);
        }        
        $fileCheck = getMIMETypeFromBinary($buffer);
        $content_type = $fileCheck['content_type'];
        $confidence = $fileCheck['confidence'];
        $method = $fileCheck['method'];
      }
      if (!is_null($content_type)) {
        if (!is_string($content_type)) {
          $content_type = null;
          $confidence = 0;
        }
      }
      if (is_null($content_type)) {
        writeLog("pathname='$pathname', content_type=null, confidence=" . getConfidenceText($confidence) . ", method=none",TYPE_TRACE);  
      } else {
        writeLog("pathname='$pathname', content_type=$content_type, confidence=" . getConfidenceText($confidence) . ", method=$method",TYPE_TRACE);          
      }
    } else {
      writeLog("'$pathname' does not exist",TYPE_TRACE);
    }
  } else {
    writeLog("pathname is not a string (type=" . gettype($pathname) . ")",TYPE_TRACE);
  }
  debugSection();
  return $content_type;
}

function getMIMETypeFromBinary($buffer) {
  debugSection("getMIMETypeFromBinary");
  $content_type = null;
  $confidence = 0;
  $method = "none";
  if (!is_null($buffer)) {
    if (is_string($buffer)) {
      if (strlen($buffer) > 2) {
        writeLog("Data Stream Meets Minimum Requirements",TYPE_TRACE);
        
        if (is_null($content_type) || $confidence < CONFIDENCE_LOW) {
          // WEBP
          if (strlen($buffer) >= 16) {
            writeLog("Checking Format: WEBP",TYPE_TRACE);
            if (substr($buffer,0,4) == MAGIC_RIFF_STRING) {
              $content_type = "image/webp";
              $confidence = CONFIDENCE_LOW;
              if (substr($buffer,8,4) == MAGIC_WEBP_STRING) {
                $confidence = CONFIDENCE_MEDIUM;
                if (substr($buffer,12,3) == MAGIC_VP8_STRING) {
                  $confidence = CONFIDENCE_CERTAIN;
                  $method = "signature";
                }
              }
            }
          }
        }
        
        //  JPEG
        if (is_null($content_type) || $confidence < CONFIDENCE_LOW) {
          if (strlen($buffer) >= 10) {
            writeLog("Checking Format: JPEG",TYPE_TRACE);
            $value = unpack("V",substr($buffer,0,4));
            if (isset($value[1])) {
              if (is_numeric($value[1])) {
                if ($value[1] == MAGIC_JPG) {
                 if (substr($buffer,6,4) == MAGIC_JPG_STRING) {
                   $content_type = "image/jpeg";
                   $confidence = CONFIDENCE_CERTAIN;
                   $method = "signature";
                 }
                }
              }
            }
          }
        }
        
        // PNG
        if (is_null($content_type) || $confidence < CONFIDENCE_LOW) {
          if (strlen($buffer) >= 8) {
            writeLog("Checking Format: PNG",TYPE_TRACE);
            $value = unpack("P",substr($buffer,0,8));
            if (isset($value[1])) {
              if (is_numeric($value[1])) {
                if ($value[1] == MAGIC_PNG) {
                  $content_type = "image/png";
                  $confidence = CONFIDENCE_CERTAIN;
                  $method = "signature";
                }
              }
            }
          }
        }

        // GIF
        if (is_null($content_type) || $confidence < CONFIDENCE_LOW) {
          if (strlen($buffer) >= 6) {
            writeLog("Checking Format: GIF",TYPE_TRACE);
            if (substr($buffer,0,6) == MAGIC_GIF_STRING87) {
              $content_type = "image/gif";
              $confidence = CONFIDENCE_CERTAIN;
              $method = "signature";
            }
            if (substr($buffer,0,6) == MAGIC_GIF_STRING89) {
              $content_type = "image/gif";
              $confidence = CONFIDENCE_CERTAIN;
              $method = "signature";
            }
          }
        }
        
        // BMP
        if (is_null($content_type) || $confidence < CONFIDENCE_LOW) {
          if (strlen($buffer) >= 6) {
            writeLog("Checking Format: BMP",TYPE_TRACE);
            if (substr($buffer,0,2) == MAGIC_BMP) {
              $value = unpack("V",substr($buffer,2,4));
              $content_type = "image/bmp";
              $confidence = CONFIDENCE_MEDIUM;
              if (isset($value[1])) {
                if (is_numeric($value[1])) {
                  if ($value[1] == strlen($buffer)) {
                    $confidence = CONFIDENCE_CERTAIN;
                    $method = "signature";
                  }
                }
              }
            }
          }
        }
        
        //  ICO
        if (is_null($content_type) || $confidence < CONFIDENCE_LOW) {
          if (strlen($buffer) >= 6) {
            writeLog("Checking Format: ICO",TYPE_TRACE);
            $value = unpack("v",substr($buffer,0,2));
            if (isset($value[1])) {
              if (is_numeric($value[1])) {
                if ($value[1] == 0) {
                  $value = unpack("v",substr($buffer,2,2));
                  if (isset($value[1])) {
                    if ($value[1] == 1) {
                      $value = unpack("v",substr($buffer,4,2));
                      if ($value > 0) {
                        $content_type = "image/x-icon";
                        $confidence = CONFIDENCE_HIGH;
                        $method = "markers";
                      }
                    }
                  }
                }
              }
            }
          }
        }
        
        //  OTHER
        if (is_null($content_type)) {
          $flag_binary = false;
          if (getConfiguration("extensions","mbstring")) {
            writeLog("Using mbstring to determine content type",TYPE_TRACE);
            $method = "mb_detect_encoding";
            if (mb_detect_encoding($buffer) == "ASCII") {
              $content_type = "text/plain";
              $confidence = CONFIDENCE_HIGH;
            } else {
              $content_type = "application/octet-stream";
              $confidence = CONFIDENCE_HIGH;
            }
          } else {
            writeLog("Using alternate method to determine content type",TYPE_TRACE);
            $pointer = 0;
            $flag_loop = true;
            $flag_binary = false;
            $confidence = CONFIDENCE_HIGH;
            $content_type = "text/plain";
            $method = "bytecheck";
            do {
              $value = @unpack("C",substr($buffer,$pointer,1));
              if ($value === false) {
                # Error Unpacking
              } else {
                if (isset($value[1])) {
                  if (is_numeric($value[1])) {
                    if ($value[1] > 127) {
                      $flag_binary = true;
                      $flag_loop = false;
                    }
                  }
                }
              }
              $pointer++;
              if ($pointer >= strlen($buffer)) {
                $flag_loop = false;
              }
            } while ($flag_loop);
            if ($flag_binary) {
              $content_type = "application/octet-stream";
            }           
          }
        }
        
        # TO DO:
        #   SVG
        if ($content_type == "text/plain") {
        }
      }
    }
  }
  if (is_null($content_type)) {
    writeLog("Unable to discover content_type",TYPE_TRACE);
  } else {
    writeLog("Identified as $content_type with $confidence % confidence using method: $method",TYPE_TRACE);
  }
  $retval['content_type'] = $content_type;
  $retval['confidence'] = $confidence;
  $retval['method'] = $method;
  debugSection();
  return $retval;
}

function getMIMETypeUsingEXIF($pathname = null) {
  debugSection("getMIMETypeUsingEXIF");
  $content_type = null;
  $confidence = 0;
  $method = "none";
  if (getConfiguration("extensions","exif")) {
    $phpUA = ini_get(PHP_OPTION_USER_AGENT);
    $timeout = ini_get(PHP_OPTION_DEFAULT_SOCKET_TIMEOUT);
    $method = "exif";
    writeLog("WARNING: exif_imagetype is sometimes refused access to an icon.",TYPE_TRACE);
    if (!is_null($pathname)) {
      if (is_string($pathname)) {
        writeLog("pathname='$pathname', method=$method, content-type: null, useragent=$phpUA, timeout=$timeout",TYPE_TRACE);
        $filetype = @exif_imagetype($pathname);
        if (!is_null($filetype)) {
          if (function_exists("image_type_to_mime_type")) {
            $method = "exif:image_type_to_mime_type";
            $content_type = image_type_to_mime_type($filetype);
          } else {
            switch ($filetype) {
              case IMAGETYPE_GIF:
                $content_type = "image/gif";
                break;
              case IMAGETYPE_JPEG:
                $content_type = "image/jpeg";
                break;
              case IMAGETYPE_PNG:
                $content_type = "image/png";
                break;
              case IMAGETYPE_ICO:
                $content_type = "image/x-icon";
                break;
              case IMAGETYPE_WEBP:
                $content_type = "image/webp";
                break;
              case IMAGETYPE_BMP:
                $content_type = "image/bmp";
                break;
              default:
                $content_type = null;
                $file_extension = null;
            }
          }
          if (!is_null($content_type)) {
            $file_extension = lookupExtensionByMIME($content_type);
            $confidence = CONFIDENCE_CERTAIN;
          } 
        }
      } else {
        writeLog("pathname=invalid (" . gettype($pathname) . "), method=$method, content-type: null, useragent=$phpUA, timeout=$timeout",TYPE_TRACE);
      }
    } else {
      writeLog("pathname=null, method=$method, content-type: null, useragent=$phpUA, timeout=$timeout",TYPE_TRACE);
    }
  } else {
  }
  $retval['content_type'] = $content_type;
  $retval['confidence'] = $confidence;
  $retval['method'] = $method;  
  debugSection();
  return $retval;
}

/* HELPER: Change URL from relative to absolute */
function convertRelativeToAbsolute($rel, $base, $port = null) {
  debugSection("convertRelativeToAbsolute");
  writeLog(showValue("rel",$rel) . ", " . showValue("base",$base) . ", " . showValue("port",$port),TYPE_TRACE);
  $retval = null;
  $method = "none";
	extract(parse_url($base));
  if (is_null($retval)) {
    if (strpos( $rel,"//" ) === 0) {
      $method = "strpos";
      $retval = $scheme . ':' . $rel;
    }
  }
  if (is_null($retval)) {
    if (parse_url( $rel, PHP_URL_SCHEME ) != '') {
      $method = "parse_url";
      $retval = $rel;
    }
  }
  if (is_null($retval)) {
    if ($rel[0] == '#' or $rel[0] == '?') {
      $method = "querystring";
      $retval = $base . $rel;
    }
  }
  if (is_null($retval)) {
    $method = "preg_replace";
    $path = preg_replace( '#/[^/]*$#', '', $path);
    if ($rel[0] ==  '/') $path = '';
    $abs = $host;
    if (!is_null($port)) { $abs .= ":$port"; }
    $abs .= $path . "/" . $rel;
    $abs = preg_replace( "/(\/\.?\/)/", "/", $abs);
    $abs = preg_replace( "/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs);
    $retval = $scheme . '://' . $abs;
  }
  writeLog("returning: " . showValue(null,$retval,false) . ", " . showValue("method",$method),TYPE_TRACE);
  debugSection();
	return $retval;
}

/*  Valid Image */
function isValidImage($content_type = null) {
  debugSection("isValidImage");
  $retval = false;
  if (!is_null($content_type)) {
    if (is_string($content_type)) {
      $content_type = strtolower($content_type);
      writeLog("Searching for match of $content_type",TYPE_TRACE);
      switch ($content_type) {
        case "image/ico":
          $retval = true;
          break;      
        case "image/x-icon":
          $retval = true;
          break;
        case "image/vnd.microsoft.icon":
          $retval = true;
          break;
        case "image/webp":
          $retval = true;
          break;
        case "image/png":
          $retval = true;
          break;
        case "image/jpeg":
          $retval = true;
          break;
        case "image/gif":
          $retval = true;
          break;
        case "image/svg+xml":
          $retval = true;
          break;
        case "image/avif":
          $retval = true;
          break;
        case "image/apng":
          $retval = true;
          break;
        case "image/bmp":
          $retval = true;
          break;
        case "image/tiff":
          $retval = true;
          break;
      }
    }
  }
  debugSection();
  return $retval;
}

/*  Content Type Lookups */
function lookupExtensionByMIME($content_type = null) {
  debugSection("lookupExtensionByMIME");
  $file_extension = null;
  if (!is_null($content_type)) {
    $content_type = strtolower($content_type);
    writeLog("Searching for match of $content_type",TYPE_TRACE);
    switch ($content_type) {
      case "image/ico":
        $file_extension = "ico";
        break;      
      case "image/x-icon":
        $file_extension = "ico";
        break;
      case "image/vnd.microsoft.icon":
        $file_extension = "ico";
        break;
      case "image/webp":
        $file_extension = "webp";
        break;
      case "image/png":
        $file_extension = "png";
        break;
      case "image/jpeg":
        $file_extension = "jpg";
        break;
      case "image/gif":
        $file_extension = "gif";
        break;
      case "image/svg+xml":
        $file_extension = "svg";
        break;
      case "image/avif":
        $file_extension = "avif";
        break;
      case "image/apng":
        $file_extension = "apng";
        break;
      case "image/bmp":
        $file_extension = "bmp";
        break;
      case "image/tiff":
        $file_extension = "tif";
        break;
    }
  }
  debugSection();
  return $file_extension;
}

function lookupContentTypeByExtension($extension = null) {
  debugSection("lookupContentTypeByExtension");
  $content_type = null;
  if (!is_null($extension)) {
    $extension = strtolower($extension);
    writeLog("Searching for match of $extension",TYPE_TRACE);
    switch ($extension) {
      case "gif":
        $content_type = "image/gif";
        break;
      case "png":
        $content_type = "image/png";
        break;
      case "jpg":
        $content_type = "image/jpeg";
        break;
      case "jpeg":
        $content_type = "image/jpeg";
        break;
      case "ico":
        $content_type = "image/x-icon";
        break;
      case "svg":
        $content_type = "image/svg+xml";
        break;
      case "webp":
        $content_type = "image/webp";
        break;
      case "avif":
        $content_type = "image/avif";
        break;
      case "apng":
        $content_type = "image/apng";
        break;
      case "bmp":
        $content_type = "image/bmp";
        break;
      case "tif":
        $content_type = "image/tiff";
        break;
    }
  }
  debugSection();
  return $content_type;
}

/*  Guess Content Type By Extension */
function guessContentType($extension) {
  $content_type = lookupContentTypeByExtension($extension);
  return $content_type;
}

/*  See if we have an HTML document */
function isHTML($buffer) {
  $retval = false;
  if (!is_null($buffer)) {
    if (is_string($buffer)) {
      if ($buffer != strip_tags($buffer)) {
        $retval = true;
      }
    }
  }
  return $retval;
}

/* Get Icon Extension / Verify Icon */
function getIconExtension($url, $noFallback = false) {
  debugSection("getIconExtension");
  $content_type = null;
  $file_extension = null;
  $method = null;
  $http_code = null;
  $is_valid = false;
  $confidence = 0;
  $width = -1;
  $height = -1;
  $reason = "not set";
  if (!is_null($url)) {
    if (is_string($url)) {
      if (!empty($url)) {
        $content = load($url);
        if (isLastLoadResultValid()) {
          $http_code = getLastLoadResult("http_code");
          $RequestData = lookupHTTPResponse($http_code);
          if ($RequestData['ok']) {
            $content_type = getLastLoadResult("content_type");
            
            # Debug
            if (is_array($content_type)) {
              writeLog("url='$url', last load contains content_type is an array!!!",TYPE_TRACE);
              var_dump($content_type);
            }
            
            if (is_null($content_type)) {
              writeLog("url='$url', last load contains content_type: null",TYPE_TRACE);
            } else {
              writeLog("url='$url', last load contains content_type: $content_type)",TYPE_TRACE);
            }
          } else {
            writeLog("url='$url', last load result returned an error=" . $RequestData['code'] . " (" . $RequestData['description'] . ")",TYPE_TRACE);
            if (is_null($http_code)) {
              $content_type = getLastLoadResult("content_type");
              if (is_null($content_type)) {
                $reason = "no http code or content_type returned from server";
              } else {
                $reason = "no http code returned from server";
              }
            } else {
              $content = null;
              $noFallback = true;
              $is_valid = false;
              $reason = "server response was " . $RequestData['code'] . " " . $RequestData['description'];
            }
          }
        } else {
          writeLog("url='$url', last load result was invalid",TYPE_TRACE);
          $reason = "failed to load url";
        }
        if (!is_null($content)) {
          if (is_null($content_type)) {
            $method = "getMIMEType";
            $content_type = getMIMEType($content);
            if (!is_null($content_type)) {
              $confidence = CONFIDENCE_CERTAIN;
              writeLog("url='$url', method=$method, content-type=$content_type",TYPE_TRACE);
            }
          }
          if (!is_null($content_type)) {
            if ($content_type == "application/octet-stream") {
              $method = "getMIMEType";
              $content_type = getMIMEType($content);
              $confidence = CONFIDENCE_CERTAIN;
              writeLog("url='$url', method=$method, content-type=$content_type; (was application/octet-stream)",TYPE_TRACE);
            }
          }
          if (!is_null($content_type)) {
            $method = "mimetype";
            writeLog("url='$url', method=$method, content-type=$content_type",TYPE_TRACE);
            switch ($content_type) {
              case "text/html":
                $noFallback = true;
                $file_extension = null;
                $is_valid = false;
                $reason = "not an image";
                break;
              case "application/octet-stream":
                if (!getConfiguration("global","allow_octet_stream")) {
                  $noFallback = true;
                  $file_extension = null;
                  $is_valid = false;
                  $reason = "generic binary data";
                }
              default:
                $file_extension = lookupExtensionByMIME($content_type);
                if (is_null($file_extension)) {
                  $is_valid = false;
                  $reason = "lookupExtensionByMIME returned null";
                } else {
                  $is_valid = true;
                }
            }
          }
          if (is_null($content_type)) {
            if (getConfiguration("extensions","exif")) {
              $fileCheck = getMIMETypeUsingEXIF($url);
              $content_type = $fileCheck['content_type'];
              $confidence = $fileCheck['confidence'];
              $method = $fileCheck['method'];
              if (is_null($content_type)) {
                writeLog("url='$url', method=$method, content-type=null",TYPE_TRACE);
              } else {
                writeLog("url='$url', method=$method, content-type=$content_type",TYPE_TRACE);
                $file_extension = lookupExtensionByMIME($content_type);
              }
            }
          }
        }
        if (is_null($file_extension)) {
          if (!$noFallback) {
            $method = "datacheck";
            $fileCheck = getMIMETypeFromBinary($content);
            $content_type = $fileCheck['content_type'];
            $confidence = $fileCheck['confidence'];
            $method = $method . ":" . $fileCheck['method'];
            if (is_null($content_type)) {
              writeLog("url='$url', method=$method, content-type=null",TYPE_TRACE);
            } else {
              writeLog("url='$url', method=$method, content-type=$content_type",TYPE_TRACE);
              $file_extension = lookupExtensionByMIME($content_type);
            }
          }
        }
        if (is_null($file_extension)) {
          if (!$noFallback) {
            $method = "extension";
            writeLog("url='$url', method=$method, content-type=null",TYPE_TRACE);
            $extension = @preg_replace('/^.*\.([^.]+)$/D', '$1', $url);
            if (isValidType($extension)) {
              $file_extension = $extension;
            }
          }
        }
        $was_valid = $is_valid;
        if (!is_null($file_extension)) {
          $is_valid = isValidType($file_extension);
          if ($was_valid & !$is_valid) { $reason = "isValidType vetoed file"; }
        }
        if (is_null($content_type)) {
          if (!is_null($file_extension)) {
            $content_type = guessContentType($file_extension);
            $confidence = CONFIDENCE_MEDIUM;
            $reason = "content type is a guess";
          }
        }
      } else {
        writeLog("URL given is empty",TYPE_TRACE);
        $reason = "URL was empty";
      }
    } else {
      writeLog("URL given is not a string",TYPE_TRACE);
      $reason = "URL was not a string";
    }
  } else {
    writeLog("URL is null",TYPE_TRACE);
    $reason = "URL was null";
  }
  
  if ($is_valid) {
    $reason = "accepted";
    if (!is_null($url)) {
      if (is_string($url)) {
        $data = @getimagesize($url);
        if (!is_null($data)) {
          if (!empty($data)) {
            if (isset($data[0])) { if (is_numeric($data[0])) { $width = $data[0]; } }
            if (isset($data[1])) {  if (is_numeric($data[1])) { $height = $data[1]; } }
            if (isset($data['mime'])) { if (!is_null($content_type)) { $content_type = $data['mime']; } }
          }
        }
      }
    }
  }

  $retval = array();
  $retval['valid'] = $is_valid;
  $retval['extension'] = $file_extension;
  $retval['content_type'] = $content_type;
  $retval['method'] = $method;
  $retval['reason'] = $reason;
  $retval['width'] = $width;
  $retval['height'] = $height;
  $retval['wXh'] = $width . "x" . $height;
  $retval['confidence'] = $confidence;
  
  debugSection();
  return $retval;
}

/*  Validate File Type */
function isValidType($type) {
  $retval = false;
  $extensionList = getConfiguration("global","valid_types");
  $type = normalizeKey($type);
  if (empty($extensionList)) {
    $retval = true;
  } else {
    foreach ($extensionList as $validExtension) {
      if ($type == $validExtension) {
        $retval = true;
        break;
      }
    }      
  }
  return $retval;
}

function getValidTypes() {
  $retval = null;
  $extensionList = getConfiguration("global","valid_types");
  if (!empty($extensionList)) {
    foreach ($extensionList as $validExtension) {
      if (!is_null($retval)) { $retval .= ", "; }
      $retval .= $validExtension;
    }
  }
  return $retval;
}

function checkIconAcceptance($url = null) {
  debugSection("checkIconAcceptance");
  $retval = false;
  if (!is_null($url)) {
    if (is_string($url)) {
      writeLog("Checking Icon Type for '$url'",TYPE_TRACE);
      $fileData = getIconExtension($url,false);
      if ($fileData['valid']) {
        $extension = $fileData['extension'];
        $content_type = $fileData['content_type'];
        if ((is_null($extension)) && (is_null($content_type))) {
          writeLog("getIconExtension returned valid but both extension and content_type are null",TYPE_TRACE);
        } else {
          if ((is_null($extension)) && (!is_null($content_type))) {
            $extension = lookupExtensionByMIME($content_type);
          }
          if ((!is_null($extension)) && (is_null($content_type))) {
            $content_type = lookupContentTypeByExtension($extension);
          }
          if (isValidType($extension)) {
            if (validateIcon($url)) {
              $retval = true;
            } else {
              writeLog("validateIcon rejected the icon",TYPE_TRACE);
            }
          } else {
            writeLog("isValidType rejected the icon",TYPE_TRACE);
          }
        }
      } else {
        writeLog("getIconExtension rejected the icon (reason: " . $fileData['reason'] . ")",TYPE_TRACE);
      }
    } else {
      writeLog("URL is not a string",TYPE_TRACE);
    }
  } else {
    writeLog("URL is null",TYPE_TRACE);
  }
  debugSection();              
  return $retval;
}

function validateIconByHash($hash) {
  debugSection("validateIconByHash");
  $retval = true;
  # check blocklist
  
  debugSection();
  
  return $retval;
}

function validateIcon($iconfile, $removeIfInvalid = false) {
  debugSection("validateIcon");
  $retval = true;
  # TO DO:
  #   if size data is available, reject if invalid
  
  # use load, will load via url or locally
  #   md5 hash should be present, call validateIconByHash
  #   removeIfInvalid is only applicable if it's a local file
  # TO DO
  #   determine if the pathname is:
  #     a valid image file
  #     not in the blocklist
  #
  # if it is not a valid (or is blocked) and removeIfInvalid is true, delete it.
  debugSection();
  
  return $retval;
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
  debugSection("initializePHPAgent");
  $userAgent = getConfiguration("http","useragent");
  if (is_null($userAgent)) { $userAgent = getConfiguration("http","default_useragent"); }
  if (!is_null($userAgent)) { ini_set(PHP_OPTION_USER_AGENT, $userAgent); }
  if (ini_get(PHP_OPTION_DEFAULT_SOCKET_TIMEOUT) > getConfiguration("http","http_timeout")) { ini_set(PHP_OPTION_DEFAULT_SOCKET_TIMEOUT, getConfiguration("http","http_timeout")); }
  debugSection();
}

/*  Show Values */
function showValue($label = null,$value = null,$showtype = true) {
  $retval = null;
  if (is_null($value)) {
    $value = "null";
    $type = null;
  } else {
    $type = gettype($value);
  }
  if (!is_null($label)) {
    $retval .= $label . "=";
  }
  $flag_add_type = $showtype;
  if (is_bool($value)) {
    $retval .= showBoolean($value);
  } elseif (is_string($value)) {
    $retval .= "'" . $value . "'";
  } elseif (is_numeric($value)) {
    $retval .= $value;
  } else {
    $retval .= "($type)";
    $flag_add_type = false;
  }
  if ($flag_add_type) { if (!is_null($type)) { $retval .= " ($type)"; } }
  return $retval;
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
  $parameters = normalizeKey($parameters);
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
      if (!getConfiguration("extensions","get")) {
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
  debugSection("debugMode");
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
    if ($log_level & TYPE_SPECIAL) { $retval = true; }
  }
  if ($console_enabled) {
    if ($console_level & TYPE_DEBUGGING) { $retval = true; }
    if ($console_level & TYPE_TRACE) { $retval = true; }
    if ($console_level & TYPE_SPECIAL) { $retval = true; }
  }
  debugSection();
  return $retval;
}

/*  Initialize Log File */
/*  Also acts to see if logging (console and/or file) is enabled */
function initializeLogFile() {
  $retval = false;
  if (getItem("suppress_logfile")) {
    /*  File Logging is Suppressed */
    if (getConfiguration("console","enabled")) { $retval = true; }
  } else {
    $log_enabled = getConfiguration("logging","enabled");
    if ($log_enabled) {
      $log_opt_append = setBoolean(getConfiguration("logging","append"));
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
            echo "APPEND: " . showBoolean($log_opt_append) . ", " . showBoolean($flag_open_append) . ", " . showBoolean(getConfiguration("logging","append")) . "\n";
            
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
  }  
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
    if ($type & TYPE_SPECIAL)
    {
      $string_type = "[SPECIAL] ";
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
          if (!is_null($log_opt_timestamp_format)) { $file_string_timestamp = getTimestamp($log_opt_timestamp_format); }
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
          if (!is_null($console_opt_timestamp_format)) { $console_string_timestamp = getTimestamp($console_opt_timestamp_format); }
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

/* format timestamp with some defaults */
function getTimestamp($format = null,$time = null) {
  debugSection("getTimestamp");
  if (is_null($format)) { $format = DEFAULT_LOG_TIMESTAMP_FORMAT; }
  if (is_null($time)) { $time = time(); }
  debugSection();
  return date($format,$time);
}

/*
**  Configuration Controller
**
*/
function setConfiguration($scope = "global",$option = null,$value = null,$default = null,$type = CONFIG_TYPE_SCALAR) {
  debugSection("setConfiguration");
  global $configuration;
  $flag_fallback = true;
  $flag_handled = false;
  if (!is_null($option)) {
    if (is_string($option)) {
      $option = normalizeKey($option);
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
  }
  debugSection();
}

function getConfiguration($scope = "global",$option = null) {
  debugSection("getConfiguration");
  global $configuration;
  $value = null;
  if (!is_null($option)) {
    $option = normalizeKey($option);
    if (isset($configuration[$scope][$option])) { $value = $configuration[$scope][$option]; }
  }
  debugSection();
  return $value;
}

/*  Validate a Configuration Setting */
function validateConfigurationSetting($scope = "global",$option = null,$type = 0,$min = 0,$max = 0) {
  debugSection("validateConfigurationSetting");
  global $configuration;
  if (!is_null($option)) {
    $option = normalizeKey($option);
    if (is_string($option)) {
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
  }
  debugSection();
}

/*  Validate URL List */
function validateURLList() {
  debugSection("validateURLList");
  global $URLList;
  $counter = 0;
  $temp = array();
  if (empty($URLList)) {
    if (getConfiguration("global","debug")) {
      writeLog("Using Test URLs",TYPE_DEBUGGING);
      $URLList = array(
        'http://aws.amazon.com',
        'http://www.apple.com',
        'http://www.dribbble.com',
        'https://www.docker.com',
        'https://www.gaffling.com/',
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
        'https://www.whatsapp.com',
      );
    }
  } else {
    foreach ($URLList as $url) {
      $counter++;
      $scheme = parse_url($url,PHP_URL_SCHEME);
      if (is_null($scheme)) {
        if (getConfiguration("http","default_protocol_https")) {
          $scheme = "https";
        } else {
          $scheme = "http";
        }
        writeLog("$counter: $url is missing protocol, adding $scheme",TYPE_TRACE);
        $temp[] = $scheme . "://" . $url;
      } else {
        $temp[] = $url;
      }
    }
    $URLList = $temp;
  }
  debugSection();
}

/*  Normalize a Key */
function normalizeKey($key) {
  debugSection("normalizeKey");
  if (isset($key)) {
    if (!is_null($key)) {
      if (is_string($key)) {
        $key = strtolower($key);
      } else {
        $key = null;
      }
    }
  }
  debugSection();
  return $key;
}

/*
**  API Controller **
**
**  Structure:
**    name      Name/ID for the API
**    url       URL for the API
**    json      Does API return JSON?
**    enabled   Is API enabled?
**    display   Display Name (cosmetic only), defaults to name
**    apikey    API Key
**
**    json_structure is an array of the expected json data returned.
**        error       if there is an error, information will be in this field
**        icons       the sub-array containing data
**        link        the field containing the url for the icon
**        sizeWxH     the field for the size of the icon in WIDTHxHEIGHT notation
**        mime        the field containing the mime type for the icon
**        size        the field for the size of the icon (in pixels, assumes square)
**    apikey is implemented.
**
*/

/*  Check if element is valid for API Structure */
function isValidAPIElement($element) {
  debugSection("isValidAPIElement");
  $retval = false;
  if (isset($element)) {
    if (!is_null($element)) {
      if (is_string($element)) {
        $element = normalizeKey($element);
        if ($element == "display") { $retval = true; }
        if ($element == "name") { $retval = true; }
        if ($element == "url") { $retval = true; }
        if ($element == "json") { $retval = true; }
        if ($element == "enabled") { $retval = true; }
        if ($element == "json_structure") { $retval = true; }
        if ($element == "apikey") { $retval = true; }
      }
    }
  }
  debugSection();  
  return $retval;
}

function loadDefaultAPIs($clear = false) {
  debugSection("loadDefaultAPIs");
  if ($clear) { emptyAPIList(); }
  addAPI("faviconkit","https://api.faviconkit.com/<DOMAIN>/<SIZE>",false,DEFAULT_ENABLE_APIS);
  addAPI("favicongrabber","http://favicongrabber.com/api/grab/<DOMAIN>",true,DEFAULT_ENABLE_APIS,array("icons" => "icons","link" => "src","sizeWxH" => "sizes","mime" => "type","error" => "error"));
  addAPI("google","http://www.google.com/s2/favicons?domain=<DOMAIN>",false,DEFAULT_ENABLE_APIS);
  addAPI("iconhorse","https://icon.horse/icon/<DOMAIN>",false,DEFAULT_ENABLE_APIS);
  setConfiguration("global","api_list","internal");
  debugSection();
  return $clear;
}

/* Add an API */
function addAPI($name,$url,$json = false,$enabled = true,$json_structure = array(),$display = null,$apikey = null) {
  debugSection("addAPI");
  global $apiList;
  $retval = false;
  if (is_string($name)) {
    $name = normalizeKey($name);
    if (isAPIDefined($name)) {
      # API already exists
    } else {
      if (is_null($display)) { $display = $name; }
      $entry = array(
        "display" => $display,
        "name" => $name,
        "url" => $url,
        "json" => setBoolean($json),
        "enabled" => setBoolean($enabled),
        "json_structure" => $json_structure,
        "apikey" => $apikey,
      );
      array_push($apiList,$entry);
      refreshAPIList();
    }
  }
  debugSection();
  return $retval;
}

function isAPIDefined($name) {
  debugSection("isAPIDefined");
  global $apiList;
  $retval = false;
  if (is_string($name)) {
    $name = normalizeKey($name);
    foreach ($apiList as $item) {
      if (isset($item['name'])) {
        if ($item['name'] == $name) {
          $retval = true;
          break;
        }
      }
    }
  }
  debugSection();
  return $retval;
}

/*  Return a List of Enabled APIs as an Array */
function getAPIIndex() {
  debugSection("getAPIIndex");
  global $apiList;
  $retval = array();
  if (!empty($apiList)) {
    foreach ($apiList as $item) {
      if (isset($item['name'])) {
        if (isset($item['enabled'])) {
          if ($item['enabled']) {
            array_push($retval,$item['name']);
          }
        }
      }
    }
  }
  debugSection();
  return $retval;
}

/*  Get a List of APIs */
function getAPIList($displayName = false) {
  debugSection("getAPIList");
  global $apiList;
  $retval = null;
  $counter = 0;
  if (!empty($apiList)) {
    foreach ($apiList as $item) {
      if (isset($item['name'])) {
        $api_name = $item['name'];
        if ($displayName) { if (isset($item['display'])) { $api_name = $item['display']; } }
        $api_enabled = $item['enabled'];
        if (!is_null($api_name)) { 
          if (!is_null($retval)) { $retval .= ", "; }
          $retval .= $api_name;
          if (!$api_enabled) { $retval .= "*"; }
        }
      }
    }
  }
  if (is_null($retval)) { if ($displayName) { $retval = "None"; } else { $retval = "none"; } }
  debugSection();
  return $retval;
}

/*  Refresh API List */
function refreshAPIList($isenabled = true) {
  debugSection("refreshAPIList");
  global $apiList; 
  $retval = false;
  $count = 0;
  if (!empty($apiList)) {
    $retval = true;
    foreach ($apiList as $item) {
      if (isset($item['display'])) { $api_display = $item['display']; } else { $api_display = null; }
      if (isset($item['name'])) { $api_name = $item['name']; } else { $api_name = null; }
      if (isset($item['url'])) { $api_url = $item['url']; } else { $api_url = null; }
      if (isset($item['json'])) { $api_json = $item['json']; } else { $api_json = null; }
      if (isset($item['enabled'])) { $api_enabled = $item['enabled']; } else { $api_enabled = null; }
      if (isset($item['json_structure'])) { $api_json_structure = $item['json_structure']; } else { $api_json_structure = null; }
      if (isset($item['apikey'])) { $api_apikey = $item['apikey']; } else { $api_apikey = null; }
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
  debugSection();
  return $retval;
}

/*  Return a count of APIs */
function getAPICount($isenabled = true) {
  debugSection("getAPICount");
  $api_count = getItem("api_count");
  if (!isset($api_count)) { 
    refreshAPIList($isenabled);
    $api_count = getItem("api_count");
  }
  debugSection();
  return $api_count;
}

/* Return an API object */
function getAPI($name) {
  debugSection("getAPI");
  $return_object = lookupAPI('name',$name);
  debugSection();
  return $return_object;
}

/* Return the Name of a Random API */
function getRandomAPIName() {
  debugSection("getRandomAPIName");
  global $apiList;
  $api_name = null;
  $api_count = getAPICount();
  $api_index = getAPIIndex();
  if ($api_count > 0) {
    shuffle($api_index);
    $api_name = array_shift($api_index);
  }
  debugSection();
  return $api_name;
}

/* Select a Random API */
function getRandomAPI() {
  debugSection("getRandomAPI");
  global $apiList;
  $api_count = getAPICount();
  $return_object = getEmptyAPIEntry();
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
  debugSection();
  return $return_object;
}

/*  Get a Null API Entry */
function getEmptyAPIEntry() {
  debugSection("getEmptyAPIEntry");
  $return_object = array(
    "display" => null,
    "name" => null,
    "url" => null,
    "json" => false,
    "enabled" => false,
    "json_structure" => array(),
    "apikey" => null,
    "verb" => null,
  );  
  debugSection();
  return $return_object;
}

/* Empty API List */
function emptyAPIList() {
  debugSection("emptyAPIList");
  global $apiList;
  $apiList = array();
  refreshAPIList();
  debugSection();
}

/* Lookup API */
function lookupAPI($element,$value) {
  debugSection("lookupAPI");
  global $apiList;
  $return_object = getEmptyAPIEntry();
  if (is_string($element)) {
    $element = normalizeKey($element);
    if (isValidAPIElement($element)) {
      foreach ($apiList as $item) {
        if (!is_null($item['name'])) {
          if (isset($item[$element])) {
            if (strcasecmp($item[$element], $value) == 0) {
              $return_object = array();
              $return_object = $item;
              break;
            }
          }
        }
      }
    }
  }
  debugSection();
  return $return_object;
}

/*  Populate API URL */
function getAPIurl($url,$domain,$size = 0,$apikey = null) {
  debugSection("getAPIurl");
  $processed_url = null;
  if ((is_string($url)) && (is_string($domain))) {
    if (!is_numeric($size)) { $size = 0; }
    if ($size == 0) { $size = getConfiguration("global","icon_size"); }
    $processed_url = $url;
    if (!is_null($domain)) { $processed_url = str_replace("<DOMAIN>",$domain,$processed_url); }
    if ($size != 0) { $processed_url = str_replace("<SIZE>",$size,$processed_url); }
    if (!is_null($apikey)) { $processed_url = str_replace("<APIKEY>",$apikey,$processed_url); }
  }
  debugSection();
  return $processed_url;
}

/*
** Capability Controller
*/
function addCapability($scope = "global", $capability = null,$value = false) {
  debugSection("addCapability");
  global $capabilities;
  $retval = false;
  if (is_string($scope)) {
    if (!is_null($capability)) {
      $capability = normalizeKey($capability);
      if (is_string($capability)) {
        $capabilities[$scope][$capability] = $value;
        $retval = true;
      }
    }
  }
  debugSection();
  return $retval;
}

function getCapability($scope = "global", $capability = null) {
  debugSection("getCapability");
  global $capabilities;
  $value = false;
  if (is_string($scope)) {
    if (!is_null($capability)) {
      if (is_string($capability)) {
        $capability = normalizeKey($capability);
        if (isset($capabilities[$scope][$capability])) { $value = $capabilities[$scope][$capability]; }
      }
    }
  }
  debugSection();
  return $value;
}

/*
** Blocklist Controller
*/
function addBlocklist($value) {
  debugSection("addBlocklist");
  global $blockList;
  $retval = false;
  if (is_string($value)) {
    if (isset($value)) {
      if (!searchBlocklist($value)) {
        array_push($blockList,strtolower($value));
        $retval = true;
      }
    }
  }
  debugSection();
  return $retval;
}

function searchBlocklist($value) {
  debugSection("searchBlocklist");
  global $blockList;
  $retval = false;
  if (isset($value)) {
    if (is_string($value)) {
      foreach ($blockList as $item) {
        if (strcasecmp($item, $value) == 0) {
          $retval = true;
          break;
        }
      }
    }
  }
  debugSection();
  return $retval;
}

/*
** List Controller
*/
function loadList($list) {
  debugSection("loadList");
  $retval = array();
  if (isset($list)) {
    if (is_string($list)) {
      if (file_exists($list)) {
        $retval = file($list,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      } else {
        if (count($retval) == 0) {
          $retval = explode(",",str_replace(array(",",";"," "),",",$list));
        }
      }
    }
  }
  debugSection();
  return $retval;
}

/*
**  Internal Data Controller
*/
function setItem($name,$value = null) {
  debugSection("setItem");
  global $internalData;
  $retval = false;
  if (is_string($name)) {
    $name = normalizeKey($name);
    $internalData[$name] = $value;
    $retval = true;
  }
  debugSection();
  return $retval;
}

function getItem($name) {
  debugSection("getItem");
  global $internalData;
  $value = null;
  if (is_string($name)) {
    $name = normalizeKey($name);
    if (isset($internalData[$name])) { $value = $internalData[$name]; }
  }
  debugSection();
  return $value;
}

/*
**  Statistics Controller
*/
function addStatistic($name,$value = null, $replaceIfTrue = false) {
  debugSection("addStatistic");
  global $statistics;
  if (is_string($name)) {
    $name = normalizeKey($name);
    if (!isStatisticDefined($name,$replaceIfTrue)) {
      if (is_null($value)) { $statistics[$name] = 0; } else { $statistics[$name] = $value; }
    }
  }
  debugSection();
}

/*
**  Does Statistic Exist
*/
function isStatisticDefined($name, $deleteIfTrue = false) {
  debugSection("isStatisticDefined");
  global $statistics;
  $retval = false;
  if (is_string($name)) {
    $name = normalizeKey($name);
    foreach ($statistics as $item) {
      if (isset($item['name'])) {
        if ($item['name'] == $name) {
          $retval = true;
          break;
        }
      }
    }
  }
  if (($retval) && ($deleteIfTrue)) {
    if (deleteStatistic($name)) {
      $retval = false;
    }
  }
  debugSection();
  return $retval;
}

function updateStatistic($name,$value) {
  debugSection("updateStatistic");
  global $statistics;
  if (is_string($name)) {
    $name = normalizeKey($name);
    if (isset($statistics[$name])) { if (!is_null($value)) { $statistics[$name] = $value; } }
  }
  debugSection();
}

function incrementStatistic($name,$value = 1) {
  debugSection("incrementStatistic");
  global $statistics;
  if (is_string($name)) {
    $name = normalizeKey($name);
    if (is_numeric($value)) {
      if (isset($statistics[$name])) { if (!is_null($value)) { $statistics[$name] += $value; } }
    }
  }
  debugSection();
}

function decrementStatistic($name,$value = 1) {
  debugSection("decrementStatistic");
  global $statistics;
  if (is_string($name)) {
    $name = normalizeKey($name);
    if (is_numeric($value)) {
      if (isset($statistics[$name])) { if (!is_null($value)) { $statistics[$name] -= $value; } }
    }
  }
  debugSection();
}

function getStatistic($name) {
  debugSection("getStatistic");
  global $statistics;
  $retval = 0;
  if (is_string($name)) {
    $name = normalizeKey($name);
    if (isset($statistics[$name])) { $retval = $statistics[$name]; }
  }
  debugSection();
  return $retval;
}

function deleteStatistic($name) {
  debugSection("deleteStatistic");
  global $statistics;
  $retval = false;
  if (is_string($name)) {
    $name = normalizeKey($name);
    if (isset($statistics[$name])) {
      unset($statistics[$name]);
      $retval = true;
    }
 }
 debugSection();
 return $retval;
}

/*
**  Processed Controller
**  
**  Structure:
**    url         key field (string)
**    favicon     url for the favicon (string)
**    icontype    mime content-type (string)
**    method      method used to get favicon (string)
**    local       local pathname (string)
**    hash        md5hash of the icon
**    tries       number of attempts made (numeric)
**    overwrite   was overwrite used? (boolean)
**    saved       was icon saved locally? (boolean)
**    elapsed     elapsed time to get icon (float)
**    state       current status (numeric lookup)
**    updated     was an updated icon found? (boolean)
**    accepted    icon has met requirements (if state is STATE_FOUND)
**
**  Usage:
**    Most of the functions are framework, these are the main ones:
**
**    addProcessEntry($url)     
**    updateProcessEntry($url,$element,$value)
**    isIconAccepted($url)
**    
**
*/

/*  Is the element valid? */
function isValidProcessElement($element) {
  debugSection("isValidProcessElement");
  $retval = false;
  if (isset($element)) {
    if (!is_null($element)) {
      if (is_string($element)) {
        $element = normalizeKey($element);
        if ($element == "url") { $retval = true; }
        if ($element == "favicon") { $retval = true; }
        if ($element == "icontype") { $retval = true; }
        if ($element == "method") { $retval = true; }
        if ($element == "local") { $retval = true; }
        if ($element == "hash") { $retval = true; }
        if ($element == "tries") { $retval = true; }
        if ($element == "overwrite") { $retval = true; }
        if ($element == "saved") { $retval = true; }
        if ($element == "elapsed") { $retval = true; }
        if ($element == "state") { $retval = true; }
        if ($element == "updated") { $retval = true; }
        if ($element == "accepted") { $retval = true; }
      }
    }
  }
  debugSection();
  return $retval;
}

/*  Add a new Entry */
function addProcessEntry($url) {
  debugSection("addProcessEntry");
  global $processed;
  $retval = false;
  if (is_string($url)) {
    if (isProcessDefined($url)) {
      writeLog("'$url' already has a processed entry",TYPE_TRACE);
    } else {
      $entry['url'] = normalizeKey($url);
      array_push($processed,$entry);
      $retval = true;
    }
  }
  debugSection();
  return $retval;
}

/*  Is it a valid entry? */
function isProcessDefined($url) {
  debugSection("isProcessDefined");
  global $processed;
  $retval = false;
  if (is_string($url)) {
    $url = normalizeKey($url);
    foreach ($processed as $item) {
      if (isset($item['url'])) {
        if ($item['url'] == $url) {
          $retval = true;
          break;
        }
      }
    }
  }
  debugSection();
  return $retval;
}

/*  Update an Entry */
function updateProcessEntry($url,$element,$value = null) {
  debugSection("updateProcessEntry");
  global $processed;
  $retval = false;
  if (is_string($url)) {
    $entry = getProcessEntry('url',normalizeKey($url));
    if (isset($entry['url'])) {
      $element = normalizeKey($element);
      if (isValidProcessElement($element)) {
        $entry[$element] = $value;
        if (updateProcessRecord($entry)) {
          $retval = true;
        }
      }
    }
  }
  debugSection();
  return $retval;
}

/*  Update the Structure */
function updateProcessRecord($entry) {
  debugSection("updateProcessRecord");
  global $processed;
  $retval = false;
  if (is_array($entry)) {
    if (isset($entry['url'])) {
      $found_key = array_search($entry['url'], array_column($processed, 'url'));
      if ($found_key !== false) {
        $processed[$found_key] = $entry;
        $retval = true;
      }
    }
  }
  debugSection();
  return $retval;
}

/*  Return an Empty Object */
function getEmptyProcessEntry() {
  debugSection("getEmptyProcessEntry");
  $return_object = array(
    "url" => null,
    "favicon" => null,
    "icontype" => null,
    "method" => null,
    "local" => null,
    "saved" => false,
    "elapsed" => 0,
    "overwrite" => false,
    "hash" => null,
    "tries" => 0,
    "state" => STATE_WANTED,
    "updated" => false,
    "accepted" => false,
  );
  debugSection();
  return $return_object;
}

/*  Get Process Entry */
function getProcessEntry($element,$value) {
  debugSection("getProcessEntry");
  global $processed;
  $return_object = getEmptyProcessEntry();
  if (is_string($element)) {
    $element = normalizeKey($element);
    foreach ($processed as $item) {
      if (!is_null($item['url'])) {
        if (isset($item[$element])) {
          if (strcasecmp($item[$element], $value) == 0) {
            $return_object = $item;
            break;
          }
        }
      }
    }
  }
  debugSection();
  return $return_object;
}

/*  Shortcut Functions  */
/*  Shortcut to see if Icon is "Wanted" */
function isIconWanted($url) {
  debugSection("isIconWanted");
  $retval = false;
  if (is_string($url)) {
    $object = getProcessEntry("url",$url);
    if (!empty($object)) {
      if (isset($object['state'])) { if ($object['state'] == STATE_WANTED) { $retval = true; } }
    }
  }
  debugSection();
  return $retval;
}

/*  Shortcut to see if Icon is "Accepted" */
function isIconAccepted($url) {
  debugSection("isIconAccepted");
  $retval = false;
  if (is_string($url)) {
    $object = getProcessEntry("url",$url);
    if (!empty($object)) {
      if ((isset($object['state'])) && (isset($object['accepted']))) {
        if (($object['state'] == STATE_FOUND) && ($object['accepted'])) {
          $retval = true;
        }
      }
    }
  }
  debugSection();
  return $retval;
}
/*
**  Last Load Result Controller
**
**  NOTE:
**    This is always only *one* last load record.
**
**
**  This is a storage array to buffer loading
**    url             url accessed (key)
**    method          method used (string)
**    content_type    MIME content type (if known)
**    http_code       HTTP Response (if known/applicable)
**    scheme          Protocol Used (null if file)
**    content         The data
**    hash            Hash of the data
**    valid           Is this record valid? (boolean)
**    error           Is there an error? (boolean)
**    response        Response Text (string)
**    response_code   Intepreted Response Code
**    added           Unix time when this record was added
**
**  Usage:
**    lastLoadResult($url,$method,$content_type,$http_code,$scheme,$protocol,$content)
**    getLastLoadResult($element)
**    zeroLastLoadResult()          
**
**    With getLastLoadResult if the $element parameter is omitted, the entire record is returned.
**
*/

/*  Is the element valid? */
function isValidLastLoadElement($element) {
  debugSection("isValidLastLoadElement");
  $retval = false;
  if (isset($element)) {
    if (!is_null($element)) {
      if (is_string($element)) {
        $element = normalizeKey($element);
        if ($element == "url") { $retval = true; }
        if ($element == "method") { $retval = true; }
        if ($element == "content_type") { $retval = true; }
        if ($element == "http_code") { $retval = true; }
        if ($element == "scheme") { $retval = true; }
        if ($element == "protocol") { $retval = true; }
        if ($element == "content") { $retval = true; }
        if ($element == "hash") { $retval = true; }
        if ($element == "valid") { $retval = true; }
        if ($element == "error") { $retval = true; }
        if ($element == "response") { $retval = true; }
        if ($element == "response_code") { $retval = true; }
        if ($element == "added") { $retval = true; }
      }
    }
  }
  debugSection();
  return $retval;
}

/*  Is the current structure valid? */
function isLastLoadResultValid() {
  debugSection("isLastLoadResultValid");
  global $lastLoad;
  $retval = false;
  if (isset($lastLoad['valid'])) {
    if (is_bool($lastLoad['valid'])) {
      $retval = $lastLoad['valid'];
    }
  }
  debugSection();
  return $retval;
}

/*  Populate Structure */
function lastLoadResult($url = null,$method = null,$content_type = null,$http_code = null,$scheme = null,$protocol = null,$content = null) {
  debugSection("lastLoadResult");
  global $lastLoad;
  $retval = false;
  if (!is_null($url)) { if (!is_string($url)) { $url = null; } }
  if (!is_null($method)) { if (!is_string($method)) { $method = null; } }
  if (!is_null($content_type)) { if (!is_string($content_type)) { $content_type = null; } }
  if (!is_null($http_code)) { if (!is_numeric($http_code)) { $http_code = null; } }
  if (!is_null($scheme)) { if (!is_string($scheme)) { $scheme = null; } }
  if (!is_null($protocol)) { if (!is_string($protocol)) { $protocol = null; } }
  if (!is_null($url)) { $retval = true; }
  $RequestResponse = lookupHTTPResponse($http_code);
  $is_error = true;
  if ($RequestResponse['ok']) { $is_error = false; }
  $lastLoad = array(
    "url" => $url,
    "method" => $method,
    "content_type" => $content_type,
    "http_code" => $http_code,
    "scheme" => $scheme,
    "protocol" => $protocol,
    "content" => $content,
    "hash" =>  md5($content),
    "valid" => setBoolean($retval),
    "added" => time(),
    "error" => $is_error,
    "response" => $RequestResponse['description'],
    "response_code" => $RequestResponse['code'],
  );
  debugSection();
  return $retval;
}

/*  Destroy Structure */
function zeroLastLoadResult() {
  debugSection("zeroLastLoadResult");
  global $lastLoad;
  $lastLoad = getEmptyLastLoadResult();
  debugSection();
  return true;
}

/*  Return an Empty Structure */
function getEmptyLastLoadResult() {
  debugSection("getEmptyLastLoadResult");
  $entry = array(
    "url" => null,
    "method" => null,
    "content_type" => null,
    "http_code" => null,
    "scheme" => null,
    "protocol" => null,
    "content" => null,
    "hash" =>  null,
    "valid" => false,
    "error" => true,
    "added" => -1,
    "response" => "EMPTY_RECORD",
    "response_code" => "(null)",
  );
  debugSection();
  return $entry;
}

/*  Get Structure or Element From It */
function getLastLoadResult($element = null) {
  debugSection("getLastLoadResult");
  global $lastLoad;
  $return_data = getEmptyLastLoadResult();
  if (is_null($element)) {
    $return_data = $lastLoad;
  } else {
    if (is_string($element)) {
      $element = normalizeKey($element);
      if (isValidLastLoadElement($element)) {
        if (isset($lastLoad[$element])) {
          $return_data = $lastLoad[$element];
        } else {
          $return_data = null;
        }
      } else {
        $return_data = null;
      }
    }
  }
  debugSection();
  return $return_data;
}

/*  Timers
**
**  At least one second precision, more depending on PHP version and extensions
**
**  Structure:
**    name          key
**    start         start time
**    type          Type of timer used
**    finish        finish time
**    elapsed       elapsed time (microseconds)
**    elapsed_hr    elapsed time (high resolution)
**    running       timer active? (boolean)
**      
**
**  Usage:
**    startTimer("name")      : starts the timer, returns start time or -1 if error
**    stopTimer("name")       : stops the timer, returns end time or -1 if error
**    getElapsedTime("name")  : returns the elapsed time for the timer (including a running one) or -1 if an error
**    getTimer("name")        : returns the entire object
**
**  There are three types of timers used.
**    hrtime, microtime and time
**
**  NOTE: A specific timer can be requested when the timer is started, startTimer("name",TIME_TYPE_HRTIME) 
**
**  Once a timer is started, the end timer will try to use the same method that the start used.
*/

/*  Get Time  */
function getTime($request_type = TIME_TYPE_ANY) {
  debugSection("getTime");
  $time = null;
  $type = null;
  if (!is_numeric($request_type)) { $request_type = TIME_TYPE_ANY; }
  if (is_null($time)) {
    if (($request_type == TIME_TYPE_ANY) || ($request_type == TIME_TYPE_HRTIME)) {
      if (getConfiguration("extensions","hrtime")) { 
        $value = hrtime(true);
        if ($value !== false) {
          $time = $value;
          $type = TIME_TYPE_HRTIME;
        }
      }
    }
  }
  if (is_null($time)) {
    if (($request_type == TIME_TYPE_ANY) || ($request_type == TIME_TYPE_MICROTIME)) {
      $value = microtime(true);
      if ($value !== false) {
        $time = $value;
        $type = TIME_TYPE_MICROTIME;
      }
    }
  }
  if (is_null($time)) {
    if (($request_type == TIME_TYPE_ANY) || ($request_type == TIME_TYPE_STANDARD)) {
      $time = time();
      $type = TIME_TYPE_STANDARD;
    }
  }
  if (is_null($time)) {
    $time = -1;
    $type = -1;
  }
  $retval = array(
    "time" => floatval($time),
    "type" => $type,
  );
  debugSection();
  return $retval;
}

/*  Start Timer */
function startTimer($name,$type = TIME_TYPE_ANY) {
  debugSection("startTimer");
  global $timers;
  $retval = null;
  $name = normalizeKey($name);
  $flag_start_timer = false;
  $found_key = array_search($name, array_column($timers, 'name'));
  if ($found_key === false) {
    writeLog("Timer for '$name' can be created, not found",TYPE_SPECIAL);
    $flag_start_timer = true;
  } else {
    if (!array_key_exists($found_key,$timers)) {
      writeLog("Timer for '$name' can be created, found ($found_key) but array key is missing",TYPE_SPECIAL);
      $flag_start_timer = true;
    }
  }
  if ($flag_start_timer) {
    $entry = getEmptyTimer();
    $stopwatch = getTime($type);
    if ($stopwatch['time'] != -1) {
      writeLog("Starting Timer for '$name'",TYPE_SPECIAL);
      $entry['name'] = $name;
      $entry['start'] = $stopwatch['time'];
      $entry['type'] =  $stopwatch['type'];
      $entry['finish'] = 0;
      $entry['elapsed'] = -1;
      $entry['elapsed_hr'] = -1;
      $entry['running'] = true;
      $retval = $entry['start'];
      array_push($timers,$entry);
    } else {
      writeLog("Failed to Start Timer for '$name'",TYPE_SPECIAL);
      $retval = -1;
    }
  } else {
    writeLog("Timer '$name' already exists.",TYPE_SPECIAL);
  }
  debugSection();
  return $retval;
}

/*  Stop Timer */
function stopTimer($name,$elapsed = false) {
  debugSection("stopTimer");
  global $timers;
  $retval = null;
  $name = normalizeKey($name);
  $found_key = array_search($name, array_column($timers, 'name'));
  if ($found_key !== false) {
    if (array_key_exists($found_key,$timers)) {
      if ($timers[$found_key]['running']) {
        writeLog("Stopping Timer for '$name' ($found_key), return elapsed? " . showBoolean($elapsed),TYPE_SPECIAL);
        $stopwatch = getTime($timers[$found_key]['type']);
        if ($stopwatch['time'] != -1) {
          $timers[$found_key]['finish'] = $stopwatch['time'];
          if ($timers[$found_key]['start'] != -1) {
            $elapsed = floatval($timers[$found_key]['finish'] - $timers[$found_key]['start']);
            if ($timers[$found_key]['type'] == TIME_TYPE_HRTIME) {
              $timers[$found_key]['elapsed_hr'] = $elapsed;
              $timers[$found_key]['elapsed'] = $elapsed/1e+6;
            }
            if ($timers[$found_key]['type'] == TIME_TYPE_MICROTIME) {
              $timers[$found_key]['elapsed'] = $elapsed;
            }
          }
          if ($elapsed) {
            $retval = $timers[$found_key]['elapsed'];
          } else {
            $retval = $timers[$found_key]['finish'];
          }
          $timers[$found_key]['running'] = false;
        } else {
          $retval = -1;      
        }
      } else {
        writeLog("Timer for '$name' already stopped, return elapsed? " . showBoolean($elapsed),TYPE_SPECIAL);
        if ($elapsed) {
          $retval = $timers[$found_key]['elapsed'];
        } else {
          $retval = $timers[$found_key]['finish'];
        }
      }
    } else {
      writeLog("Request to stop timer '$name' which has a missing key",TYPE_SPECIAL);
    }
  } else {
    writeLog("Request to stop timer '$name' which does not exist",TYPE_SPECIAL);
  }
  debugSection();
  return $retval;
}

/*  Get Empty Timer */
function getEmptyTimer() {
  debugSection("getEmptyTimer");
  $entry = array(
    "name" => null,
    "start" => null,
    "finish" => null,
    "type" => TIME_TYPE_ANY,
    "elapsed" => -1,
    "elapsed_hr" => -1,
    "running" => false,
  );
  debugSection();
  return $entry;
}

/*  Get Timer Value */
function getTimer($name) {
  debugSection("getTimer");
  global $timers;
  $entry = getEmptyTimer();
  if (is_string($name)) {
    $name = normalizeKey($name);
    $found_key = array_search($name, array_column($timers, 'name'));
    if ($found_key !== false) {
      writeLog("Returning timer object '$name' ($found_key) as requested",TYPE_SPECIAL);
      $entry = $timers[$found_key];
    } else {
      writeLog("Timer object '$name' was requested but not found",TYPE_SPECIAL);
    }
  }
  debugSection();
  return $entry;
}

/*  Get Elapsed Time */
function getElapsedTime($name) {
  debugSection("getElapsedTime");
  global $timers;
  $elapsed = -1;
  $name = normalizeKey($name);
  $found_key = array_search($name, array_column($timers, 'name'));
  if ($found_key !== false) {
    writeLog("Elapsed time for timer '$name' ($found_key) has been requested",TYPE_SPECIAL);
    $start = $timers[$found_key]['start'];
    $finish = $timers[$found_key]['finish'];
    $type = $timers[$found_key]['type'];
    if ($timers[$found_key]['elapsed'] != -1) {
      $elapsed = $timers[$found_key]['elapsed'];
      writeLog("Returning calculated value",TYPE_SPECIAL);
    } else {
      if ((!is_null($start)) && (!is_null($finish))) {
        if (($start != -1) && ($finish != -1)) {
          writeLog("Calculated value missing, calculating manually",TYPE_SPECIAL);
          $elapsed = floatval($finish - $start);
          if ($elapsed != -1) {
            if ($type == TIME_TYPE_HRTIME) {
              $elapsed = $elapsed/1e+6;
            }
          }
        } else {
          $elapsed = -1;
        }
      } elseif (is_null($start)) {
        if ($start != -1) {
          $stopwatch = getTime($type);
          if ($stopwatch['time'] != -1) {
            writeLog("Calculated running value",TYPE_SPECIAL);
            $elapsed = floatval($stopwatch['time'] - $start);
            if ($elapsed != -1) {
              if ($type == TIME_TYPE_HRTIME) {
                $elapsed = $elapsed/1e+6;
              }
            }
          } else {
            $elapsed = -1;
          }
        }
      }
    }
  }
  debugSection();
  return $elapsed;
}

/*  Reset Timer 
**
**  The command "unset($timers[$found_key]);" should delete the entry but it does not
**    array_search will still find it.
**
**  As a workaground, the values are zeroed instead.
**
*/
function resetTimer($name) {
  debugSection("resetTimer");
  global $timers;
  
  $retval = false;
  $name = normalizeKey($name);
  $found_key = array_search($name, array_column($timers, 'name'));
  if ($found_key !== false) {
    #  unset($timers[$found_key]);
    $timers[$found_key]['name'] = null;
    $timers[$found_key]['type'] = TIME_TYPE_ANY;
    $timers[$found_key]['start'] = 0;
    $timers[$found_key]['finish'] = 0;
    $timers[$found_key]['type'] = 0;
    $timers[$found_key]['elapsed'] = -1;
    $timers[$found_key]['elapsed_hr'] = -1;
    $timers[$found_key]['running'] = false;
    writeLog("Resetting Timer for '$name' (key: $found_key)",TYPE_SPECIAL);
    $retval = true;
  }
  debugSection();
  return $retval;
}

/*  Debug Functions */
function debugDumpStructures() {
  debugSection("debugDumpStructures");
  global $processed;
  global $internalData;
  global $configuration;
  global $capabilities;
  global $statistics;
  $retval = false;
  if (getConfiguration("global","debug")) {
    if (getConfiguration("debug","dump_structures")) {
      $report = "";
      $report .= getItem("banner") . "\n";
      $report .= getTimestamp() . "\n";
      $report .= getConfiguration("logging","separator") . "\n";
      $report .= "[capabilities]\n";
      $report .= print_r($capabilities,true);
      $report .= getConfiguration("logging","separator") . "\n";
      $report .= "[configuration]\n";
      $report .= print_r($configuration,true);
      $report .= getConfiguration("logging","separator") . "\n";
      $report .= "[statistics]\n";
      $report .= print_r($statistics,true);
      $report .= getConfiguration("logging","separator") . "\n";
      $report .= "[internaldata]\n";
      $report .= print_r($internalData,true);
      $report .= getConfiguration("logging","separator") . "\n";
      $report .= "[processed]\n";
      $report .= print_r($processed,true);
      $report .= getConfiguration("logging","separator") . "\n";   
      if (getConfiguration("extensions","put")) {
        if (@file_put_contents(getConfiguration("debug","dump_file"), $report) === false) {
          $retval = false;
        } else {
          $retval = true;
        }
      } else {
        # TO DO:
        #   fallback
      }
    }
  }
  debugSection();
}

/*
**  Determine Capabilities of PHP installation
**  addCapability($scope,$capability,$value)
*/
function determineCapabilities() {
  debugSection("determineCapabilities");
  $flag_hrtime = false;
  $flag_console = false;
  $flag_curl = false;
  $flag_get_contents = false;
  $flag_put_contents = false;
  $flag_fileinfo = false;
  $flag_fileinfo_extension = false;
  $flag_mimetype = false;
  $flag_exif = false;
  $flag_mbstring = false;
  $flag_gd = false;
  $flag_gmagick = false;
  $flag_imagemagick = false;
  if (php_sapi_name() == "cli") { $flag_console = true; }
  if (extension_loaded("curl")) { if (function_exists('curl_version')) { $flag_curl = true; } }
  if (function_exists('file_get_contents')) { $flag_get_contents = true; }
  if (function_exists('file_put_contents')) { $flag_put_contents = true; }
  if (function_exists('mime_content_type')) { $flag_mimetype = true; }
  if (function_exists('hrtime')) { $flag_hrtime = true; }
  if (extension_loaded("fileinfo")) { if (function_exists('finfo_open')) { $flag_fileinfo = true; } }
  if (extension_loaded("gd")) { if (function_exists('getimagesize')) { $flag_gd = true; } }
  if (extension_loaded("imagick")) { $flag_imagemagick = true; }
  if (extension_loaded("gmagick")) { $flag_gmagick = true; }
  if (extension_loaded("mbstring")) { if (function_exists('mb_detect_encoding')) { $flag_mbstring = true; } }
  if ((extension_loaded("exif")) && (extension_loaded("mbstring"))) { if (function_exists('exif_imagetype')) { $flag_exif = true; } }

  if ($flag_fileinfo) { if (defined("FILEINFO_EXTENSION")) {  $flag_fileinfo_extension = true; } }
  addCapability("php","version",phpversion());
  addCapability("php","console",$flag_console);
  addCapability("php","curl",$flag_curl);
  addCapability("php","exif",$flag_exif);
  addCapability("php","get",$flag_get_contents);
  addCapability("php","put",$flag_put_contents);
  addCapability("php","fileinfo",$flag_fileinfo);
  addCapability("php","fileinfo_extension",$flag_fileinfo_extension);
  addCapability("php","mbstring",$flag_mbstring);
  addCapability("php","mimetype",$flag_mimetype);
  addCapability("php","gd",$flag_gd);
  addCapability("php","imagemagick",$flag_imagemagick);
  addCapability("php","gmagick",$flag_gmagick);
  addCapability("php","hrtime",$flag_hrtime);
  addCapability("os","name",php_uname('s'));
  addCapability("os","release",php_uname('r'));
  addCapability("os","version",php_uname('v'));
  addCapability("os","machine",php_uname('m'));
  if (defined("DIRECTORY_SEPARATOR")) { addCapability("os","directory_separator",DIRECTORY_SEPARATOR); }
  if (strtolower(substr(getCapability("os","name"), 0, 3)) === 'win') {
    addCapability("os","case_sensitive",false);
    if (!defined("DIRECTORY_SEPARATOR ")) { addCapability("os","directory_separator","\\"); }
  } else {
    addCapability("os","case_sensitive",true);
    if (!defined("DIRECTORY_SEPARATOR ")) { addCapability("os","directory_separator","/"); }
  }
  
  setConfiguration("extensions","curl",(getCapability("php","curl")));
  setConfiguration("extensions","exif",(getCapability("php","exif")));
  setConfiguration("extensions","get",(getCapability("php","get")));
  setConfiguration("extensions","put",(getCapability("php","put")));
  setConfiguration("extensions","mbstring",(getCapability("php","mbstring")));
  setConfiguration("extensions","fileinfo",(getCapability("php","fileinfo")));
  setConfiguration("extensions","mimetype",(getCapability("php","mimetype")));
  setConfiguration("extensions","gd",(getCapability("php","gd")));
  setConfiguration("extensions","imagemagick",(getCapability("php","imagemagick")));
  setConfiguration("extensions","gmagick",(getCapability("php","gmagick")));
  setConfiguration("extensions","hrtime",(getCapability("php","hrtime")));
  
  debugSection();
}

function isConfidenceAccepted($level = 0) {
  $retval = false;
  $acceptlevel = getConfiguration("global","acceptable_data_confidence");
  if (is_numeric($level) && is_numeric($acceptlevel)) { 
    if ($level > 0 && $level >= $acceptlevel) { $retval = true; }
  }
  return $retval;
}
  
function getConfidenceText($level = 0) {
  $retval = "invalid";
  if (is_numeric($level)) {
    if ($level >= CONFIDENCE_CERTAIN) {
      $retval = "certain";
    } elseif ($level >= CONFIDENCE_HIGH && $level < CONFIDENCE_CERTAIN) {
      $retval = "high";
    } elseif ($level >= CONFIDENCE_MEDIUM && $level < CONFIDENCE_HIGH) {
      $retval = "medium";
    } elseif ($level >= CONFIDENCE_LOW && $level < CONFIDENCE_MEDIUM) {
      $retval = "low";
    } elseif ($level >= CONFIDENCE_UNCERTAIN && $level < CONFIDENCE_LOW) {
      $retval = "uncertain";
    } elseif ($level < CONFIDENCE_UNCERTAIN) {
      $retval = "unknown";
    }
  }
  return $retval;
}


/*  Show Capabilities */
function showCapabilities($level = TYPE_VERBOSE) {
  debugSection("showCapabilities");
  writeLog("Capabilities:",TYPE_VERBOSE);
  writeLog("* Extension cURL Enabled: " . showBoolean(getConfiguration("extensions","curl")),TYPE_VERBOSE);
  writeLog("* Extension EXIF Enabled: " . showBoolean(getConfiguration("extensions","exif")),TYPE_VERBOSE);
  writeLog("* Extension FileInfo Enabled: " . showBoolean(getConfiguration("extensions","fileinfo")),TYPE_VERBOSE);
  writeLog("* Extension GD Enabled: " . showBoolean(getConfiguration("extensions","gd")),TYPE_VERBOSE);
  writeLog("* Extension Gmagick Enabled: " . showBoolean(getConfiguration("extensions","gmagick")),TYPE_VERBOSE);
  writeLog("* Extension ImageMagick Enabled: " . showBoolean(getConfiguration("extensions","imagemagick")),TYPE_VERBOSE);
  writeLog("* Function file_get_contents Available: " . showBoolean(getConfiguration("extensions","get")),TYPE_VERBOSE);
  writeLog("* Function file_put_contents Available: " . showBoolean(getConfiguration("extensions","put")),TYPE_VERBOSE);
  writeLog("* Function hrtime Available: " . showBoolean(getConfiguration("extensions","hrtime")),TYPE_VERBOSE);
  writeLog("* Function mime_content_type Available: " . showBoolean(getConfiguration("extensions","mimetype")),TYPE_VERBOSE);
  debugSection();
}
 
/*  Validate Configuration */
function validateConfiguration() {
  debugSection("validateConfiguration");
  
  validateConfigurationSetting("global","debug",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","blocklist",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","acceptable_data_confidence",CONFIG_TYPE_NUMERIC,0,100);
  validateConfigurationSetting("global","api",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","allow_octet_stream",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","icon_size",CONFIG_TYPE_NUMERIC,RANGE_ICON_SIZE_MINIMUM,RANGE_ICON_SIZE_MAXIMUM);
  validateConfigurationSetting("global","tenacious",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","showconfig",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("console","enabled",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("console","level",CONFIG_TYPE_NUMERIC,0,TYPE_ALL + TYPE_NOTICE + TYPE_WARNING + TYPE_VERBOSE + TYPE_ERROR + TYPE_DEBUGGING + TYPE_TRACE + TYPE_SPECIAL);
  validateConfigurationSetting("console","timestamp",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("curl","verbose",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("curl","showprogress",CONFIG_TYPE_BOOLEAN);  
  validateConfigurationSetting("debug","dump_structures",CONFIG_TYPE_BOOLEAN);  
  validateConfigurationSetting("files","check_local",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("files","local_path",CONFIG_TYPE_PATH,DEFAULT_LOCAL_PATH);
  validateConfigurationSetting("files","overwrite",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("files","store",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("files","store_if_new",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("files","remove_tld",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("http","dns_timeout",CONFIG_TYPE_NUMERIC,RANGE_DNS_TIMEOUT_MINIMUM,RANGE_DNS_TIMEOUT_MAXIMUM);
  validateConfigurationSetting("http","http_timeout",CONFIG_TYPE_NUMERIC,RANGE_HTTP_TIMEOUT_MINIMUM,RANGE_HTTP_TIMEOUT_MAXIMUM);
  validateConfigurationSetting("http","http_timeout_connect",CONFIG_TYPE_NUMERIC,RANGE_HTTP_CONNECT_TIMEOUT_MINIMUM,RANGE_HTTP_CONNECT_TIMEOUT_MAXIMUM);
  validateConfigurationSetting("http","maximum_redirects",CONFIG_TYPE_NUMERIC,RANGE_HTTP_REDIRECTS_MINIMUM,RANGE_HTTP_REDIRECTS_MAXIMUM);
  validateConfigurationSetting("http","try_homepage",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("http","use_buffering",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("http","default_protocol_https",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("http","default_protocol_fallback",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("logging","append",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("logging","enabled",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("logging","level",CONFIG_TYPE_NUMERIC,0,TYPE_ALL + TYPE_NOTICE + TYPE_WARNING + TYPE_VERBOSE + TYPE_ERROR + TYPE_DEBUGGING + TYPE_TRACE + TYPE_SPECIAL);
  validateConfigurationSetting("logging","timestamp",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("mode","console",CONFIG_TYPE_BOOLEAN);
  
  validateConfigurationSetting("extensions","curl",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","exif",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","fileinfo",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","gd",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","get",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","gmagick",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","hrtime",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","imagemagick",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","mbstring",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","mimetype",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("extensions","put",CONFIG_TYPE_BOOLEAN);
  
  if (getConfiguration("logging","enabled")) {
    if (is_null(getConfiguration("logging","pathname"))) {
      setConfiguration("logging","enabled",false);
    }
    if (getConfiguration("logging","timestamp")) {
      if (is_null(getConfiguration("logging","timestampformat"))) {
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
        setConfiguration("console","timestamp",false);
      }
    }
  }
    
  if (getConfiguration("files","store")) {
    if (is_null(getConfiguration("files","local_path"))) {
        setConfiguration("files","store",false);
    } else {
      if (!file_exists(getConfiguration("files","local_path"))) {
        setConfiguration("files","store",false);
      }
    }
  }
  
  //  Disable any truly unavailable extensions/functions
  if (getConfiguration("extensions","curl")) {
    if (!getCapability("php","curl")) {
      setConfiguration("extensions","curl",false);
    }
  }
  if (getConfiguration("extensions","exif")) {
    if (!getCapability("php","exif")) {
      setConfiguration("extensions","exif",false);
    }
  }
  if (getConfiguration("extensions","fileinfo")) {
    if (!getCapability("php","fileinfo_extension")) {
      setConfiguration("extensions","fileinfo",false);
    }
  }
  if (getConfiguration("extensions","fileinfo")) {
    if (!getCapability("php","fileinfo")) {
      setConfiguration("extensions","fileinfo",false);
    }
  }
  if (getConfiguration("extensions","gd")) {
    if (!getCapability("php","gd")) {
      setConfiguration("extensions","gd",false);
    }
  }
  if (getConfiguration("extensions","get")) {
    if (!getCapability("php","get")) {
      setConfiguration("extensions","get",false);
    }
  }
  if (getConfiguration("extensions","gmagick")) {
    if (!getCapability("php","gmagick")) {
      setConfiguration("extensions","gmagick",false);
    }
  }  
  if (getConfiguration("extensions","hrtime")) {
    if (!getCapability("php","hrtime")) {
      setConfiguration("extensions","hrtime",false);
    }
  }    
  if (getConfiguration("extensions","imagemagick")) {
    if (!getCapability("php","imagemagick")) {
      setConfiguration("extensions","imagemagick",false);
    }
  }  
  if (getConfiguration("extensions","mbstring")) {
    if (!getCapability("php","mbstring")) {
      setConfiguration("extensions","mbstring",false);
    }
  }
  if (getConfiguration("extensions","put")) {
    if (!getCapability("php","put")) {
      setConfiguration("extensions","put",false);
    }
  }

  if (getConfiguration("curl","enabled")) {
    if (!getConfiguration("extensions","curl")) {
      setConfiguration("curl","enabled",false);
    }
  }

  # TO DO:
  #   if saving icons, fully validate by attempting to write a test file to the save path.
  
  debugSection();
}

