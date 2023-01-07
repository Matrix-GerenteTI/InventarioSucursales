<?php

require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";
require_once $_SERVER['DOCUMENT_ROOT']."/intranet/modelos/nomina/trabajadores.php";


class FaltanteInventario  
{

    protected $modeloInventario;
    protected $modeloTrabajador ;

    public function __construct( )
    {
        $this->modeloInventario = new Inventario;
        $this->modeloTrabajador = new  Trabajador;
    }
    
    public function SetAccionCorrectiva( $fecha )
    {
        //obtenemos las susucrsales que tengan almacen de herramientas
        $listaAlmacenesHerramientas = $this->modeloInventario->getAlmacenesHerramientas();

        foreach ($listaAlmacenesHerramientas as $almacen ) {
            //comprobando que  no haya acciones correctivas repetida
            $inventarioHerramientas = $this->modeloInventario->getInventarioHerramienta( $almacen->ID , $fecha );
            $sancionAplicada = false;

            if ( sizeof( $inventarioHerramientas )  > 0 ) {
                foreach ( $inventarioHerramientas  as $herramienta) {
                    if (  $herramienta['fisico3'] != null ) {
                        $diferencia = $herramienta['fisico3']  -$herramienta['stock'];
                        if ( $diferencia < 0 ) {
                            //Aplica descuento por el monto y la canridad
                            if( $sancionAplicada == false ){
                                $this->modeloInventario->setAccionCorrectivaInventario( $herramienta['idpadre'] , 1);
                                $sancionAplicada = true;
                            }
                            //obteniendo el precio de la herramienta que hace falta
                            $costoHerramienta = $this->modeloInventario->getCostoHerramienta( $herramienta['codigo'] , $herramienta['idalmacen'] );

                            $totalDescuento = abs( $costoHerramienta[0]->CTOPROMEDIO * $diferencia );
                            $cantDiasMes = cal_days_in_month(CAL_GREGORIAN,  date('m'), date('Y') );
                            $fechaDescuento = date('d') <= 15 ? date("Y-m-15") :  date("Y-m-$cantDiasMes") ;
                            $this->modeloTrabajador->aplicaAccionCorrectiva([
                                'empleado' => $herramienta['nip'],
                                'puesto' => $herramienta['idpuesto'],
                                'sucursal' => $herramienta['idsucursal'],
                                'fechaIncidencia' => date("Y-m-d"),
                                'motivo' => "FALTANTE DE ".abs($diferencia)."  pieza(s) de ".$herramienta['descripcion'],
                                'plan' => "Se comprará nuevamente la herramienta con el monto descontado",
                                'aplicaSancion' => $fechaDescuento,
                                'monto' => $totalDescuento
                            ]);

                        }
                    } else if( $herramienta['fisico2'] != null )  {
                        $diferencia = $herramienta['fisico2']  -$herramienta['stock'];
                        if ( $diferencia < 0 ) {
                            //Aplica descuento por el monto y la canridad
                            if( $sancionAplicada == false ){
                                $this->modeloInventario->setAccionCorrectivaInventario( $herramienta['idpadre'] , 1);
                                $sancionAplicada = true;
                            }                            
                            $costoHerramienta = $this->modeloInventario->getCostoHerramienta( $herramienta['codigo'] , $herramienta['idalmacen'] );

                            $totalDescuento = abs( $costoHerramienta[0]->CTOPROMEDIO * $diferencia );
                            $cantDiasMes = cal_days_in_month(CAL_GREGORIAN,  date('m'), date('Y') );
                            $fechaDescuento = date('d') <= 15 ? date("Y-m-15") :  date("Y-m-$cantDiasMes") ;
                            $this->modeloTrabajador->aplicaAccionCorrectiva([
                                'empleado' => $herramienta['nip'],
                                'puesto' => $herramienta['idpuesto'],
                                'sucursal' => $herramienta['idsucursal'],
                                'fechaIncidencia' => date("Y-m-d"),
                                'motivo' => "FALTANTE DE ".abs($diferencia)."  pieza(s) de ".$herramienta['descripcion'],
                                'plan' => "Se comprará nuevamente la herramienta con el monto descontado",
                                'aplicaSancion' => $fechaDescuento,
                                'monto' => $totalDescuento
                            ]);
                        }
                    }else{
                        $diferencia = $herramienta['fisico']  -$herramienta['stock'];
                        if ( $diferencia < 0 ) {
                            //Aplica descuento por el monto y la canridad
                            if( $sancionAplicada == false ){
                                $this->modeloInventario->setAccionCorrectivaInventario( $herramienta['idpadre'] , 1);
                                $sancionAplicada = true;
                            }       
                            $costoHerramienta = $this->modeloInventario->getCostoHerramienta( $herramienta['codigo']  , $herramienta['idalmacen'] );

                            $totalDescuento = abs( $costoHerramienta[0]->CTOPROMEDIO * $diferencia );
                            $cantDiasMes = cal_days_in_month(CAL_GREGORIAN,  date('m'), date('Y') );
                            $fechaDescuento = date('d') <= 15 ? date("Y-m-15") :  date("Y-m-$cantDiasMes") ;
                            $this->modeloTrabajador->aplicaAccionCorrectiva([
                                'empleado' => $herramienta['nip'],
                                'puesto' => $herramienta['idpuesto'],
                                'sucursal' => $herramienta['idsucursal'],
                                'fechaIncidencia' => date("Y-m-d"),
                                'motivo' => "FALTANTE DE ".abs($diferencia)."  pieza(s) de ".$herramienta['descripcion'],
                                'plan' => "Se comprará nuevamente la herramienta con el monto descontado",
                                'fechaDescuento' => $fechaDescuento,
                                'monto' => $totalDescuento
                            ]);                     

                            exit();
                        }
                    }
                    
                }
            }
        }
        

    }
}

$faltanteInventario = new FaltanteInventario;
$faltanteInventario->SetAccionCorrectiva( date('Y-m-d') );
