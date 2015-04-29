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
        # Other preparation ...
    ```

2. We're using [Composer][] and [WPackagist][],

    ```sh
        curl -sS https://getcomposer.org/installer | php
        git clone https://github.com/IET-OU/oer-evidence-hub-org.git
        cd oer-evidence-hub-org
        make install-lace
    ```

3. Edit the Wordpress configuration script,

    ```sh
        vi wordpress/wp-config.php
    ```

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

Tiny Forge, version ? - a snapshot is included in this Git repo.

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

---
© 2015 The Open University. ([Institute of Educational Technology][])


[wp-install]: http://codex.wordpress.org/Installing_WordPress
[wp-secrets]: https://api.wordpress.org/secret-key/1.1/salt/
[plugin-readme-jx]: https://github.com/mhawksey/wp-juxtalearn-hub#readme
[plugin-readme]: https://github.com/mhawksey/wp-evidence-hub#readme
[blog-build-plugin]: http://mashe.hawksey.info/2013/10/building-an-evidence-hub-plugin-for-wordpress
[submodules]: http://git-scm.com/book/en/Git-Tools-Submodules
[submodules-cheat]: http://blog.jacius.info/git-submodule-cheat-sheet/

[@IET-OU/wp-evidence-hub]:   https://github.com/mhawksey/wp-evidence-hub
[@IET-OU/wp-juxtalearn-hub]: https://github.com/IET-OU/wp-juxtalearn-hub
[@IET-OU/oer-ev ../wp-juxtalearn-quiz]:
    https://github.com/IET-OU/oer-evidence-hub-org/tree/juxtalearn/wp-juxtalearn-quiz
[@IET-OU/oer-ev ../juxtalearn-clipit-client]: https://github.com/IET-OU/oer-evidence-hub-org/tree/juxtalearn/juxtalearn-clipit-client

[@IET-OU/oer-ev ../simple_embed.php]: https://github.com/IET-OU/oer-evidence-hub-org/blob/juxtalearn/custom-functions/simple_embed.php
[IET-OU/oer-ev ../ou-attribution]: https://github.com/IET-OU/oer-evidence-hub-org/blob/juxtalearn/ou-attribution/
[@juxtalearn/juxtalearn-cookie-authentication]: https://github.com/juxtalearn/juxtalearn-cookie-authentication
[@nfreear/wp-accessify]:  https://github.com/nfreear/wp-accessify
[@jewlofthelotus/SlickQuiz-WordPress]: https://github.com/jewlofthelotus/SlickQuiz-WordPress
[Martin Hawksey]: https://mashe.hawksey.info/
[OER Research Hub]: http://oerresearchhub.org/
[blog-oer-map]: http://oerresearchhub.org/2014/05/14/visit-oer-impact-map-for-evidence-of-oer-impact/
[JuxtaLearn]: http://juxtalearn.eu/
[Institute of Educational Technology]: http://iet.open.ac.uk/


[Composer]: https://getcomposer.org/doc/00-intro.md#system-requirements "Dependency Manager for PHP - getting started"
[WPackagist]: http://wpackagist.org/ "This site mirrors the WordPress plugin and theme directories as a Composer repository."

[End]: http://example
