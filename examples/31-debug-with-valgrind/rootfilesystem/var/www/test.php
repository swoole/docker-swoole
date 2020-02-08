<?php

declare(strict_types=1);

for ($i = 1; $i <= 3; $i++) {
    go(function () {
        co::sleep(0.01);
    });
}
