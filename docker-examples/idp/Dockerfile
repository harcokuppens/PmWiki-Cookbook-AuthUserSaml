# syntax=docker/dockerfile:1.4


##########################################################################################
#  saml identity provider (idp) using simple user/passwd authentication in a config file
##########################################################################################

# usage: 
#  
#  run container(s) with docker command:
#  * this image together with the idp image: (run in parent dir container docker-compose.yml)
#
#      docker compose  -f docker-compose.yml  up --build
#
#  * only this image:
#
#      docker build -t img-idp . && docker run -p 8443:443  --rm  --name  ctr-idp  img-idp  
#    
#  then open in browser:
#
#      https://localhost:8443



# alternatives:  https://github.com/kristophjunge/docker-test-saml-idp
#                or https://github.com/kenchan0130/docker-simplesamlphp


#-----------------------------------------------
#  configuration  by docker arguments
#-----------------------------------------------

# idea from https://github.com/kristophjunge/docker-test-saml-idp use env. variables to possibly override sp in idp
# use ARG instead ENV because ARG works over multiple stages, and ENV only in on stage

# specify ports for the sp's know for idp; specify the ports space separated for easy processing
# by default only have one SP configure on port 9443:
ARG SP_PORTS="9443"
##example multiple sp: 
#ARG SP_PORTS="9443 7443"

ARG SIMPLESAMLPHP_SP_ENTITY_ID='https://localhost:$PORT/simplesaml/module.php/saml/sp/metadata.php/default-sp'
ARG SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE='https://localhost:$PORT/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp'
ARG SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE='https://localhost:$PORT/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp'


ARG SIMPLESAMLPHP_VERSION='1.19.5'


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
RUN apt-get update && apt-get -y install vim less iproute2 iputils-ping gettext-base


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

        # config for simplesamlphp
        #-------------------------
        # https://simplesamlphp.org/docs/latest/simplesamlphp-install.html#download-and-install-simplesamlphp
        SetEnv SIMPLESAMLPHP_CONFIG_DIR /var/simplesamlphp/config

        Alias /simplesaml /var/simplesamlphp/www

        <Directory /var/simplesamlphp/www>
            Require all granted
        </Directory>
        #-------------------------

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
# src: https://simplesamlphp.org/docs/stable/simplesamlphp-idp.html
# note: 
#  - some names we changed in better names
#  - the sp metadata we parameterized with environment variables which you can override (default values set at top of file)

# installation simplesamlphp
# --------------------------
# we 1. copy source in /var/simplesamlphp  and 2. use in config "Alias /simplesaml /var/simplesamlphp/www": 
# 1. copy source in /var/simplesamlphp
ARG SIMPLESAMLPHP_VERSION
ENV SIMPLESAMLPHP_VERSION="${SIMPLESAMLPHP_VERSION}"
RUN mkdir /var/simplesamlphp && curl -s -L  https://github.com/simplesamlphp/simplesamlphp/releases/download/v${SIMPLESAMLPHP_VERSION}/simplesamlphp-${SIMPLESAMLPHP_VERSION}.tar.gz --output - | tar  -xz --strip-components 1  -C /var/simplesamlphp && chmod -R +rX  /var/simplesamlphp


# install certificates for simplesamlphp
#---------------------------------------
# 
# create certificate for idp
# src: https://serverfault.com/questions/649990/non-interactive-creation-of-ssl-certificate-requests
# .pem is private key and .crt is certificate
# RUN openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out /var/simplesamlphp/cert/idp.crt -keyout /var/simplesamlphp/cert/idp.pem -subj "/C=NL/ST=Netherlands/L=Nijmegen/O=Radboud University/OU=ICIS SWS/CN=cs.ru.nl"
#
# Instead above we create certificate outside docker and copy certificate into docker image
# because then we keep using the same certificate when rebuilding image. 
# Otherwise it will change per build, and you have to reaccept that selfsigned cert. in your browser.
# So each website needs unique certificate so we create it outside of docker, and copy it into the image:

