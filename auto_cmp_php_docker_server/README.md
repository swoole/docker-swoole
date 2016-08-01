# auto_cmp_php_docker_server
cmp: alpine (edge) + php7 (latest) + php7-opcache + php7-swoole

# Usage

@ref cmptech/cmp_app_server

# Links

* [SWOOLE]
```
http://swoole.com/
http://github.com/swoole/swoole-src/
```

* [LINUX] install php7 w+ swoole in user space:
```
https://github.com/wanjochan/misctools/raw/master/php-fpm-swoole-one-click.sh
```

* [MAC] install php7 with brew
```bash
# install brew
# http://brew.sh/
/usr/bin/ruby -e "$(curl -fsSL https://github.com/Homebrew/install/raw/master/install)"

# install php70 +fpm +opcache +swoole
brew install php70 --with-fpm
brew install php70-opcache
brew install php70-swoole
brew unlink php70 && brew link php70

sudo brew unlink php70
sudo brew remove php70*
```

