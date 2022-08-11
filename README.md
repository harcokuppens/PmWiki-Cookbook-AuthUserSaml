# AuthUserSaml extension for PmWiki

SAML authentication extension for AuthUser in PmWiki.

## Description

Security Assertion Markup Language (SAML) is an open standard for exchanging authentication and authorization data between parties, in particular, between an identity provider and a service provider. See https://en.wikipedia.org/wiki/Security_Assertion_Markup_Language for a more extensive explanation.

This extension uses the SAML implementation library from SimpleSAMLphp (https://simplesamlphp.org) for integrating SAML authentication into PmWiki's identity-based authorization system named [AuthUser](https://www.pmwiki.org/wiki/Cookbook/AuthUser). For a more extensive explanation look at the [SimpleSAMLphp documentation](https://simplesamlphp.org/docs) and in particularly the [SimpleSAMLphp Service Provider QuickStart page](https://simplesamlphp.org/docs/stable/simplesamlphp-sp.html).

## Terminology

* **[PmWiki](https://www.pmwiki.org)**  <br>
  PmWiki is a wiki-based content-management system (CMS) for collaborative creation and maintenance of websites.

* **[AuthUser](https://www.pmwiki.org/wiki/PmWiki/AuthUser)** <br>
AuthUser is PmWiki's identity-based authorization system that allows access to pages to be controlled through 
the use of usernames and passwords.

* **[SAML](https://en.wikipedia.org/wiki/Security_Assertion_Markup_Language)** <br>
  Security Assertion Markup Language (SAML) is an open standard for exchanging authentication and authorization data between parties, in particular, between an identity provider and a service provider.
   
## Docker examples
 The ```docker-examples/``` directory contains 3 examples of an identity provider and a service provider setup using SimpleSAMLphp. Each example is run with docker compose to start both the identity provider and a service provider at once. One example demonstrates SAML with a simple php script, and the other two examples with pmwiki as service provider; once for a basic config, and the other with a more advanced config. Take a look at these examples to better understand how it all works. 
 
Detailed instructions are at [docker-examples/README.md](https://github.com/harcokuppens/PmWiki-Cookbook-AuthUserSaml/blob/main/docker-examples/README.md).

## Installation

Instructions for installing this extension are at the PmWiki's [AuthUserSaml cookbook](https://www.pmwiki.org/wiki/Cookbook/AuthUserSaml) page.
