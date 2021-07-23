This example shows how to use [Blackfire](https://blackfire.io) and Composer package
[upscale/swoole-blackfire](https://github.com/upscalesoftware/swoole-blackfire) to debug your Swoole web server.
_Blackfire_ is not included in the images by default, but you can easily get it installed using the built-in script
_install-blackfire.sh_.

NOTE: When debugging Swoole applications, we recommend to use [yasd](https://github.com/swoole/yasd) instead of _Blackfire_, _sdebug_, or _Xdebug_.

How to run this example?

# 1. Set Blackfire Environment Variables

Get Blackfire credentials from the official website and set these environment variables:

```bash
export BLACKFIRE_SERVER_ID=
export BLACKFIRE_SERVER_TOKEN=
export BLACKFIRE_CLIENT_ID=
export BLACKFIRE_CLIENT_TOKEN=
```

From now on, we assume that these environment variables are set up properly.

# 2. Start the Docker Containers

Start the Docker containers with one of following two commands:

* `./bin/example.sh start 33` if under root directory of the repository.
* `docker-compose up --build -d` if under same directory of this file.

# 3. Profile URLs

Execute following command to profile a web URL:

```bash
docker exec -t $(docker ps -qf "label=name=blackfire") blackfire curl http://app:9501
```

# 4. Stop the Docker Containers

Once done, you can run one of following two commands to stop the containers:

* `./bin/example.sh stop 33` if under root directory of the repository.
* `docker-compose stop` if under same directory of this file.
