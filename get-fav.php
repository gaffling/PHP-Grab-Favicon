<?php
/*

Changelog:
  created API structure
    this should permit adding APIs in the future
  allow enable/disable individual apis
  added http connect timeout
  added dns timeout
  changed $debug to a boolean
  unified output to a single function for both console and html
  moved to a configuration array/structure
  simplified grap_favicon function, only the url is passed in, the other options are read from the configuration structure

TO DO:
  read config file
    will look in current dir
    can specify with -c=path, --config=path (or --configuration)
    should just general configuration be in an array instead of individual globals?
  blocklist of icons (for example if the apis return archive.org's icon)
    list of md5 hashes
    skipped if blocklist is empty or option is goven
    --blocklist=(list of hashes or file with hashes)
    --enableblocklist
    --disableblocklist
  optional query string/form support for options
    default will be disabled for security reasons
  use new structures for configuration/capabiltiies
  
  
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

###### Copyright 2019-2020 Igor Gaffling

*/
 
$time_start = microtime(true);

/*
**  If you wish get-fav.php to be able to process commands issued via a query string or
**  form submission on a webserver, change the setting below to true.
*/
define('ENABLE_WEB_INPUT', false);

/*
**  Set Defaults
*/
define('PROJECT_NAME', 'PHP Grab Favicon');
define('PROGRAM_NAME', 'get-fav');
define('PROGRAM_VERSION', '202305221634');
define('PROGRAM_COPYRIGHT', 'Copyright 2019-2020 Igor Gaffling');

define('DEFAULT_ENABLE_APIS', true);
define('DEFAULT_USE_CURL', true);
define('DEFAULT_STORE', true);
define('DEFAULT_TRY_HOMEPAGE', true);
define('DEFAULT_OVERWRITE', false);
define('DEFAULT_ENABLE_BLOCKLIST', true);
define('DEFAULT_SIZE', 16);
define('DEFAULT_LOCAL_PATH', "./");
define('DEFAULT_HTTP_TIMEOUT', 60);
define('DEFAULT_HTTP_CONNECT_TIMEOUT', 30);
define('DEFAULT_DNS_TIMEOUT', 120);
define('DEFAULT_USER_AGENT', "FaviconBot/1.0/");
define('RANGE_HTTP_TIMEOUT_MINIMUM', 0);
define('RANGE_HTTP_TIMEOUT_MAXIMUM', 600);
define('RANGE_HTTP_CONNECT_TIMEOUT_MINIMUM', 0);
define('RANGE_HTTP_CONNECT_TIMEOUT_MAXIMUM', 600);
define('RANGE_DNS_TIMEOUT_MINIMUM', 0);
define('RANGE_DNS_TIMEOUT_MAXIMUM', 600);
define('BUFFER_SIZE', 128);
define('HTML_WARNING_STYLE', ".HTML_WARNING_STYLE.");
define('SUPPRESS_OUTPUT', "<NONE>");
define('GOOGLE_DEFAULT_ICON_MD5', '3ca64f83fdcf25135d87e08af65e68c9');
define('DEBUG_MESSAGE', 1);
define('CONFIG_TYPE_STRING', 1);
define('CONFIG_TYPE_BOOLEAN', 2);
define('CONFIG_TYPE_NUMERIC', 3);
define('CONFIG_TYPE_PATH', 4);
define('CONFIG_TYPE_SWITCH', 5);
define('CONFIG_TYPE_SWITCH_PAIR', 6);
define('CONFIG_TYPE_USERAGENT', 7);

/*
**  Initialize Arrays and Flags
*/
$blockList = array();
$URLList = array();
$apiList = array();
$configuration = array();
$capabilities = array();
$debug = false;
$consoleMode = false;

/*
**  Determine Capabilities of PHP installation
*/

addCapability("php","console",(php_sapi_name() == "cli"));
addCapability("php","curl",function_exists('curl_version'));
addCapability("php","exif",function_exists('exif_imagetype'));
addCapability("php","get",function_exists('file_get_contents'));


/* Set Configuration Defaults */

