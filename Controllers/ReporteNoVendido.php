<?php
ini_set('memory_limit', '1024M');
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Controllers/prepareExcel.php";
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";

class ReporteStock extends PrepareExcel
{
    public function generaReporte()
    {
        $inventarios = new Inventario;
        $sucursales = $inventarios->getSucursalesConAlmacen();
        $sheet= -1;
        foreach ($sucursales as $index => $sucursal) {
            $familias = $inventarios->getFamilias();

            $sheet++;
            $this->creaEmptySheet(str_replace(' ','',$sucursal->DESCRIPCION) ,$sheet);

            $i = 9;
            

            foreach ($familias as $key => $familia) {
                $productos =  $inventarios->getStockPorSubfamilia($sucursal->ID, $familia->FAMILIA);
                
                $this->libro->getActiveSheet()->setCellValue("B5", "REPORTE DE STOCK POR SUBFAMILIA");
                $this->libro->getActiveSheet()->mergeCells("B5:D5");
                $this->libro->getActiveSheet()->getStyle("B5")->applyFromArray($this->labelBold);
                $this->libro->getActiveSheet()->getStyle("B5")->applyFromArray($this->centrarTexto);                

                $this->libro->getActiveSheet()->setCellValue("A8", "CODIGO");
                $this->libro->getActiveSheet()->setCellValue("B8", "FAMILIA");
                $this->libro->getActiveSheet()->setCellValue("C8", "SUBFAMILIA");
                $this->libro->getActiveSheet()->setCellValue("D8", "DESCRIPCION");
                $this->libro->getActiveSheet()->setCellValue("E8", "STOCK");
                $this->libro->getActiveSheet()->setCellValue("F8", "SOBRANTE");                
                $this->libro->getActiveSheet()->getStyle("A8:G8")->applyFromArray($this->labelBold);

                foreach ($productos as $idx => $producto) {
                    $this->libro->getActiveSheet()->setCellValue("A$i", $producto->CODIGOARTICULO);
                    $this->libro->getActiveSheet()->setCellValue("B$i", $producto->FAMILIA);
                    $this->libro->getActiveSheet()->setCellValue("C$i", $producto->SUBFAMILIA);
                    $this->libro->getActiveSheet()->setCellValue("D$i", utf8_encode( $producto->DESCRIPCION) );
                    $this->libro->getActiveSheet()->setCellValue("E$i", $producto->STOCK);
                    $this->libro->getActiveSheet()->setCellValue("F$i", "=E$i-1");                       
                    $i++;             
                }
            }

                $this->putLogo("B1",300,150);
                $this->libro->getActiveSheet()->getStyle("A8:F$i")->applyFromArray($this->bordes);
                $this->libro->getActiveSheet()->getStyle("E9:F$i")->applyFromArray($this->centrarTexto); 
                $this->libro->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
                $this->libro->getActiveSheet()->getColumnDimension('C')->setAutoSize(false);
                $this->libro->getActiveSheet()->getColumnDimension('C')->setWidth("12");
                $this->libro->getActiveSheet()->getColumnDimension('D')->setAutoSize(false);
                $this->libro->getActiveSheet()->getColumnDimension('D')->setWidth("50");
                $this->libro->getActiveSheet()->getColumnDimension('E')->setAutoSize(false);
                $this->libro->getActiveSheet()->getColumnDimension('E')->setWidth("10");           
                $this->libro->getActiveSheet()->getColumnDimension('F')->setAutoSize(false);
                $this->libro->getActiveSheet()->getColumnDimension('F')->setWidth("13");                            
   
        }
        $reporteTerminado = new \PHPExcel_Writer_Excel2007( $this->libro);
        // ob_end_clean();
        $reporteTerminado->setPreCalculateFormulas(true);
        $reporteTerminado->save("reporteStockSubfamilia.xlsx");        
    }
}


$reporte = new ReporteStock;
$reporte->generaReporte();

$configCorreo = array("descripcionDestinatario" => "Reporte de Stock por Subfamilia",
                                       "mensaje" => "...",
                                       "pathFile" => "reporteStockSubfamilia.xlsx",
                                       "subject" => "Reporte de Stock por Subfamilia",
                                       "correos" => array('sestrada@matrix.com.mx', "raulmatrixxx@hotmail.com","jefeinventarios@matrix.com.mx")
                                     );
$reporte->enviarReporte( $configCorreo);