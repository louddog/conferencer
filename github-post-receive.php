<?php

if (isset($_REQUEST['payload'])) {
	try {
		shell_exec("git pull");
	} catch(Exception $e) {}
}