start:
	php -S localhost:8080 -t public public/index.php

practice:
	php -S localhost:8080 -t public public/practice.php

restart:
	sudo service apache2 restart