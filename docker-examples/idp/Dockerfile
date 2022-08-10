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


ARG SIMPLESAMLPHP_SP_ENTITY_ID='https://localhost:9443/simplesaml/module.php/saml/sp/metadata.php/default-sp'
ARG SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE='https://localhost:9443/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp'
ARG SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE='https://localhost:9443/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp'


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
COPY cert/idp.crt /var/simplesamlphp/cert/
COPY cert/idp.pem /var/simplesamlphp/cert/

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


# Configure debugging
# - enable saml debug messages
RUN sed -i "s|'saml' => false,|'saml' => true,|"  /var/simplesamlphp/config/config.php
# - change level of logging from NOTICE to DEBUG level  
#RUN sed -i "s|Logger::NOTICE,|Logger::DEBUG,|"  /var/simplesamlphp/config/config.php
# - send log messages to stderr  (docker uses stderr to show log messages) 
RUN sed -i "s|'logging.handler' => 'syslog',|'logging.handler' => 'errorlog',|" /var/simplesamlphp/config/config.php



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
ARG SIMPLESAMLPHP_SP_ENTITY_ID
ARG SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE
ARG SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE

# Make persist in container as environment variables
ENV SIMPLESAMLPHP_SP_ENTITY_ID="$SIMPLESAMLPHP_SP_ENTITY_ID" 
ENV SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE="$SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE" 
ENV SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE="$SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE" 


COPY <<'EOF' /var/simplesamlphp/metadata/saml20-sp-remote.php
<?php
$metadata[getenv('SIMPLESAMLPHP_SP_ENTITY_ID')] = array(
    'AssertionConsumerService' => getenv('SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE'),
    'SingleLogoutService' => getenv('SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE'),
);
EOF


# ----------------------------------------------------------------
# install website's source 
# ----------------------------------------------------------------


# copy main website from src/ folder
COPY src/ /var/www/html/





