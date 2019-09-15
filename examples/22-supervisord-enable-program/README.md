# Manage Available Programs in Supervisord

In this example, we launch a second HTTP server at port 9502 using following command while booting the Docker container:

```bash
enable-supervisord-program.sh swoole2
```

Parameter _swoole2_ references to a _Supervisord_ configuration file _swoole2.conf_ under folder _/etc/supervisor/available.d_.

You may use following commands to check if both web servers work as should:

```bash
curl -i http://127.0.0.1
curl -i http://127.0.0.1:9502
```

You may check example "20-supervisord-services" to see how we launch a second Supervisord program in a different way.
