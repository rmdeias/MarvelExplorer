# 🦸‍♂️ Marvel Explorer

This project aims to create a complete application around the Marvel universe, combining external data retrieval, local storage, secure data exposure through API Platform, an advanced search engine, and performance optimizations.

---

## 🏗️ Technologies Used
- **Marvel API**
- **Docker**
- **Symfony 7.3.3**
- **PHP 8.2+**
- **API Platform v4.2.0**
- **Doctrine ORM (MySQL)**
- **Elasticsearch**
- **Redis**


## 🚀 Overview

The application relies on the official Marvel API to fetch comics, series, characters and creators, process the data, and store it in a local MySQL database.  
To securely expose these data, the application provides a REST API built with **API Platform**, ensuring the database is never directly exposed.

To enhance search capabilities and performance, two additional technologies were integrated:

- **Elasticsearch** for powerful full-text search
- **Redis** for caching and response speed improvement

This project serves as a complete example of modern architecture built with **Symfony 7**.

---

## 🔧 Main Features

### 1. Marvel API Integration Service
- Dedicated service to interact with the Marvel API
- Automatic hash generation (ts + private key + public key)
- Secure, typed HTTP requests using Symfony HttpClient
- Data retrieval (comics, characters, etc.)

### 2. Data Processing & MySQL Storage
- Cleaning and adapting incoming data
- Mapping and converting data to Doctrine entities
- Insertion into MySQL via Doctrine ORM

### 3. Secure REST API with API Platform
- Controlled exposure of data without exposing the database directly
- Resources, filters, pagination
- Custom endpoints using DTOs and DataProviders when needed

### 4. Advanced Search with Elasticsearch
- Indexing Service 
- title and name search
- Fast and relevant results
- Scalable to additional entities

### 5. Caching with Redis
- Application-level caching to reduce calls to the Marvel API
- Caching for specific API Platform queries
- Improved performance and reduced MySQL load


##  💻 Project instalation
### docker
docker compose build php --no-cache

### run composer
docker compose up -d

docker exec -it marvel_php composer install (Normally, the build process should have already done that)

### start docker and server
docker compose up -d

docker exec -it marvel_php symfony local:server:start 

### Go to php container
docker exec -it marvel_php bash 

## 🗄️ Add database
symfony console make:migration
symfony console doctrine:migrations:migrate

## Marvel api data to db (skip that part)
symfony console doctrine:fixtures:load --append --group=characters

symfony console doctrine:fixtures:load --append --group=comics

symfony console doctrine:fixtures:load --append --group=series

symfony console doctrine:fixtures:load --append --group=creators

symfony console doctrine:fixtures:load --append --group=relations

### (since october 2025 the official Marvel API is in error 500 is better to use marvel_dump.sql)

docker exec -i marvel_db mysql -u remi -ppassword  MarvelExplorer < marvel_dump.sql

## Create indexation for ElasticSearch in marvel_php container

php -d memory_limit=512M bin/console app:index-comics

php -d memory_limit=512M bin/console app:index-series

php -d memory_limit=512M bin/console app:index-characters

## ✍️ Author
**Remi Deias**
[GitHub](https://github.com/rmdeias) | [LinkedIn](https://www.linkedin.com/in/r%C3%A9mi-deias-a416071ab/?originalSubdomain=fr)




