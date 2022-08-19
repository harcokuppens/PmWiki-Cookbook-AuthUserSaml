# syntax=docker/dockerfile:1.4

#################################################
#   simple php website with saml authentication
#################################################

# usage: 
#  
#  run container(s) with docker command:
#  * this image together with the idp image: (run in parent dir container docker-compose.yml)
#
#      docker compose  -f docker-compose.simple_php_website.yml  up --build
#
#  * only this image:
#
#      docker build -t img-simplephpwebsite . && docker run -p 9443:443  --rm  --name  ctr-simplephpwebsite  img-simplephpwebsite  
#    
#  then open in browser:
#
#      https://localhost:9443


#-----------------------------------------------
#  configuration  by docker arguments
#-----------------------------------------------

# idea from https://github.com/kristophjunge/docker-test-saml-idp use env. variables to possibly override sp in idp
# use ARG instead ENV because ARG works over multiple stages, and ENV only in on stage

# versions installed:
ARG SIMPLESAMLPHP_VERSION='1.19.5'

# install location simplsamlphp library
ARG SIMPLESAMLPHP_DIR="/var/www/html/simplesamlphp"
        

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
# 2. create ${ROOT_URL}/simplesaml/ website for remote idp to access to communicate succeeded/failed login back to website
# make in browser rootdir softlink  simplesaml -> ${SIMPLESAMLPHP_DIR}/www": 
RUN ln -s ${SIMPLESAMLPHP_DIR}/www /var/www/html/simplesaml

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
COPY cert/idp.crt ${SIMPLESAMLPHP_DIR}/cert/
COPY <<'EOF' ${SIMPLESAMLPHP_DIR}/metadata/saml20-idp-remote.php 
<?php
 $metadata[getenv('SIMPLESAMLPHP_IDP_ENTITY_ID')] = [
      #'entityid' => '....', 
      'SingleSignOnService'  => getenv('SIMPLESAMLPHP_IDP_SINGLE_SIGNON_SERVICE'),
      'SingleLogoutService'  => getenv('SIMPLESAMLPHP_IDP_SINGLE_LOGOUT_SERVICE'),
      'certificate'          => 'idp.crt',
 ]; 
EOF



# ----------------------------------------------------------------
# install website's source 
# ----------------------------------------------------------------

# copy main website from src/ folder
COPY src/ /var/www/html/



