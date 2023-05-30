About
-----

There are two ways to configure `get-fav.php`, you can use an .ini file or pass command line switches.
You can even use both, the switches will always override the configuration file.


Configuration File
-------------------

Command Line Switches
---------------------


# Configuration Settings

## global
  
### debug
  Type: Boolean
  Description: Controls debug messaging
  Default: false
  Switch: --debug

### api 
	Type: Boolean
  Description: Master switch for using APIs to get favicons
  Default: true
  Switch: --disableallapis
  
### blocklist
	Type: Boolean
	Description: Master switch to enable using blocklists
  Default: true
  Switch: --enableblocklist, --disableblocklist

## curl
  
### enabled
	Type: Boolean
  Description: If set to true, the software will use cURL
  Default: true (if curl support is detected)
  Switch: --nocurl

### verbose
	Type: Boolean
  Description: Used for CURLOPT_VERBOSE option
  Default: false
  Switch: --curl-verbose

### showprogress
	Type: Boolean
  Description: If true, CURLOPT_NOPROGRESS is set to false
  Default: false
  Switch: --curl-showprogress

## files
  
### local_path
  Type: String
  Description: Relative or absolute path for storing icons
  Default: ./
  Switch: --path (Alias: --p)
  
### overwrite
  Type: Boolean
  Description: If true, any saved icons will be overwritten
  Default: false
  Switch: --overwrite, --nooverwrite (Alias: --skip)
  
### store
  Type: Boolean
  Description: If true icons will be saved in "local_path"
  Default: true
  Switch: --store, --nostore (Alias: --save, --nosave)
  
## http
  
### default_useragent
  Type: String
  Description: Default useragent
  Default: FaviconBot/1.0/
  Switch: none
  
### dns_timeout
  Type: Integer
  Description: Number of seconds before DNS lookups timeout. (0 - 600).  (Setting this to 0 can cause an infinite wait!)
  Default: 120
  Switch: --dns-timeout
  
## http_timeout
  Type: Integer
  Description: Number of seconds before HTTP/HTTPS operations timeout. (0 - 600)  (Setting this to 0 can cause an infinite wait!)
  Default: 60
  Switch: --http-timeout
  
## http_timeout_connect
  Type: Integer
  Description: Number of seconds before HTTP/HTTPS connection attempts timeout. (0 - 600)  (Setting this to 0 can cause an infinite wait!)
  Default: 30
  Switch: --connect-timeout

## try_homepage
  Type: Boolean
  Description: Attempt to get the favicon using normal methods.
  Default: true
  Switch: --tryhomepage, --onlyuseapis
  
## useragent
  Type: String
  Description: User agent to use for HTTP/HTTPS operations.
  Default: default_useragent
  Switch: --useragent
    
## mode

### console
  Type: Boolean
  Description: Operating mode of the software.
  Default: true (CLI), false (HTTP)
  Switch: --consolemode, --noconsolemode

