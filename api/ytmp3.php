<?php
// api/ytmp3.php — endpoint YT → MP3 (logic: YtMp3ApiController)

require_once __DIR__ . '/../app/bootstrap.php';

(new YtMp3ApiController())->handle();
