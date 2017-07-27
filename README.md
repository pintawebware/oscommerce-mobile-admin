OSCOMMERCE-MOBILE-ADMIN 
=======================

Module for osCommerce version 2.0.0 and higher, containing the API for managing the online store from a mobile device.


== Installation ==

Installation is done by unpacking the module's archive into the root directory of the site:

1. Copy the archive with the module to the root directory of the site (in the same place as index.php);
2. Extract the contents of the archive to the same directory;
3. Edit the index.php file of the root directory of the site: -after the line "require ('includes / application_top.php');" Write "include ('push_api.php');".