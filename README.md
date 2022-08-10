SAML authentication extension for AuthUser in PmWiki.

## Terminology

* **[PmWiki](https://www.pmwiki.org)**  <br>
  PmWiki is a wiki-based content-management system (CMS) for collaborative creation and maintenance of websites.

* **[AuthUser](https://www.pmwiki.org/wiki/PmWiki/AuthUser)** <br>
AuthUser is PmWiki's identity-based authorization system that allows access to pages to be controlled through 
the use of usernames and passwords.

* **[SAML](https://en.wikipedia.org/wiki/Security_Assertion_Markup_Language)** <br>
  Security Assertion Markup Language (SAML) is an open standard for exchanging authentication and authorization data between parties, in particular, between an identity provider and a service provider.
  
## Installation

See PmWiki's [AuthUserSaml cookbook](https://www.pmwiki.org/wiki/Cookbook/AuthUserSaml) page.
  
## Docker examples
 The docker-examples/ directory contains 3 examples of an identity provider and a service provider setup using SimpleSAMLphp. Each example is run with docker compose to start both the identity provider and a service provider at once. One example demonstrates SAML with a simple php script, and the other two examples with pmwiki as service provider; once for a basic config, and the other with a more advanced config. Take a look at these examples to better understand how it all works. 
 
Detailed instructions are at [docker-examples/README.md](https://github.com/harcokuppens/PmWiki-Cookbook-AuthUserSaml/docker-examples/README.md).