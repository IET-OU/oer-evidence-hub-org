# OER Evidence Hub/ Juxtalearn sites


* Demo:  http://sites.hawksey.info/oerhub
* Martin Hawksey's plugin: https://github.com/mhawksey/wp-evidence-hub
* Martin's Juxtalearn plugin: <https://github.com/mhawksey/wp-juxtalearn-hub>
* Martin Hawksey's blog: [mashe.hawksey.info/2013/10/building-an-evidence-..][blog-build-plugin]


## Install

Please refer to [installing WordPress][wp-install], and [@mhawksey's Readme][plugin-readme-jx].

1. We're using [Git submodules][submodules], so please clone using the recursive flag,

        git clone --recursive https://github.com/IET-OU/oer-evidence-hub-org.git oer_evidence_hub

2. You will then need to set up three (3) symbolic links...

        cd oer_evidence_hub/wordpress/wp-content/plugins
        #ln -s  ../../../wp-evidence-hub/  wp-evidence-hub
        ln -s  ../../../wp-juxtalearn-hub/  wp-juxtalearn-hub
        ln -s  ../../../wordpress-importer/trunk/  wordpress-importer
        ln -s  ../../../wpmail-smtp/ wpmail-smtp

        cd ../themes
        ln -s ../../../tiny-forge tiny-forge

3. Copy and edit the configuration template,

        cd ../../../
        cp wp-config-OER-TEMPLATE.php wordpress/wp-config.php
        vi wordpress/wp-config.php

...


## Theme

Tiny Forge, version 1.4.1 - a snapshot is included in this Git repo.

* http://wordpress.org/themes/tiny-forge



[wp-install]: http://codex.wordpress.org/Installing_WordPress
[plugin-readme-jx]: https://github.com/mhawksey/wp-juxtalearn-hub#readme
[plugin-readme]: https://github.com/mhawksey/wp-evidence-hub#readme
[blog-build-plugin]: http://mashe.hawksey.info/2013/10/building-an-evidence-hub-plugin-for-wordpress
[submodules]: http://git-scm.com/book/en/Git-Tools-Submodules
[submodules-cheat]: http://blog.jacius.info/git-submodule-cheat-sheet/


[End]: http://example

