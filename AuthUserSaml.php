<?php

#-----------------------------------------------
#   saml authentication
#-----------------------------------------------

# configuration parameters
#-------------------------    

# configure text on login button
if( empty($AuthUserSaml_ButtonText) ) $AuthUserSaml_ButtonText="Login with Saml";

# simplesaml library within this cookbook directory
if( empty($AuthUserSaml_SimpleSamlPhp_dir) ) $AuthUserSaml_SimpleSamlPhp_dir= __DIR__ . "/simplesamlphp/";


# (:saml_loginbox:) markup
#-------------------------    


function StripLoginActionFromURL($url) {

  $url = str_replace("action=login&", "", $url);       # has arguments behind it
  $url = preg_replace("#[\?\&]action=login$#", "", $url); # is last argument

  return $url;
}

/**
 *Loginbox for standard provider
 */
function SamlLoginBox(){
  global  $AuthUserSaml_ButtonText;
  #IMPORTANT: pmwiki handles the form action with an auth request to simplesamlphp, (in doSamlAuthentification())
  #           after which simplesamlphp returns to the caller's url: the form's action url.
  #           By setting the action url to the same url as the page where the form is shown, 
  #           then after authentication you will redirected back to this page.
  
  # get url as in addressbar of browser; when using pretty urls, they are preserved!
  $url="$_SERVER[REQUEST_URI]";
  # strip action=login query parameter from url to immediately load the specific page in the url.
  # Because without stripping the action=login pmwiki will show the login form, even logged in!
  $url=StripLoginActionFromURL($url);
  # add saml_login param, so on post request we can detect this request as saml login request
  if (strpos($url,'?') !== false) {
     $url="$url&saml_login";
  } else {
     $url="$url?saml_login"; 
  }         
  
  $output = '<form action="'.$url.'" method="post"><button size="30">'.$AuthUserSaml_ButtonText.'</button></form>';
  return $output;
}

//Define markup for federated auth buttons
//Usage of the markup should be pretty straightforward
Markup_e("saml_loginbox", "directives", '/\\(:saml_loginbox:\\)/i', "SamlLoginBox()");



# handle SAML login and logout  (it supports single login and single logout!!)
#------------------------------
    

/**
 * AuthUserSaml function checks login from request parameters
 */
function AuthUserSaml($pagename, $id, $pw, $pwlist) {
  try{
    //Check if we can validate the session from saml provider
    if(@$_POST['passedSaml'] === true){  # via http post method we can never set a post variable to a bool type, only to string types!
      # samlAuthenticated: to check if you're saml authenticated
      
      $_SESSION['samlAuthenticated'] = true;       
      return true;
    }
    //nope, return false
    return false;
  } catch(ErrorException $e) {
    return false;
  }
}
# AuthUser 'saml' must be defined to register $AuthUserFunction 'saml' below
$AuthUser['saml']="//module"; # value is "//module" to signify this is an authentication module, 
                              # however this value is never used in pmwiki. (could have been any value)
$AuthUserFunctions['saml'] = 'AuthUserSaml'; # register $AuthUserFunction 'saml'


# there is not yet a session, so first start the pmwiki session; => when pmwiki calls session_start later it will reuse this session!
# important:   simplesaml does opens  again a new session but after it is done we restore the session before (using session->cleanup call below)
@session_start();

# store in session whether we are logged in with saml
# set by default status to false:
if ( ! isset($_SESSION['samlAuthenticated'])   ) $_SESSION['samlAuthenticated']=false;


/**
 * loggedInWithSaml function checks whether you are currently logged 
 * in with saml authentication.
 * Note: alternative is e.g. logged in with local pmwiki account.
 */
function loggedInWithSaml()
{
   if ( $_SESSION['samlAuthenticated']   ) return true;
   return false;
}
    

