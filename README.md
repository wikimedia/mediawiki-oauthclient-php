[![Latest Stable Version]](https://packagist.org/packages/mediawiki/oauthclient)
[![License]](https://github.com/wikimedia/mediawiki-oauthclient-php/blob/master/COPYING)

mediawiki/oauthclient
=====================

PHP [OAuth][] client for use with [Wikipedia][] and other MediaWiki-based
wikis running the [OAuth extension][].


Installation
------------

    $ composer require mediawiki/oauthclient


Usage
-----

*For working example code, see the [demo](demo/) directory.*

General usage is as follows:

1. Create a new Client with consumer key that you've registered with the wiki. Setting an user agent is highly encouraged.

       $conf = new ClientConfig( 'https://example.org/w/index.php?title=Special:OAuth' );
       $conf->setConsumer( new Consumer(
           'e331e186b64a938591e7614170814a75',
           '9b61abdfa2b88f05670af3919302b12bbc6a6e10'
       ) );
       $conf->setUserAgent( 'MyCoolApp MediaWikiOAuthClient/1.0' );
       $client = new Client( $conf );

2. Retrieve the authentication URL and the Request Token:

       list( $authUrl, $requestToken ) = $client->initiate();

3. Store the Request Token somewhere and send the user to the authentication URL.

4. When the user comes back from the wiki they'll arrive at your callback URL,
   and the query string will contain an `oauth_verifier` key.
   Use this to retrieve an Acccess Token:

       $accessToken = $client->complete( $requestToken,  $_GET['oauth_verifier'] );

5. Once you've got an Access Token you can store it
   and use it to make authenticated requests to the wiki.

   To get the user's identity:

       $ident = $client->identify( $accessToken );

   To make any API call:

       $userInfo = $client->makeOAuthCall(
            $accessToken,
            "https://example.org/w/api.php?action=query&meta=userinfo&uiprop=rights&format=json"
       );


Running tests
-------------

    composer install --prefer-dist
    composer test


History
-------
The code is a refactored version of [Stype/mwoauth-php][], which in turn is
partially based on [Andy Smith's OAuth library][]. Some code is taken from
[wikimedia/slimapp][].

Changelog:

* **1.2**, 2021-10-21 - Allow users to set a User-Agent.
* **1.1**, 2020-01-30 - PHP 7.4 support.
* **1.0**, 2019-01-23 — First stable release.
  - Improved documentation and error handling.
  - Added file-upload functionality.
* **0.1**, 2015-10-23 — First release.

---
[OAuth]: https://en.wikipedia.org/wiki/OAuth
[Wikipedia]: https://www.wikipedia.org
[OAuth extension]: https://www.mediawiki.org/wiki/Extension:OAuth
[Stype/mwoauth-php]: https://github.com/Stype/mwoauth-php
[Andy Smith's OAuth library]: https://code.google.com/p/oauth/
[wikimedia/slimapp]: https://github.com/wikimedia/wikimedia-slimapp
[Latest Stable Version]: https://img.shields.io/packagist/v/mediawiki/oauthclient.svg?style=flat
[License]: https://img.shields.io/packagist/l/mediawiki/oauthclient.svg?style=flat