COPY cert/* /var/simplesamlphp/cert/
RUN chmod a+r /var/simplesamlphp/cert/*

# config simplesamlphp
# ---------------------


# enable simplesamlphp as idp for saml version 2.0
RUN sed -i "s|'enable.saml20-idp' => false,|'enable.saml20-idp' => true,|" /var/simplesamlphp/config/config.php
# enable simple user/password config 
RUN sed -i "s|'exampleauth' => false,|'exampleauth' => true,|"  /var/simplesamlphp/config/config.php

# change contact email
RUN sed -i "s|'technicalcontact_email' => 'na@example.org',|'technicalcontact_email' => 'simplesamlphp@mailinator.com',|"  /var/simplesamlphp/config/config.php

# change adminpassword and secretsalt
RUN sed -i "s|'auth.adminpassword' => '123',|'auth.adminpassword' => 'secret',|"  /var/simplesamlphp/config/config.php
RUN sed -i "s|'secretsalt' => 'defaultsecretsalt',|'secretsalt' => '678617yfg36mjqrr1hv5xv0txi59xmz5',|"  /var/simplesamlphp/config/config.php


# # enable rememberme on ipd
# RUN sed -i "s|'session.rememberme.enable' => false,|'session.rememberme.enable' => true,|" /var/simplesamlphp/config/config.php
# RUN sed -i "s|'session.rememberme.checked' => false,|'session.rememberme.enable' => true,|" /var/simplesamlphp/config/config.php


# Configure debugging
# - enable saml debug messages
RUN sed -i "s|'saml' => false,|'saml' => true,|"  /var/simplesamlphp/config/config.php
# - change level of logging from NOTICE to DEBUG level  
#RUN sed -i "s|Logger::NOTICE,|Logger::DEBUG,|"  /var/simplesamlphp/config/config.php
# - send log messages to stderr  (docker uses stderr to show log messages) 
RUN sed -i "s|'logging.handler' => 'syslog',|'logging.handler' => 'errorlog',|" /var/simplesamlphp/config/config.php

# Prevent cookie conflicts by giving cookies unique names
#   Give cookies different name to prevent conflicts with SP in a docker compose run.
#   Normally different SP's and IdP's run each on a separate dns host causing the domain attribute to separate the cookies 
#   the browser. However when running the SP's and IdP's within docker using docker compose we communicated with the
#   different services using port mapping a localhost port to a service. This causes all cookies to get domain 'localhost'
#   in the browser running on localhost. This causes the SP and IdP using both the same SimpleSAML cookie, and more of such conflicts.
#   The easiest solution for runnnig in docker is to give each cookie an unique name.
# 
# IDP's SimpleSAML cookie 
RUN sed -i "s|'session.phpsession.cookiename' => .*|'session.phpsession.cookiename' => 'SimpleSAML_IDP',|" /var/simplesamlphp/config/config.php
# IDP's SimpleSAMLAuthToken cookie 
RUN sed -i "s|'session.authtoken.cookiename' => .*|'session.authtoken.cookiename' => 'SimpleSAMLAuthToken_IDP',|" /var/simplesamlphp/config/config.php
#
# PmWiki using php's PHPSESSID cookie (see https://www.php.net/manual/en/session.configuration.php  session.name)
RUN sed -i "s|session.name = PHPSESSID|session.name = PHPSESSID_IDP|" "$PHP_INI_DIR/php.ini"

# # BUGFIX: see https://github.com/simplesamlphp/simplesamlphp/issues/1592
# RUN sed -i "s|\$this->setupL10N();|\$this->setupL10N();\$this->setupTranslator();|" /var/simplesamlphp/lib/SimpleSAML/Locale/Localization.php


# configure idp to use 'userpass' as authentication source in ipd: username/passwords in config file
#-----------------------------------------------------------------------------------------


COPY <<'EOF' /var/simplesamlphp/metadata/saml20-idp-hosted.php
<?php
$metadata['__DYNAMIC:1__'] = [
    /*
     * The hostname for this IdP. This makes it possible to run multiple
     * IdPs from the same configuration. '__DEFAULT__' means that this one
     * should be used by default.
     */
    'host' => '__DEFAULT__',

    /*
     * The private key and certificate to use when signing responses.
     * These are stored in the cert-directory.
     */
    'privatekey' => 'idp.pem',
    'certificate' => 'idp.crt',

    #'assertion.encryption' => TRUE,  // Outgoing assertions are encrypted if 'assertion.encryption' option is enabled(TRUE). 
                                      // Requires certificate of each SP set in this IdP configuration otherwise you get :
                                      // Exception: $metadata['https://.../simplesaml/module.php/saml/sp/metadata.php/default-sp']: 
                                      //    Missing certificate in metadata.
                                     
    #'redirect.sign' => TRUE,         // Sign logout requests and logout responses sent to SP. (from this IdP)
                                      // Signatures are only validated if on the SP site the option 'redirect.validate' is enabled,  
                                      // else the signatures will just be ignored by the SP.

    #'redirect.validate' => TRUE,     // Validate signature on incoming authentication requests, logout requests and logout responses 
                                      // sent from this IdP (to an SP). 
                                      // Requires signature added on SP site by enabling the 'redirect.sign' option on the SP,
                                      // because otherwise you get error on idp: 
                                      //     Validation of received messages enabled, but no signature found on message.
    
                                      
    # OFFICIAL DOCS: from  https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-hosted.html 
    #                (or per sp https://simplesamlphp.org/docs/stable/simplesamlphp-reference-sp-remote)
    #
    #   assertion.encryption
    #     Whether assertions sent from this IdP should be encrypted. The default value is FALSE .
    #   redirect.sign
    #     Whether logout requests and logout responses sent from this IdP should be signed. 
    #     The default is FALSE .
    #   redirect.validate
    #     Whether authentication requests, logout requests and logout responses received sent 
    #     from this IdP should be validated. The default is FALSE
                          

    /*
     * The authentication source which should be used to authenticate the
     * user. This must match one of the entries in config/authsources.php.
     */
    'auth' => 'userpass',
    
    'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
      'authproc' => [
          // Convert LDAP names to oids.
          100 => ['class' => 'core:AttributeMap', 'name2oid'],
      ],
];
EOF


