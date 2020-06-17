start:
	php -S localhost:8080 -t public public/index.php

restart:
	sudo service apache2 restart