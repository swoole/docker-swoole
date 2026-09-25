FROM phpswoole/swoole

COPY ./rootfilesystem/ /

# In nightly images and in 6.3.0-rc1+ images, the default entrypoint runs under tini (as PID 1), which forwards signals
# and reaps zombie processes. A customized entrypoint replaces all that; to keep tini in such images, use this instead:
#     ENTRYPOINT ["/usr/bin/tini", "-s", "--", "/customized-entrypoint.sh"]
ENTRYPOINT ["/customized-entrypoint.sh"]
