<?php
set_time_limit(0);
ini_set('memory_limit', '20000M');

require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/PedidosLlantas.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Controllers/prepareExcel.php";

class PedidoSugeridoReporte extends PrepareExcel
{
    protected $columnasMeseses = array();

    public function getFormatoFecha()
    {
        $mc = 12;
        $mesini = $mc-1;
        $mesactual = date("m");
        $anioactual = date("Y");
        $mesinicial =  date("m",strtotime('-'.$mesini.' month' , strtotime(date("Y-m-d"))));
        // echo $mesinicial."      ".$mesactual ."<br><br>";
        $anio1 = 0;
        $anio2 = 0;
        $diferenciaFechas = ( ($mesactual*1)<($mesinicial*1) );

        if( $diferenciaFechas ){
            
            $anioinicial = $anioactual-1;
            $cantmeses1 = (12 - $mesinicial) ;
            $cantmeses2 = $mesactual;
            $anio1 = $anioinicial;
            $anio2 = $anioactual;
            $anioactual = $anioinicial ;
            $mesinicial = $cantmeses1;
        }else{
            $anioinicial = $anioactual;
            $cantmeses = ($mesactual - $mesinicial) + 1;
        }

        return array('mes' => $mesinicial, "anio" => $anioactual);
    }

    public function agrupaVentas( $ventas)
    {
        $listadoVentasMensual = array();
        $columnasReservadas = array('C','D','E','F','G','H');
        $i = 0;
        foreach ($ventas as $index => $venta) {
            if ( isset($listadoVentasMensual[$venta->ANIO][utf8_decode($venta->MEDIDA)]['VENTA'][$venta->MES]) ) {
                $listadoVentasMensual[$venta->ANIO][utf8_decode($venta->MEDIDA)]['VENTA'][$venta->MES]['CANTIDAD'] += $venta->CANTIDAD;
                $listadoVentasMensual[$venta->ANIO][utf8_decode($venta->MEDIDA)]['VENTA'][$venta->MES]['SUBFAMILIA'] = $venta->SUBFAMILIA;
                $listadoVentasMensual[$venta->ANIO][utf8_decode($venta->MEDIDA)]['VENTA'][$venta->MES]['STOCK'] = $venta->STOCK;
            }else {
                $listadoVentasMensual[$venta->ANIO][utf8_decode($venta->MEDIDA)]['VENTA'][$venta->MES]['CANTIDAD'] = $venta->CANTIDAD;
                $listadoVentasMensual[$venta->ANIO][utf8_decode($venta->MEDIDA)]['VENTA'][$venta->MES]['SUBFAMILIA'] = $venta->SUBFAMILIA;
                $listadoVentasMensual[$venta->ANIO][utf8_decode($venta->MEDIDA)]['VENTA'][$venta->MES]['STOCK'] = $venta->STOCK;
                
                if ( !in_array($venta->MES,$this->columnasMeseses) ) {
                    if( $venta->ANIO < date('Y')){
                        $this->columnasMeseses[$venta->ANIO][$venta->MES] = "";
                    }else{
                        $this->columnasMeseses[$venta->ANIO][$venta->MES] = "";
                    }
                }
            }
        }

        foreach ($this->columnasMeseses as $anio => $meses) {
            ksort($this->columnasMeseses[$anio]);
        }

        foreach ($this->columnasMeseses as $anio => $meses) {
            foreach ($meses as $mes => $contenido) {
                $this->columnasMeseses[$anio][$mes] = $columnasReservadas[$i];
                $i++;
            }
        }

        return $listadoVentasMensual;
    }

