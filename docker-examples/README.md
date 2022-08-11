# Docker SAML examples 
  
This directory contains 3 examples of an identity provider and a service provider setup using SimpleSAMLphp. Each example is run with docker compose to start both the identity provider and a service provider at once. One example demonstrates SAML with a simple php script, and the other two examples with pmwiki as service provider; once for a basic config, and the other with a more advanced config. Take a look at these examples to better understand how it all works. 


Short description examples: a SimpleSAMLphp identity provider in `idp`/ with as service provider:

1.  a simple index.php script at [`sp/simple_php_website/`](#spsimple_php_website) 
2.  a basic pmwiki installation  at [`sp/pmwiki.basic/`](#sppmwikibasic)    
3.  an advanced pmwiki installation at [`sp/pmwiki.adv/`](#sppmwikiadv)<br> Compared to the basic pmwiki installation the advanced pmwiki installation does more:   
     * extensive use of .htaccess redirects
     
        - cleanurls with .htaccess
        - pmwiki installed in subdir 'pmwiki' of webserver's root directory, but that 'pmwiki' subdir is not seen in the urls
          because we use redirects with .htaccess to pmwiki.php.  
        - we also use .htaccess for redirecting simplesaml requests from idp  to simplesamlphp directory in cookbook directory
     * the pmwiki website in 'pmwiki' subdir can easily be swapped with another(newer?) wiki in another subdir 
       just by swapping directories  
     * more advanced config
        - with pmwiki account login does also redirect on login page
        - disable password-only authentication 

The `idp/Dockerfile` follows the instructions in the SimpleSAMLphp_Identity_Provider_QuickStart.pdf and the `sp/simple_php_website/Dockerfile` follows the instruction in SimpleSAMLphp_Service_Provider_QuickStart.pdf. The pmwiki based `Dockerfile`'s are based on that `Dockerfile`.

## `sp/simple_php_website`

Demonstrating a SimpleSAMLphp identity provider with a simple index.php script.

First note that SAML uses redirects via the http protocol via the web browser on the host,
so the containers do not need to communicate directly with each other!
  
Goto the `docker-examples/` folder, and run the example with
   
    $ docker compose -f docker-compose.simple_php_website.yml  up --build

then open browser on `https://localhost:9443/` 

* the browser starts on the sp website: 
  * first accept the self signed certificate in the browser( which is easy in firefox and safari, but difficult in chrome)
  * then it shows simple page with the text: `Use simple login script: Login`
  * click on the `"Login"` link which redirects you to idp for authentication!<br>URL: https://localhost:8443/simplesaml/saml2/idp/SSOService.php?SAMLRequest=....)
  * Note: the sp sends a redirect url to the webbrowser on the host, which can access the above url to the idp. The sp container itself cannot access the above url!! (only the host can)

* then on the idp (on different host and webserver mapped to by different port 8443!!):
   * accept self signed certificate  (use firefox or safari browser, not easy to accept on chrome)     
   * login with credentials: 
   
            user: student
            password: studentpass
    
   * which after succesfull login redirects you back to the sp

* the browser shows you again the sp website:   
   * the webpage shows your login details.
   * you can press on "Logout" to logout. 
   * It shows an empty page after logging out. 
   * If you then press back in your browser to go again to https://localhost:9443/, you'll see that your are logged out now. 
   * You can login again.
          
          
Note: instead of clicking on "Login" you can also click on the link 'simplesamlphp client app' which directs you to simplesamlphp client app which is by default also installed when you install simplesaml library in you web application. Using this example client you can also test the login with the following instructions:   
         
* click the authentication tab
* click "Test configured authentication sources" link
* click on "default-sp" with link (https://localhost:9443/simplesaml/module.php/core/authenticate.php?as=default-sp) which redirects you to idp for authentication! (https://localhost:8443/simplesaml/saml2/idp/SSOService.php?SAMLRequest=....)
* After logging in on the ipd you are redicted back to a pag in this example sp (https://localhost:9443/simplesaml/module.php/core/authenticate.php?as=default-sp) which shows your login details.

## `sp/pmwiki.basic`

Goto the `docker-examples/` folder, and run the example with
   
    $ docker compose -f docker-compose.pmwiki.basic.yml  up --build

then open browser on `https://localhost:9443/` 

* the browser starts on the sp website: 
  * first accept the self signed certificate in the browser( which is easy in firefox and safari, but difficult in chrome)
  * then it shows the pmwiki wiki which is in this case the Service Provider
  * click in the top right corner on the `"Login"` link 
  * a login page is shown where you can choose between login with SAML or with a local pmwiki account. 
  * we login with SAML by pressing the `Login with Saml Identify Server` button which redirects you to idp for authentication!<br>URL: https://localhost:8443/simplesaml/saml2/idp/SSOService.php?SAMLRequest=....)
  * Note: the sp sends a redirect url to the webbrowser on the host, which can access the above url to the idp. The sp container itself cannot access the above url!! (only the host can)

* then on the idp (on different host and webserver mapped to by different port 8443!!):
   * accept self signed certificate  (use firefox or safari browser, not easy to accept on chrome)     
   * login with credentials: 
   
            user: student
            password: studentpass
    
   * which after succesfull login redirects you back to the sp

* the browser shows you again the sp website:   
   * the pmwiki wiki is shown again at the same page before logging in
   * in the top right corner the link `Logout student` is shown, which means your are logged in as 'student'
   * you can press the link `Logout student` to logout. 
   * you are immediately logged out and the link is changed back to 'Login'  
   * you can login again.

The authorization rules in the pmwiki config are set as such that only when logged in you can edit the pmwiki pages.   

Note: when logging out, you are logged out from pmwiki but not from the identity provider.
So when logging in again you are immediately logged in because the identity provider says you are already logged in as `student`.

## `sp/pmwiki.adv`

Goto the `docker-examples/` folder, and run the example with
   
    $ docker compose -f docker-compose.pmwiki.adv.yml  up --build

then open browser on `https://localhost:9443/`

The behavior for `sp/pmwiki.adv` is the same as for `sp/pmwiki.basic` except that nicer urls are shown for the wiki pages.
