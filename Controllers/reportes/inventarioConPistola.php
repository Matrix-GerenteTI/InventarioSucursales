<?php

require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";
require_once $_SERVER['DOCUMENT_ROOT']."/intranet/controladores/Reportes/prepareExcel.php";

class InventarioConPistola extends PrepareExcel
{
    protected $modeloInventario;
    
    public function __construct()
    {
        parent::__construct();
        $this->libro->getProperties()->setTitle('INVENTARIOS CON PISTOLA'); 
        $this->modeloInventario = new Inventario;
    }

    public function generarReporte( $mes , $anio)
    {
        $listaTodosInventarios =  $this->modeloInventario->getInventariosConPistolaBarra( $mes , $anio );
        
        //Agrupando los inventarios por Sucursales 
        $inventariosAgrupados = [];
        foreach ($listaTodosInventarios as $inventario) {
            if ( !isset( $inventariosAgrupados[ $inventario['sucursal'] ] ) ) {
                $inventariosAgrupados[ $inventario['sucursal'] ]['inventarios'] = [ $inventario ];
            } else {
                array_push( $inventariosAgrupados[ $inventario['sucursal'] ]['inventarios'] , $inventario );
            }
            
        }

        $hoja = 0;
        foreach ( $inventariosAgrupados as $sucursal => $inventarios) {
            $this->creaEmptySheet( $sucursal , $hoja );


            $i = 9;


            $this->libro->getActiveSheet()->setAutoFilter("A8:E8");
            $this->putLogo("B1", 200,200);
            $this->libro->getActiveSheet()->mergeCells("A4:E4");
            $this->libro->getActiveSheet()->setCellValue("A4","Reporte de Inventarios con Pistola Lectora de Códigos de Barra");
            $this->libro->getActiveSheet()->getStyle("A4")->applyFromArray( $this->labelBold);   
            $this->libro->getActiveSheet()->getStyle("A4")->applyFromArray( $this->centrarTexto );
    
            $this->libro->getActiveSheet()->mergeCells("B5:D5");
            $this->libro->getActiveSheet()->setCellValue("B5", $this->getMesAsString($mes) );
            $this->libro->getActiveSheet()->getStyle("B5")->applyFromArray( $this->labelBold);   
            $this->libro->getActiveSheet()->getStyle("B5")->applyFromArray( $this->centrarTexto );


            $this->libro->getActiveSheet()->setCellValue("A8" , "Fecha de Realización");
            $this->libro->getActiveSheet()->setCellValue("B8", "Familia" );
            $this->libro->getActiveSheet()->setCellValue("C8", "Subfamilia");
            $this->libro->getActiveSheet()->setCellValue("D8", "Cantidad Inventariado" );
            $this->libro->getActiveSheet()->setCellValue("E8", "Responsable");

            $this->libro->getActiveSheet()->getStyle("A8:E8")->applyFromArray( $this->labelBold);
            $this->libro->getActiveSheet()->getStyle("A8:E8")->applyFromArray( $this->centrarTexto );
            $this->libro->getActiveSheet()->getStyle("A8:E8")->getFill()->applyFromArray( $this->setColorFill("DF013A") );
            $this->libro->getActiveSheet()->getStyle("A8:E8")->applyFromArray( $this->setColorText("ffffff",12) );

            foreach ($inventarios['inventarios']  as $inventario) {
                $this->libro->getActiveSheet()->setCellValue("A$i" , str_replace('-','/' ,$inventario['fechaCaptura'] ) );
                $this->libro->getActiveSheet()->setCellValue("B$i", $inventario['familia'] );
                $this->libro->getActiveSheet()->setCellValue("C$i", $inventario['subfamilia'] );
                $this->libro->getActiveSheet()->setCellValue("D$i", $inventario['cantidad'] );
                $this->libro->getActiveSheet()->setCellValue("E$i", $inventario['usuario'] );
                $this->libro->getActiveSheet()->getRowDimension($i)->setRowHeight(25);
                $this->libro->getActiveSheet()->getStyle("A$i")->applyFromArray( $this->labelBold);
                $this->libro->getActiveSheet()->getStyle("A$i:E$i")->applyFromArray( $this->centrarTexto );
                $i++;
            }

            $this->libro->getActiveSheet()->getStyle("A8:E".($i-1) )->applyFromArray( $this->bordes );

            $this->libro->getActiveSheet()->getColumnDimension("A")->setAutoSize(false);
            $this->libro->getActiveSheet()->getColumnDimension("A")->setWidth("30");
            $this->libro->getActiveSheet()->getColumnDimension("B")->setAutoSize(false);
            $this->libro->getActiveSheet()->getColumnDimension("B")->setWidth("20");
            $this->libro->getActiveSheet()->getColumnDimension("C")->setAutoSize(false);
            $this->libro->getActiveSheet()->getColumnDimension("C")->setWidth("20");    
            $this->libro->getActiveSheet()->getColumnDimension("D")->setAutoSize(false);
            $this->libro->getActiveSheet()->getColumnDimension("D")->setWidth("15");       
            $this->libro->getActiveSheet()->getColumnDimension("E")->setAutoSize(false);
            $this->libro->getActiveSheet()->getColumnDimension("E")->setWidth("20");                                                   
            $hoja++;
        }
        

        $reporteTerminado = new \PHPExcel_Writer_Excel2007( $this->libro);
        $reporteTerminado->setPreCalculateFormulas(true);
        $reporteTerminado->setIncludeCharts(TRUE);
         $reporteTerminado->save($_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/controllers/reportes/inventarioConPistola.xlsx");
        $ubicacion = "http://servermatrixxxb.ddns.net:8181/inventarioSucursales/controllers/reportes/inventarioConPistola.xlsx";
        echo "<a href='$ubicacion'>DESCARGA</a>";
    }

}


$reporteInventarios = new InventarioConPistola;
$reporteInventarios->generarReporte( date('m') , date('Y') );