setConfiguration("global","debug",false);
setConfiguration("global","api",DEFAULT_ENABLE_APIS);
setConfiguration("global","blocklist",DEFAULT_ENABLE_BLOCKLIST);
setConfiguration("curl","enabled",getCapability("php","curl"));
setConfiguration("curl","verbose",false);
setConfiguration("curl","showprogress",false);
setConfiguration("files","local_path",DEFAULT_LOCAL_PATH);
setConfiguration("files","overwrite",DEFAULT_OVERWRITE);
setConfiguration("files","store",DEFAULT_STORE);
setConfiguration("http","default_useragent",DEFAULT_USER_AGENT);
setConfiguration("http","dns_timeout",DEFAULT_DNS_TIMEOUT);
setConfiguration("http","http_timeout",DEFAULT_HTTP_TIMEOUT);
setConfiguration("http","http_timeout_connect",DEFAULT_HTTP_CONNECT_TIMEOUT);
setConfiguration("http","try_homepage",DEFAULT_TRY_HOMEPAGE);
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
  "tryhomepage",
  "onlyuseapis",
  "disableallapis",
  "enableblocklist",
  "disableblocklist",
  "store",
  "nostore",
  "save",
  "nosave",
  "overwrite",
  "nooverwrite",
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

# TO DO:
#   "enableapi::"
#   "disableapi::"
#
# e.g. --enableapi=google,faviconkit


$options = getopt($shortopts, $longopts);

if ((isset($options['v'])) || (isset($options['ver'])) || (isset($options['version'])))
{
  echo PROJECT_NAME . " (" . PROGRAM_NAME . ") v" . PROGRAM_VERSION ."\n";
  echo PROGRAM_COPYRIGHT . "\n";
  exit;
}

if ((isset($options['help'])) || (isset($options['h'])) || (isset($options['?'])))
{
  echo "Usage: $script_name (Switches)\n\n";
  echo "--list=FILE/LIST            Filename or a delimited list of URLs to check.\n";
  echo "--blocklist=FILE/LIST       Filename or a delimited list of MD5 hashes to block.\n";
  echo "--path=PATH                 Location to store icons (default is " . DEFAULT_LOCAL_PATH . ")\n";
  echo "\n";
  echo "--tryhomepage               Try homepage first, then APIs. (default is true)\n";
  echo "--onlyuseapis               Only use APIs.\n";
  echo "--disableapis               Don't use APIs.\n";
  echo "--enableblocklist           Enable blocklist. (default is true)\n";
  echo "--disableblocklist          Disable blocklist.\n";
  echo "--store                     Store favicons locally. (default is true)\n";
  echo "--nostore                   Do not store favicons locally.\n";
  echo "--overwrite                 Overwrite local favicons (default is false)\n";
  echo "--skip                      Skip local favicons if they are already present. (default is true)\n";
  echo "--consolemode               Force console output.\n";
  echo "--noconsolemode             Force HTML output.\n";
  echo "--debug                     Enable debug messages.\n";
  echo "--user-agent=AGENT_STRING   Customize the user agent.\n";
  echo "--curl-verbose              Enable cURL verbose.\n";
  echo "--http-timeout=SECONDS      Set http timeout (default is 60).\n";
  echo "\n";
  echo "Lists can be separated with space, comma or semi-colon.\n";
  echo "For a complete list of switches, please read the 'switches.txt' file.\n";
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
      $configuration = array_replace_recursive($configuration, $configuration_from_file);
      unset($configuration_from_file);
      validateConfiguration();
    }
  }
}

/*
**   Process Command Line Switches
*/
 
