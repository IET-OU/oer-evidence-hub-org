# OER Impact Map, Juxtalearn & LACE Evidence Hub sites


The web sites:

* <http://oermap.org>
* <http://trickytopic.juxtalearn.net>
* <http://evidence.laceproject.eu>

Principle WordPress plugins:

* [@IET-OU/wp-evidence-hub][]
* [@IET-OU/wp-juxtalearn-hub][]
* [@IET-OU/oer-ev ../wp-juxtalearn-quiz][]
* [@IET-OU/oer-ev ../juxtalearn-clipit-client][]

Other plugins and libraries:

* [@IET-OU/wp-iet-generic-plugins][]
* [@juxtalearn/juxtalearn-cookie-authentication][]
* [@jewlofthelotus/SlickQuiz-WordPress][]
* [@nfreear/wp-accessify][]


Demos/blog posts:

* Demo:  <http://sites.hawksey.info/oerhub>
* Martin Hawksey's blog: [mashe.hawksey.info/2013/10/building-an-evidence-..][blog-build-plugin]
* [Rob Farrow's blog about OER Map][blog-oer-map]

(Note, the OER Impact Map site was called the "OER Evidence Hub".)


## Install

Please refer to [installing WordPress][wp-install], and [@mhawksey's Readme][plugin-readme-jx]:

1. Preparation (Redhat or CentOS Linux),

    ```sh
        yum -y install  git
        yum -y install  php-mysql
        # Other preparations ...
    ```

2. Install [Composer][] globally:
    ```sh
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
    ```

2. We're using [WPackagist][] and [iet-satis][],

    ```sh
        cd /var/www   # Or, wherever you put web sites.
        git clone https://github.com/IET-OU/oer-evidence-hub-org.git lace-wp
        cd lace-wp
        composer install --no-dev
        vi .env
        composer update --no-dev
    ```

3. Edit the Wordpress configuration script,

        vi  wp-config.php

4. Edit Apache configuration,

        vi /etc/httpd/conf.d/oerevidencehub-org.conf

...


## Upgrade

1. Pull latest modifications from Github,

        make update

3. Maybe, set up additional symbolic links - probably manually,

        more Makefile
        ln -sf  ../../../{NAME}  wordpress/wp-content/plugins/{NAME}


## Theme

Tiny Forge - a snapshot is included via this Git repo.

* <http://wordpress.org/themes/tiny-forge>


## Contributors

* [@IET-OU/wp-evidence-hub][] - project: [OER Research Hub][]:
    * [Martin Hawksey][] (original developer)
    * Rob Farrow (lead researcher)
    * Nick Freear (developer)
* [@IET-OU/wp-juxtalearn-hub][], [@IET-OU/oer-ev ../wp-juxtalearn-quiz], [@IET-OU/oer-ev ../juxtalearn-clipit-client] - project: [JuxtaLearn]:
    * Nick Freear (developer)
    * Gill Clough (lead researcher)
    * Martin Hawksey (developer, wp-juxtalearn-hub)

## GDPR

Details of GDPR / privacy fixes can be found in [Bug #56][].

---
© 2015 The Open University. ([Institute of Educational Technology][])


[wp-install]: https://codex.wordpress.org/Installing_WordPress
[wp-secrets]: https://api.wordpress.org/secret-key/1.1/salt/
[plugin-readme-jx]: https://github.com/mhawksey/wp-juxtalearn-hub#readme
[plugin-readme]: https://github.com/mhawksey/wp-evidence-hub#readme
[blog-build-plugin]: https://mashe.hawksey.info/2013/10/building-an-evidence-hub-plugin-for-wordpress

[@IET-OU/wp-evidence-hub]:   https://github.com/IET-OU/wp-evidence-hub "Fork of: @mhawksey/wp-evidence-hub"
[@IET-OU/wp-juxtalearn-hub]: https://github.com/IET-OU/wp-juxtalearn-hub
[@IET-OU/wp-juxtalearn-quiz]: https://bitbucket.org/nfreear/wp-juxtalearn-quiz
[@IET-OU/wp-juxtalearn-clipit-client]: https://bitbucket.org/nfreear/wp-juxtalearn-clipit-client
[@IET-OU/oer-ev ../wp-juxtalearn-quiz]:
    https://github.com/IET-OU/oer-evidence-hub-org/tree/juxtalearn/wp-juxtalearn-quiz
[@IET-OU/oer-ev ../juxtalearn-clipit-client]: https://github.com/IET-OU/oer-evidence-hub-org/tree/juxtalearn/juxtalearn-clipit-client

[@IET-OU/wp-iet-generic-plugins]: https://github.com/IET-OU/wp-iet-generic-plugins
     "WordPress plugins: Simple Embed, Simple Menu, [tagcloud], [wp_query], IET attribution ..."
[@juxtalearn/juxtalearn-cookie-authentication]: https://github.com/juxtalearn/juxtalearn-cookie-authentication
[@nfreear/wp-accessify]:  https://github.com/nfreear/wp-accessify
[@jewlofthelotus/SlickQuiz-WordPress]: https://github.com/jewlofthelotus/SlickQuiz-WordPress
[Martin Hawksey]: https://mashe.hawksey.info/
[OER Research Hub]: http://oerresearchhub.org/
[blog-oer-map]: http://oerresearchhub.org/2014/05/14/visit-oer-impact-map-for-evidence-of-oer-impact/
[JuxtaLearn]: http://juxtalearn.eu/
[Institute of Educational Technology]: https://iet.open.ac.uk/

[Composer]: https://getcomposer.org/doc/00-intro.md#system-requirements "Dependency Manager for PHP - getting started"
[WPackagist]: https://wpackagist.org/ "This site mirrors the WordPress plugin and theme directories as a Composer repository."
[iet-satis]: https://embed.open.ac.uk/iet-satis/ "IET's test/ private Satis-based Packagist repository"
[bug #56]: https://github.com/IET-OU/oer-evidence-hub-org/issues/56 "GDPR/privacy"

[End]: //
