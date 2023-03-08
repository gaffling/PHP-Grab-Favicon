<?php
/*

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

/* Defaults */

$console_mode = false;
$localPath = ".";
$save_local = true;
$try_homepage = true;
$debug = null;
$testURLs = array();

/* Command Line Options */
$shortopts  = "";
$shortopts  = "l::";
$shortopts  = "p::";
$shortopts .= "h?";

$longopts  = array(
  "list::",
  "path::",
  "usestdin",
  "tryhomepage",
  "onlyuseapis",
  "store",
  "nostore",
  "debug",
  "help",
);

$options = getopt($shortopts, $longopts);

/* Process Options */

$opt_list = null;
$opt_localpath = null;
$opt_usestdin = null;
$opt_tryhomepage = null;
$opt_storelocal = null;
$opt_debug = null;

if (isset($options['list'])) { $opt_list = $options['list']; }
if (isset($options['path'])) { $opt_localpath = $options['path']; }
if (isset($options['l'])) { $opt_list = $options['l']; }
if (isset($options['p'])) { $opt_localpath = $options['p']; }
if (isset($options['store'])) { $opt_storelocal = true; }
if (isset($options['nostore'])) { $opt_storelocal = false; }
if (isset($options['tryhomepage'])) { $opt_tryhomepage = true; }
if (isset($options['onlyuseapis'])) { $opt_tryhomepage = false; }
if (isset($options['debug'])) { $opt_debug = true; }

if (!is_null($opt_localpath)) { $localPath = $opt_localpath; }
if (!is_null($opt_tryhomepage)) { $try_homepage = $opt_tryhomepage; }
if (!is_null($opt_storelocal)) { $save_local = $opt_storelocal; }
if (!is_null($opt_storelocal)) { $save_local = $opt_storelocal; }
if (!is_null($opt_debug)) { if ($opt_debug) { $debug = "true"; } else { $debug = null; } }

# TO DO:
# stdin

