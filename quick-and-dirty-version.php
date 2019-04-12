<?php

/* Get Favicon of given URL, save it an return the PNG Favicon Image Path */

$url = 'https://github.com/';
echo '<img src="'.save_favicon($url).'">';

function save_favicon($url, $path='./') {
  $url = parse_url($url, PHP_URL_HOST);
  $file = $path.$url.'.png';
  if ( !file_exists()) {
  $fp = fopen ($file, 'w+');
    $ch = curl_init('http://www.google.com/s2/favicons?domain='.$url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FILE, $fp); /* Save the returned Data to File */
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
  }
  return $file;
}
