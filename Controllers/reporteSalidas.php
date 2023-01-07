<?php 
    set_time_limit(0);
    ini_set('memory_limit', '2000000M');
    require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";
    require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Controllers/prepareExcel.php";

    

    class GenerarReporte extends PrepareExcel
    {
        private $meses = ['Enero',"Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
        private $columnasDias = ["","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP"];
        private $columnas = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O"];
        private $lista = array();
        public $host = "172.16.0.70:/var/lib/firebird/3.0/data/PREDICTION.FDB";
        public $user="SYSDBA";
        public $pass="masterkey";
        private $conexion;
        public function index()
        {

        }

        public function prepararReporte($year)
        {
            $this->conexion = @ibase_pconnect($this->host,$this->user,$this->pass) or die("Error al conectarse a la base de datos: ".ibase_errmsg());
            $mes = 0;
            $sheet= -1;
            $this->libro->getActiveSheet()->getColumnDimension( "A" )->setAutoSize(true);
            
            for ($b=0; $b < sizeof($this->meses); $b++) {
                $mes = $b + 1;
                $anio = $year;
                $today = date('d-m-Y') ;
                $almacenes = $this->getAlmacenes();
                
                $cantDiasMes = cal_days_in_month(CAL_GREGORIAN,$mes,$anio);
                $sheet++;
                $this->creaEmptySheet($this->meses[$mes-1],$sheet);
                $this->putLogo("F1",300,150);
                $this->libro->getActiveSheet()->mergeCells("B5:AP5");
                $this->libro->getActiveSheet()->setCellValue("B5","Reporte de Compras, Salidas realizados por sucursal: " . $this->getMesAsString($mes) . " $anio ");
                $this->libro->getActiveSheet()->getStyle("B5")->applyFromArray($this->labelBold);
                $this->libro->getActiveSheet()->getStyle("B5")->applyFromArray($this->centrarTexto); 
                
                $this->libro->getActiveSheet()->setCellValue("A8", "SUCURSAL");
                $this->libro->getActiveSheet()->setCellValue("B8","CÓDIGO");
                $this->libro->getActiveSheet()->setCellValue("C8","DESCRIPCIÓN");
                $this->libro->getActiveSheet()->setCellValue("D8","CANTIDAD");
                $this->libro->getActiveSheet()->mergeCells("E7:F7");
                $this->libro->getActiveSheet()->getStyle("A7:AT7")->applyFromArray($this->labelBold);
                $this->libro->getActiveSheet()->getStyle("A7:AT7")->applyFromArray($this->centrarTexto);
                $this->libro->getActiveSheet()->setCellValue("E7","ALTAS");
                $this->libro->getActiveSheet()->mergeCells("G7:H7");
                $this->libro->getActiveSheet()->setCellValue("G7","BAJAS");
                $this->libro->getActiveSheet()->setCellValue("E8","COSTO UNITARIO");
                $this->libro->getActiveSheet()->setCellValue("F8","TOTAL");
                $this->libro->getActiveSheet()->setCellValue("G8","COSTO UNITARIO");
                $this->libro->getActiveSheet()->setCellValue("H8","TOTAL");
                $this->libro->getActiveSheet()->setCellValue("I8","USUARIO");
                $this->libro->getActiveSheet()->setCellValue("J8","FECHA");
                $this->libro->getActiveSheet()->setCellValue("K8","MOTIVO");
                $this->libro->getActiveSheet()->getStyle("A8:AP8")->getFill()->applyFromArray( $this->setColorFill("DF013A") );
                $this->libro->getActiveSheet()->getStyle("A8:AP8")->applyFromArray($this->labelBold);
                $this->libro->getActiveSheet()->getStyle("A8:AP8")->applyFromArray($this->centrarTexto);       
                $this->libro->getActiveSheet()->getStyle("A8:AP8")->applyFromArray( $this->setColorText("ffffff",12) );

                $days = array('Sun'=> 'Domingo', 'Mon' =>'Lunes', 'Tue'=>'Martes', 'Wed'=>'Miercoles','Thu' => 'Jueves','Fri'=>'Viernes', 'Sat'=>'Sábado');
                for ($a=1; $a <= $cantDiasMes; $a++) { 
                    $diaSemana = date('D',strtotime(("$a-$mes-$anio")));
                    $diaSemana = $days[$diaSemana];
                    $this->libro->getActiveSheet()->setCellValue($this->columnasDias[$a]."7",$diaSemana);
                    $this->libro->getActiveSheet()->getStyle($this->columnasDias[$a]."7")->getAlignment()->setTextRotation(90);
                    $this->libro->getActiveSheet()->getRowDimension("7")->setRowHeight(60);
                    $this->libro->getActiveSheet()->getStyle($this->columnasDias[$a]."7")->applyFromArray( $this->labelBold);
                    $this->libro->getActiveSheet()->setCellValue($this->columnasDias[$a]."8", $a);
                    $this->libro->getActiveSheet()->getColumnDimension($this->columnasDias[$a])->setAutoSize(true);
                }
                $fila = 9;
                $filainicio = $fila;
                $entrada = 0;
                $entradalinea = 0;
                $salida = 0;
                $salidalinea = 0;
                for ($i=0; $i < sizeof($almacenes); $i++) {
                    $filainicio = $fila;
                    $movimientos = 0;
                    for ($j=1; $j <= $cantDiasMes; $j++) {
                        $fecha = $j . "." . $mes . "." . $anio;
                        $salidas = $this->obtenerConsulta($fecha,$almacenes[$i]['id']);
                        for ($k=0; $k < sizeof($salidas); $k++) { 
                            $contador = 0;
                            $filasalida = $fila;
                            for ($l=0; $l < sizeof($salidas[$k]['productos']); $l++) {
                                $fila++;
                                $contador++;
                                $movimientos++;
                                $this->libro->getActiveSheet()->setCellValue("B".$fila,$salidas[$k]['productos'][$l]['CODIGO']);
                                $this->libro->getActiveSheet()->setCellValue("C".$fila,$salidas[$k]['productos'][$l]['PRODUCTO']);
                                $this->libro->getActiveSheet()->setCellValue("D".$fila,$salidas[$k]['productos'][$l]['CANTIDAD']);
                                if ($salidas[$k]['salida']['status'] == 'SALIDA EMITIDO') {
                                    $this->libro->getActiveSheet()->setCellValue("G".$fila,$salidas[$k]['productos'][$l]["COSTO"]);
                                    $this->libro->getActiveSheet()->setCellValue("H".$fila,$salidas[$k]['productos'][$l]["COSTOLINEA"]);
                                    $salida += $salidas[$k]['productos'][$l]['COSTO'];
                                    $salidalinea += $salidas[$k]['productos'][$l]['COSTOLINEA'];
                                } else {
                                    $this->libro->getActiveSheet()->setCellValue("E".$fila,$salidas[$k]['productos'][$l]["COSTO"]);
                                    $this->libro->getActiveSheet()->setCellValue("F".$fila,$salidas[$k]['productos'][$l]["COSTOLINEA"]);
                                    $entrada += $salidas[$k]['productos'][$l]['COSTO'];
                                    $entradalinea += $salidas[$k]['productos'][$l]['COSTOLINEA'];
                                }
                                $this->libro->getActiveSheet()->setCellValue("I".$fila,$salidas[$k]['salida']['usuario']);
                                $this->libro->getActiveSheet()->setCellValue("J".$fila,$salidas[$k]['salida']['fecha']);
                                $this->libro->getActiveSheet()->setCellValue("K".$fila,$salidas[$k]['salida']['observaciones']);
                                $this->libro->getActiveSheet()->setCellValue($this->columnasDias[$j].$fila,"X");
                                $this->libro->getActiveSheet()->getStyle($this->columnasDias[$j].$fila)->applyFromArray($this->labelBold);
                                $this->libro->getActiveSheet()->getStyle($this->columnasDias[$j].$fila)->applyFromArray($this->centrarTexto);
                                $this->libro->getActiveSheet()->getStyle($this->columnasDias[$j].$fila)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
                                $this->libro->getActiveSheet()->getStyle($this->columnasDias[$j].$fila)->applyFromArray( $this->setColorText("ffffff",12) );
                                $this->libro->getActiveSheet()->setCellValue("A".$fila,$almacenes[$i]['almacen']);
                                $this->libro->getActiveSheet()->getStyle("A".$fila)->applyFromArray($this->labelBold);
                                
                            }
                        }
                    }
                    //$fila++;
                }
                $fila++;
                $this->libro->getActiveSheet()->setCellValue("D".$fila,"TOTAL");
                $this->libro->getActiveSheet()->getStyle("D".$fila)->applyFromArray($this->labelBold);
                $this->libro->getActiveSheet()->setCellValue("E".$fila,$entrada);
                $this->libro->getActiveSheet()->getStyle("E".$fila)->applyFromArray($this->labelBold);
                $this->libro->getActivesheet()->setCellValue("F".$fila,$entradalinea);
                $this->libro->getActiveSheet()->getStyle("F".$fila)->applyFromArray($this->labelBold);
                $this->libro->getActiveSheet()->setCellValue("G".$fila,$salida);
                $this->libro->getActiveSheet()->getStyle("G".$fila)->applyFromArray($this->labelBold);
                $this->libro->getActivesheet()->setCellValue("H".$fila,$salidalinea);
                $this->libro->getActiveSheet()->getStyle("H".$fila)->applyFromArray($this->labelBold);
                for ($c=0; $c < sizeof($this->columnas); $c++) { 
                    $this->libro->getActiveSheet()->getColumnDimension($this->columnas[$c])->setAutoSize(true);
                }
            }
            $archivo = "ReporteSalidasEntradas".$today.".xlsx";
            $reporteTerminado = new \PHPExcel_Writer_Excel2007($this->libro);
            $reporteTerminado->setPreCalculateFormulas(TRUE);
            $reporteTerminado->save($archivo);

            return $archivo;
        }

        public function getAlmacenes()
        {
            $query = "SELECT * FROM CFG_ALMACENES ca WHERE ca.PWD='MATRIX' AND ca.ACTIVO='S'";
            $setence = ibase_query($this->conexion,$query);
            $arreglo = [];
            $fila = 9;
            while($row = ibase_fetch_assoc($setence))
            {
                $almacen = [
                    'id' => $row['ID'],
                    'almacen' => $row['DESCRIPCION'],
                    'zona' => $row['ZONA'],
                    'fila' => $fila
                ];
                array_push($arreglo,$almacen);
                $fila++;
            }

            return $arreglo;
        }

        public function obtenerConsulta($fecha,$almacen)
        {
            
            /*if(isset($_GET['fecha']))
                $fecha = $_GET['fecha'];
            else
                $fecha = date('d.m.Y');*/
            //$fecha = '22.02.2022';
            
            $arrItems = array();
            /*$query = "SELECT c.ID as ID, c.NUMDOCTONC as NUMDOCTONC, c.FECHA as FECHA, c.STATUS as STATUS, c.OBSERVACIONES as OBSERVACIONES,
            d.CANTIDAD as CANTIDAD, d.COSTO as COSTO, d.COSTOLINEA as COSTOLINEA, d.DESCRIPCION as PRODUCTO, d.CODIGO as CODIGO
            FROM REF_DETCOMPRASTRASPREGS d  
            INNER JOIN REF_COMPRASTRASPREGS c d ON c.ID=d.FKPADREF_COMPRASTRASPREGS WHERE c.STATUS='SALIDA EMITIDO' ORDER BY c.ID";*/
            $query = "SELECT c.ID as ID, c.NUMDOCTO as NUMDOCTO, c.FECHA as FECHA, c.STATUS as STATUS, c.OBSERVACIONES as OBSERVACIONES, c.IMPIVA as IVA, c.SUBTOTAL as SUBTOTAL, c.TOTAL as TOTAL, cu.USU_NOMBRE as NOMBRE, cu.USU_APELPAT as APELLIDO, ca.DESCRIPCION AS ALMACEN
            FROM REF_COMPRASTRASPREGS c INNER JOIN CFG_USUARIOS cu ON cu.ID_USUARIO=c.FK1MCFG_USUARIOS INNER JOIN CFG_ALMACENES ca ON ca.ID=c.FK1MCFG_ALMACENES WHERE c.FECHA='".$fecha."' AND c.FK1MCFG_ALMACENES=".$almacen." AND (c.STATUS='SALIDA EMITIDO' OR c.STATUS='COMPRA EMITIDO' OR c.STATUS='ENTRADA EMITIDO')  ORDER BY c.FK1MCFG_ALMACENES";
            $setence = ibase_query($this->conexion,$query);
            $arreglo = [];
            while($row = ibase_fetch_assoc($setence))
            {
                $productos = [];
                $salida = [
                    'almacen' => $row['ALMACEN'],
                    'id' => $row['ID'],
                    'numdocto' => $row['NUMDOCTO'],
                    'fecha' => $row['FECHA'],
                    'status' => $row['STATUS'],
                    'observaciones' => $row['OBSERVACIONES'],
                    'usuario' => $row['NOMBRE'] . " " . $row["APELLIDO"],
                    'total' => $row['TOTAL'],
                    'subtotal' => $row['SUBTOTAL'],
                    'iva' => $row['IVA'],
                ];
                if (!in_array($row['ID'],$arrItems)) {
                    $arrItems[] = $row['ID'];
                    $subquery = "SELECT * FROM REF_DETCOMPRASTRASPREGS d WHERE d.FKPADREF_COMPRASTRASPREGS=".$row['ID']."";
                    $sentencia = ibase_query($this->conexion,$subquery);
                    while($dp = ibase_fetch_assoc($sentencia))
                    {
                        $producto = [
                            'CODIGO' => $dp['CODIGO'],
                            'PRODUCTO' => $dp['DESCRIPCION'],
                            'CANTIDAD' => $dp['CANTIDAD'],
                            'COSTO' => $dp['COSTO'],
                            'COSTOLINEA' => $dp['COSTOLINEA']
                        ];
                        array_push($productos,$producto);
                    }
                    $array = [
                        'salida' => $salida,
                        'productos' => $productos,
                    ];
                    array_push($arreglo,$array);
                }
                
            }
            //echo json_encode($arreglo);
            
            $html = "<table>
            <thead>
                <tr>
                    <th>SUCURSAL</th>
                    <th>ID</th>
                    <th>DOCUMENTO</th>
                    <th>FECHA</th>
                    <th>MOTIVO</th>
                    <th>STATUS</th>
                    <th>SUBTOTAL</th>
                    <th>IVA</th>
                    <th>TOTAL</th>
                    <th></th>
                    <th>USUARIO</th>
                </tr>
            </thead>
            <tbody>";
            for ($i=0; $i < sizeof($arreglo); $i++) { 
                $html.="<tr>
                        <td>".$arreglo[$i]['salida']['almacen']."</td>
                        <td>".$arreglo[$i]['salida']['id']."</td>
                        <td>".$arreglo[$i]['salida']['numdocto']."</td>
                        <td>".$arreglo[$i]['salida']['fecha']."</td>
                        <td>".$arreglo[$i]['salida']['observaciones']."</td>
                        <td>".$arreglo[$i]['salida']['status']."</td>
                        <td>$".number_format($arreglo[$i]['salida']['subtotal'],2,'.',',')."</td>
                        <td>$".number_format($arreglo[$i]['salida']['iva'],2,'.',',')."</td>
                        <td>$".number_format($arreglo[$i]['salida']['total'],2,'.',',')."</td>
                        <td>
                            <table>
                                <thead>
                                    <th>CODIGO</th>
                                    <th>PRODUCTO</th>
                                    <th>CANTIDAD</th>
                                    <th>COSTO</th>
                                    <th>COSTO LINEA</th>
                                </thead>
                                ";
                for ($j=0; $j < sizeof($arreglo[$i]['productos']); $j++) { 
                    $html.= "<tr>
                        <td>".$arreglo[$i]['productos'][$j]['CODIGO']."</td>
                        <td>".$arreglo[$i]['productos'][$j]['PRODUCTO']."</td>
                        <td>".$arreglo[$i]['productos'][$j]['CANTIDAD']."</td>
                        <td>".$arreglo[$i]['productos'][$j]['COSTO']."</td>
                        <td>".$arreglo[$i]['productos'][$j]['COSTOLINEA']."</td>
                    </tr>";
                }
                $html.="</table>
                        </td>
                        <td>".$arreglo[$i]['salida']['usuario']."</td>
                    </tr>";
                
            }

            $html.= "</tbody>
            </table>";
            $html.= "<style>
            table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
            height: auto;
            width: auto;
            }
            </style>";
            echo $html;
            return $arreglo;
        }
    }

    $reporte = new GenerarReporte;

    if(isset($_GET['year']))
        $year = $_GET['year'];
        
    $year = date('Y');
    $report = $reporte->prepararReporte($year);
    $mails = array('gerenteti@matrix.com.mx','ti@matrix.com.mx','compras@matrix.com.mx',"gerente_auditoria@matrix.com.mx","director@matrix.com.mx");
    $configCorreo = array("descripcionDestinatario" => "Reporte de Salidas, Entradas",
                                       "mensaje" => "<b>Reporte de Salidas, Entradas del sistema Prediction:</b><p></p>",
                                       "pathFile" => $report,
                                       "subject" => "Reporte de Salidas, Entradas",
                                       "correos" => $mails
                                     );
    $reporte->enviarReporte( $configCorreo);  
?>