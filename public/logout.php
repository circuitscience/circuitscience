<?php
require_once __DIR__ . '/../app/Core/auth.php';

logoutUser();
redirect('/');
