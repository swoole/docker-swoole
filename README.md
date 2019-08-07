# Feature List

* Built-in commands for managing _Swoole_ extensions, _Supervisord_ programs, etc.
* Easy to manage booting scripts in Docker.
* Allow to run PHP scripts and other commands/scripts directly in different environments (including ECS).
* Use one root filesystem for simplicity (one Docker `COPY` command only in dockerfiles).
* _Composer_ included.

# TODOs

* Add script(s) to manage Swoole extensions.
* Add examples.
* Use environment variables to load Swoole extensions dynamically when booting Docker containers.

# Credits

Current implementation borrows ideas from [Demin](https://deminy.in)'s work at [Glu Mobile](https://glu.com).
