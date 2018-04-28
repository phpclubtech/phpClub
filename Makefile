.RECIPEPREFIX +=

test:
  ./vendor/bin/phpunit

server:
  php -S 127.0.0.1:9001 -t public ../dev-server.php
