<?php
set_time_limit(0);
ini_set('memory_limit', '20000M');

require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Controllers/prepareExcel.php";

class RevisionInsumo  extends PrepareExcel
{
    private  $columnasDia =['','B', 'C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG'];
    private $columnaTotal = "AF";

    private $lista = array();

    public function generaReporte( $mes, $anio )
    {   
        /* Rangos de fecha */
            $fechaInicio = new DateTime(); //Fecha de inicio del mes
            $fechaInicio->modify('first day of this month');
            
            $fechaFin = new DateTime(); //Fecha de fin del mes
            $fechaFin->modify('last day of this month');
        /* Fin rango fechas */

        $modeloInventario = new Inventario;
        $checklisSucursales = $modeloInventario->getSucursalesChecklist( $fechaInicio, $fechaFin );

        $cantDiasMes = cal_days_in_month(CAL_GREGORIAN , $mes, $anio);
        if ( $cantDiasMes == 28 ) {
            $this->columnaTotal = "AD";
        } else if( $cantDiasMes == 29) {
            $this->columnaTotal = "AE";
        }else if( $cantDiasMes == 30){
            $this->columnaTotal = "AF";
        }else{
            $this->columnaTotal = "AG";
        }        
        
        $this->creaEmptySheet( "Checklist Sucursales", 0 );

        $this->putLogo("F1",300,150);
        $this->libro->getActiveSheet()->mergeCells("B5:W5");
        $this->libro->getActiveSheet()->setCellValue("B5", "Checklist mensual por sucursal: ". $this->getMesAsString( $mes)." $anio" );
        $this->libro->getActiveSheet()->getStyle("B5")->applyFromArray($this->labelBold);
        $this->libro->getActiveSheet()->getStyle("B5")->applyFromArray($this->centrarTexto);        


        $this->libro->getActiveSheet()->setCellValue("A8", "ALMACEN");
        $this->libro->getActiveSheet()->getStyle("A8:".$this->columnaTotal."8")->getFill()->applyFromArray( $this->setColorFill("DF013A") );
        $this->libro->getActiveSheet()->getStyle("A8:".$this->columnaTotal."8")->applyFromArray($this->labelBold);
        $this->libro->getActiveSheet()->getStyle("A8:".$this->columnaTotal."8")->applyFromArray($this->centrarTexto);       
        $this->libro->getActiveSheet()->getStyle("A8:".$this->columnaTotal."8")->applyFromArray( $this->setColorText("ffffff",12) );  
        $this->libro->getActiveSheet()->setCellValue($this->columnaTotal."8", "TOTAL");

        $ultimaFila = 9;
        $cuentaDiaHabil = 0;
        $days = array('Sun'=> 'Domingo', 'Mon' =>'Lunes', 'Tue'=>'Martes', 'Wed'=>'Miercoles','Thu' => 'Jueves','Fri'=>'Viernes', 'Sat'=>'Sábado');
        for ($i=1; $i <= $cantDiasMes  ; $i++) { 
            $diaSemana = date('D', strtotime(("$i-$mes-$anio") ) );
            $diaSemana = $days[$diaSemana];
            if ( $diaSemana != "Domingo") {
                $cuentaDiaHabil++;
            }

            $this->libro->getActiveSheet()->setCellValue($this->columnasDia[$i]."7", $diaSemana);
            $this->libro->getActiveSheet()->getStyle($this->columnasDia[$i]."7")->getAlignment()->setTextRotation(90);
            $this->libro->getActiveSheet()->getRowDimension("7")->setRowHeight(60);
            $this->libro->getActiveSheet()->getStyle($this->columnasDia[$i]."7")->applyFromArray( $this->labelBold);    

            $this->libro->getActiveSheet()->setCellValue($this->columnasDia[$i]."8", $i);
            $this->libro->getActiveSheet()->getColumnDimension($this->columnasDia[$i])->setAutoSize(true);
        }
        $this->libro->getActiveSheet()->getColumnDimension( "A" )->setAutoSize(false);
        $this->libro->getActiveSheet()->getColumnDimension( "A" )->setWidth("30");   

        $filaActual = 9;
        foreach ($checklisSucursales as $sucursal) {
            $this->libro->getActiveSheet()->setCellValue("A".$filaActual, $sucursal['descripcion']);
            $this->libro->getActiveSheet()->getStyle("A".$filaActual)->applyFromArray($this->labelBold);
            
            // rellenando con X todas las celdas
			$countOmisiones = 0;
            for ($i=1; $i <= $cantDiasMes  ; $i++) { 
				//Checamos si es domingo para solo afectar a Laureles y Libramiento
                $diaSem = date('w', strtotime(date("$i-m-Y")))*1;
                $fechaHoy =  date("Y")."-".date("m")."-".date("d");
				if(($i<=(date("j"))) && ($diaSem!=0)){
                    $this->libro->getActiveSheet()->setCellValue($this->columnasDia[$i].$filaActual, "X");
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$filaActual)->applyFromArray($this->labelBold);
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$filaActual)->applyFromArray($this->centrarTexto);
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$filaActual)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$filaActual)->applyFromArray( $this->setColorText("ffffff",12) );  
					$countOmisiones++;
				}else if(($sucursal["fecha"] == null) || ($diaSem==0)){
					$this->libro->getActiveSheet()->setCellValue($this->columnasDia[$i].$filaActual, " ");
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$filaActual)->applyFromArray($this->labelBold);
                    $this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$filaActual)->applyFromArray($this->centrarTexto);
				}
            }
            
            $this->libro->getActiveSheet()->setCellValue($this->columnaTotal.$filaActual, $countOmisiones);
            $this->libro->getActiveSheet()->getStyle($this->columnaTotal.$filaActual)->applyFromArray($this->centrarTexto);
            $filaActual = $filaActual + 1;   
            $ultimaFila = $filaActual;
            $this->lista[$sucursal['descripcion']][0] = $countOmisiones;
        }
        $this->libro->getActiveSheet()->getStyle("A8:".$this->columnaTotal.$ultimaFila)->applyFromArray($this->bordes);

        $reporteTerminado = new \PHPExcel_Writer_Excel2007( $this->libro);
        // ob_end_clean();
        $reporteTerminado->setPreCalculateFormulas(true);
        $reporteTerminado->save("reporteChecklistSucursales.xlsx");
        return $this->lista;
        //echo json_encode( $sucursalInventario );
    }
}

$reporte = new RevisionInsumo;
$lista = $reporte->generaReporte( date('m'), date("Y") );

/* Correos para Producción */
$mails = array("sestrada@matrix.com.mx",
                
                
                
                "software@matrix.com.mx",
                
                "raulmatrixxx@hotmail.com",
                "rh@matrix.com.mx",
                "gerenteventasnorte@matrix.com.mx",
                "software2@matrix.com"
             );

//$mails = array('kevinsl@live.com.mx');

$configCorreo = array("descripcionDestinatario" => "Reporte de Checklist por Sucursal",
                      "mensaje" => "<b>RESUMEN DE LOS CHECKLIST POR SUCURSAL:</b>",
                      "pathFile" => "reporteChecklistSucursales.xlsx",
                      "subject" => "Reporte de Checklist por Sucursal",
                      "correos" => $mails
                     );
$reporte->enviarReporte( $configCorreo);                                     
