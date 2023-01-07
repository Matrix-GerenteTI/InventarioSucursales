<?php
set_time_limit(0);
ini_set('memory_limit', '20000M');

require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/PedidosSugueridos.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Controllers/prepareExcel.php";

class PedidoSugeridoReporte extends PrepareExcel
{
    protected $columnasMeseses = array();

    public function getFormatoFecha()
    {
        $mc = 6;
        $mesini = $mc-1;
        $mesactual = date("m");
        $anioactual = date("Y");
        $mesinicial =  date("m",strtotime('-'.$mesini.' month' , strtotime(date("Y-m-d"))));
        $anio1 = 0;
        $anio2 = 0;
        if(($mesactual*1)<($mesinicial*1)){
            $anioinicial = $anioactual-1;
            $cantmeses1 = (12 - $mesinicial) + 1;
            $cantmeses2 = $mesactual;
            $anio1 = $anioinicial;
            $anio2 = $anioactual;
        }else{
            $anioinicial = $anioactual;
            $cantmeses = ($mesactual - $mesinicial) + 1;
        }

        return array('mes' => 07, "anio" => 2018);
    }

    public function agrupaVentas( $ventas)
    {
        $listadoVentasMensual = array();
        $columnasReservadas = array('G','H','I','J','K','L','M','N','O','P','Q','R');
        $i = 0;
        foreach ($ventas as $index => $venta) {
            if ( isset($listadoVentasMensual[$venta->ANIO][$venta->FAMILIA][$venta->SUBFAMILIA][utf8_decode($venta->CODIGO)]['VENTA'][$venta->MES]) ) {
                $listadoVentasMensual[$venta->ANIO][$venta->FAMILIA][$venta->SUBFAMILIA][utf8_decode($venta->CODIGO)]['VENTA'][$venta->MES]['CANTIDAD'] += $venta->CANTIDAD;
                $listadoVentasMensual[$venta->ANIO][$venta->FAMILIA][$venta->SUBFAMILIA][utf8_decode($venta->CODIGO)]['VENTA'][$venta->MES]['EXISTENCIA'] += $venta->EXISTENCIA;
                
            }else {
                $listadoVentasMensual[$venta->ANIO][$venta->FAMILIA][$venta->SUBFAMILIA][utf8_decode($venta->CODIGO)]['VENTA'][$venta->MES]['CANTIDAD'] = $venta->CANTIDAD;
                $listadoVentasMensual[$venta->ANIO][$venta->FAMILIA][$venta->SUBFAMILIA][utf8_decode($venta->CODIGO)]['VENTA'][$venta->MES]['EXISTENCIA'] = $venta->EXISTENCIA;
                $listadoVentasMensual[$venta->ANIO][$venta->FAMILIA][$venta->SUBFAMILIA][utf8_decode($venta->CODIGO)]['VENTA'][$venta->MES]['DESCRIPCION']  = utf8_decode( $venta->DESCRIPCION );
                
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

        // echo json_encode( [$listadoVentasMensual] );
        // exit();
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
            $columnasReservadas = array('G','H','I','J','K','L','M','N','O','P','Q','R');
        $sheet = -1;
        foreach ($sucursales as   $sucursal) {
                $fecha['sucursal'] = $sucursal->ID;
                $productosVendidos =  $sugeridos->getProductosVendidos($fecha);

                $ventasAgrupadas = $this->agrupaVentas( $productosVendidos);
                $i = 9;
                if ( sizeof($ventasAgrupadas) ) {

                    $sheet++;

                    $banderaMeses = 0;
                    $ultimoMes = "";
                    $this->creaEmptySheet(str_replace(' ','',$sucursal->DESCRIPCION) ,$sheet);

                    $this->libro->getActiveSheet()->mergeCells("A5:O5");
                    $this->libro->getActiveSheet()->setCellValue("A5", 'PEDIDOS SUGERIDOS DE '.strtoupper($sucursal->DESCRIPCION).' AL '.date('d/m/Y h:i:s'));
                    $this->libro->getActiveSheet()->getStyle("A5")->applyFromArray($this->labelBold);
                    $this->libro->getActiveSheet()->getStyle("A5")->applyFromArray($this->centrarTexto);

                    $this->libro->getActiveSheet()->setCellValue("A8", "CODIGO");
                    $this->libro->getActiveSheet()->setCellValue("B8", "DESCRIPCION");
                    foreach ($this->columnasMeseses as $anio => $meses) {
                        foreach ($meses as $mes => $contenido) {
                            $this->libro->getActiveSheet()->setCellValue($this->columnasMeseses[$anio][$mes]."8", $arrayMeses[$mes]);
                            $ultimoMes = $mes;
                            $banderaMeses++;
                        }
                    }                    
                    if ( $banderaMeses < 12) {
                        while ( $banderaMeses <= 12) {
                            $ultimoMes++;
                            $this->libro->getActiveSheet()->setCellValue($columnasReservadas[$banderaMeses-1]."8", $arrayMeses[$ultimoMes-1]);
                            $banderaMeses++;
                        }
                    }
                    $this->libro->getActiveSheet()->setCellValue("C8", "COSTO");
                    $this->libro->getActiveSheet()->setCellValue("D8", "PVP1");
                    $this->libro->getActiveSheet()->setCellValue("E8", "PVP2");
                    $this->libro->getActiveSheet()->setCellValue("F8", "PVP3");

                    $this->libro->getActiveSheet()->setCellValue("S8", "FAMILIA");
                    $this->libro->getActiveSheet()->setCellValue("T8", "SUBFAMILIA");
                    $this->libro->getActiveSheet()->setCellValue("U8", "EXIST. ACTUAL");
                    $this->libro->getActiveSheet()->setCellValue("V8", "PROM. VENTAS");
                    $this->libro->getActiveSheet()->setCellValue("W8", "PED. SUGERIDO");
                    $this->libro->getActiveSheet()->getStyle("A8:W8")->applyFromArray($this->labelBold);
                    $this->libro->getActiveSheet()->setAutoFilter('A8:W8');
                    $lastRow = 9;
                    $filasCodigos = []; 

                    foreach ($ventasAgrupadas as $anio => $familias) {
                        foreach ($familias as $familia => $subFamilias) {
                            foreach ($subFamilias as $subFamilia => $productos) {
                                foreach ($productos as $codigo => $producto) {
                                    $precios_costos = $sugeridos->getInfoArticulo($codigo) ;
                                    ksort($producto['VENTA']);
                                    $sumatoriaVentas = 0;
                                    foreach ($producto['VENTA'] as $mes => $infoVenta) {
                                        $existenciaArticulo = $sugeridos->getExistenciaArticulo($codigo, $sucursal->ID);

                                        if( isset( $filasCodigos[$codigo] ) ){
                                            $i = $filasCodigos[$codigo];
                                        }else{
                                            $vendido =  $this->libro->getActiveSheet()->getCell( $this->columnasMeseses[$anio][$mes].$i)->getValue();
                                            if( $vendido != '' ){
                                                $i = $lastRow;
                                                $lastRow++;
                                            }
                                            $filasCodigos[$codigo] = $i ;
                                            
                                        }

                                        $this->libro->getActiveSheet()->setCellValue($this->columnasMeseses[$anio][$mes].$i, $infoVenta['CANTIDAD']);
                                        $this->libro->getActiveSheet()->setCellValue("B".$i, $infoVenta['DESCRIPCION']);
                                        $this->libro->getActiveSheet()->setCellValue("A".$i, $codigo);
                                        $this->libro->getActiveSheet()->setCellValue("C".$i, $precios_costos[0]->COSTO);
                                        $this->libro->getActiveSheet()->getStyle("C$i")->getNumberFormat()->setFormatCode("$#,##0.00;-$#,##0.00");
                                        $this->libro->getActiveSheet()->setCellValue("D".$i, $precios_costos[0]->PVP1);
                                        $this->libro->getActiveSheet()->getStyle("D$i")->getNumberFormat()->setFormatCode("$#,##0.00;-$#,##0.00");
                                        $this->libro->getActiveSheet()->setCellValue("E".$i, $precios_costos[0]->PVP2);
                                        $this->libro->getActiveSheet()->getStyle("E$i")->getNumberFormat()->setFormatCode("$#,##0.00;-$#,##0.00");
                                        $this->libro->getActiveSheet()->setCellValue("F".$i, $precios_costos[0]->PVP3);
                                        $this->libro->getActiveSheet()->getStyle("F$i")->getNumberFormat()->setFormatCode("$#,##0.00;-$#,##0.00");

                                        $this->libro->getActiveSheet()->setCellValue("S".$i, $familia);
                                        $this->libro->getActiveSheet()->setCellValue("T".$i, $subFamilia);
                                        $this->libro->getActiveSheet()->setCellValue("U".$i, isset($existenciaArticulo[0]->STOCK) ? $existenciaArticulo[0]->STOCK : '0' );
                                        $sumatoriaVentas += $infoVenta['CANTIDAD'];
                                    }
                                    $promedioVentas = $sumatoriaVentas /6;
                                    $productoEnMeses = sizeof( $producto['VENTA']);
                                    if ( $productoEnMeses > 2) {
                                        $stock =  $this->libro->getActiveSheet()->getCell( "U$i")->getValue();

                                        $this->libro->getActiveSheet()->setCellValue("V".$i, round($promedioVentas) );
                                        if ( $stock < $promedioVentas) {
                                            $this->libro->getActiveSheet()->setCellValue("W".$i, "=ABS(U$i-V$i)");
                                        } else{
                                            $this->libro->getActiveSheet()->setCellValue("W".$i, "0");
                                        }                                    
                                        
                                    }else{
                                        $this->libro->getActiveSheet()->setCellValue("V".$i, 0 );
                                        $this->libro->getActiveSheet()->setCellValue("W".$i, 0);                                        
                                    }
                                    $this->libro->getActiveSheet()->getStyle("U$i:W$i")->applyFromArray($this->centrarTexto);
                                
                                $i++;
                                }
                            }
                        }
                        $lastRow = $i;
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
                    $this->libro->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('O')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('P')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
                    $this->libro->getActiveSheet()->getColumnDimension('r')->setAutoSize(true);

                    $this->putLogo("E1",300,150);
                    $this->libro->getActiveSheet()->getStyle("A8:W".($i-1))->applyFromArray($this->bordes);
                }                
        }
        $reporteTerminado = new \PHPExcel_Writer_Excel2007( $this->libro);
        // ob_end_clean();
        $reporteTerminado->setPreCalculateFormulas(true);
        $reporteTerminado->save("reportePedidosSugeridos.xlsx");
        return $ventasAgrupadas;

    }
}


$reporte = new PedidoSugeridoReporte;
($reporte->generarReporte() );

$configCorreo = array("descripcionDestinatario" => "Reporte de Pedidos Sugeridos por Sucursal",
                                       "mensaje" => "...",
                                       "pathFile" => "reportePedidosSugeridos.xlsx",
                                       "subject" => "Reporte de Pedidos Sugeridos por Sucursal",
                                       "correos" => array('sestrada@matrix.com.mx', "almacenes@matrix.com.mx", "compras@matrix.com.mx", "gerenteventas@matrix.com.mx", "gerenteventasnorte@matrix.com.mx", "gerentecomercialaltos@matrix.com.mx", "raulmatrixxx@hotmail.com", "luisimatrix@hotmail.com")
                                     );
// $reporte->enviarReporte( $configCorreo);