# OER Evidence Hub site.


* Demo:  http://sites.hawksey.info/oerhub/
* Martin Hawksey's plugin: https://github.com/mhawksey/wp-evidence-hub


## Install

Please refer to [installing WordPress][wp-install], and [@mhawksey's Readme][plugin-readme].

1. We're using [Git submodules][submodules], so please clone using the recursive flag,

    git clone --recursive http://github.com/IET-OU/oer-evidence-hub-org oer_evidence_hub

2. You will then need to set up two symbolic links...

    cd oer_evidence_hub/wordpress/wp-content/plugins
    ln -s  ../../../wp-evidence-hub/ wp-evidence-hub

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
[plugin-readme]: https://github.com/mhawksey/wp-evidence-hub#readme
[submodules]: http://git-scm.com/book/en/Git-Tools-Submodules


[End.]