function doSamlAuthentification() {
     global $AuthUserSaml_SimpleSamlPhp_dir,$action;

     # do saml login     
     if(isset($_REQUEST['saml_login'])){
        require_once("$AuthUserSaml_SimpleSamlPhp_dir/lib/_autoload.php");         
        $as = new \SimpleSAML\Auth\Simple('default-sp');
        if (!$as->isAuthenticated()) {    
          $loginUrl = $as->getLoginURL();
          header('Location: '  . $loginUrl); 
          exit;
        }
        
        # ok, saml authenticated! 
        # Now get the authid and finish the authentication in pmwiki.
        $as->requireAuth();
        
        $attributes = $as->getAttributes();
        $session = \SimpleSAML\Session::getSessionFromRequest();
        $session->cleanup();

        # parse attributes to set authid
        # note: you could also set some attributes in $_SESSION for later use (not done here)
        $authid=$attributes["urn:oid:0.9.2342.19200300.100.1.1"][0];    
        
        $_POST['authid']=$authid;
        $_POST['passedSaml'] = true;  
    }
   

 
    // sync remote SAML SP logout with PmWiki  
    //   logout in PmWiki when SAML SP got remotely logged out by SAML IDP 
    // explanation:           
    //   when using Single-Logout the logout on another website could have caused this website's SP to be also
    //   be logged out from SAML. (the IDP did a call to SAML library logout url).
    //   However the PmWiki website is not informed by the SP, but must apply polling to sync. 
    //   So when the PmWiki website is logged in with SAML, then on each query PmWiki must query the SP whether 
    //   it is logged out, and then log out the SAML user. 
    //   Note: querying the SP is just using the SAML library which looks in the SAML cookie for its login status. 
    //         => so VERY QUICK!
    if( $_SESSION['samlAuthenticated'] ) {
        # according to PmWiki's session we are logged in with SAML
        # we now going to verify this is also the case for the SAML SP using the SimpleSamlPhp library:
        require_once("$AuthUserSaml_SimpleSamlPhp_dir/lib/_autoload.php");
        $as = new \SimpleSAML\Auth\Simple('default-sp');
        $saml_authenticated=$as->isAuthenticated();
        $session = \SimpleSAML\Session::getSessionFromRequest();
        $session->cleanup();
        
        if (!$saml_authenticated) {     
            # logout from pmwiki
            # logout by cleaning up session, but not doing redirect, but continue handling current request without being logged in!
            doLogout();
            
            # update SAML status in pmwiki session
            # first create new session because just deleted in doLogout
            @session_start();
            $_SESSION['samlAuthenticated']=false;
        }       
    }   
 
    # after above sync  $_SESSION['samlAuthenticated'] is the correct SAML authentication status!
    
    # do SAML logout
    if( $action=="logout" && $_SESSION['samlAuthenticated']){
         # when action=logout always do SAML logout
         
         require_once("$AuthUserSaml_SimpleSamlPhp_dir/lib/_autoload.php");
         $as = new \SimpleSAML\Auth\Simple('default-sp');        
         # get SAML logout url 
         $logoutUrl = $as->getLogoutURL();
         # restore normal session 
         $session = \SimpleSAML\Session::getSessionFromRequest();
         $session->cleanup();   

         # mark logout from SAML in pmwiki session 
         # note: session will be deleted when logged out, so next param will also be deleted,
         #       however to be sure we still set it to false 
         $_SESSION['samlAuthenticated']=false; 
                  
         # redirect to logout url
         # note: logout url has current url as returnUrl parameter, 
         #       thus when SAML logout at logout url  is done it loads the return url
         #       The return url still contains the logout action so on return we will
         #       not be saml authenticated and will not do redirect, 
         #       but does execute the standard pmwiki logout action.
         header('Location: '  . $logoutUrl);
         exit; # needed otherwise above header not executed!

         # if not saml logged in then script continues and 
         # because action=logout the standard logout action is done by pmwiki 
    }
 
}     

function doLogout() {
  global  $LogoutCookies;
  SDV($LogoutCookies, array());
  @session_start();
  $_SESSION = array();
  if ( session_id() != '' || isset($_COOKIE[session_name()]) )
    pmsetcookie(session_name(), '', time()-43200, '/');
  foreach ($LogoutCookies as $c)
    if (isset($_COOKIE[$c])) pmsetcookie($c, '', time()-43200, '/');
  session_destroy();
}

doSamlAuthentification();

