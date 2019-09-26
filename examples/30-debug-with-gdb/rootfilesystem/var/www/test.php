<?php

for ($i = 1; $i <= 3; $i++) {
    go(function () {
        co::sleep(0.01);
    });
}
