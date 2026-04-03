<?php
declare( strict_types = 1 );

session_start();
session_destroy();

echo "You are now logged out. <a href='index.php'>Log in.</a>";
