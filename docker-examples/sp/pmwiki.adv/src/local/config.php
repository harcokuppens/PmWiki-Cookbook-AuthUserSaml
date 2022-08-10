<?php if (!defined('PmWiki')) exit();


#-----------------------------------------------
#   Use "Clean URLs"
#-----------------------------------------------
# https://www.pmwiki.org/wiki/Cookbook/CleanUrls

$EnablePathInfo = 1;
$ScriptUrl = "https://localhost:9443";

#-----------------------------------------------
#  authentication
#-----------------------------------------------
# src: http://www.pmwiki.org/wiki/PmWiki/AuthUser
# Note: you can configure users and groups in SiteAdmin.AuthUser page,
#       however in example below we do this in this config file using
#       the $AuthUser array

# local authentication 
# --------------------

# set fixed hidden user accounts 
$AuthUser['admin'] = crypt('admin');  

# set fixed hidden authorization roles 
#  * use a pmwiki user group for a role
#    where we give user x the role y, by adding the user x to group y!
#  * special role: admin (almighty administrator)
#  * add people to admin role/group as follow:
$AuthUser['@admins'][] = 'admin' ;  # local 'hidden' pmwiki account


# enable saml authentification
# -----------------------------
# first set some optional configuration parameters (they have good defaults)
$AuthUserSaml_ButtonText="Login with Saml Identify Server";
#$AuthUserSaml_SimpleSamlPhp_dir='/var/www/html/simplesamlphp';
require_once("$FarmD/cookbook/AuthUserSaml/AuthUserSaml.php");
# -> if request url has request param '..?saml_login' then above code does saml authentication
# -> and it registers AuthUserFunction for saml to let pmwiki verify saml is authenticated
# The saml identiprovider has following credentials setup: 
#   user: student
#   password: studentpass

# we can make saml user admin:
$AuthUser['@admins'][] = 'student'; # user from saml identity provider

# enable user authentication in pmwiki
# ------------------------------------
# -> immediately authenticates user
#      * either checks if authentication with saml already succeeded
#      * or does local authentication with local user/passwd file
include_once("$FarmD/scripts/authuser.php");

# disable password-only authentication  (no username supplied)
#-------------------------------------------------------------
# When including authuser.php  it tries to verify author using $_POST['authid'] and $_POST['authpw'].
# Password only verification happens later in pmwiki.php script and that will fail if passwd is unset in _POST!!
# So next line must be put after including authuser.php because without password user authentication won't work!
if ( array_key_exists("authpw",$_POST) ) unset($_POST['authpw']);

# autoredirect to page after successfull login:
# ----------------------------------------------
#    Problem: if logging via login menu then after successfull local pmwiki login you still will 
#             be shown the login form because of the '?action=login' at the end of the url.
#             Note: no a problem for a Saml login.
#    Solution: after succesfull login redirect to the current page without '?action=login'
if( !empty($AuthId) && $action=='login') {
    Redirect($pagename);
}

# set $Author  to logged in user  $AuthId
#-----------------------------------------
$Author = $AuthId; # set by default Author  of edit to AuthId  (loginname!)

#-----------------------------------------------
#  authorization
#-----------------------------------------------

// sets DEFAULT authorization rules TO:
//  * everone : read permission
//  * authenticated users: read+write permission
//  * people in admin group :  all permissions
$DefaultPasswords['read'] = ""; # empty means : everybody allowed
$DefaultPasswords['admin'] = array('@admins');
$DefaultPasswords['attr'] = array('@admins');
$DefaultPasswords['edit'] = 'id:*';         # special syntax to allow logged in users
$DefaultPasswords['upload'] = 'id:*';


