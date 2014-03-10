# OER Impact Map/ Juxtalearn sites


* Demo:  http://sites.hawksey.info/oerhub
* Martin Hawksey's plugin: https://github.com/mhawksey/wp-evidence-hub
* JuxtaLearn trick topic tool: <http://juxtalearn.net>
* Martin's Juxtalearn plugin: <https://github.com/mhawksey/wp-juxtalearn-hub>
* Martin Hawksey's blog: [mashe.hawksey.info/2013/10/building-an-evidence-..][blog-build-plugin]

(Note, the OER Impact Map site was called the "OER Evidence Hub".)


## Install

Please refer to [installing WordPress][wp-install], and [@mhawksey's Readme][plugin-readme-jx]:

1. Preparation (Redhat or CentOS Linux),

        yum -y install  git
        yum -y install  php-mysql

2. We're using [Git submodules][submodules], so please clone using the recursive flag,

        git clone --recursive https://github.com/IET-OU/oer-evidence-hub-org.git oer_evidence_hub

3. You may need to switch branches,

        git checkout origin juxtalearn:juxtalearn

4. You will then need to set up symbolic links, upload directories and so on...

        make install-oer
   Or

        make install-juxta

5. Edit the Wordpress configuration script,

        vi wordpress/wp-config.php

6. Edit Apache configuration,

        vi /etc/httpd/conf.d/oerevidencehub-org.conf

...


## Upgrade

1. Pull latest modifications from Github,

        git pull origin juxtalearn:juxtalearn

2. Ensure that submodules are correctly updated,

        git submodule update --init

3. Set up additional symbolic links - probably manually,

        more Makefile
        ln -sf  ../../../{NAME}  wordpress/wp-content/plugins/{NAME}


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
