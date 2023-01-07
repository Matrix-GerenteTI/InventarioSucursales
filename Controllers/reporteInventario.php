<?php
date_default_timezone_set("America/Mexico_City");
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Controllers/prepareExcel.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";

class ReporteInventario extends PrepareExcel 
{
    
    public function generaReporte ()
    {
        $inventarios = new Inventario;
        $sucursales = $inventarios->getSucursalesConAlmacen();
        $almacenesHerramientas = $inventarios->getAlmacenesHerramientas();
        $sucursales = array_merge( $sucursales , $almacenesHerramientas );

        $sheet= -1;
        foreach ($sucursales as $index => $sucursal) {
            $inventario = $inventarios->getInventariosSucursal( array('sucursal' => $sucursal->ID, 'fecha' => date('Y-m-d')));        

            
            $i = 9;
            if ( sizeof($inventario)) {
                $sheet++;
                $this->creaEmptySheet(str_replace(' ','',$sucursal->DESCRIPCION) ,$sheet);

                $tipoInventario = "ALEATORIO";
                if ($inventario[0]['tipo'] == 1) {
                    $tipoInventario = "GENERAL";
                }else if( $inventario[0]['tipo'] == 4){
                    $tipoInventario = "GENERAL CON CODIGOS DE BARRA";
                }else if( $inventario[0]['tipo'] == 5){
                    $tipoInventario = "HERRAMIENTA";
                }


                $this->libro->getActiveSheet()->mergeCells("A5:D5");
                $this->libro->getActiveSheet()->setCellValue("A5", "INVENTARIO $tipoInventario REALIZADO EL ".date('Y-m-d'));
                $this->libro->getActiveSheet()->getStyle("A5")->applyFromArray($this->labelBold);
                $this->libro->getActiveSheet()->getStyle("A5")->applyFromArray($this->centrarTexto);

                $this->libro->getActiveSheet()->setCellValue("A8", "CODIGO");
                $this->libro->getActiveSheet()->setCellValue("B8", "DESCRIPCION");
                $this->libro->getActiveSheet()->setCellValue("C8", "COSTO");
                $this->libro->getActiveSheet()->setCellValue("D8", "FAMILIA");
                $this->libro->getActiveSheet()->setCellValue("E8", "SUBFAMILIA");
                $this->libro->getActiveSheet()->setCellValue("F8", "REVISION 1");
                $this->libro->getActiveSheet()->setCellValue("G8", "REVISION 2");
                $this->libro->getActiveSheet()->setCellValue("H8", "REVISION 3");
                $this->libro->getActiveSheet()->setCellValue("I8", "SISTEMA");
                $this->libro->getActiveSheet()->setCellValue("J8", "DIFERENCIA");
                $this->libro->getActiveSheet()->setCellValue("K8", "COSTO x DIFERENCIA");
                $this->libro->getActiveSheet()->setCellValue("L8", "HORA REALIZACION");
                $this->libro->getActiveSheet()->mergeCells("L8:M8");
                $this->libro->getActiveSheet()->getStyle("A8:L8")->applyFromArray($this->labelBold);

                $herramientas = [];
                $inventarioNormal = false ;
                foreach ($inventario as $idx => $producto) {

                    if ( $producto['tipo'] == 5) { // es un inventario de herramientas?
                        array_push( $herramientas ,$producto );
                        
                        continue;
                    }else{
                        $inventarioNormal = true;
                    }
                    $dateTime = explode(' ',$producto['fechaCaptura']);
                    $this->libro->getActiveSheet()->setCellValue("A$i",  $producto['codigo']);
                    $this->libro->getActiveSheet()->setCellValue("B$i", $producto['descripcion']);
                    $this->libro->getActiveSheet()->setCellValue("C$i", $inventarios->getCostoArticulo($producto['codigo']) );
                    $this->libro->getActiveSheet()->getStyle("C$i")->getNumberFormat()->setFormatCode("$#,##0.00;-$#,##0.00");
                    $this->libro->getActiveSheet()->setCellValue("D$i", $producto['familia']);
                    $this->libro->getActiveSheet()->setCellValue("E$i", $producto['subfamilia']);
                    $this->libro->getActiveSheet()->setCellValue("F$i", $producto['fisico']);
                    $this->libro->getActiveSheet()->setCellValue("G$i", $producto['fisico2'] == '' ? $producto['fisico'] : $producto['fisico2']);
                    $this->libro->getActiveSheet()->setCellValue("H$i", ( $producto['fisico3'] == '' ) ? "=G$i" : $producto['fisico3']);
                    $this->libro->getActiveSheet()->setCellValue("I$i", $producto['stock']);
                    $this->libro->getActiveSheet()->setCellValue("L$i", $dateTime[1]);
                    $this->libro->getActiveSheet()->getStyle("L$i")->applyFromArray($this->centrarTexto);
                    $this->libro->getActiveSheet()->mergeCells("L$i:M$i");

                    //Esta sección es modificada en el caso de que en la revision 3 sea  cero o null
                    if ($producto['fisico2']  == 0  ) {
                        if ( $producto['fisico3'] == 0) {
                            $this->libro->getActiveSheet()->setCellValue("J$i","=F$i-I$i");
                        }
                    }elseif( $producto['fisico3'] == 0){
                        $this->libro->getActiveSheet()->setCellValue("J$i","=G$i-I$i");
                    }else{
                        $this->libro->getActiveSheet()->setCellValue("J$i","=H$i-I$i");
                    }
                    $this->libro->getActiveSheet()->setCellValue("K$i","=C$i*J$i");      
                    $this->libro->getActiveSheet()->getStyle("K$i")->getNumberFormat()->setFormatCode("$#,##0.00;-$#,##0.00");                       
                    $i++;           
                }

                $this->putLogo("B1",300,150);
                $this->libro->getActiveSheet()->getStyle("A8:M".($i-1))->applyFromArray($this->bordes);
                $this->libro->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);

                // haciendo una nueva hoja para mostrar los inventarios de herramientas
                if ( sizeof( $herramientas) ) {
                    
                    if ( $inventarioNormal == true) {
                        $sheet++;
                        $this->creaEmptySheet( "HERR_".str_replace(' ','',$sucursal->DESCRIPCION) ,$sheet);
                            $tipoInventario = "HERRAMIENTA";                    
                    }

                    $this->libro->getActiveSheet()->mergeCells("A5:D5");
                    $this->libro->getActiveSheet()->setCellValue("A5", "INVENTARIO $tipoInventario REALIZADO EL ".date('Y-m-d'));
                    $this->libro->getActiveSheet()->getStyle("A5")->applyFromArray($this->labelBold);
                    $this->libro->getActiveSheet()->getStyle("A5")->applyFromArray($this->centrarTexto);
    
                    $this->libro->getActiveSheet()->setCellValue("A8", "CODIGO");
                    $this->libro->getActiveSheet()->setCellValue("B8", "DESCRIPCION");
                    $this->libro->getActiveSheet()->setCellValue("C8", "COSTO");
                    $this->libro->getActiveSheet()->setCellValue("D8", "FAMILIA");
                    $this->libro->getActiveSheet()->setCellValue("E8", "SUBFAMILIA");
                    $this->libro->getActiveSheet()->setCellValue("F8", "REVISION 1");
                    $this->libro->getActiveSheet()->setCellValue("G8", "REVISION 2");
                    $this->libro->getActiveSheet()->setCellValue("H8", "REVISION 3");
                    $this->libro->getActiveSheet()->setCellValue("I8", "SISTEMA");
                    $this->libro->getActiveSheet()->setCellValue("J8", "DIFERENCIA");
                    $this->libro->getActiveSheet()->setCellValue("K8", "COSTO x DIFERENCIA");
                    $this->libro->getActiveSheet()->setCellValue("L8", "HORA REALIZACION");
                    $this->libro->getActiveSheet()->mergeCells("L8:M8");
                    $this->libro->getActiveSheet()->getStyle("A8:L8")->applyFromArray($this->labelBold);
    
                    
                    $i = 9;
                    foreach ($herramientas as $idx => $producto) {
                        if ( $producto['tipo'] == 5) { // es un inventario de herramientas?

                        $dateTime = explode(' ',$producto['fechaCaptura']);
                        $this->libro->getActiveSheet()->setCellValue("A$i",  $producto['codigo']);
                        $this->libro->getActiveSheet()->setCellValue("B$i", $producto['descripcion']);
                        $this->libro->getActiveSheet()->setCellValue("C$i", $inventarios->getCostoArticulo($producto['codigo']) );
                        $this->libro->getActiveSheet()->getStyle("C$i")->getNumberFormat()->setFormatCode("$#,##0.00;-$#,##0.00");
                        $this->libro->getActiveSheet()->setCellValue("D$i", $producto['familia']);
                        $this->libro->getActiveSheet()->setCellValue("E$i", $producto['subfamilia']);
                        $this->libro->getActiveSheet()->setCellValue("F$i", $producto['fisico']);
                        $this->libro->getActiveSheet()->setCellValue("G$i", $producto['fisico2'] == '' ? $producto['fisico'] : $producto['fisico2']);
                        $this->libro->getActiveSheet()->setCellValue("H$i", ( $producto['fisico3'] == '' ) ? "=G$i" : $producto['fisico3']);
                        $this->libro->getActiveSheet()->setCellValue("I$i", $producto['stock']);
                        $this->libro->getActiveSheet()->setCellValue("L$i", $dateTime[1]);
                        $this->libro->getActiveSheet()->getStyle("L$i")->applyFromArray($this->centrarTexto);
                        $this->libro->getActiveSheet()->mergeCells("L$i:M$i");
    
                        //Esta sección es modificada en el caso de que en la revision 3 sea  cero o null
                        if ($producto['fisico2']  == 0  ) {
                            if ( $producto['fisico3'] == 0) {
                                $this->libro->getActiveSheet()->setCellValue("J$i","=F$i-I$i");
                            }
                        }elseif( $producto['fisico3'] == 0){
                            $this->libro->getActiveSheet()->setCellValue("J$i","=G$i-I$i");
                        }else{
                            $this->libro->getActiveSheet()->setCellValue("J$i","=H$i-I$i");
                        }
                        $this->libro->getActiveSheet()->setCellValue("K$i","=C$i*J$i");      
                        $this->libro->getActiveSheet()->getStyle("K$i")->getNumberFormat()->setFormatCode("$#,##0.00;-$#,##0.00");                       
                        $i++;    
                        }       
                    }
    
                    $this->putLogo("B1",300,150);
                    $this->libro->getActiveSheet()->getStyle("A8:M".($i-1))->applyFromArray($this->bordes);
                    $this->libro->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
                }
            }
        }
        $reporteTerminado = new \PHPExcel_Writer_Excel2007( $this->libro);
        // ob_end_clean();
        $reporteTerminado->setPreCalculateFormulas(true);
        $reporteTerminado->save("reporteInventarios.xlsx");
    }
}

$reporte = new ReporteInventario;
$reporte->generaReporte();

$configCorreo = array("descripcionDestinatario" => "Reporte de Inventarios",
                                       "mensaje" => "...",
                                       "pathFile" => "reporteInventarios.xlsx",
                                       "subject" => "Reporte de Inventarios",
                                       "correos" => array('gerenteti@matrix.com.mx', "raulmatrixxx@hotmail.com","gtealmacen@matrix.com.mx","gerenteventas@matrix.com.mx","dispersion@matrix.com.mx","almacenlaureles@matrix.com.mx","software2@matrix.com.mx","cavim@matrix.com.mx","admonrh@matrix.com.mx","rhmatrix2019@gmail.com","gerente_auditoria@matrix.com.mx","director@matrix.com.mx")
									   //'correos' => array('auxsistemas@matrix.com.mx')
                                     );

$inventarios = new Inventario;
$fechaActual = date("Y-m-d");
if ( sizeof($inventarios->getInventarioCurrentDay( $fechaActual ) ) ) {
    $reporte->enviarReporte( $configCorreo);
}
