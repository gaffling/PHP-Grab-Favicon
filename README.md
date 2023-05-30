![PHP-Grab-Favicon](https://socialify.git.ci/gaffling/PHP-Grab-Favicon/image?description=1&font=KoHo&language=1&owner=1&stargazers=1&theme=Dark)

> This `PHP Favicon Grabber v1.1` use a given url, save a copy (if wished) and return the image path.

How it Works
------------

1. Check if the favicon already exists local or no local copy is desired, if so return path & filename
2. Else load URL and try to match the favicon location with regex
3. If we have a match the favicon link see if it's valid
4. If we have no favicon we try to get one in domain root
5. If there is still no favicon we attempt to get one using a random API
6. If favicon should be saved try to load the favicon URL
7. If a local copy is desired, it will be saved and the pathname will be shown


How To Use
---------
These are the most common command line options, for a full list, invoke with `--help` or read `CONFIGURATION.md`

**Usage:** `get-fav.php` _(Switches)_

| Switch | Description | Notes |
| ------ | ----------- | ----- |
| `--list` | File or List of URLs | Example: `--list=github.com,microsoft.com,www.google.com` |
| `--path` | Location to store icons | Default is `./` |
| `--store` | Store local copies of icons | Default is `true` |

Tip: `--list` can also point to a text file with a list of URLs.

Coming Soon 
-----------
Optional split the download dir into several sub-dirs (MD5 segment of filename e.g. /af/cd/example.com.png) if there are a lot of favicons.

Infos about Favicon
-------------------
https://github.com/audreyr/favicon-cheat-sheet

###### Copyright 2019-2023 Igor Gaffling