if (isset($opt_list)) {
  if (file_exists($opt_list)) {
    $testURLs = file($opt_list,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  } else {
    if (count($testURLs) == 0) {
      $testURLs = explode(",",str_replace(array(",",";"," "),",",$opt_list));
    }
  }
}

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

# TO DO
# check to see that save location is writable if enabled


foreach ($testURLs as $url) {
  $grap_favicon = array(
    'URL' => $url,   // URL of the Page we like to get the Favicon from
    'SAVE'=> $save_local,   // Save Favicon copy local (true) or return only favicon url (false)
    'DIR' => $localPath,   // Local Dir the copy of the Favicon should be saved
    'TRY' => $try_homepage,   // Try to get the Favicon frome the page (true) or only use the APIs (false)
    'DEV' => $debug,   // Give all Debug-Messages ('debug') or only make the work (null)
  );
  $favicons[] = grap_favicon($grap_favicon);
}

foreach ($favicons as $favicon) {
  if ($console_mode) {
    echo "Icon: $ravicon\n";
  } else {
    echo '<img title="'.$favicon.'" style="width:32px;padding-right:32px;" src="'.$favicon.'">';
  }
}

if ($console_mode) {
  # TO DO: console runtime timer
} else {
  echo '<br><br><tt>Runtime: '.round((microtime(true)-$_SERVER["REQUEST_TIME_FLOAT"]),2).' Sec.';
}

function grap_favicon( $options=array() ) {

  // avoid script runtime timeout
  $max_execution_time = ini_get("max_execution_time");
  set_time_limit(0); // 0 = no timelimit

  // Ini Vars
  $url       = (isset($options['URL']))?$options['URL']:'gaffling.com';
  $save      = (isset($options['SAVE']))?$options['SAVE']:true;
  $directory = (isset($options['DIR']))?$options['DIR']:'./';
  $trySelf   = (isset($options['TRY']))?$options['TRY']:true;
  $DEBUG     = (isset($options['DEV']))?$options['DEV']:null;

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

	// FOR DEBUG ONLY
	if($DEBUG=='debug')print('<b style="color:red;">Domain</b> #'.@$domain.'#<br>');

  # TO DO: 
  # this needs to check for different types
  
	// Make Path & Filename
	$filePath = preg_replace('#\/\/#', '/', $directory.'/'.$domain.'.png');
	// change save path & filename of icons ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

	// If Favicon not already exists local
  if ( !file_exists($filePath) or @filesize($filePath)==0 ) {

    // If $trySelf == TRUE ONLY USE APIs
    if ( isset($trySelf) and $trySelf == TRUE ) {

      // Load Page
      $html = load($url, $DEBUG);

      // Find Favicon with RegEx
      $regExPattern = '/((<link[^>]+rel=.(icon|shortcut icon|alternate icon)[^>]+>))/i';
      if ( @preg_match($regExPattern, $html, $matchTag) ) {
        $regExPattern = '/href=(\'|\")(.*?)\1/i';
        if ( isset($matchTag[1]) and @preg_match($regExPattern, $matchTag[1], $matchUrl)) {
          if ( isset($matchUrl[2]) ) {

            // Build Favicon Link
            $favicon = rel2abs(trim($matchUrl[2]), 'http://'.$domain.'/');

          	// FOR DEBUG ONLY
          	if($DEBUG=='debug')print('<b style="color:red;">Match</b> #'.@$favicon.'#<br>');

          }
        }
      }

      // If there is no Match: Try if there is a Favicon in the Root of the Domain
    	if ( empty($favicon) ) {
      	$favicon = 'http://'.$domain.'/favicon.ico';

      	// Try to Load Favicon
        if ( !@getimagesize($favicon) ) {
          unset($favicon);
        }
    	}

    } // END If $trySelf == TRUE ONLY USE APIs

    // If nothink works: Get the Favicon from API
    if ( !isset($favicon) or empty($favicon) ) {

      // Select API by Random
      $random = rand(1,3);

      // Faviconkit API
      if ($random == 1 or empty($favicon)) {
        $favicon = 'https://api.faviconkit.com/'.$domain.'/16';
      }

      // Favicongrabber API
      if ($random == 2 or empty($favicon)) {
        $echo = json_decode(load('http://favicongrabber.com/api/grab/'.$domain,FALSE),TRUE);

        // Get Favicon URL from Array out of json data (@ if something went wrong)
        $favicon = @$echo['icons']['0']['src'];

      }

      // Google API (check also md5() later)
      if ($random == 3) {
        $favicon = 'http://www.google.com/s2/favicons?domain='.$domain;
      }

      // FOR DEBUG ONLY
      if($DEBUG=='debug')print('<b style="color:red;">'.$random.'. API</b> #'.@$favicon.'#<br>');

    } // END If nothink works: Get the Favicon from API


    // If Favicon should be saved
    if ( isset($save) and $save == TRUE ) {
      echo "Saving, loading $favicon\n";

      //  Load Favicon
      $content = load($favicon, $DEBUG);
      echo "loaded\n";
      
      // If Google API don't know and deliver a default Favicon (World)
      if ( isset($random) and $random == 3 and
           md5($content) == '3ca64f83fdcf25135d87e08af65e68c9' ) {
        $domain = 'default'; // so we don't save a default icon for every domain again

        // FOR DEBUG ONLY
        if($DEBUG=='debug')print('<b style="color:red;">Google</b> #use default icon#<br>');

      }

      //  Get Type
      $fileExtension = geticonextension($favicon);
      if (is_null($fileExtension)) {
        if ($console_mode) {
          if($DEBUG=='debug')print('Invalid File Type for $favicon\n');
        } else {
          if($DEBUG=='debug')print('<b style="color:red;">Write-File</b> #INVALID_IMAGE#<br>');
        }
      } else {
        $filePath = preg_replace('#\/\/#', '/', $directory.'/'.$domain.'.'.$fileExtension);
        
        // Write
        $fh = @fopen($filePath, 'wb');
        fwrite($fh, $content);
        fclose($fh);

        // FOR DEBUG ONLY
        if ($console_mode) {
          if($DEBUG=='debug')print('Writing File $filepath\n');
        } else {
          if($DEBUG=='debug')print('<b style="color:red;">Write-File</b> #'.@$filePath.'#<br>');
        }
      }

    } else {

      // Don't save Favicon local, only return Favicon URL
      $filePath = $favicon;
    }

	} // END If Favicon not already exists local

	// FOR DEBUG ONLY
	if ($DEBUG=='debug') {

    // Load the Favicon from local file
	  if ( !function_exists('file_get_contents') ) {
      $fh = @fopen($filePath, 'r');
      while (!feof($fh)) {
        $content .= fread($fh, 128); // Because filesize() will not work on URLS?
      }
      fclose($fh);
    } else {
      $content = file_get_contents($filePath);
    }
	  print('<b style="color:red;">Image</b> <img style="width:32px;"
	         src="data:image/png;base64,'.base64_encode($content).'"><hr size="1">');
  }

  // reset script runtime timeout
  set_time_limit($max_execution_time); // set it back to the old value

  // Return Favicon Url
  return $filePath;

} // END MAIN Function

/* HELPER load use curl or file_get_contents (both with user_agent) and fopen/fread as fallback */
function load($url, $DEBUG) {
  if ( function_exists('curl_version') ) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'FaviconBot/1.0 (+http://'.$_SERVER['SERVER_NAME'].'/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $content = curl_exec($ch);
    if ( $DEBUG=='debug' ) { // FOR DEBUG ONLY
      $http_code = curl_getinfo($ch);
      print('<b style="color:red;">cURL</b> #'.$http_code['http_code'].'#<br>');
    }
    curl_close($ch);
    unset($ch);
  } else {
  	$context = array ( 'http' => array (
        'user_agent' => 'FaviconBot/1.0 (+http://'.$_SERVER['SERVER_NAME'].'/)'),
    );
    $context = stream_context_create($context);
	  if ( !function_exists('file_get_contents') ) {
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

/* HELPER: Change URL from relative to absolute */
function rel2abs( $rel, $base ) {
	extract( parse_url( $base ) );
	if ( strpos( $rel,"//" ) === 0 ) return $scheme . ':' . $rel;
	if ( parse_url( $rel, PHP_URL_SCHEME ) != '' ) return $rel;
	if ( $rel[0] == '#' or $rel[0] == '?' ) return $base . $rel;
	$path = preg_replace( '#/[^/]*$#', '', $path);
	if ( $rel[0] ==  '/' ) $path = '';
	$abs = $host . $path . "/" . $rel;
	$abs = preg_replace( "/(\/\.?\/)/", "/", $abs);
	$abs = preg_replace( "/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs);
	return $scheme . '://' . $abs;
}

/* GET ICON IMAGE TYPE  */
function geticonextension( $url ) {
  $filetype = exif_imagetype($url);
  $retval = null;
  if ($filetype) {
    if ($filetype == IMAGETYPE_GIF) { $retval = "gif"; }
    if ($filetype == IMAGETYPE_JPEG) { $retval = "jpg"; }
    if ($filetype == IMAGETYPE_PNG) { $retval = "png"; }
    if ($filetype == IMAGETYPE_ICO) { $retval = "ico"; }
    if ($filetype == IMAGETYPE_WEBP) { $retval = "webp"; }
    if ($filetype == IMAGETYPE_BMP) { $retval = "bmp"; }
    if ($filetype == IMAGETYPE_GIF) { $retval = "gif"; }
  }
  return $retval;
}