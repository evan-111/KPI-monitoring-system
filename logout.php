<?php
require_once 'includes/auth.php';

logoutSupervisor();
header('Location: login.php');
exit();
