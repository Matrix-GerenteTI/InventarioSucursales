<?php
set_time_limit(0);
ini_set('memory_limit', '20000M');

require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Controllers/prepareExcel.php";

class RevisionInventarios  extends PrepareExcel
{
    private  $columnasDia =['','B', 'C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG'];
    private $columnaTotal = "AF";

    private $lista = array();

    public function generaReporte( $mes, $anio )
    {
        $modeloInventario = new Inventario;
        $inventariosRealizadosSucursales = $modeloInventario->getSucursalesInventariosHechos( $mes );

        $sucursales = $modeloInventario->getSucursalesConAlmacenActivo();
		$sucursalesDomingo = array('10787','10755');

        // creando un arreglo de las sucursa y agregar en ella los dias del mes que realizó inventario
        $sucursalInventario = [];
        $filaUbicacion = 9;
        foreach ( $sucursales as  $sucursal) {
            $sucursalInventario[ $sucursal->ID ]['sucursal'] = $sucursal->DESCRIPCION;
            $sucursalInventario[ $sucursal->ID]['diasInventario'] = [];
            $sucursalInventario[ $sucursal->ID]['fila'] = $filaUbicacion;
            $filaUbicacion++;
            /*foreach ($inventariosRealizadosSucursales  as $idx => $inventario) {
                if ( $sucursal->ID  == $inventario['sucursal_id'] ) {
                    array_push($sucursalInventario[ $sucursal->ID ]['diasInventario'], $inventario  );   
                    unset( $inventariosRealizadosSucursales[$idx] );
                }
            }*/
            $sucursalInventario[ $sucursal->ID]['ID'] =  $sucursal->ID;
        }

        $auditores = [
            ['fila' => 23, 'auditor' => 'C.P. Feliciano Coutino de la Torre'],
            ['fila' => 24, 'auditor' => 'C.P. Felipe Rosales Rosales'],

        ];
        $cantDiasMes = cal_days_in_month(CAL_GREGORIAN , $mes, $anio);
        $cantDiasMesAuditores = 20;
        if ( $cantDiasMes == 28 ) {
            $this->columnaTotal = "AD";
        } else if( $cantDiasMes == 29) {
            $this->columnaTotal = "AE";
        }else if( $cantDiasMes == 30){
            $this->columnaTotal = "AF";
        }else{
            $this->columnaTotal = "AG";
        }        
        
        $this->creaEmptySheet( "Inventarios", 0 );

        $this->putLogo("F1",300,150);
        $this->libro->getActiveSheet()->mergeCells("B5:W5");
        $this->libro->getActiveSheet()->setCellValue("B5", "Detalle inventarios realizados por sucursal : ". $this->getMesAsString( $mes)." $anio" );
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

        foreach ($sucursalInventario as $sucursal) {
            $this->libro->getActiveSheet()->setCellValue("A".$sucursal['fila'], $sucursal['sucursal']);
            $this->libro->getActiveSheet()->getStyle("A".$sucursal['fila'])->applyFromArray($this->labelBold);
            // rellenando con X todas las celdas
			$countOmisiones = 0;
            for ($i=1; $i <= $cantDiasMes  ; $i++) {
				//Checamos si es domingo para solo afectar a Laureles y Libramiento
				$diaSem = date('w', strtotime(date("$i-$mes-Y")))*1;
                $inventariosOmitidosSucursales = $modeloInventario->getSucursalesInventariosOmitidos( $anio, $mes, $i, $sucursal['ID'] );

                $fecha_entrada = strtotime("11:00:00");
                
                //if($inventariosOmitidosSucursales>0 ||($diaSem==0 && !in_array($sucursal['ID'],$sucursalesDomingo)) || ($i>(date("d")*1))){ // Por si aplica los domingos para quienes abren
                $esInventarioValido = $inventariosOmitidosSucursales[2] != null ? strtotime($inventariosOmitidosSucursales[2] )  - $fecha_entrada : null ;
                echo $esInventarioValido;
                // var_dump( $inventariosOmitidosSucursales );
                if( ( $inventariosOmitidosSucursales[1]>0 || ($diaSem==0) || ($i>(date("d")*1)) )  && ( $esInventarioValido <= 0 && $fecha_entrada != null )  )  {
                    $this->libro->getActiveSheet()->setCellValue($this->columnasDia[$i].$sucursal['fila'], " ");
                    $this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$sucursal['fila'])->applyFromArray($this->labelBold);
                    $this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$sucursal['fila'])->applyFromArray($this->centrarTexto);
                }else{
					$this->libro->getActiveSheet()->setCellValue($this->columnasDia[$i].$sucursal['fila'], "X");
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$sucursal['fila'])->applyFromArray($this->labelBold);
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$sucursal['fila'])->applyFromArray($this->centrarTexto);
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$sucursal['fila'])->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$sucursal['fila'])->applyFromArray( $this->setColorText("ffffff",12) );  
					$countOmisiones++;
				}
            }


			/*            
            foreach ( $sucursal['diasInventario'] as $diasInventario ) {
                $this->libro->getActiveSheet()->setCellValue($this->columnasDia[$diasInventario['dia']].$sucursal['fila'], " ");
            }*/
            $this->libro->getActiveSheet()->setCellValue($this->columnaTotal.$sucursal['fila'], $countOmisiones);
            $this->libro->getActiveSheet()->getStyle($this->columnaTotal.$sucursal['fila'])->applyFromArray($this->centrarTexto);     
            $ultimaFila = $sucursal['fila'];
            $this->lista[$sucursal['sucursal']][0] = $countOmisiones;
        }

        foreach ($auditores as $auditor) {
            echo $auditor;
            $this->libro->getActiveSheet()->setCellValue("A".$auditor['fila'],$auditor['auditor']);
            $this->libro->getActiveSheet()->getStyle("A".$auditor['fila'])->applyFromArray($this->labelBold);
            $countOmisiones = 0;
            for ($i=1; $i <= $cantDiasMesAuditores  ; $i++) {
                $diaSem = date('W',strtotime(date("$i-$mes-Y")))*1;
                $inventariosOmitidosSucursales = $modeloInventario->getAuditoresInventariosOmitidos($anio,$mes,$i,$auditor['auditor']);
                $fecha_entrada = strtotime("20:00:00");
                
                //if($inventariosOmitidosSucursales>0 ||($diaSem==0 && !in_array($sucursal['ID'],$sucursalesDomingo)) || ($i>(date("d")*1))){ // Por si aplica los domingos para quienes abren
                $esInventarioValido = $inventariosOmitidosSucursales[2] != null ? strtotime($inventariosOmitidosSucursales[2] )  - $fecha_entrada : null ;
                echo $esInventarioValido;
                if( ( $inventariosOmitidosSucursales[1]>0 || ($diaSem==0) || ($i>(date("d")*1)) )  && ( $esInventarioValido <= 0 && $fecha_entrada != null )  )  {
                    $this->libro->getActiveSheet()->setCellValue($this->columnasDia[$i].$auditor['fila'], " ");
                    $this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$auditor['fila'])->applyFromArray($this->labelBold);
                    $this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$auditor['fila'])->applyFromArray($this->centrarTexto);
                }else{
					$this->libro->getActiveSheet()->setCellValue($this->columnasDia[$i].$auditor['fila'], "X");
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$auditor['fila'])->applyFromArray($this->labelBold);
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$auditor['fila'])->applyFromArray($this->centrarTexto);
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$auditor['fila'])->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
					$this->libro->getActiveSheet()->getStyle($this->columnasDia[$i].$auditor['fila'])->applyFromArray( $this->setColorText("ffffff",12) );  
					$countOmisiones++;
				}
            }

            $this->libro->getActiveSheet()->setCellValue($this->columnaTotal.$auditor['fila'], $countOmisiones);
            $this->libro->getActiveSheet()->getStyle($this->columnaTotal.$auditor['fila'])->applyFromArray($this->centrarTexto);     
            $ultimaFila = $auditor['fila'];
            $this->lista[$auditor['auditor']][0] = $countOmisiones;
        }
        

        $this->libro->getActiveSheet()->getStyle("A8:".$this->columnaTotal.$ultimaFila)->applyFromArray($this->bordes);

        $reporteTerminado = new \PHPExcel_Writer_Excel2007( $this->libro);
        // ob_end_clean();
        $reporteTerminado->setPreCalculateFormulas(true);
        $reporteTerminado->save("reporteInventariosRealizados.xlsx");
        return $this->lista;
        //echo json_encode( $sucursalInventario );
    }
}

