This example shows how to use [Blackfire](https://blackfire.io) and Composer package
[upscale/swoole-blackfire](https://github.com/upscalesoftware/swoole-blackfire) to debug your Swoole web server.
_Blackfire_ is not included in the images by default, but you can easily get it installed using the built-in script
_install-blackfire.sh_.

This feature is supported for 4.4.14+ only.

How to run this example?

# 1. Start a Docker Container

Replace `Your_Blackfire_Server_ID` and `Your_Blackfire_Server_Token` with your Blackfire server ID and server token in
the following command, then execute it to start a container:

```bash
docker run --rm --name app \
    -p 80:9501 \
    -e BLACKFIRE_SERVER_ID=Your_Blackfire_Server_ID \
    -e BLACKFIRE_SERVER_TOKEN=Your_Blackfire_Server_Token \
    -t $(docker build -q .)
```

# 2. Profile a URL via curl

Execute following command from the Docker host to profile a web URL:

```bash
blackfire curl http://127.0.0.1
```

Before running the command, you need to have Blackfire installed on the Docker host, and have Blackfire client ID and
client token configured properly with command `blackfire config` first.

# 3. Stop the Container

Once done, you can run following command to stop the container:

```bash
docker stop app
```
