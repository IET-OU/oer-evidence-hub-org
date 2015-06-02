# GNU Make file for OER Map, JuxtaLearn & LACE evidence hub..

# Environment.
COMPOSER=php ../composer.phar
PLUGIN_DIR=wordpress/wp-content/plugins
FILES_DIR=wordpress/wp-content/uploads
THEME_DIR=wordpress/wp-content/themes/
THEME_SRC=../../../wp-content/themes/
BRANCH=CR40-composer
DIFF=git diff --no-color | cat
GLOG=git log --graph --decorate --pretty=oneline --abbrev-commit -3 | cat
XGETTEXT=/usr/local/bin/xgettext
WORDPRESS=--language=PHP --keyword=__:1 -k_e:1 -k_x:1
PO_DIR=translations/
META=--copyright-holder="Copyright 2014 The Open University." \
 --msgid-bugs-address=iet-webmaster@open.ac.uk --package-name
JSHINT=../node_modules/jshint/bin/jshint


help:
	# OER Map/ JuxtaLearn/ LACE Evidence Hub installer.
	# Available targets:
	@echo "	install-oer install-jl install-lace update jl-quiz-pot jl-quiz-lint ..."
	@echo

composer:
	cp composer-TEMPLATE.json composer.json

install-common: self
	git checkout $(BRANCH)
	$(COMPOSER) require wikimedia/composer-merge-plugin:v1.0.0
	make composer
	# vi composer.json
	$(COMPOSER) update --prefer-source

sym-links:
	[ -d "$(PLUGIN_DIR)-BAK" ] || mv $(PLUGIN_DIR) $(PLUGIN_DIR)-BAK
	[ -L $(PLUGIN_DIR) ] || ln -s ../../wp-content/plugins $(PLUGIN_DIR)
	[ -L $(FILES_DIR) ] || ln -s ../../wp-content/uploads $(FILES_DIR)
	[ -L $(THEME_DIR)tiny-forge ] || ln -s $(THEME_SRC)tiny-forge $(THEME_DIR)tiny-forge
	[ -L wordpress/wp-config.php ] || ln -s ../wp-config.php wordpress/wp-config.php
	[ -L wordpress/.htaccess ] || ln -s ../.htaccess-TEMPLATE wordpress/.htaccess
	#chown -R apache:apache  wp-content/uploads

install-oer: install-common
	@echo Installing OER MAP...
	cp  ./wp-config-OER-TEMPLATE.php  wp-config.php

install-lace: install-common
	@echo Installing LACE Evidence Hub...
	[ -f wp-config.php ] || cp  ./wp-config-LACE-TEMPLATE.php  wp-config.php
	#$(COMPOSER) run-script install-lace
	$(COMPOSER) require-suggest "(LACE|Exp-LA)"
	make sym-links

install-jl: install-common
	@echo Installing JuxtaLearn...
	cp  ./wp-config-JUXTA-TEMPLATE.php  wp-config.php

update:
	git pull origin $(BRANCH):$(BRANCH)
	$(COMPOSER) update --prefer-source

accessify:
	git submodule update --init --recursive  wp-accessify
	# cd wp-accessify; git submodule update --init; cd ..

jl-quiz-pot:
	# Extract text for translation (i18n) to GetText POT templates.
	find "wp-juxtalearn-quiz" -type f -name "*.php" \
	| $(XGETTEXT) $(WORDPRESS) $(META)=JuxtaLearn-Quiz -f - \
	--from-code=utf-8 --add-comments=/ -o $(PO_DIR)juxtalearn-quiz.pot
	more $(PO_DIR)juxtalearn-quiz.pot

jl-hub-pot:
	find "wp-juxtalearn-hub" -type f -name "*.php" -and -not -path "*/lib/*" \
	| $(XGETTEXT) $(WORDPRESS) $(META)=JuxtaLearn-Hub -f - \
	--from-code=utf-8 --add-comments=/ -o $(PO_DIR)juxtalearn-hub.pot
	more $(PO_DIR)juxtalearn-hub.pot

tinyforge-pot:
	find "tiny-forge/1.5.4.2" -type f -name "*.php" \
	| $(XGETTEXT) $(WORDPRESS) $(META)=Tiny-Forge -f - \
	--from-code=utf-8 --add-comments=/ -o $(PO_DIR)tinyforge.pot
	more $(PO_DIR)tinyforge.pot

msgfmt:
	# Compile binary MO file. Not working :(.
	find "translations" -type f -name "*.po" \
	-print -exec bash -c 'msgfmt -o "$0.mo" "$0"' {} \;

msgtest:
	find "translations" -type f -name "*.po" \
	-print -exec sh -c 'echo "[$0] [$1]"' foobar {} \;

install-dev:
	npm install jshint

jl-quiz-lint:
	#$(JSHINT) wp-juxtalearn-quiz/js/juxtalearn-quiz-scaffold.js
	find "wp-juxtalearn-quiz" -type f -name "*.js" -exec $(JSHINT) {} \;	

SUGGEST = "(LACE|Exp-LA|yyyyy)"
test-s:
	#make test-s S="(x|y)"
	@echo env S = $S
test-old:
	grep -v -q apache /etc/passwd && chown -R apache:apache  wordpress/wp-content/files/
	#grep -v -q apache /etc/passwd && echo Hi
	#grep -v -q apache /etc/passwd || echo Hi 2
	@echo "Test ends."

test-2:
	ln -sf ../../../feedwordpress  $(PLUGIN_DIR)/feedwordpress
	ln -sf ../../../google-sitemap-generator $(PLUGIN_DIR)/google-sitemap-generator

find-ln:
	# find -L  ~/workspace/lace-wp-2  -type l
	ls -lR . | grep ^l

test: composer
	$(COMPOSER) validate -vvv
	cd composer-shared-packages; php ../../composer.phar validate -vvv
diag:
	-$(COMPOSER) diagnose -vvv
status:
	git status
	-$(COMPOSER) status -v
self:
	$(COMPOSER) self-update -vvv
clear-cache:
	$(COMPOSER) clear-cache -v

diff:
	$(DIFF)
	@echo ""
	cd wp-content/plugins/wp-evidence-hub;        $(DIFF)
	@echo ""
	cd wp-content/plugins/wp-iet-generic-plugins; $(DIFF)
log:
	@$(GLOG)
	@echo ""
	## cd wp-content/plugins/wp-evidence-hub
	@cd wp-content/plugins/wp-evidence-hub;        $(GLOG)
	@echo ""
	## cd wp-content/plugins/wp-iet-generic-plugins
	@cd wp-content/plugins/wp-iet-generic-plugins; $(GLOG)

scp:
	scp root@iet-lace-approval.open.ac.uk:/var/www/*.diff ~/Dropbox/Public/lace/

clean:
	rm -rf vendor/
	rm -rf wordpress/
	rm -rf wp-content/plugins
	rm -rf wp-content/themes
	rm -f composer.*
	make clear-cache

#.DEFAULT_GOAL: help

.PHONY: help test jl-quiz-pot jl-hub-pot install-jl install-oer install-lace install-common sym-links install-dev jl-quiz-lint find-ln diag status self

#End.
