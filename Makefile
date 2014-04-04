# GNU Make file for OER Evidence Hub/ Juxtalearn/ ..

# Environment
XGETTEXT=/usr/local/bin/xgettext
WORDPRESS=--language=PHP --keyword=__:1
META=--copyright-holder="Copyright 2014 The Open University." \
 --msgid-bugs-address=iet-webmaster@open.ac.uk --package-name


help:
	@echo OER Evidence Hub/ Juxtalearn installer.
	@echo
	@echo "	Commands:"
	@echo "		make install-oer"
	@echo "		make install-juxta"
	@echo "		make jl-quiz-pot"
	@echo

sym-links:
	#cd oer_evidence_hub/
	ln -sf ../../../wordpress-importer/trunk wordpress/wp-content/plugins/wordpress-importer
	ln -sf  ../../../wpmail-smtp  wordpress/wp-content/plugins/wpmail-smtp
	#cd ../themes
	ln -sf  ../../../tiny-forge/1.5.4.2  wordpress/wp-content/themes/tiny-forge

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
	git clone https://github.com/wp-plugins/slickquiz.git slickquiz
	cp -r slickquiz  wordpress/wp-content/plugins/slickquiz
	ln -sf  ../../../custom-functions   wordpress/wp-content/plugins/custom-functions
	ln -sf  ../../../wp-juxtalearn-hub  wordpress/wp-content/plugins/wp-juxtalearn-hub
	ln -sf  ../../../wp-juxtalearn-quiz wordpress/wp-content/plugins/wp-juxtalearn-quiz

	# git submodule update --init
	# git checkout quiz/CR1/scaffold
	# git push origin quiz/CR1/scaffold:quiz/CR1/scaffold

jl-quiz-pot:
	# Extract text for translation (i18n) to GetText POT templates.
	find "wp-juxtalearn-quiz" -type f -name "*.php" \
	| $(XGETTEXT) $(WORDPRESS) $(META)=JuxtaLearn-Quiz -f - \
	--from-code=utf-8 --add-comments=/ -o juxtalearn-quiz.pot
	more juxtalearn-quiz.pot

jl-hub-pot:
	find "wp-juxtalearn-hub" -type f -name "*.php" -and -not -path "*/lib/*" \
	| $(XGETTEXT) $(WORDPRESS) $(META)=JuxtaLearn-Hub -f - \
	--from-code=utf-8 --add-comments=/ -o juxtalearn-hub.pot
	more juxtalearn-hub.pot


test:
	grep -v -q apache /etc/passwd && chown -R apache:apache  wordpress/wp-content/files/
	#grep -v -q apache /etc/passwd && echo Hi
	#grep -v -q apache /etc/passwd || echo Hi 2
	@echo "Test ends."


.PHONY: test jl-quiz-pot jl-hub-pot install-juxta install-oer install-cmn sym-links

#End.
