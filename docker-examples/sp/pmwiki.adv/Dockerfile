# syntax=docker/dockerfile:1.4

#################################################
#   pmwiki with saml authentication (advanced)
#################################################

# usage: 
#  
#  run container(s) with docker command:
#  * this image together with the idp image: (run in parent dir container docker-compose.yml)
#
#      docker compose  -f docker-compose.pmwiki.adv.yml  up --build
#
#  * only this image:
#
#      docker build -t img-pmwiki . && docker run -p 9443:443  --rm  --name  ctr-pmwiki  img-pmwiki  
#    
#  then open in browser:
#
#      https://localhost:9443


#-----------------------------------------------
#  configuration  by docker arguments
#-----------------------------------------------

# idea from https://github.com/kristophjunge/docker-test-saml-idp use env. variables to possibly override sp in idp
# use ARG instead ENV because ARG works over multiple stages, and ENV only in on stage


ARG PORT='9443'

# versions installed:
ARG PMWIKI_VERSION='2.3.7'
ARG SIMPLESAMLPHP_VERSION='1.19.5'

# install pmwiki into 'pmwiki' subdir of website's rootfolder
ARG PMWIKI_DIR='/var/www/html/pmwiki'

# install simplsamlphp library into AuthUserSaml cookbook folder
# note: double quotes to let ${PMWIKI_DIR} interpreted when setting SIMPLESAMLPHP_DIR.
#       This trick doesn't work in a docker-compose.yml file. 
ARG SIMPLESAMLPHP_DIR="${PMWIKI_DIR}/cookbook/AuthUserSaml/simplesamlphp"
#    `-> in config of cookbook/AuthUserSaml/AuthUserSaml.php we have the
#        config param '$AuthUserSaml_SimpleSamlPhp_dir' to configure the location of 
#        the simplsamlphp library relative to its cookbook directory: 
#            $AuthUserSaml_SimpleSamlPhp_dir= __DIR__ . "/simplesamlphp/";
#            require_once("$AuthUserSaml_SimpleSamlPhp_dir/lib/_autoload.php"); 
#        => so if we change above variable, also change it in cookbook/AuthUserSaml/AuthUserSaml.php         

# simplesamlphp configuration variables to specify the remote identity provider (idp) 
# in the configuration of the service provider(sp) which is our pmwiki website using the simplesamlphp library.
ARG SIMPLESAMLPHP_IDP_ENTITY_ID='https://localhost:8443/simplesaml/saml2/idp/metadata.php'
#   `-> used in config/authsources.php and metadata/saml20-idp-remote.php
ARG SIMPLESAMLPHP_IDP_SINGLE_SIGNON_SERVICE='https://localhost:8443/simplesaml/saml2/idp/SSOService.php'
#   `-> used in metadata/saml20-idp-remote.php
ARG SIMPLESAMLPHP_IDP_SINGLE_LOGOUT_SERVICE='https://localhost:8443/simplesaml/saml2/idp/SingleLogoutService.php'
#   `-> used in metadata/saml20-idp-remote.php


#-----------------------------------------------
#  setup apache with ssl
#-----------------------------------------------

# use base php apache image which by default only supports http (no https)
# ------------------------------------------------------------------------
FROM php:7.4-apache
# src:
#    php https://github.com/docker-library/docs/blob/master/php/README.md#apache-with-a-dockerfile
#
#    apache config in /etc/apache2/apache2.conf
#    Dockerfile at: 
#     https://github.com/docker-library/php/blob/f2b879649fb8238dfa96592c49cd676601ee33a1/7.4/bullseye/apache/Dockerfile
#      which contains the lines: 
#          ENTRYPOINT ["docker-php-entrypoint"]
#          # https://httpd.apache.org/docs/2.4/stopping.html#gracefulstop
#          STOPSIGNAL SIGWINCH
#
#          COPY apache2-foreground /usr/local/bin/
#          WORKDIR /var/www/html
#
#          EXPOSE 80
#          CMD ["apache2-foreground"]

