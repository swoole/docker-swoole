<?php

go(function () {
    $a = [];
    for ($i = 1; $i <= 3; $i++) {
        co::sleep(0.01);
        $a[$i] = 2;
    }
});
