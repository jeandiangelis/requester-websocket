## Requester

### Install `docker`

 * Installation guide [here](https://docs.docker.com/engine/installation/)
 
### Pulling the image
 
 * After installing docker run the following command: `docker pull jeanangelis/requester`

### Clone the project from [github](https://github.com/jeandiangelis/requester-websocket)
 
### Running the container

 * To start the container run the following command: `docker run -d -v path/to/the/cloned/project:/var/www/requester -p 12345:8000 --name requester jeanangelis/requester`
 
### Setting up the servers

 * docker exec -it request bash
 * mysqld start
 * cd /var/www/requester
 * php composer.phar install
 * php app/console asset:install
 * php app/console socketserver:start
 * check docker container ip **DOCKER_IP**: cat /etc/hosts
 * php app/console server:run **DOCKER_IP** :8000
 * Go to the web browser and hit localhost:12345
 
That's it!