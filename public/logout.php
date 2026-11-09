<?php
require_once __DIR__ . '/../src/config.php';

session_destroy();
redirect('/login.php'); 