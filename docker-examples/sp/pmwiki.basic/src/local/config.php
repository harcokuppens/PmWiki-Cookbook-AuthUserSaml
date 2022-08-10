<?php if (!defined('PmWiki')) exit();



#-----------------------------------------------
#  authentication
#-----------------------------------------------
# doc: http://www.pmwiki.org/wiki/PmWiki/AuthUser

# enable saml authentification in AuthUser
# ----------------------------------------
# first set some optional configuration parameters (they have good defaults)
$AuthUserSaml_ButtonText="Login with Saml Identify Server";
#$AuthUserSaml_SimpleSamlPhp_dir='/var/www/html/simplesamlphp';
require_once("$FarmD/cookbook/AuthUserSaml/AuthUserSaml.php");
# -> if request url has request param '..?saml_login' then above code does saml authentication
# -> and it registers AuthUserFunction for saml to let pmwiki verify saml is authenticated
# The saml identyprovider has following credentials setup: 
#   user: student
#   password: studentpass

# enable user authentication in pmwiki
# ------------------------------------
# Note: users and groups must be defined before including authuser.php because otherwise they are ignored!
# set fixed hidden user accounts 
$AuthUser['admin'] = crypt('admin');  
# add people to admin group as follow:
$AuthUser['@admins'][] = 'admin' ;  # local 'hidden' pmwiki account
$AuthUser['@admins'][] = 'student'; # user from saml identity provider

# -> immediately authenticates user
#      * either checks if authentication with saml already succeeded
#      * or does local authentication with local user/passwd file
include_once("$FarmD/scripts/authuser.php");


#-----------------------------------------------
#  authorization
#-----------------------------------------------
# doc: https://www.pmwiki.org/wiki/PmWiki/PasswordsAdmin

# sets DEFAULT authorization rules TO:
#  * everone : read permission
#  * authenticated users: read+write permission
#  * people in admin group :  all permissions
$DefaultPasswords['read'] = ""; # empty means : everybody allowed
$DefaultPasswords['admin'] = array('@admins');
$DefaultPasswords['attr'] = array('@admins');
$DefaultPasswords['edit'] = 'id:*';         # special syntax to allow logged in users
$DefaultPasswords['upload'] = 'id:*';


