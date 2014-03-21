# GNU Make file for OER Evidence Hub/ Juxtalearn/ ..

# Environment.
PLUGIN_DIR=wordpress/wp-content/plugins


help:
	@echo OER Evidence Hub/ Juxtalearn installer.
	@echo
	@echo "	Commands:"
	@echo "		make install-oer"
	@echo "		make install-juxta"
	@echo

sym-links:
	#cd oer_evidence_hub/
	ln -sf ../../../wordpress-importer/trunk $(PLUGIN_DIR)/wordpress-importer
	ln -sf  ../../../wpmail-smtp  $(PLUGIN_DIR)/wpmail-smtp
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
	ln -sf  ../../../social-connect  $(PLUGIN_DIR)/social-connect
	#Bug: ln -sf  ../../../jetpack  $(PLUGIN_DIR)/jetpack
	cp -r  jetpack  $(PLUGIN_DIR)/jetpack
	ln -sf ../../../feedwordpress  $(PLUGIN_DIR)/feedwordpress
	ln -sf ../../../google-sitemap-generator $(PLUGIN_DIR)/google-sitemap-generator
	ln -sf  ../../../wp-evidence-hub  $(PLUGIN_DIR)/wp-evidence-hub

install-juxta: install-cmn
	@echo Installing Juxtalearn...
	cp  ./wp-config-JUXTA-TEMPLATE.php  wordpress/wp-config.php
	ln -sf  ../../../custom-functions   $(PLUGIN_DIR)/custom-functions
	ln -sf  ../../../wp-juxtalearn-hub  $(PLUGIN_DIR)/wp-juxtalearn-hub


test-2:
	ln -sf ../../../feedwordpress  $(PLUGIN_DIR)/feedwordpress
	ln -sf ../../../google-sitemap-generator $(PLUGIN_DIR)/google-sitemap-generator


test:
	grep -v -q apache /etc/passwd && chown -R apache:apache  wordpress/wp-content/files/
	#grep -v -q apache /etc/passwd && echo Hi
	#grep -v -q apache /etc/passwd || echo Hi 2
	@echo "Test ends."


.PHONY: test install-juxta install-oer install-cmn sym-links

#End.
