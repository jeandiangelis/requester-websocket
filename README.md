## Requester

### Install `docker`

 * Installation guide [here](https://docs.docker.com/engine/installation/)
 
### Pulling the image
 
 * After installing docker run the following command: `docker pull jeanangelis/requester`

### Clone the project from [github](https://github.com/jeandiangelis/requester-websocket)
 
### Running the container

 * To start the container run the following command: `docker run -d -v path/to/the/cloned/project:/var/www/requester -p 12345:8000 --name requester jeanangelis/requester tail -f /dev/null`
 * Run `docker ps` to check if the container is running
 
### Setting up the servers

 * docker exec -it requester bash
 * Run: mysqld start
 * Run another terminal instance and the command `docker exec -it requester bash` again
 * cd /var/www/requester
 * php composer.phar install
 * php app/console asset:install
 * check docker container ip **DOCKER_IP**: cat /etc/hosts (set to 172.17.0.2 in case it is not)
 * php app/console socketserver:start **DOCKER_IP** 5555
 * Run another terminal instance and the command `docker exec -it requester bash` again
 * cd /var/www/requester
 * php app/console server:run **DOCKER_IP** :8000
 * Go to the web browser and hit localhost:12345
 
That's it!