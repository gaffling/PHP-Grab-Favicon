*This is still being written*

About
-----

There are two ways to configure `get-fav.php`, you can use an .ini file or pass command line switches.
You can even use both, the switches will always override the configuration file.


Configuration File
------------------

These are standard .ini file format files which consist of "sections" and then options and values.

## global
  
### debug
  Type: Boolean
  Description: Controls debug modes
  Default: false
  Switch: `--debug`
  NOTE: This does not control debug logging, see the *console* and *logging* sections for that.

### api 
	Type: Boolean
  Description: Master switch for using APIs to get favicons
  Default: true
  Switch: `--disableallapis`
  
### blocklist
	Type: Boolean
	Description: Master switch to enable using blocklists
  Default: true
  Switch: `--enableblocklist`, `--disableblocklist`

### icon_size
	Type: Numeric
	Description: Try to get this size of icons (some APIs only)
  Default: 32
  Switch: `--size=N`

## console

### enabled
	Type: Boolean
	Description: Master switch to enable console output
  Default: true
  Switch: None

### level
	Type: Numeric
	Description: Log Level
  Default: 31 (NOTICES, WARNINGS, VERBOSE, ERRORS)
  Switch: `--level=N`
  
### timestamp
	Type: Boolean
	Description: Display timestamps
  Default: false
  Switch: `--showtimestamp`, `--hidetimestamp`
  
### timestampformat
	Type: String
	Description: Formatting for timestamps.
  Default: "Y-m-d H:i:s"
  Switch: None
  NOTE: For a complete list of formatting options, please consult https://www.php.net/manual/en/datetime.format.php
  
## curl
  
### enabled
	Type: Boolean
  Description: If set to true, the software will use cURL
  Default: true (if curl support is detected)
  Switch: `--nocurl`

### verbose
	Type: Boolean
  Description: Used for CURLOPT_VERBOSE option
  Default: false
  Switch: `--curl-verbose`

### showprogress
	Type: Boolean
  Description: If true, CURLOPT_NOPROGRESS is set to false
  Default: false
  Switch: `--curl-showprogress`

## files
  
### local_path
  Type: String
  Description: Relative or absolute path for storing icons
  Default: ./
  Switch: `--path="<PATH>"` (Alias: `-p`)
  
### overwrite
  Type: Boolean
  Description: If true, any saved icons will be overwritten
  Default: false
  Switch: `--overwrite`, `--nooverwrite` (Alias: `--skip`)
  
### store
  Type: Boolean
  Description: If true icons will be saved in "local_path"
  Default: true
  Switch: `--store`, `--nostore` (Alias: `--save`, `--nosave`)
  
### remove_told
  Type: Boolean
  Description: Remove top level domain (.e.g ".com") when saving icons.
  Default: false
  Switch: `--removetld`, `--noremovetld`
  
## http
  
### default_useragent
  Type: String
  Description: Default useragent
  Default: FaviconBot/1.0/
  Switch: None
  NOTE: Generally this should not be changed, you want *useragent* instead.
  
### dns_timeout
  Type: Integer
  Description: Number of seconds before DNS lookups timeout. (0 - 600).  (Setting this to 0 can cause an infinite wait!)
  Default: 120
  Switch: `--dns-timeout=N`
  
## http_timeout
  Type: Integer
  Description: Number of seconds before HTTP/HTTPS operations timeout. (0 - 600)  (Setting this to 0 can cause an infinite wait!)
  Default: 60
  Switch: `--http-timeout=N`
  
## http_timeout_connect
  Type: Integer
  Description: Number of seconds before HTTP/HTTPS connection attempts timeout. (0 - 600)  (Setting this to 0 can cause an infinite wait!)
  Default: 30
  Switch: `--connect-timeout=N`
  
## maximum_redirects  
  Type: Integer
  Description: Number of redirects will be followed before giving up.
  Default: 5
  Switch: None
  NOTE: Only for non-cURL functions.

## try_homepage
  Type: Boolean
  Description: Attempt to get the favicon using normal methods.
  Default: true
  Switch: `--tryhomepage`, `--onlyuseapis`
  
## useragent
  Type: String
  Description: User agent to use for HTTP/HTTPS operations.
  Default: default_useragent
  Switch: `--useragent="STRING"`
  
## use_buffering
  Type: Boolean
  Description: If the same URL is requested in more than once in a row, simply use the previous data.
  Default: true
  Switch: None
  
## logging

### append
  Type: Boolean
  Description: Append the log file.
  Default: true
  Switch: `--append`, `--noappend`

### enabled
  Type: Boolean
  Description: Log to a file.
  Default: true
  Switch: `--log`, `--nolog`
  
## level
	Type: Numeric
	Description: Log Level
  Default: 31 (NOTICES, WARNINGS, VERBOSE, ERRORS)
  Switch: `--loglevel=N`
  
## pathname
  Type: String
  Description: Path and filename for the log file.
  Default: `get-fav.log`
  Switch: `--logfile="<PATHNAME>"
  
## separator
  Type: String
  Description: Separator to use when appending to a log file.
  Default: (80 `*` characters)
  Switch: None
  
## timestamp  
	Type: Boolean
	Description: Log timestamps
  Default: false
  Switch: `--timestamp`, `--notimestamp`
  
### timestampformat
	Type: String
	Description: Formatting for timestamps.
  Default: "Y-m-d H:i:s"
  Switch: None
  NOTE: For a complete list of formatting options, please consult https://www.php.net/manual/en/datetime.format.php

## mode

### console
  Type: Boolean
  Description: Operating mode of the software.
  Default: true (CLI), false (HTTP)
  Switch: `--consolemode`, `--noconsolemode`
  NOTE: Generally this should not be forced, the script will auto-detect which mode to use.




Command Line Switches
---------------------



Log Levels
----------


APIs
----


