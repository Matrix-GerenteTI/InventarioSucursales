<?php
session_start();
date_default_timezone_set("America/Mexico_City");
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";

class SessionHandlerx 
{
    protected $inventarios;

    public function __construct()
    {
        $this->inventarios = new Inventario;
    }
    
    public function validate()
    {
        $valida = $this->inventarios->checkUser($_POST['user'], $_POST['password'] );

        var_dump( $valida );
        if ( $valida ) {
            $_SESSION['usuario'] = $valida[0]['username'];
            $_SESSION['idempleado'] = $valida[0]['idempleado'];
            header('Location: http://servermatrixxxb.ddns.net:8181/inventarioSucursales/');
        }else{

        }
    }
}
