<?php
// api/generate.php — endpoint node tree → Lua/rbxmx (logic: GenerateApiController)

require_once __DIR__ . '/../app/bootstrap.php';

(new GenerateApiController())->handle();
