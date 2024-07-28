Dependencies: docker and docker-compose-plugin
Závislosti: docker a docker-compose-plugin

### Setting up FKSDB container
### Příprava FKSDB kontejneru
---

- Open a terminal in `FKSDB/log/` run `sudo chmod 777 .` to make log folder writable by container
- In `FKSDB/docker/`, run `sudo docker compose build` to create docker image
- Run `sudo docker compose up` or `sudo docker compose up --remove-orphans` to start container 
- Using a browser window, connect to `localhost:8080` to open an interface with database


- Otevřete termínál v `FKSDB/log/` a zadejte `sudo chmod 777 .`
- V `FKSDB/docker/` a spusťte `docker compose build`
- Po dokončení spusťte `sudo docker compose up` nebo `sudo docker compose up --remove-orphans` pro zpuštění kontejneru
- V prohlížeči se připojte na `localhost:8080` pro interakci s databází
