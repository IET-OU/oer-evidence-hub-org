# GNU Make file for OER Evidence Hub/ Juxtalearn/ ..

help:
	@echo OER Evidence Hub/ Juxtalearn installer. Commands:
	@echo "		make install-oer"
	@echo "		make install-jx"

sym-links:
	#cd oer_evidence_hub/
	ln -s  ./wordpress-importer/trunk/  wordpress/wp-content/plugins/wordpress-importer
	ln -s  ./wpmail-smtp/ wordpress/wp-content/plugins/wpmail-smtp
	#cd ../themes
	ln -s ./tiny-forge themes/tiny-forge

install-cmn: sym-links
	cp  ./wp-config-OER-TEMPLATE.php  wordpress/wp-config.php
	mkdir  wordpress/wp-content/files/
	chown -R apache:apache  wordpress/wp-content/files/
	mkdir  wordpress/wp-content/uploads/
	chown -R apache:apache  wordpress/wp-content/uploads/

install-oer: install-cmn
	@echo Installing OER Evidence Hub...
	ln -s  ./wp-evidence-hub/  wordpress/wp-content/plugins/wp-evidence-hub

install-jx: install-cmn
	@echo Installing Juxtalearn...
	ln -s  ./wp-juxtalearn-hub/  wordpress/wp-content/plugins/wp-juxtalearn-hub

#End.