    public function generarReporte()
    {
        $fecha = $this->getFormatoFecha();

        $sugeridos = new PedidosSugeridos;
        $sucursales = $sugeridos->getSucursalesConAlmacen();
         $suc = new stdClass;
         $suc->ID = "%";
         $suc->DESCRIPCION = "GENERAL";

         array_push( $sucursales, $suc);
		 
		 $familiasarr = array("LLANTA","RIN");

        	$arrayMeses = array(1=>'ENE',
					  2=>'FEB',
					  3=>'MAR',
					  4=>'ABR',
					  5=>'MAY',
					  6=>'JUN',
					  7=>'JUL',
					  8=>'AGO',
					  9=>'SEP',
					  10=>'OCT',
					  11=>'NOV',
					  12=>'DIC');
            $columnasReservadas = array('C','D','E','F','G','H');
        $sheet = -1;
        foreach ($familiasarr as   $familia) {
                $fecha['familia'] = $familia;
                $productosVendidos =  $sugeridos->getProductosVendidos($fecha);

				$ventasAgrupadas = $this->agrupaVentas( $productosVendidos);
                
                
                $i = 9;
                if ( sizeof($ventasAgrupadas) ) {

                    $sheet++;

                    $banderaMeses = 0;
                    $ultimoMes = "";
                    $this->creaEmptySheet(str_replace(' ','',$familia) ,$sheet);

                    $this->libro->getActiveSheet()->mergeCells("A5:O5");
                    $this->libro->getActiveSheet()->setCellValue("A5", 'ACUMULADO DE VENTAS DE '.strtoupper($familia).' AL '.date('d/m/Y h:i:s'));
                    $this->libro->getActiveSheet()->getStyle("A5")->applyFromArray($this->labelBold);
                    $this->libro->getActiveSheet()->getStyle("A5")->applyFromArray($this->centrarTexto);

                    $this->libro->getActiveSheet()->setCellValue("B8", "MEDIDA");
                    var_dump( $this->columnasMeseses );
                    foreach ($this->columnasMeseses as $anio => $meses) {
                        foreach ($meses as $mes => $contenido) {
                            $this->libro->getActiveSheet()->setCellValue($this->columnasMeseses[$anio][$mes]."8", $arrayMeses[$mes]);
                            $ultimoMes = $mes;
                            $banderaMeses++;
                            
                        }
                    }           
					$this->libro->getActiveSheet()->setCellValue("H8", "TOTAL");
					$this->libro->getActiveSheet()->setCellValue("I8", "STOCK");
                    
                    // if ( $banderaMeses < 11) {
                    //     while ( $banderaMeses <= 11) {
                    //         $ultimoMes++;
                    //         echo $columnasReservadas[$banderaMeses-1]."<br";
                    //         $this->libro->getActiveSheet()->setCellValue($columnasReservadas[$banderaMeses-1]."8", $arrayMeses[$ultimoMes-1]);
                    //         $banderaMeses++;
                    //     }
                    // }
                    $this->libro->getActiveSheet()->getStyle("A8:H8")->applyFromArray($this->labelBold);
                    $this->libro->getActiveSheet()->setAutoFilter('A8:H8');

                    foreach ($ventasAgrupadas as $anio => $productos) {
                    //    foreach ($familias as $familia => $subFamilias) {
                    //        foreach ($subFamilias as $subFamilia => $productos) {
                                foreach ($productos as $medida => $producto) {
                                    //$precios_costos = $sugeridos->getInfoArticulo($codigo) ;
                                    ksort($producto['VENTA']);
                                    $sumatoriaVentas = 0;
                                    foreach ($producto['VENTA'] as $mes => $infoVenta) {
										if($familia!='RIN'){
											$this->libro->getActiveSheet()->setCellValue("A".$i, $medida);
										}else{
											$expl = explode("_",$medida);
											$this->libro->getActiveSheet()->setCellValue("A".$i, $expl[1]);
										}
										$this->libro->getActiveSheet()->setCellValue("B".$i, $infoVenta['SUBFAMILIA']);
                                        $this->libro->getActiveSheet()->setCellValue($this->columnasMeseses[$anio][$mes].$i, $infoVenta['CANTIDAD']);
										$sumatoriaVentas += $infoVenta['CANTIDAD'];
									}
									$this->libro->getActiveSheet()->setCellValue("H".$i, $sumatoriaVentas);
                                    $this->libro->getActiveSheet()->getStyle("O$i:H$i")->applyFromArray($this->centrarTexto);
                                
                                $i++;
                                }
                    //        }
                    //    }
                    }


                    foreach ($columnasReservadas as $columna) {
                        $j= 9;
                        while ($j< $i) {
                            $cantidadVendida =  $this->libro->getActiveSheet()->getCell( $columna."$j")->getValue();
                            if ( $cantidadVendida == NULL || $cantidadVendida == '') {
                                $cantidadVendida =  $this->libro->getActiveSheet()->setCellValue( $columna."$j",'0');
                            }
                            $j++;
                        }
                        
                    }
                    $this->libro->getActiveSheet()->getColumnDimension('A')->setAutoSize(false);
                    $this->libro->getActiveSheet()->getColumnDimension('A')->setWidth("10");
                    $this->libro->getActiveSheet()->getColumnDimension('B')->setAutoSize(false);
                    $this->libro->getActiveSheet()->getColumnDimension('B')->setWidth("35");
                    $this->libro->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
                    // $this->libro->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
                    // $this->libro->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
                    // $this->libro->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
                    // $this->libro->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
                    // $this->libro->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
                    // $this->libro->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
                    // $this->libro->getActiveSheet()->getColumnDimension('O')->setAutoSize(true);
                    // $this->libro->getActiveSheet()->getColumnDimension('P')->setAutoSize(true);
                    // $this->libro->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);

                    $this->putLogo("E1",300,150);
                    $this->libro->getActiveSheet()->getStyle("A8:H".($i-1))->applyFromArray($this->bordes);
                }                
        }
        $reporteTerminado = new \PHPExcel_Writer_Excel2007( $this->libro);
        // ob_end_clean();
        $reporteTerminado->setPreCalculateFormulas(true);
        $reporteTerminado->save("reportePedidosLlantas.xlsx");
        return $ventasAgrupadas;

    }
}


$reporte = new PedidoSugeridoReporte;
($reporte->generarReporte() );

// $configCorreo = array("descripcionDestinatario" => "Reporte de Acumulado de Ventas por Familia",
//                                        "mensaje" => "...",
//                                        "pathFile" => "reportePedidosLlantas.xlsx",
//                                        "subject" => "Reporte de Acumulado de Ventas por Familia",
//                                        "correos" => array('sestrada@matrix.com.mx')
//                                      );
// $reporte->enviarReporte( $configCorreo);