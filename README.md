![PHP-Grab-Favicon](https://socialify.git.ci/gaffling/PHP-Grab-Favicon/image?description=1&font=KoHo&language=1&owner=1&stargazers=1&theme=Dark)

> This `PHP Favicon Grabber v1.1` use a given url, save a copy (if wished) and return the image path.

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

How To Use (Command Line)
-------------------------

**Usage:** '''get-fav.php''' _(Switches)_

| Switch | Description | Notes |
| ------ | ----------- | ----- |
| --list | File or List of URLs | Example: --list=github.com,microsoft.com,www.google.com |



Todo
----
Optional split the download dir into several sub-dirs (MD5 segment of filename e.g. /af/cd/example.com.png) if there are a lot of favicons.

Infos about Favicon
-------------------
https://github.com/audreyr/favicon-cheat-sheet

###### Copyright 2019-2020 Igor Gaffling