# for debugging we install
#--------------------------
RUN apt-get update && apt-get -y install vim less iproute2 iputils-ping

#  enable ssl
#-----------------------------------------------
# note: we use ideas from https://github.com/nezhar/php-docker-ssl/blob/master/Dockerfile

# 1. install certificates for apache
#
# create certificate for apache 
#RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/ssl-cert-snakeoil.key -out /etc/ssl/certs/ssl-cert-snakeoil.pem -subj "/C=NL/ST=Netherlands/L=Nijmegen/Radboud University/OU=ICIS SWS/CN=cs.ru.nl"
#
# Instead above we create certificate outside docker and copy certificate into docker image
# because then we keep using the same certificate when rebuilding image. 
# Otherwise it will change per build, and you have to reaccept that selfsigned cert. in your browser.
# So each website needs unique certificate so we create it outside of docker, and copy it into the image:
COPY cert/ssl-cert-apache.key /etc/ssl/private/ssl-cert-snakeoil.key
COPY cert/ssl-cert-apache.pem /etc/ssl/certs/ssl-cert-snakeoil.pem




# 2. configure apache
#
# Use in config "Alias /simplesaml /var/simplesamlphp/www"
# below we use a stripped and patched version from image's default file /etc/apache2/sites-available/default-ssl.conf
# plus additions described in
#   https://simplesamlphp.org/docs/latest/simplesamlphp-install.html#download-and-install-simplesamlphp
# by copying config file in /etc/apache2/sites-enabled/ we enable a ssl website config
COPY <<EOF /etc/apache2/sites-enabled/default-ssl.conf
<IfModule mod_ssl.c>
    <VirtualHost _default_:443>
        ServerAdmin webmaster@localhost

        DocumentRoot /var/www/html
        
        # below commented, because we use default of apache base image that logging is done to stdout/stderr
        #ErrorLog ${APACHE_LOG_DIR}/error.log
        #CustomLog ${APACHE_LOG_DIR}/access.log combined

        SSLEngine on

        SSLCertificateFile    /etc/ssl/certs/ssl-cert-snakeoil.pem
        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key


        <FilesMatch "\.(cgi|shtml|phtml|php)$">
                SSLOptions +StdEnvVars
        </FilesMatch>
        <Directory /usr/lib/cgi-bin>
                SSLOptions +StdEnvVars
        </Directory>

        # below commented, because we install simplesamlphp into pmwiki's cookbook directory instead
        # so that we can install it in pmwiki without needing to change apache's config
        #
        # # config for simplesamlphp
        # #-------------------------
        # # https://simplesamlphp.org/docs/stable/simplesamlphp-install.html#configuring-apache
        # SetEnv SIMPLESAMLPHP_CONFIG_DIR /var/simplesamlphp/config
        #
        # Alias /simplesaml /var/simplesamlphp/www
        #
        # <Directory /var/simplesamlphp/www>
        #     Require all granted
        # </Directory>
        # #-------------------------

    </VirtualHost>
</IfModule>
EOF


RUN a2enmod rewrite
## next line not needed because by copying config file in /etc/apache2/sites-enabled/ we already enabled the ssl config
#RUN a2ensite default-ssl
RUN a2enmod ssl

# https://github.com/docker-library/docs/blob/master/php/README.md#configuration
# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"


# KISS: only support https access (http closed,no redirect needed!)
# EXPOSE 80 # note: already in "php:7.4-apache" base image, but we don't use it!
EXPOSE 443

# ----------------------------------------------------------------
# install pmwiki : https://www.pmwiki.org/wiki/PmWiki/Installation
# ----------------------------------------------------------------

ARG PORT
ENV PORT="${PORT}"

# declare args in local phase to get default arg values set from top of Dockerfile
ARG PMWIKI_DIR
# Make persist in container as environment variables
ENV PMWIKI_DIR="${PMWIKI_DIR}" 

# 0. php website starts with content from src/ folder 
RUN mkdir -p ${PMWIKI_DIR} 
COPY src/ ${PMWIKI_DIR}