$reporte = new RevisionInventarios;
$lista = $reporte->generaReporte( date("m"), date("Y") );
// $lista = $reporte->generaReporte( '05', date("Y") );

//Correos para Producción
$mails = array('gerenteti@matrix.com.mx','ti@matrix.com.mx','dispersion@matrix.com.mx','corporativo@matrix.com.mx','cavim@matrix.com.mx','admonrh@matrix.com.mx','rhmatrix2019@gmail.com','gerente_auditoria@matrix.com.mx','director@matrix.com.mx');


//Correos para SandBox
//$mails = array('sestrada@matrix.com.mx');


$texto = "<table border=1>";
$texto.= "  <tr><th>SUCURSAL</th><th>OMISIONES</th><th>SANCION</th></tr>";
foreach($lista as $sucursal => $omisiones){
    $texto.= "<tr><td>".$sucursal."</td><td>".$omisiones[0]."</td><td>$ ".number_format($omisiones[0]*50,2,'.',',')."</td></tr>";
}
$texto.= "</table>";
$configCorreo = array("descripcionDestinatario" => "Reporte de Inventarios Aleatorios",
                                       "mensaje" => "<b>RESUMEN DE OMISIONES Y MULTAS:</b><p>".$texto."</p>",
                                       "pathFile" => "reporteInventariosRealizados.xlsx",
                                       "subject" => "Reporte de Inventarios Aleatorios",
                                       "correos" => $mails
                                     );
$reporte->enviarReporte( $configCorreo);                                     
