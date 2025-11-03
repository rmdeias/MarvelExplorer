# MarvelExplorer

## Docker
### install docker
docker compose build php --no-cache

### run composer
docker compose up -d

docker exec -it marvel_php composer install

### start docker and server
docker compose up -d

docker exec -it marvel_php symfony local:server:start 

### Go to php container
docker exec -it marvel_php bash 

## Add database
symfony console make:migration

symfony console doctrine:migrations:migrate

## Marvel api data to db

symfony console doctrine:fixtures:load --append --group=characters

symfony console doctrine:fixtures:load --append --group=comics

symfony console doctrine:fixtures:load --append --group=series

symfony console doctrine:fixtures:load --append --group=creators

symfony console doctrine:fixtures:load --append --group=relations

## Create indexation for ElasticSearch

php -d memory_limit=512M bin/console app:index-comics

php -d memory_limit=512M bin/console app:index-series

php -d memory_limit=512M bin/console app:index-characters




