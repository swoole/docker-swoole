# Manage Available Programs in Supervisord

In this example, we launch a second HTTP server at port 81 using following command while booting Docker containers:

```bash
enable-supervisord-program.sh swoole3
```

Parameter _http81_ references to a _Supervisord_ configuration file _http81.conf_ under folder _/etc/supervisor/available.d_.

You may use following commands to check if both web server work as should:

```bash
docker exec -t $(docker ps -qf "name=app") bash -c "curl -i http://127.0.0.1"
docker exec -t $(docker ps -qf "name=app") bash -c "curl -i http://127.0.0.1:09502"
docker exec -t $(docker ps -qf "name=app") bash -c "curl -i http://127.0.0.1:09503"
```
