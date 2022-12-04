This example shows how to use Blackfire and Composer package
[upscale/swoole-blackfire](https://github.com/upscalesoftware/swoole-blackfire) to debug your Swoole web server.

Thanks to [Blackfire](https://blackfire.io) for providing free open-source subscription for their awesome profiling tool.

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

Switch over to the same directory of this file and start the Docker containers with command `docker-compose up --build -d`.

# 3. Profile URLs

Execute following command to profile a web URL:

```bash
docker compose exec -ti blackfire blackfire curl http://app:9501
```

# 4. Stop the Docker Containers

Once done, you can run command `docker-compose stop` to stop the containers.
