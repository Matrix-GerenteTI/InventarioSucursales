<?php

require_once $_SERVER['DOCUMENT_ROOT']."/Models/inventarios.php";
require_once $_SERVER['DOCUMENT_ROOT']."/intranet/controladores/Reportes/prepareExcel.php";


class RevisionInventarioPorRolEmpleado extends PrepareExcel
{
    protected $modeloInventario;
    
    public function __construct()
    {
        parent::__construct();
        $this->libro->getProperties()->setTitle('REVISIÓN DE INVENTARIOS POR EMPLEADO'); 
        $this->modeloInventario = new Inventario;
    }
}
