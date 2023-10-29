composer self-update
composer install
php bin/console doctrine:migrations:migrate -n
php bin/console cache:clear
yarn install
yarn encore production
