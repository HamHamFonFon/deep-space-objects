# Deep Space Objects
Atlas for deep space objects (Symfony 4 / Elastic Search / Vue.js)

Installation
==
### Clone project
`git clone git@github.com:HamHamFonFon/deep-space-objects.git` 
 
### Generate SSL certificate for HTTPS
```
openssl req -new -newkey rsa:2048 -nodes -out deepskyobjects_local.csr -keyout deepskyobjects_local.key -subj "/C=FR/ST=/L=Montpellier/O=/OU=Montpellier/CN=deepskyobjects.local"
``` 
Copy path of deepskyobjects_local.csr and deepskyobjects_local.key in .env file.

### Init .env files
```
 cp .env.dist .env
``` 
 
### Launch docker stack
 ```
 docker-compose build
 ```

### Add hosts into hosts file
 `sudo echo $(docker network inspect bridge | grep Gateway | grep -o -E '[0-9\.]+') "deepskyobjects.local" >> /etc/hosts`


### Start Docker stack on :

```
docker-compose up -d
docker exec -ti dso_php bash
```

### Install dependencies

```
cd deep-space-objects
composer install
yarn install
``` 

Symfony app :
 - http://symfony.local

### Instal dependencies - PROD
```
cd /path/to/project
php72 composer.phar install --no-dev --optimize-autoloader
yarn install
yarn encore prod
```

Elastic Search
==

Because of [removal of mappings type in ES 6.X](https://www.elastic.co/guide/en/elasticsearch/reference/6.5/removal-of-types.html), we create two indexes, each got his own mapping. 

### Create index with mappings and import data
```
curl -X DELETE "elasticsearch:9200/deepspaceobjects"
curl -X DELETE "elasticsearch:9200/constellations"
curl -X DELETE "elasticsearch:9200/observations"

curl -X PUT elasticsearch:9200/deepspaceobjects?pretty=true -H 'Content-Type: application/json' -d @config/elasticsearch/mappings/deepspaceobjects.mapping.json
curl -X PUT elasticsearch:9200/constellations?pretty=true -H 'Content-Type: application/json' -d @config/elasticsearch/mappings/constellations.mapping.json
curl -X PUT elasticsearch:9200/observations?pretty=true -H 'Content-Type: application/json' -d @config/elasticsearch/mappings/observations.mapping.json
```
NB : not delete indexes if deployed in prod yet

### Create bulk from source
```
php bin/console dso:convert-bulk <type>
```
List of values for <type> : dso20, constellations

### Bulk import Data
```
curl -X POST elasticsearch:9200/_bulk?pretty=true -H 'Content-Type: application/json' --data-binary @config/elasticsearch/bulk/<type>.bulk.json
```

### Status data
| Catalog | Abbreviation | Count data | Total data |
| ------- | ---------- | ---------- | ---------- |
| Messier | messier | 110 | 110 |
| New General Catalog | ngc | 7252 | 7840 |
| Sharpless | sh |127 | 313 |
| Index catalog | ic | 208 | 5386 |
| RCW | rcw |38 | 182 |
| Collinder | cr | 90 | 471 |
| Caldwell | cld | 109 | 109 |
| Abell galaxies | agc | 26 | 4073 |
| Abell planetary nebula | abl |86 | 86 |
| UGC | ugc | 276 | 12921 |
| PGC | pgc |43 | 73197 |
| LDN | ldn | 18 | ? |
| LBN | lbn | 52 | ? |
| Dolidze-Dzimselejvili | dodz | 11 | 11 |
| David Dunlap | ddo | 4 | 343 |
| vdB | vdb | 40 | 158 |
| Sto (Stock Open Cluster) | sto | 23 | 24 |
| Lynga | lyn | 13 | 15 |
| Pismis | pis | 24 | 27 |
| Minkowski | mkw | 163 | ? |
| Menzel | mzl | 3 | 3 |
| Biurakan | biu | 9 | 13 |
| Bochum | boc | 13 | 15 |
| Melotte | mel | 16 | 243 |


 http://www.dreistein.nl/dso.aspx?m=2&ca_71=on&qh=sh&o=-3&p=1
(no Stock22)
(no Lynga10 lynga15)
http://www.dreistein.nl/dso.aspx?m=2&ca_53=on&qh=Minkowski&o=3&p=2
http://www.dreistein.nl/dso.aspx?m=2&ca_56=on&qh=melotte

Sources :
- Wikipedia
- http://www.dreistein.nl
- Simbad
- https://telescopius.com/

### Add new DSO Data

```
    {"create": {"_index": "deepspaceobjects", "_type": "_doc", "_id": "%randId%"}},
    {
      "id": "",
      "catalog": [""],
      "order": null,
      "data": {
        "desigs": [""],
        "alt": {
          "alt": "",
          "alt_fr": "",
          "alt_es": "",
          "alt_de": "",
          "alt_pt": "",
          "alt_it": ""
        },
        "type": "",
        "mag": 999,
        "dim": "",
        "const_id": "",
        "cl": "",
        "dist_al": null,
        "discover": "",
        "discover_year": 0,
        "ra": "",
        "dec": "",
        "astrobin_id": null
      },
      "geometry": {
        "type": "Point",
        "coordinates": [
          0,
          0
        ]
      }
    }
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
