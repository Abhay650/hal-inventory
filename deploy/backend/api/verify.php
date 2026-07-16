<?php
require __DIR__ . '/../config.php';

$username = require_auth();
json_response(['success' => true, 'username' => $username]);