# configure 'userpass': set username/passwords in config file
#--------------------------------------------------------------

COPY <<'EOF' /var/simplesamlphp/config/authsources.php
<?php
$config = [

    // This is a authentication source which handles admin authentication.
    'admin' => [
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ],

  'userpass' => [
    'exampleauth:UserPass',
    
    // doc: https://github.com/simplesamlphp/simplesamlphp/blob/master/config-templates/authsources.php
    // Give the user an option to save their username for future login attempts
    // And when enabled, what should the default be, to save the username or not
    // 'remember.username.enabled' => false,  // default: false;  if true, keeps track of the user so that the username is pre-filled in the login box
    // 'remember.username.checked' => false,  // default: false;  requires 'remember.username.enabled' set to true, otherwise checkbox not shown
    
    'student:studentpass' => [
      'uid' => ['student'],
      'eduPersonAffiliation' => ['member', 'student'],
    ],
    'employee:employeepass' => [
      'uid' => ['employee'],
      'eduPersonAffiliation' => ['member', 'employee'],
    ],
  ],
];
EOF


# adding remote SP   => IMPORTANT: each SP must be registered!!
#------------------

# declare args in local phase to get default arg values set from top of Dockerfile
ARG SP_PORTS="$SP_PORTS"
ARG SIMPLESAMLPHP_SP_ENTITY_ID
ARG SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE
ARG SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE

# Make persist in container as environment variables
ENV SP_PORTS="$SP_PORTS" 
ENV SIMPLESAMLPHP_SP_ENTITY_ID="$SIMPLESAMLPHP_SP_ENTITY_ID" 
ENV SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE="$SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE" 
ENV SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE="$SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE" 


# create template for an sp
COPY <<EOF /tmp/template_meta_sp.txt
\$metadata['$SIMPLESAMLPHP_SP_ENTITY_ID'] = array(
    'AssertionConsumerService' => '$SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE',
    'SingleLogoutService' => '$SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE',

    'certificate' => 'sp.crt',
);
EOF
# fill template  with "evnsubst '$PORT'" which only replaces the PORT variable (leaving $metadata intact)
RUN  echo '<?php' > /var/simplesamlphp/metadata/saml20-sp-remote.php &&\
     for PORT in $SP_PORTS;\
     do\
       export PORT="$PORT";\
       cat /tmp/template_meta_sp.txt | envsubst '$PORT' >> /var/simplesamlphp/metadata/saml20-sp-remote.php;\
     done  
        


# ----------------------------------------------------------------
# install website's source 
# ----------------------------------------------------------------


# copy main website from src/ folder
COPY src/ /var/www/html/





