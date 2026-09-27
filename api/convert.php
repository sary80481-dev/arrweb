<?php
// api/convert.php — endpoint HTML → node tree (logic: ConvertApiController)

require_once __DIR__ . '/../app/bootstrap.php';

(new ConvertApiController())->handle();
