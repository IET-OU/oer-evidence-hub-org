# OER Evidence Hub/ Juxtalearn sites


* Demo:  http://sites.hawksey.info/oerhub
* Martin Hawksey's plugin: https://github.com/mhawksey/wp-evidence-hub
* Martin's Juxtalearn plugin: <https://github.com/mhawksey/wp-juxtalearn-hub>
* Martin Hawksey's blog: [mashe.hawksey.info/2013/10/building-an-evidence-..][blog-build-plugin]


## Install

Please refer to [installing WordPress][wp-install], and [@mhawksey's Readme][plugin-readme-jx]:

1. Preparation (Redhat or CentOS Linux),

        yum -y install  git
        yum -y install  php-mysql

2. We're using [Git submodules][submodules], so please clone using the recursive flag,

        git clone --recursive https://github.com/IET-OU/oer-evidence-hub-org.git oer_evidence_hub

3. You will then need to set up symbolic links...

        make install-oer
   Or

        make install-juxta

4. Edit the Wordpress configuration script,

        vi wordpress/wp-config.php

5. Edit Apache configuration,

        vi /etc/httpd/conf.d/oerevidencehub-org.conf

...


## Theme

Tiny Forge, version 1.4.1 - a snapshot is included in this Git repo.

* http://wordpress.org/themes/tiny-forge



[wp-install]: http://codex.wordpress.org/Installing_WordPress
[wp-secrets]: https://api.wordpress.org/secret-key/1.1/salt/
[plugin-readme-jx]: https://github.com/mhawksey/wp-juxtalearn-hub#readme
[plugin-readme]: https://github.com/mhawksey/wp-evidence-hub#readme
[blog-build-plugin]: http://mashe.hawksey.info/2013/10/building-an-evidence-hub-plugin-for-wordpress
[submodules]: http://git-scm.com/book/en/Git-Tools-Submodules
[submodules-cheat]: http://blog.jacius.info/git-submodule-cheat-sheet/


[End]: http://example
