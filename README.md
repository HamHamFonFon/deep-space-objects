# Deep Space Objects
Atlas for deep space objects (Symfony 4 / Elastic Search / Vue.js)

Installation
==
### Clone project
`git clone git@github.com:HamHamFonFon/deep-space-objects.git` 
 
### Init .env files
```
 cp .env.local.dist .env.local
 cp .env.dist .env
``` 
 
### Generate SSL certificate for HTTPS
```
openssl req -x509 -out localhost.crt -keyout localhost.key   -newkey rsa:2048 -nodes -sha256   -subj '/CN=localhost' -extensions EXT -config <( \
    printf "[dn]\nCN=localhost\n[req]\ndistinguished_name = dn\n[EXT]\nsubjectAltName=DNS:localhost\nkeyUsage=digitalSignature\nextendedKeyUsage=serverAuth")
``` 
Copy path of localhost.crt and localhost.key in .env file.

 
### Launch docker stack
 ```
 docker-compose build
 ```

### Add hosts into hosts file
 `sudo echo $(docker network inspect bridge | grep Gateway | grep -o -E '[0-9\.]+') "symfony.local" >> /etc/hosts`


### Start Docker stack on :

```
docker-compose up -d
docker exec -ti dso_php bash
```

### Install dependencies

```
cd deep-space-objects
composer install
npm install
``` 

Symfony app :
 - http://symfony.local

Use
==

### Install symfony components
 > composer require <components>

### Use nodeJs and NPM
 > WIP


Elastic Search
==
### Create index with mappings and import data
```
curl -X DELETE "elasticsearch:9200/constellations"
curl -X DELETE "elasticsearch:9200/deepspaceobjects"
curl -X PUT elasticsearch:9200/constellations?pretty=true -H 'Content-Type: application/json' -d @config/elasticsearch/mappings/constellations.mapping.json
curl -X PUT elasticsearch:9200/deepspaceobjects?pretty=true -H 'Content-Type: application/json' -d @config/elasticsearch/mappings/dso.mapping.json
```

### Bulk import Data
```
curl -X POST elasticsearch:9200/_bulk?pretty=true -H 'Content-Type: application/json' --data-binary @config/elasticsearch/data/constellations.bulk.json
```

Authors
==
 Stéphane MEAUDRE <balistik.fonfon@gmail.com>

Sources
=======
Docker stack based on stacks by :
> https://github.com/maxpou/docker-symfony
> https://framagit.org/3rr0r/docker-sf4
> https://github.com/neiluJ/api-vue-boilerplate

Licences
==