# the SP running on port 443 is mapped in docker to a port on the localhost
# using the ARG "PORT" we can change the mapping port on localhost:
RUN sed -i "s|https://localhost:9443|https://localhost:${PORT}|" ${PMWIKI_DIR}/local/config.php

# 1. copy pmwiki source 
ARG PMWIKI_VERSION
ENV PMWIKI_VERSION="${PMWIKI_VERSION}"
RUN curl -s -L https://www.pmwiki.org/pub/pmwiki/pmwiki-${PMWIKI_VERSION}.tgz --output - | tar  -xz --strip-components 1  -C ${PMWIKI_DIR}    


# 2. use .htaccess file to do redirection to pmwiki.php in pmwiki/ subdir
#    and to redirect idp's request to /simplesamlphp to 
# configuring auth source in sp
COPY <<'EOF' /var/www/html/.htaccess
RewriteEngine On

# Send requests without parameters to pmwiki/
RewriteRule ^$           pmwiki/  [L]

# send /simplesaml/.. requests to pmwiki/simplesaml/...
RewriteCond %{REQUEST_URI} !^/pmwiki/simplesaml(.*)$
RewriteRule ^simplesaml/(.*) pmwiki/simplesaml/$1  [QSA,L]

# Send requests starting with capital letter or digit to pmwiki/
RewriteCond %{REQUEST_URI} !^/pmwiki(.*)$
RewriteRule ^([A-Z0-9\xa0-\xff].*) pmwiki/$1  [QSA,L]
EOF

