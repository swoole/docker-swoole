Under this folder are _.conf_ configuration files included by the _Supervisor_ configuration file "/etc/supervisor/supervisord.conf".

Besides the _.conf_ files under this folder, _.conf_ files from folder _../service.d_ or _../task.d_ will be copied to
here automatically when starting a Docker container.

If there are two _.conf_ files of the same name there both under this folder and under folder _../service.d_ or _../task.d_,
 the latter will be used. You may check file _/usr/local/boot/supervisor.sh_ in the image and see how that happens.
