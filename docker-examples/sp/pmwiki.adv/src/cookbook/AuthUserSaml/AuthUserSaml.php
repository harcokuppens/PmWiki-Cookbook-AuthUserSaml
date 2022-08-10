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



# handle saml login
#-------------------    

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
     global $AuthUserSaml_SimpleSamlPhp_dir;

     # first start session; important to do this for that simplesaml does open a session
     @session_start();
          
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
   
   if(isset($_REQUEST['logout'])){
     $_SESSION['samlAuthenticated']=false;
   }     
}     


doSamlAuthentification();
