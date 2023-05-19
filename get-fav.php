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

TO DO:
  read config file
  blocklist of icons (for example if the apis return archive.org's icon)
    list of md5 hashes
    skipped if blocklist is empty or option is goven
    --blocklist=(list of hashes or file with hashes)
    --enableblocklist
    --disableblocklist
  
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

$grap_favicon = array(
'URL' => $url,   // URL of the Page we like to get the Favicon from
'SAVE'=> true,   // Save Favicon copy local (true) or return only favicon url (false)
'DIR' => './',   // Local Dir the copy of the Favicon should be saved
'TRY' => true,   // Try to get the Favicon frome the page (true) or only use the APIs (false)
'OVR' => false,  // Skip if file is already local (false) or overwrite (true)
'DEV' => null,   // Give all Debug-Messages ('debug') or only make the work (null)
);

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

define('PROGRAM_NAME', 'get-fav');
define('PROGRAM_VERSION', '202305191528');
define('DEFAULT_ENABLE_APIS', true);
define('DEFAULT_USE_CURL', true);
define('DEFAULT_SAVE_LOCAL', true);
define('DEFAULT_TRY_HOMEPAGE', true);
define('DEFAULT_OVERWRITE', false);
define('DEFAULT_SIZE', 16);
define('DEFAULT_LOCAL_PATH', "./");
define('HTML_WARNING_STYLE', ".HTML_WARNING_STYLE.");
define('SUPPRESS_OUTPUT', "<NONE>");
define('GOOGLE_DEFAULT_ICON_MD5', '3ca64f83fdcf25135d87e08af65e68c9');
define('DEBUG_MESSAGE', 1);

$apiList = array();
$testURLs = array();
$debug = null;
$consoleMode = false;


/* Defaults */
$useCURL = DEFAULT_USE_CURL;
$overWrite = DEFAULT_OVERWRITE;
$localPath = DEFAULT_LOCAL_PATH;
$saveLocal = DEFAULT_SAVE_LOCAL;
$tryHomepage = DEFAULT_TRY_HOMEPAGE;
$enableAPIFavIconKit = DEFAULT_ENABLE_APIS;
$enableAPIFavIconGrabber = DEFAULT_ENABLE_APIS;
$enableAPIGoogle = DEFAULT_ENABLE_APIS;

/* Fall back if CURL is not available */
if (!function_exists('curl_version')) { $useCURL = false; }

/* Blocked Icon List */
$blockList = array(
  '3ca64f83fdcf25135d87e08af65e68c9',
  'd0fefd1fde1699e90e96a5038457b061',
);

/* Detect Console Mode, can be overridden with switches */

if (php_sapi_name() == "cli") { $consoleMode = true; }

if ($consoleMode) { $script_name = basename(__FILE__); } else { $script_name = basename($_SERVER['PHP_SELF']); }

/* Command Line Options */
$shortopts  = "";
$shortopts  = "l::";
$shortopts  = "p::";
$shortopts .= "h?";

$longopts  = array(
  "list::",
  "path::",
  "user-agent::",
  "curl-timeout::",
  "http-timeout::",
  "connect-timeout::",
  "dns-timeout::",
  "tryhomepage",
  "onlyuseapis",
  "disableapis",
  "enableblocklist",
  "disableblocklist",
  "store",
  "nostore",
  "save",
  "nosave",
  "overwrite",
  "skip",
  "nocurl",
  "curl-verbose",
  "consolemode",
  "noconsolemode",
  "enablefaviconkit",
  "enablefavicongrabber",
  "enablegoogle",  
  "disablegoogle",
  "disablefaviconkit",
  "disablefavicongrabber",
  "debug",
  "help",
);

# TO DO:
#   "enableapi::"
#   "disableapi::"
#
# e.g. --enableapi=google,faviconkit


$options = getopt($shortopts, $longopts);

if ((isset($options['help'])) || (isset($options['h'])) || (isset($options['?'])))
{
  echo "Usage: $script_name (Switches)\n\n";
  echo "--list=FILE/LIST            Filename or a delimited list of URLs to check.\n";
  echo "--blocklist=FILE/LIST       Filename or a delimited list of MD5 hashes to block.\n";
  echo "--path=PATH                 Location to store icons (default is $localPath)\n";
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

/* Initialize */

$curl_verbose = null;
$curl_enabled = null;
$dns_timeout = null;
$http_useragent = null;
$http_timeout = null;
$http_timeout_connect = null;

/* Process Options */

$opt_list = null;
$opt_localpath = null;
$opt_usestdin = null;
$opt_tryhomepage = null;
$opt_storelocal = null;
$opt_debug = null;
$opt_console = null;
$opt_timeout = null;
$opt_timeout_connect = null;
$opt_timeout_dns = null;
$opt_http_user_agent = null;
$opt_curl_verbose = false;
$opt_http_timeout = null;
$opt_http_timeout_connect = null;
$opt_dns_timeout = null;
$opt_use_faviconkit = null;
$opt_use_favicongrabber = null;
$opt_use_google = null;
$opt_nocurl = null;


if (isset($options['debug'])) { $opt_debug = true; }
if (isset($options['list'])) { $opt_list = $options['list']; }
if (isset($options['blocklist'])) { $opt_blocklist = $options['blocklist']; }
if (isset($options['path'])) { $opt_localpath = $options['path']; }
if (isset($options['l'])) { $opt_list = $options['l']; }
if (isset($options['p'])) { $opt_localpath = $options['p']; }
if (isset($options['consolemode'])) { $opt_console = true; }
if (isset($options['store'])) { $opt_storelocal = true; }
if (isset($options['save'])) { $opt_storelocal = true; }
if (isset($options['skip'])) { $overWrite = false; }
if (isset($options['tryhomepage'])) { $opt_tryhomepage = true; }
if (isset($options['nostore'])) { $opt_storelocal = false; }
if (isset($options['nosave'])) { $opt_storelocal = false; }
if (isset($options['onlyuseapis'])) { $opt_tryhomepage = false; }
if (isset($options['noconsolemode'])) { $opt_console = false; }
if (isset($options['overwrite'])) { $overWrite = true; }
if (isset($options['user-agent'])) { $opt_http_user_agent = $options['user-agent']; }
if (isset($options['curl-verbose'])) { $opt_curl_verbose = true; }
if (isset($options['curl-timeout'])) { $opt_http_timeout = $options['curl-timeout']; }
if (isset($options['http-timeout'])) { $opt_http_timeout = $options['http-timeout']; }
if (isset($options['connect-timeout'])) { $opt_http_timeout_connect = $options['connect-timeout']; }
if (isset($options['dns-timeout'])) { $opt_dns_timeout = $options['dns-timeout']; }
if (isset($options['nocurl'])) { $opt_nocurl = $options['nocurl']; }

if (isset($options['enablefaviconkit'])) { $enableAPIFavIconKit = true; }
if (isset($options['enablefavicongrabber'])) { $enableAPIFavIconGrabber = true; }
if (isset($options['enablegoogle'])) { $enableAPIGoogle = true; }
if (isset($options['disablefaviconkit'])) { $enableAPIFavIconKit = false; }
if (isset($options['disablefavicongrabber'])) { $enableAPIFavIconGrabber = false; }
if (isset($options['disablegoogle'])) { $enableAPIGoogle = false; }
if (isset($options['disableapis'])) {
  $enableAPIFavIconKit = false;
  $enableAPIFavIconGrabber = false;
  $enableAPIGoogle = false;
}

if (!is_null($opt_localpath)) { if (file_exists($opt_localpath)) { $localPath = $opt_localpath; } }
if (!is_null($opt_tryhomepage)) { $tryHomepage = $opt_tryhomepage; }
if (!is_null($opt_storelocal)) { $saveLocal = $opt_storelocal; }
if (!is_null($opt_debug)) { if ($opt_debug) { $debug = true; } else { $debug = false; } }
if (!is_null($opt_console)) { $consoleMode = $opt_console; }
if (!is_null($opt_http_timeout)) { if (is_numeric($opt_http_timeout)) { if ($opt_http_timeout >= 0 && $opt_http_timeout < 600) { $opt_timeout = $opt_http_timeout; } } }
if (!is_null($opt_http_timeout_connect)) { if (is_numeric($opt_http_timeout_connect)) { if ($opt_http_timeout_connect >= 0 && $opt_http_timeout_connect < 600) { $opt_timeout_connect = $opt_http_timeout_connect; } } }
if (!is_null($opt_dns_timeout)) { if (is_numeric($opt_dns_timeout)) { if ($opt_dns_timeout >= 0 && $opt_dns_timeout < 600) { $opt_timeout_dns = $opt_dns_timeout; } } }
if (!is_null($opt_nocurl)) { $useCURL = false; }

if ($useCURL) { setGlobal('curl_enabled', 1); } else { setGlobal('curl_enabled', 0); }

if (isset($opt_list)) {
  if (file_exists($opt_list)) {
    $testURLs = file($opt_list,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  } else {
    if (count($testURLs) == 0) {
      $testURLs = explode(",",str_replace(array(",",";"," "),",",$opt_list));
    }
  }
}

if (isset($opt_blocklist)) {
  if (file_exists($opt_blocklist)) {
    $blockList = file($opt_blocklist,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  } else {
    if (count($blockList) == 0) {
      $blockList = explode(",",str_replace(array(",",";"," "),",",$opt_blocklist));
    }
  }
}

if (is_null($opt_http_user_agent)) { if (isset($_SERVER['SERVER_NAME'])) { $opt_http_user_agent = 'FaviconBot/1.0 (+http://'.$_SERVER['SERVER_NAME'].'/'; } else { $opt_http_user_agent = 'FaviconBot/1.0/'; } }
if (strtolower($opt_http_user_agent) != "none") { setGlobal('http_useragent', $opt_http_user_agent); }
setGlobal('curl_verbose', $opt_curl_verbose);
if (!is_null($opt_timeout)) { setGlobal('http_timeout', $opt_timeout); }
if (!is_null($opt_timeout_connect)) { setGlobal('http_timeout_connect', $opt_timeout_connect); }
if (!is_null($opt_timeout_dns)) { setGlobal('dns_timeout', $opt_timeout_dns); }

/* Initialize APIs */
addAPI("faviconkit","https://api.faviconkit.com/<DOMAIN>/16",false,$enableAPIFavIconKit);
addAPI("favicongrabber","http://favicongrabber.com/api/grab/<DOMAIN>",true,$enableAPIFavIconGrabber,array("icons","0","src"));
addAPI("google","http://www.google.com/s2/favicons?domain=<DOMAIN>",false,$enableAPIGoogle);

/* If test URLs is empty, setup testing list */
if (count($testURLs) == 0) {
  $testURLs = array(
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

writeOutput("Debug ON",SUPPRESS_OUTPUT,DEBUG_MESSAGE);

/*  Process List */
foreach ($testURLs as $url) {
  $grap_favicon = array(
    'URL' => $url,          // URL of the Page we like to get the Favicon from
    'SAVE'=> $saveLocal,    // Save Favicon copy local (true) or return only favicon url (false)
    'DIR' => $localPath,    // Local Dir the copy of the Favicon should be saved
    'TRY' => $tryHomepage,  // Try to get the Favicon frome the page (true) or only use the APIs (false)
    'OVR' => $overWrite,    // Overwrite existing local files or skip
    'DEV' => $debug,        // Give all Debug-Messages ('debug') or only make the work (null)
  );
  $favicons[] = grap_favicon($grap_favicon, $consoleMode);
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
function grap_favicon($options=array(), $consoleMode = false) {
  if (!$consoleMode) {
    // avoid script runtime timeout
    $max_execution_time = ini_get("max_execution_time");
    set_time_limit(0); // 0 = no timelimit
  }

  $api_name = null;
  $api_url = null;
  $api_json = false;
  $api_enabled = false;
  
  // Ini Vars
  $url       = (isset($options['URL']))?$options['URL']:'gaffling.com';
  $save      = (isset($options['SAVE']))?$options['SAVE']:true;
  $directory = (isset($options['DIR']))?$options['DIR']:'./';
  $trySelf   = (isset($options['TRY']))?$options['TRY']:true;
  $debug     = (isset($options['DEV']))?$options['DEV']:false;
  $overwrite = (isset($options['OVR']))?$options['OVR']:false;

  // URL to lower case
	$url = strtolower($url);

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
  if (isset($trySelf) && $trySelf == TRUE) {

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

  // If nothink works: Get the Favicon from API
  if ((!isset($favicon)) || (empty($favicon))) {
    $api_count = getAPICount();
    
    if ($api_count > 0)
    {
      writeOutput("Selecting API",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
      $selectAPI = getRandomAPI();
      if (isset($selectAPI['name']))
      {
        $api_name = $selectAPI['name'];
        $api_url = $selectAPI['url'];
        $api_json = $selectAPI['json'];
        $api_enabled = $selectAPI['enabled'];
        $api_json_structure = $selectAPI['json_structure'];
        
        if ($api_enabled)
        {
          writeOutput("Using API: $api_name",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
          $favicon = getAPIurl($api_url,$domain,DEFAULT_SIZE);
          if ($api_json)
          {
            $echo = json_decode(load($favicon),TRUE);
            $favicon = $echo;
            if (!empty($api_json_structure)) {
              foreach ($api_json_structure as $element) {
                $favicon = $favicon[$element];
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
    writeLog("Attempting to load favicon",SUPPRESS_OUTPUT,DEBUG_MESSAGE);

    //  Load Favicon
    $content = load($favicon);
    
    if (empty($content)) {
      writeLog("Failed to load favicon",SUPPRESS_OUTPUT,DEBUG_MESSAGE);
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
            fwrite($fh, $content);
            fclose($fh);
            writeOutput("Writing File $filePath","<b style=".HTML_WARNING_STYLE.">Write-File</b> #".@$filePath."#<br>",DEBUG_MESSAGE);
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
      if (!function_exists('file_get_contents')) {
        $fh = @fopen($filePath, 'r');
        while (!feof($fh)) {
          $content .= fread($fh, 128); // Because filesize() will not work on URLS?
        }
        fclose($fh);
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
  
  //  Get Options
  $opt_curl = gethttpoption('curl_enabled',0);
  $operationTimeOut = gethttpoption('http_timeout', 60);
  $connectTimeout = gethttpoption('http_timeout_connect', 30);
  $dnsTimeout = gethttpoption('dns_timeout', 120);
  $userAgent = gethttpoption('http_useragent', null);
  if ($opt_curl) {
    writeOutput("cURL: Operation Timeout=$operationTimeOut, Connection Timeout=$connectTimeout, DNS Timeout=$dnsTimeout","<b style=".HTML_WARNING_STYLE.">cURL</b> #Operation Timeout=$operationTimeOut, Connection Timeout=$connectTimeout, DNS Timeout=$dnsTimeout#<br>",DEBUG_MESSAGE);
    $curlVerbose = gethttpoption('curl_verbose', false);
    $curlShowProgress = gethttpoption('curl_showprogress', false);
    $ch = curl_init($url);
    if (!is_null($userAgent)) { curl_setopt($ch, CURLOPT_USERAGENT, $userAgent); }
    curl_setopt($ch, CURLOPT_VERBOSE, $curlVerbose);
    curl_setopt($ch, CURLOPT_TIMEOUT, $operationTimeOut);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout); 
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, $dnsTimeout); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($curlShowProgress) { curl_setopt($ch, CURLOPT_NOPROGRESS, false); }
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
    $context_options = array(
      'http' => array(
        'user_agent' => $userAgent,
        'timeout' => $operationTimeOut,
      )
    );
    $context = stream_context_create($context_options);
	  if (!function_exists('file_get_contents')) {
      $fh = fopen($url, 'r', FALSE, $context);
      $content = '';
      while (!feof($fh)) {
        $content .= fread($fh, 128); // Because filesize() will not work on URLS?
      }
      fclose($fh);
    } else {
      $content = file_get_contents($url, NULL, $context);
    }
  }
  return $content;
}

/* Get an option, if null use default */
function gethttpoption($option,$default)
{
  $tempvalue = getGlobal($option);
  if (is_null($tempvalue)) { $tempvalue = $default; }
  return $tempvalue;
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
    if (function_exists('exif_imagetype')) {
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
  if (function_exists('exif_imagetype')) {
    $userAgent = gethttpoption('http_useragent', null);
    if (!is_null($userAgent)) { ini_set('user_agent', $userAgent); }
  }
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
  global $debug;
  global $consoleMode;
  
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