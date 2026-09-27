<?php
// api/spoof.php — endpoint Auto Spoof (logic: SpoofApiController)

require_once __DIR__ . '/../app/bootstrap.php';

(new SpoofApiController())->handle();