setConfiguration("global","debug",(isset($options['debug']))?$options['debug']:null,null,CONFIG_TYPE_SWITCH);
setConfiguration("global","blocklist",(isset($options['enableblocklist']))?$options['enableblocklist']:null,(isset($options['disableblocklist']))?$options['disableblocklist']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("mode","console",(isset($options['consolemode']))?$options['consolemode']:null,(isset($options['noconsolemode']))?$options['noconsolemode']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("files","local_path",(isset($options['path']))?$options['path']:null,null,CONFIG_TYPE_PATH);
setConfiguration("files","store",(isset($options['store']))?$options['store']:null,(isset($options['nostore']))?$options['nostore']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("files","overwrite",(isset($options['overwrite']))?$options['overwrite']:null,(isset($options['nooverwrite']))?$options['nooverwrite']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("http","try_homepage",(isset($options['tryhomepage']))?$options['tryhomepage']:null,(isset($options['onlyuseapis']))?$options['onlyuseapis']:null,CONFIG_TYPE_SWITCH_PAIR);
setConfiguration("http","useragent",(isset($options['user-agent']))?$options['user-agent']:null,null,CONFIG_TYPE_USERAGENT);
setConfiguration("http","http_timeout",(isset($options['http-timeout']))?$options['http-timeout']:null);
setConfiguration("http","http_timeout_connect",(isset($options['connect-timeout']))?$options['connect-timeout']:null);
setConfiguration("http","dns_timeout",(isset($options['dns-timeout']))?$options['dns-timeout']:null);
setConfiguration("curl","verbose",(isset($options['curl-verbose']))?$options['curl-verbose']:null,null,CONFIG_TYPE_SWITCH);
setConfiguration("curl","showprogress",(isset($options['curl-showprogress']))?$options['curl-showprogress']:null,null,CONFIG_TYPE_SWITCH);

if (isset($options['nocurl'])) { setConfiguration("curl","enabled",false); }
if (isset($options['disableallapis'])) { setConfiguration("global","api",false); }

validateConfiguration();

/*  
**  Process Lists
*/

$URLList = loadList((isset($options['list']))?$options['list']:null);
$blockList = loadList((isset($options['blocklist']))?$options['blocklist']:null);
$enabledAPIList = loadList((isset($options['enableapis']))?$options['enableapis']:null);
$disabledAPIList = loadList((isset($options['disableapis']))?$options['disableapis']:null);

/*
**  Create Blocklist
*/
$blockList = array(
  '3ca64f83fdcf25135d87e08af65e68c9',
  'd0fefd1fde1699e90e96a5038457b061',
);


$flag_enabled = true;

if (isset($options['disableallapis'])) { $flag_enabled = false; }

/* Initialize APIs */
addAPI("faviconkit","https://api.faviconkit.com/<DOMAIN>/16",false,$flag_enabled);
addAPI("favicongrabber","http://favicongrabber.com/api/grab/<DOMAIN>",true,$flag_enabled,array("icons","0","src"));
addAPI("google","http://www.google.com/s2/favicons?domain=<DOMAIN>",false,$flag_enabled);

# TO DO:
#   Go through APIs and enable/disable individually


/* If test URLs is empty, setup testing list */
if (empty($URLList)) {
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

/*  Set PHP User Agent if Required */
initializeUserAgent();

/*  Start the Show */
writeOutput("Debug ON",SUPPRESS_OUTPUT,DEBUG_MESSAGE);

/*  Process List */
foreach ($URLList as $url) {
  $favicons[] = grap_favicon($url);
}

/*  Show Results */
foreach ($favicons as $favicon) {
  if (!empty($favicon)) { writeOutput("Icon: $favicon","<img title=\"$favicon\" style=\"width:32px;padding-right:32px;\" src=\"$favicon\">"); }
}

/*  Show Runtime */
writeOutput("\nRuntime: ".round(microtime(true)-$time_start,2)." Sec.","<br><br><tt>Runtime: ".round((microtime(true)-$_SERVER["REQUEST_TIME_FLOAT"]),2)." Sec.");


/*****************************************************
                FUNCTIONS
*****************************************************/
function grap_favicon($url) {
  // URL to lower case
	$url          = strtolower($url);
  
  //  Init Vars
  $consoleMode  = getConfiguration("mode","console");
  $save         = getConfiguration("files","store");
  $directory    = getConfiguration("files","local_path");
  $trySelf      = getConfiguration("http","try_homepage");
  $debug        = getConfiguration("global","debug");
  $overwrite    = getConfiguration("files","overwrite");

  $api_name = null;
  $api_url = null;
  $api_json = false;
  $api_enabled = false;
  
  $filePath = null;

  if (!$consoleMode) {
    // avoid script runtime timeout
    $max_execution_time = ini_get("max_execution_time");
    set_time_limit(0); // 0 = no timelimit
  }

	// Get the Domain from the URL
  $domain = parse_url($url, PHP_URL_HOST);

  // Check Domain
  $domainParts = explode('.', $domain);
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

  writeOutput("Domain: $domain","<b style=".HTML_WARNING_STYLE.">Domain</b> #".@$domain."#<br>",DEBUG_MESSAGE);

  // If $trySelf == TRUE ONLY USE APIs
  if (isset($trySelf) && $trySelf == true) {

    // Load Page
    $html = load($url);

    if (empty($html)) {
      writeOutput("No data received","<b style=".HTML_WARNING_STYLE.">No data received</b><br>",DEBUG_MESSAGE);
    } else {
      writeOutput("Attempting RegEx Match",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
      // Find Favicon with RegEx
      $regExPattern = '/((<link[^>]+rel=.(icon|shortcut\sicon|alternate\sicon)[^>]+>))/i';
      if (@preg_match($regExPattern, $html, $matchTag)) {
        writeOutput("RegEx Initial Pattern Matched\n" . print_r($matchTag,TRUE),SUPPRESS_OUTPUT,DEBUG_MESSAGE);
        $regExPattern = '/href=(\'|\")(.*?)\1/i';
        if (isset($matchTag[1]) && @preg_match($regExPattern, $matchTag[1], $matchUrl)) {
          writeOutput("RegEx Secondary Pattern Matched",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
          if (isset($matchUrl[2])) {
            writeOutput("Found Match, Building Link",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
            // Build Favicon Link
            $favicon = rel2abs(trim($matchUrl[2]), 'http://'.$domain.'/');
            writeOutput("Match $favicon",'<b style=".HTML_WARNING_STYLE.">Match</b> #'.@$favicon.'#<br>',DEBUG_MESSAGE);
          } else {
            writeOutput("Failed To Find Match",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
          }
        } else {
          writeOutput("RegEx Secondary Pattern Failed To Match",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
        }
      } else {
        writeOutput("RegEx Initial Pattern Failed To Match",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
      }
    }

    // If there is no Match: Try if there is a Favicon in the Root of the Domain
    if (empty($favicon)) {
      $favicon = 'http://'.$domain.'/favicon.ico';
      writeOutput("Attempting Direct Match using $favicon",SUPPRESS_OUTPUT,DEBUG_MESSAGE);

      // Try to Load Favicon
      # if ( !@getimagesize($favicon) ) {
      # https://www.php.net/manual/en/function.getimagesize.php
      # Do not use getimagesize() to check that a given file is a valid image.
      $fileExtension = geticonextension($favicon,false);
      if (is_null($fileExtension)) {
        unset($favicon);
        writeOutput("Failed Direct Match",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
      }
    }
  } // END If $trySelf == TRUE ONLY USE APIs

  // If nothing works: Get the Favicon from API
  if ((!isset($favicon)) || (empty($favicon))) {
    $api_count = getAPICount();
    
    if ($api_count > 0)
    {
      writeOutput("Selecting API",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
      $selectAPI = getRandomAPI();
      if (isset($selectAPI['name'])) {
        $api_name = $selectAPI['name'];
        $api_url = $selectAPI['url'];
        $api_json = $selectAPI['json'];
        $api_enabled = $selectAPI['enabled'];
        $api_json_structure = $selectAPI['json_structure'];
        
        if ($api_enabled)
        {
          writeOutput("Using API: $api_name",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
          $favicon = getAPIurl($api_url,$domain,DEFAULT_SIZE);
          if ($api_json) {
            $echo = json_decode(load($favicon),true);
            if (!is_null($echo)) {
              $favicon = $echo;
              if (!empty($api_json_structure)) {
                foreach ($api_json_structure as $element) {
                  $favicon = $favicon[$element];
                }
              }  
            }            
          }
          writeOutput("$api_name API Result: '$favicon'","<b style=".HTML_WARNING_STYLE.">$api_name API Result</b> #".@$favicon."#<br>",DEBUG_MESSAGE);
        } else {
          writeOutput("Selected API ($api_name) Is Disabled!",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
        }
      } else {
        writeOutput("Failed To Select API",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
      }
    } else {
      writeOutput("No APIs Available",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
    }
  } // END If nothing works: Get the Favicon from API


  // If Favicon should be saved
  if ((isset($save)) && ($save == TRUE)) {
    unset($content);
    writeOutput("Attempting to load favicon",SUPPRESS_OUTPUT,DEBUG_MESSAGE);

    //  Load Favicon
    $content = load($favicon);
    
    if (empty($content)) {
      writeOutput("Failed to load favicon",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
    } else {
      if (!is_null(getGlobal('redirect_url'))) { $favicon = getGlobal('redirect_url'); }
      if (!is_null($api_name)) {
        if ($api_name == "google") {
          if (md5($content) == GOOGLE_DEFAULT_ICON_MD5) {
            $domain = 'default'; // so we don't save a default icon for every domain again
            writeOutput("Google: #use default icon#","<b style=".HTML_WARNING_STYLE.">Google</b> #use default icon#<br>",DEBUG_MESSAGE);
          }
        }
      }

      //  Get Type
      if (!empty($favicon)) {
        $fileExtension = geticonextension($favicon);
        if (is_null($fileExtension)) {
          writeOutput("Invalid File Type for $favicon","<b style=".HTML_WARNING_STYLE.">Write-File</b> #INVALID_IMAGE#<br>",DEBUG_MESSAGE);
        } else {
          $filePath = preg_replace('#\/\/#', '/', $directory.'/'.$domain.'.'.$fileExtension);

          //  If overwrite, delete it
          if (file_exists($filePath)) { if ($overwrite) { unlink($filePath); } }

          //  If file exists, skip
          if (file_exists($filePath)) {
            writeOutput("Skipping File $filePath","<b style=".HTML_WARNING_STYLE.">Skip-File</b> #".@$filePath."#<br>",DEBUG_MESSAGE);
          } else {
            // Write
            $fh = @fopen($filePath, 'wb');
            if ($fh) {
              fwrite($fh, $content);
              fclose($fh);
              writeOutput("Writing File $filePath","<b style=".HTML_WARNING_STYLE.">Write-File</b> #".@$filePath."#<br>",DEBUG_MESSAGE);
            } else {
              # TO DO:
              # Error getting handle
              writeOutput("Error Writing File $filePath","<b style=".HTML_WARNING_STYLE.">Error-Write-File</b> #".@$filePath."#<br>",DEBUG_MESSAGE);
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
	if ($debug) {
    // Load the Favicon from local file
    if (!empty($filePath)) {
      if (!getCapability("php","get")) {
        $fh = @fopen($filePath, 'r');
        if ($fh) {
          while (!feof($fh)) {
            $content .= fread($fh, BUFFER_SIZE); // Because filesize() will not work on URLS?
          }
          fclose($fh);
        } else {
          # TO DO:
          # ERROR READING
        }
      } else {
        $content = file_get_contents($filePath);
      }
      # TO DO:
      #   Get proper mime type for image for HTML
      writeOutput(geticonextension($filePath) . " format file loaded from $filePath","<b style=".HTML_WARNING_STYLE.">Image</b> <img style=\"width:32px;\" src=\"data:image/png;base64,".base64_encode($content)."\"><hr size=\"1\">",DEBUG_MESSAGE);
    }
  }

  if (!$consoleMode) {
    // reset script runtime timeout
    set_time_limit($max_execution_time); // set it back to the old value
  }

  // Return Favicon Url
  return $filePath;

} // END MAIN Function

/* HELPER load use curl or file_get_contents (both with user_agent) and fopen/fread as fallback */
function load($url) {
  $previous_url = null;
  setGlobal('redirect_url',null);
  if (getConfiguration("curl","enabled")) {
    // cURL Method
    writeOutput("cURL: Operation Timeout=" . getConfiguration("http","http_timeout") . ", Connection Timeout=" . getConfiguration("http","http_timeout_connect") . ", DNS Timeout=" . getConfiguration("http","dns_timeout"),"<b style=".HTML_WARNING_STYLE.">cURL</b> #Operation Timeout=" . getConfiguration("http","http_timeout") . ", Connection Timeout=" . getConfiguration("http","http_timeout_connect") . ", DNS Timeout=" . getConfiguration("http","dns_timeout") . "#<br>",DEBUG_MESSAGE);
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
    $http_code = $curl_response['http_code'];
    writeOutput("cURL: Return Code=$http_code for '$url'","<b style=".HTML_WARNING_STYLE.">cURL</b> #$http_code#<br>",DEBUG_MESSAGE);
    # If redirected, handle that
    if ($curl_response['url'] != $url) {
      $previous_url = $url;
      $url = $curl_response['url'];
      writeOutput("cURL: Redirecting to '$url'",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
      curl_setopt($ch, CURLOPT_URL, $url);
      $content = curl_exec($ch);
      $curl_response = curl_getinfo($ch);
      $http_code = $curl_response['http_code'];
      if ($http_code == 200) { setGlobal('redirect_url',$url); }
    }
    curl_close($ch);
    unset($ch);
  } else {
    //  Non-Curl Method
    $context_options = array(
      'http' => array(
        'user_agent' => getConfiguration("http","useragent"),
        'timeout' => getConfiguration("http","http_timeout"),
      )
    );
    $context = stream_context_create($context_options);
	  if (!getCapability("php","get")) {
      //  Fallback if file_get_contents is not available
      $fh = fopen($url, 'r', false, $context);
      if ($fh) {
        $content = '';
        while (!feof($fh)) {
          $content .= fread($fh, BUFFER_SIZE); // Because filesize() will not work on URLS?
        }
        fclose($fh);
      } else {
        # TO DO:
        # error getting handle
      }
    } else {
      $content = file_get_contents($url, null, $context);
    }
  }
  return $content;
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

/* GET ICON IMAGE TYPE  */
function geticonextension($url, $noFallback = false) {
  $retval = null;
  if (!empty($url))
  {
    // If exif_imagetype is not available, it will simply return the extension
    if (getCapability("php","exif")) {
      $filetype = @exif_imagetype($url);
      if ($filetype) {
        if ($filetype == IMAGETYPE_GIF) { $retval = "gif"; }
        if ($filetype == IMAGETYPE_JPEG) { $retval = "jpg"; }
        if ($filetype == IMAGETYPE_PNG) { $retval = "png"; }
        if ($filetype == IMAGETYPE_ICO) { $retval = "ico"; }
        if ($filetype == IMAGETYPE_WEBP) { $retval = "webp"; }
        if ($filetype == IMAGETYPE_BMP) { $retval = "bmp"; }
        if ($filetype == IMAGETYPE_GIF) { $retval = "gif"; }
      }
    } else {
      if (!$noFallback) { $retval = @preg_replace('/^.*\.([^.]+)$/D', '$1', $url); }
    }
  }
  return $retval;
}

function validateicon($pathname, $removeIfInvalid = false)
{
  # TO DO
  #   determine if the pathname is:
  #     a valid image file
  #     not in the blocklist
  #
  # if it is not a valid (or is blocked) and removeIfInvalid is true, delete it.
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
function initializeUserAgent()
{
  if (getCapability("php","exif")) {
    $userAgent = getConfiguration("http","useragent");
    if (!is_null($userAgent)) { ini_set('user_agent', $userAgent); }
  }
}

function showBoolean($value)
{
  $retval = "null";
  if (isset($value)) {
    $value = setBoolean($value);
    if ($value) { $retval = "true"; } else { $retval = "false"; }
  }
  return $retval;
}

function setBoolean($value)
{
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

function setRange($value,$min = null,$max = null)
{
  if (is_numeric($value))
  {
    if (!is_null($min)) { if ($value < $min) { $value = $min; } }
    if (!is_null($max)) { if ($value > $max) { $value = $max; } }
  }
  
  $value = intval($value);
  
  return $value;
}

/*
**  API Support Functions **
*/
/* Add an API */
function addAPI($name,$url,$json = false,$enabled = true,$json_structure = array())
{
  global $apiList;

  $entry = array();
  $entry['name'] = $name;
  $entry['url'] = $url;
  $entry['json'] = $json;
  $entry['enabled'] = $enabled;
  $entry['json_structure'] = $json_structure;

  array_push($apiList,$entry);
}

function getAPICount($isenabled = true)
{
  global $apiList; 
  $retval = 0;
  
  if (!empty($apiList))
  {
    foreach ($apiList as $item) {
      $api_name = $item['name'];
      $api_url = $item['url'];
      $api_json = $item['json'];
      $api_enabled = $item['enabled'];
      $api_json_structure = $item['json_structure'];
      if (isset($api_name))
      {
        if ($isenabled) {
          if ($api_enabled) { $retval++; }
        } else {
          $retval++;
        }
      }
    }
  }
  
  return $retval;
}

/* Return an API object */
function getAPI($name)
{
  return lookupAPI('name',$name);
}

/* Select a Random API */
function getRandomAPI()
{
  global $apiList;
  
  $api_count = getAPICount();
  $return_object = array();
  $return_object['name'] = "";
  $return_object['url'] = "";
  $return_object['json'] = false;
  $return_object['enabled'] = false;
  $return_object['json_structure'] = array();
  
  if ($api_count > 0)
  {
    $counter = 0;
    foreach ($apiList as $item) {
      $counter++;
      if ($item['enabled'] === true)
      {
        if ((rand(1,100) > rand(25, 75)) || ($counter == $api_count))
        {
          $return_object = $item;
          break;
        }
      }
    }
  }
  return $return_object;
}

/* Lookup API */
function lookupAPI($element,$value)
{
  global $apiList;

  $return_object = array();
  $return_object['name'] = "";
  $return_object['url'] = "";
  $return_object['json'] = false;
  $return_object['enabled'] = false;
  $return_object['json_structure'] = array();
  
  foreach ($apiList as $item)
  {
    if (strcasecmp($item[$element], $value) == 0)
    {
      $return_object = array();
      $return_object = $item;
      break;
    }
  }

  return $return_object;
}

function getAPIurl($url,$domain,$size = 16)
{
  $processed_url = $url;
  $processed_url = str_replace("<DOMAIN>",$domain,$processed_url);
  $processed_url = str_replace("<SIZE>",$size,$processed_url);
  return $processed_url;
}

/*  Output Function */
function writeOutput($text,$html = null,$type = 0)
{
  $debug = getConfiguration("global","debug");
  $consoleMode = getConfiguration("mode","console");
  
  $flag_display = true;
  if (is_null($html)) { if ($text != SUPPRESS_OUTPUT) { $html = $text . "<br>"; } }
  if (($type == DEBUG_MESSAGE) && (!$debug)) { $flag_display = false; }
  
  if ($flag_display)
  {
    if ($consoleMode) {
      if ($text != SUPPRESS_OUTPUT) { echo "$text\n"; }
    } else {
      if ($html != SUPPRESS_OUTPUT) { print($html ."\n"); }
    }
  }
  
  return $flag_display;
}

/*
**  Configuration Controller
**
*/
function setConfiguration($scope = "global",$option,$value = null,$default = null,$type = 0)
{
  global $configuration;
  $flag_fallback = true;
  $flag_handled = false;
  
  if (isset($value))
  {
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

function getConfiguration($scope = "global",$option)
{
  global $configuration;
  
  if (isset($configuration[$scope][$option])) { $value = $configuration[$scope][$option]; } else { $value = null; }
  
  return $value;
}

function validateConfigurationSetting($scope = "global",$option,$type = 0,$min = 0,$max = 0)
{
  global $configuration;
  
  if (isset($configuration[$scope][$option])) {
    $value = $configuration[$scope][$option];
    switch ($type) {
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

function validateConfiguration()
{
  # Ensure that booleans are properly typed
  # Ensure numerics are numeric and in proper ranges
  
  validateConfigurationSetting("global","debug",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","blocklist",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("global","api",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("files","store",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("files","overwrite",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("http","try_homepage",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("curl","verbose",CONFIG_TYPE_BOOLEAN);
  validateConfigurationSetting("curl","showprogress",CONFIG_TYPE_BOOLEAN);  
  validateConfigurationSetting("http","http_timeout",CONFIG_TYPE_NUMERIC,RANGE_HTTP_TIMEOUT_MINIMUM,RANGE_HTTP_TIMEOUT_MAXIMUM);
  validateConfigurationSetting("http","http_timeout_connect",CONFIG_TYPE_NUMERIC,RANGE_HTTP_CONNECT_TIMEOUT_MINIMUM,RANGE_HTTP_CONNECT_TIMEOUT_MAXIMUM);
  validateConfigurationSetting("http","dns_timeout",CONFIG_TYPE_NUMERIC,RANGE_DNS_TIMEOUT_MINIMUM,RANGE_DNS_TIMEOUT_MAXIMUM);
}

/*
** Capability Controller
*/

function addCapability($scope = "global", $capability,$value = false)
{
  global $capabilities;
  
  $capabilities[$scope][$capability] = $value;
}

function getCapability($scope = "global", $capability)
{
  global $capabilities;
  
  if (isset($capabilities[$scope][$capability])) { $value = $capabilities[$scope][$capability]; } else { $value = null; }
  
  return $value;
}

/*
** List Conroller
*/
function loadList($list)
{
  $retval = null;
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