<?php
session_start();
date_default_timezone_set("America/Mexico_City");
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";

class Session 
{
	public static function checkSession(){
        $inventarios = new Inventario;
        if(isset($_POST['usuario']) && isset($_POST['password'])){
            $usuario = $inventarios->checkUser($_POST['usuario'],$_POST['password']);
            //var_dump($usuario);
            if(sizeof($usuario)>0){
                $_SESSION['usuario'] = $usuario[0]['username'];
                $_SESSION['idempleado'] = $usuario[0]['idempleado'];
                header('Location: http://servermatrixxxb.ddns.net:8181/inventarioSucursales/views/inventario.php');
				die();
            }else{
                header('Location: http://servermatrixxxb.ddns.net:8181/inventarioSucursales/views/login/index.php');
				die();
            }
        }else{
            if(isset($_SESSION['usuario'])){
                header('Location: http://servermatrixxxb.ddns.net:8181/inventarioSucursales/views/inventario.php');
				die();
            }else{
                header('Location: http://servermatrixxxb.ddns.net:8181/inventarioSucursales/views/login/index.php');
				die();
			}
        }
        //return $sucursales;
    } 

}