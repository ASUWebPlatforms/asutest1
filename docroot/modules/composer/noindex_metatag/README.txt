INTRODUCTION
------------
The Noindex Metatag module is to whitelist the route
to add meta robots "noindex" to the HTML head
for all those selected routes. 

Its a simple configuration to prevents the site
from search engines based on the domain name or relative path.
<meta name="robots" content="noindex" />


REQUIREMENTS
------------
This module requires no modules outside of Drupal core.


INSTALLATION
------------
Install the Noindex Metatag as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420
for further information.

1) Copy the noindex_metatag folder in the modules folder
in your Drupal directory.
Or use composer to download the module

2) Enable the module using Manage -> Extend (/admin/modules).


CONFIGURATION
-------------
1) Enable the settings at
	- Manage -> Configuration -> Search and Meta data -> 
	  Noindex Metatag Settings
     (/admin/config/search/noindex-metatag)
	- Enable the functionality
  
2) Add noindex Routes
	- A list of routes that you want to be add
	  no-index. Wildcard "*" is supported.
  
3) Domain path: A list of host routes
   (with out http:// or https://). 
   If the domain name http://dev.example.com means just add dev.*
    Example:
      local.*
      dev.*

4) Relative path: A list of Relative url with slash(/)
    Example:
      /admin/*
      /user/reset/*
      /blogs
	  /events
