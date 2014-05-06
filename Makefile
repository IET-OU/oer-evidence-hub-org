# GNU Make file for OER Evidence Hub/ Juxtalearn/ ..

# Environment
XGETTEXT=/usr/local/bin/xgettext
WORDPRESS=--language=PHP --keyword=__:1 -k_e:1 -k_x:1
PO_DIR=translations/
META=--copyright-holder="Copyright 2014 The Open University." \
 --msgid-bugs-address=iet-webmaster@open.ac.uk --package-name
JSHINT=../node_modules/jshint/bin/jshint


help:
	@echo OER Evidence Hub/ Juxtalearn installer.
	@echo
	@echo "	Commands:"
	@echo "		make install-oer"
	@echo "		make install-juxta"
	@echo "		make jl-quiz-pot"
	@echo "		make jl-quiz-lint"
	@echo


sym-links:
	#cd oer_evidence_hub/
	ln -sf ../../../wordpress-importer/trunk wordpress/wp-content/plugins/wordpress-importer
	ln -sf  ../../../wpmail-smtp  wordpress/wp-content/plugins/wpmail-smtp
	#cd ../themes
	ln -sf  ../../../tiny-forge/1.5.4.2  wordpress/wp-content/themes/tiny-forge
	ln -s ../../translations wordpress/wp-content/translations

install-cmn: sym-links
	mkdir  wordpress/wp-content/files/
	-chown -R apache:apache  wordpress/wp-content/files/
	# "-" cross-OS compatibility - ignore errors.
	mkdir  wordpress/wp-content/uploads/
	-chown -R apache:apache  wordpress/wp-content/uploads/

install-oer: install-cmn
	@echo Installing OER Evidence Hub...
	cp  ./wp-config-OER-TEMPLATE.php  wordpress/wp-config.php
	ln -sf  ../../../social-connect  wordpress/wp-content/plugins/social-connect
	ln -sf  ../../../jetpack  wordpress/wp-content/plugins/jetpack
	ln -sf  ../../../wp-evidence-hub  wordpress/wp-content/plugins/wp-evidence-hub

install-juxta: install-cmn
	@echo Installing Juxtalearn...
	cp  ./wp-config-JUXTA-TEMPLATE.php  wordpress/wp-config.php
	#git clone https://github.com/wp-plugins/slickquiz.git slickquiz
	cp -r SlickQuiz-WordPress  wordpress/wp-content/plugins/slickquiz
	ln -sf  ../../../custom-functions   wordpress/wp-content/plugins/jxl-custom-functions
	ln -sf  ../../../wp-juxtalearn-hub  wordpress/wp-content/plugins/wp-juxtalearn-hub
	ln -sf  ../../../wp-juxtalearn-quiz wordpress/wp-content/plugins/wp-juxtalearn-quiz

	# git submodule update --init
	# git checkout quiz/CR1/scaffold
	# git push origin quiz/CR1/scaffold:quiz/CR1/scaffold

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


test:
	grep -v -q apache /etc/passwd && chown -R apache:apache  wordpress/wp-content/files/
	#grep -v -q apache /etc/passwd && echo Hi
	#grep -v -q apache /etc/passwd || echo Hi 2
	@echo "Test ends."


.PHONY: test jl-quiz-pot jl-hub-pot install-juxta install-oer install-cmn sym-links install-dev jl-quiz-lint

#End.
