#!/bin/sh
#ref http://jimhoskins.com/2013/07/27/remove-untagged-docker-images.html

#Remove all stopped containers.
docker rm $(docker ps -a -q)

#Remove all untagged images
docker rmi -f $(docker images -a | grep "^<none>" | awk "{print \$3}")
