<?php

for ($i = 1; $i <= 2000; $i++) {
    go(function () {
        co::sleep(1);
    });
}