# adapt .htaccess file for changes in subdir of pmwiki installation
RUN subdir=${PMWIKI_DIR#/var/www/html/} && subdir=${subdir%/} && sed -i "s#pmwiki#$subdir#g" /var/www/html/.htaccess


# .htaccess file which does 
# - redirect requests in ${PMWIKI_DIR} to pmwiki.php
# - redirect requests to 
# NOTE: if pmwiki installed in rootdir of webserver(/var/www/html) then this file overwrites above .htaccess which is fine
COPY <<'EOF' ${PMWIKI_DIR}/.htaccess
# https://www.pmwiki.org/wiki/Cookbook/CleanUrls
# Use mod_rewrite to enable "Clean URLs" for a PmWiki installation.
RewriteEngine On

# Send requests without parameters to pmwiki.php.
RewriteRule ^$           pmwiki.php  [L]
# Send requests for index.php to pmwiki.php.
RewriteRule ^index\.php$ pmwiki.php  [L]

# below fails, because cookbook/.htaccess denies all access -> instead use softlink!
# # send /simplesaml/.. requests to /cookbook/AuthUserSaml/simplesamlphp/www/...
# RewriteCond %{REQUEST_URI} !^/cookbook/AuthUserSaml/simplesamlphp/www(.*)$
# RewriteRule ^simplesaml/(.*) cookbook/AuthUserSaml/simplesamlphp/www/$1  [QSA,L]


# Send requests to pmwiki.php, appending the query string part.
RewriteRule ^([A-Z0-9\xa0-\xff].*)$ pmwiki.php?n=$1  [QSA,L]
EOF



# 3. copy improved Site pages(Site.AuthForm,Site.PageActions) which support saml authentication
COPY wikilib.d/create_wikipage.bash wikilib.d/Site.AuthForm.txt wikilib.d/Site.PageActions.txt /tmp
RUN cd /tmp/ && bash create_wikipage.bash Site.AuthForm.txt ${PMWIKI_DIR}/wikilib.d/Site.AuthForm &&\
    bash create_wikipage.bash Site.PageActions.txt ${PMWIKI_DIR}/wikilib.d/Site.PageActions

# 4. fix write permissions
RUN  chmod -R a+rwX /var/www/html/

#---------------------------------------------------------------
#  install simplesamlphp: follow quickstart guide simplesamlphp
#---------------------------------------------------------------
# src: https://simplesamlphp.org/docs/stable/simplesamlphp-sp.html
# note: 
#  - some names we changed in better names
#  - the idp metadata we parameterized with environment variables which you can override (default values set at top of file)

# declare args in local phase to get default arg values set from top of Dockerfile
ARG SIMPLESAMLPHP_DIR
# Make persist in container as environment variables
ENV SIMPLESAMLPHP_DIR="${SIMPLESAMLPHP_DIR}" 

# installation simplesamlphp
# --------------------------
# 1. install  simplesamlphp in ${SIMPLESAMLPHP_DIR}   
ARG SIMPLESAMLPHP_VERSION
ENV SIMPLESAMLPHP_VERSION="${SIMPLESAMLPHP_VERSION}"
RUN mkdir -p ${SIMPLESAMLPHP_DIR} && curl -s -L  https://github.com/simplesamlphp/simplesamlphp/releases/download/v${SIMPLESAMLPHP_VERSION}/simplesamlphp-${SIMPLESAMLPHP_VERSION}.tar.gz --output - | tar  -xz --strip-components 1  -C ${SIMPLESAMLPHP_DIR} && chmod -R +rX  ${SIMPLESAMLPHP_DIR}    

# 2. The remote idp uses simplesaml url request to the sp (pmwiki website) to communicate  to the website whether login succeeded 
#    In first instance the simplesaml url request handled by  ${ROOT_URL}/.htaccess  redirects /simplesaml/ 
#    url's into the 'pmwiki' subdir of the website.
#    Then within 'pmwiki' subdir a softlink redirects 'simplesaml' to ${SIMPLESAMLPHP_DIR}/www".
#    To separate the redirection outside and inside 'pmwiki' subdir allows you more easily to move this pmwiki installation.
RUN ln -s ${SIMPLESAMLPHP_DIR}/www ${PMWIKI_DIR}/simplesaml
#     `-> uses .htaccess redirect from rootdir into ${PMWIKI_DIR}  
#         but within ${PMWIKI_DIR} a softlink is used because cookbook/ folder has a deny all .htaccess file

## Next line could be added to strip some unneeded installation files, to keep the install smaller.
#RUN  cd ${SIMPLESAMLPHP_DIR} && rm -rf COPYING *.md  config-templates metadata-templates tests  dictionaries docs extra log phpunit.xml locales schemas/ routing/  bin/ attributemap/ package-lock.json package.json webpack.config.js vendor/twig/ vendor/phpfastcache vendor/phpmailer vendor/whitehat101/ vendor/gettext  www/assets/fonts


# install certificates for simplesamlphp
#---------------------------------------
# 
# create certificate for sp
#   src: https://serverfault.com/questions/649990/non-interactive-creation-of-ssl-certificate-requests
#   .pem is private key and .crt is certificate
# RUN openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out /var/simplesamlphp/cert/sp.crt -keyout /var/simplesamlphp/cert/sp.pem -subj "/C=NL/ST=Netherlands/L=Nijmegen/O=Radboud University/OU=ICIS SWS/CN=cs.ru.nl"
#
# Instead above we create certificate outside docker and copy certificate into docker image
# because then we keep using the same certificate when rebuilding image. 
# Otherwise it will change per build, and you have to reaccept that selfsigned cert. in your browser.
# So each website needs unique certificate so we create it outside of docker, and copy it into the image:
COPY cert/sp.crt ${SIMPLESAMLPHP_DIR}/cert/
COPY cert/sp.pem ${SIMPLESAMLPHP_DIR}/cert/

# config simplesamlphp
# ---------------------

# change contact email
RUN sed -i "s|'technicalcontact_email' => 'na@example.org',|'technicalcontact_email' => 'simplesamlphp@mailinator.com',|"  ${SIMPLESAMLPHP_DIR}/config/config.php

# change adminpassword and secretsalt
RUN sed -i "s|'auth.adminpassword' => '123',|'auth.adminpassword' => 'secret',|"  ${SIMPLESAMLPHP_DIR}/config/config.php
RUN sed -i "s|'secretsalt' => 'defaultsecretsalt',|'secretsalt' => '678617yfg36mjqrr1hv5xv0txi59xmz5',|"  ${SIMPLESAMLPHP_DIR}/config/config.php


# Configure debugging
# - enable saml debug messages
RUN sed -i "s|'saml' => false,|'saml' => true,|"  ${SIMPLESAMLPHP_DIR}/config/config.php
# - change level of logging from NOTICE to DEBUG level  
#RUN sed -i "s|Logger::NOTICE,|Logger::DEBUG,|"  ${SIMPLESAMLPHP_DIR}/config/config.php
# - send log messages to stderr  (docker uses stderr to show log messages) 
RUN sed -i "s|'logging.handler' => 'syslog',|'logging.handler' => 'errorlog',|" ${SIMPLESAMLPHP_DIR}/config/config.php



# Prevent cookie conflicts by giving cookies unique names
#   Give cookies different name to prevent conflicts with SP in a docker compose run.
#   Normally different SP's and IdP's run each on a separate dns host causing the domain attribute to separate the cookies 
#   the browser. However when running the SP's and IdP's within docker using docker compose we communicated with the
#   different services using port mapping a localhost port to a service. This causes all cookies to get domain 'localhost'
#   in the browser running on localhost. This causes the SP and IdP using both the same SimpleSAML cookie, and more of such conflicts.
#   The easiest solution for runnnig in docker is to give each cookie an unique name.
# 
# SP's SimpleSAML cookie 
RUN sed -i "s|'session.phpsession.cookiename' => .*|'session.phpsession.cookiename' => 'SimpleSAML_SP${PORT}',|" ${SIMPLESAMLPHP_DIR}/config/config.php
# SP's SimpleSAMLAuthToken cookie 
RUN sed -i "s|'session.authtoken.cookiename' => .*|'session.authtoken.cookiename' => 'SimpleSAMLAuthToken_SP${PORT}',|" ${SIMPLESAMLPHP_DIR}/config/config.php
#
# PmWiki using php's PHPSESSID cookie (see https://www.php.net/manual/en/session.configuration.php  session.name)
RUN sed -i "s|session.name = PHPSESSID|session.name = PHPSESSID_SP${PORT}|" "$PHP_INI_DIR/php.ini"



# # BUGFIX: see https://github.com/simplesamlphp/simplesamlphp/issues/1592
# RUN sed -i "s|\$this->setupL10N();|\$this->setupL10N();\$this->setupTranslator();|" /var/www/html/pmwiki/cookbook/AuthUserSaml/simplesamlphp/./lib/SimpleSAML/Locale/Localization.php



# declare args in local phase to get default arg values set from top of Dockerfile
ARG SIMPLESAMLPHP_IDP_ENTITY_ID
ARG SIMPLESAMLPHP_IDP_SINGLE_SIGNON_SERVICE
ARG SIMPLESAMLPHP_IDP_SINGLE_LOGOUT_SERVICE

# Make persist in container as environment variables
ENV SIMPLESAMLPHP_IDP_ENTITY_ID="$SIMPLESAMLPHP_IDP_ENTITY_ID" 
ENV SIMPLESAMLPHP_IDP_SINGLE_SIGNON_SERVICE="$SIMPLESAMLPHP_IDP_SINGLE_SIGNON_SERVICE" 
ENV SIMPLESAMLPHP_IDP_SINGLE_LOGOUT_SERVICE="$SIMPLESAMLPHP_IDP_SINGLE_LOGOUT_SERVICE" 

# configuring auth source in sp
COPY <<'EOF' ${SIMPLESAMLPHP_DIR}/config/authsources.php
<?php
 $config = [

     // This is a authentication source which handles admin authentication.
     'admin' => [
         // The default is to use core:AdminPassword, but it can be replaced with
         // any authentication source.

         'core:AdminPassword',
     ],

     /* This is the name of this authentication source, and will be used to access it later. */
     'default-sp' => [
         'saml:SP',
         
         // from https://simplesamlphp.org/docs/1.19/simplesamlphp-sp
         //  If you enable a certificate for your Service Provider, 
         //  it may be able to sign requests and response sent to the Identity Provider, 
         //    ^^^^^^^^^^      ^^^^
         //      `-> not directly done, must first be activated!    
         //   as well as receiving encrypted responses.
         //              ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         
         # see documentation at  https://www.pmwiki.org/wiki/Cookbook/AuthUserSaml#config 
         
         #'privatekey' => 'sp.pem',       // Enabling a certificate for your Service Provider 
         #'certificate' => 'sp.crt',      //  => allows you to receive encrypted assertions from IdP (not enforced).
         
         #'assertion.encryption' => TRUE,  // Incoming assertions MUST be encrypted. 
                                           // If this option is set to TRUE , unencrypted assertions will be rejected.
                                           // if FALSE it will handle unencrypted assertions, but it will also handle 
                                           // encrypted assertions ONLY IF certificate if idp is configured which 
                                           // is by default REQUIRED!!!
         
         #'redirect.sign' => TRUE,         // Sign authentication requests, logout requests and logout responses sent from this SP (to IdP)
                                           // Signatures are only validated if on the IdP site the 'redirect.sign' 
                                           // option is enabled otherwise the signatures will just be ignored by the IdP.
         
         # redirect.validate' => TRUE,     // Validate signature on logout requests and logout responses received by this SP (from IdP)
                                           // Requires signature added on IdP site by enabling the 'redirect.sign' option on the Idp,
                                           // because otherwise you get error on SP: 
                                           //     Validation of received messages enabled, but no signature found on message.    
         
         # OFFICIAL DOCS: from https://simplesamlphp.org/docs/stable/saml/sp.html  (or per idp specific
         #                https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote.html#)
         #
         # assertion.encryption
         #    Whether assertions received by this SP must be encrypted. The default value is FALSE . 
         #    If this option is set to TRUE , unencrypted assertions will be rejected.
         # redirect.sign
         #    Whether authentication requests, logout requests and logout responses sent from 
         #    this SP should be signed. The default is FALSE .
         # redirect.validate
         #    Whether logout requests and logout responses received by this SP should be validated. 
         #    The default is FALSE .
         

         
                                          
         /*
          * The entity ID of the IdP this should SP should contact.
          * Can be NULL/unset, in which case the user will be shown a list of available IdPs.
          */
         'idp' => getenv('SIMPLESAMLPHP_IDP_ENTITY_ID'),
     ],
 ];
EOF


#  Adding IdPs to the SP
#COPY cert/idp.crt ${SIMPLESAMLPHP_DIR}/cert/
COPY cert/* ${SIMPLESAMLPHP_DIR}/cert/

COPY <<'EOF' ${SIMPLESAMLPHP_DIR}/metadata/saml20-idp-remote.php 
<?php
 $metadata[getenv('SIMPLESAMLPHP_IDP_ENTITY_ID')] = [
      #'entityid' => '....', 
      'SingleSignOnService'  => getenv('SIMPLESAMLPHP_IDP_SINGLE_SIGNON_SERVICE'),
      'SingleLogoutService'  => getenv('SIMPLESAMLPHP_IDP_SINGLE_LOGOUT_SERVICE'),
    
     'certificate'          => 'idp.crt',
     # Lets take the situation where the assertion.encryption and redirect.validate options are both false on both SP and IdP 
     # What happens then if something is wrong with certificate configuration in the SP:
     # * if you would comment out the certificate line then on the authentication response from the IdP 
     #   you get the Exception: Missing certificate in metadata for 'https://.../simplesaml/saml2/idp/metadata.php'
     # * if you use the wrong certificate  then on the authentication response from the IdP
     #   you get the Exception :  Unable to validate Signature          
     #    => documentation says: SimpleSAMLphp only signs authentication responses by default. 
     #       From these exceptions you can see it also by default tries to validate them. 
     #       (even when redirect.validate is false on both SP and IdP)
     

 ]; 
EOF




