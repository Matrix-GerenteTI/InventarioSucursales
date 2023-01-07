<?php

require_once $_SERVER['DOCUMENT_ROOT']."/inventario/Controllers/session/inventario.php";


$session = new \SessionHandlerx();

$session->validate();