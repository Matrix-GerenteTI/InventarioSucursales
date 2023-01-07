<?php

require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Controllers/prepareExcel.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";

class ReporteStockFamilia extends PrepareExcel
{
    public function generaReporte( $familia)
    {
        $inventarios = new Inventario;
        $stockFamilia = $inventarios->stockAllAlmacenesbyFamilias( $familia);

        $stockAgrupado = array();
        $arrayColumnaAlmacen = array();
        $columnasReservadas = array('E','F','G','H','I','J','K','L','M','N','O','P','Q','R');
        $j = 0;

        foreach ($stockFamilia as $idx => $productos) {
            if ( isset($stockAgrupado[$productos->SUBFAMILIA][$productos->CODIGOARTICULO]['ALMACENES'][$productos->ALMACEN]) ){
                $stockAgrupado[$productos->SUBFAMILIA][$productos->CODIGOARTICULO]['ALMACENES'][$productos->ALMACEN]['STOCK'] += $productos->STOCK;
            }else {
                $stockAgrupado[$productos->SUBFAMILIA][$productos->CODIGOARTICULO]['ALMACENES'][$productos->ALMACEN]['STOCK']  = $productos->STOCK;
                $stockAgrupado[$productos->SUBFAMILIA][$productos->CODIGOARTICULO]['ALMACENES'][$productos->ALMACEN]['DESCRIPCION'] = $productos->DESCRIPCION;
                
                if ( $productos->ALMACEN != "APARTADOS_MATRIZ" && $productos->ALMACEN != "PRESTAMOS CENTRO" ) {
                    if ( !isset( $arrayColumnaAlmacen[$productos->ALMACEN])) {
                        $arrayColumnaAlmacen[$productos->ALMACEN] = $columnasReservadas[$j];
                        $j++;
                    }
                }


            }
        }

        $this->creaEmptySheet($familia ,0);

        // for ($count=1; $count < 10 ; $count++) { 
        //     $this->libro->getActiveSheet()->freezePane('A'.$count);
        // }

        $this->libro->getActiveSheet()->setCellValue("D5", "REPORTE DE STOCK POR $familia DEL ".date('d/m/Y'));
        $this->libro->getActiveSheet()->mergeCells("D5:H5");
        $this->libro->getActiveSheet()->getStyle("D5")->applyFromArray($this->labelBold);
        $this->libro->getActiveSheet()->getStyle("D5")->applyFromArray($this->centrarTexto);                

        $this->libro->getActiveSheet()->setCellValue("A8", "CODIGO");
        $this->libro->getActiveSheet()->setCellValue("B8", "DESCRIPCION");
        $this->libro->getActiveSheet()->setCellValue("C8", "FAMILIA");
        $this->libro->getActiveSheet()->setCellValue("D8", "SUBFAMILIA");
        
        $this->libro->getActiveSheet()->getStyle("A8:D8")->applyFromArray($this->labelBold);
        $this->libro->getActiveSheet()->getStyle("A8:D8")->applyFromArray($this->centrarTexto);
         $this->libro->getActiveSheet()->getStyle('A8:D8')->applyFromArray($this->borderBottom);   
         

        foreach ($arrayColumnaAlmacen as $almacen => $columna) {
            $this->libro->getActiveSheet()->setCellValue($columna."8",  $almacen);
            $this->libro->getActiveSheet()->getStyle($columna."8")->applyFromArray($this->labelBold);
             $this->libro->getActiveSheet()->getStyle($columna."8")->applyFromArray($this->centrarTexto);     
            $this->libro->getActiveSheet()->getColumnDimension($columna)->setAutoSize(true);
             $this->libro->getActiveSheet()->getStyle($columna."8")->applyFromArray($this->borderBottom);
        }

        $i = 9;
        foreach ($stockAgrupado as $subFamilia => $articulo) {
            foreach ($articulo as $codigo => $almacenes) {
                $this->libro->getActiveSheet()->setCellValue("A$i",$codigo );
                $this->libro->getActiveSheet()->setCellValue("C$i",$familia );
                $this->libro->getActiveSheet()->setCellValue("D$i",$subFamilia );
                $this->libro->getActiveSheet()->setCellValue("D$i",$subFamilia );                
                foreach ($almacenes['ALMACENES'] as $almacen => $contenidoAlmacen) {
                    if ( $almacen != "APARTADOS_MATRIZ" && $almacen!= "PRESTAMOS CENTRO" ) {

                        $this->libro->getActiveSheet()->setCellValue("B$i",utf8_encode( $contenidoAlmacen['DESCRIPCION'] ));
                        $this->libro->getActiveSheet()->setCellValue($arrayColumnaAlmacen[$almacen].$i,$contenidoAlmacen['STOCK'] );
                        $this->libro->getActiveSheet()->getStyle($arrayColumnaAlmacen[$almacen].$i)->applyFromArray($this->centrarTexto);
                    }
                
                }
                $i++;
            }
        }
        
            $this->putLogo("D1",300,150);
            
            foreach ($arrayColumnaAlmacen as $almacen => $columna) {
                $k= 9;
                while ($k< $i) {
                    $stock =  $this->libro->getActiveSheet()->getCell( $columna."$k")->getValue();
                    if ( $stock == NULL || $stock == '') {
                        $stock =  $this->libro->getActiveSheet()->setCellValue( $columna."$k",'0');
                        $this->libro->getActiveSheet()->getStyle($arrayColumnaAlmacen[$almacen].$k)->applyFromArray($this->centrarTexto);
                    }
                    $k++;
                }
            }
            // $this->libro->getActiveSheet()->getStyle("A8:F$i")->applyFromArray($this->bordes);
            // $this->libro->getActiveSheet()->getStyle("D9:F$i")->applyFromArray($this->centrarTexto); 
            $this->libro->getActiveSheet()->getColumnDimension('A')->setAutoSize(false);
            $this->libro->getActiveSheet()->getColumnDimension('A')->setWidth("10");
            $this->libro->getActiveSheet()->getColumnDimension('B')->setAutoSize(false);
            $this->libro->getActiveSheet()->getColumnDimension('B')->setWidth("40");
            $this->libro->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
            $this->libro->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
            

        $reporteTerminado = new \PHPExcel_Writer_Excel2007( $this->libro);
        // ob_end_clean();
        $reporteTerminado->setPreCalculateFormulas(true);
        $reporteTerminado->save("reporteStockFamilias.xlsx");               
    }


}

$reporte = new ReporteStockFamilia;
$reporte->generaReporte('RIN');