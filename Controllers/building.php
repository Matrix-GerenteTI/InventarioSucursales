<?php

require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";


class InventarioController  
{
    public function getArticulos( $sucursal)
    {
        $inventarioModel = new Inventario;
        $listadoGralArticulos = ( $inventarioModel->getArticulosNoInventariados( $sucursal ) );
        //generando los indices aleatorios 
        $rand = range(0, sizeof( ( $listadoGralArticulos))-1 );
        // shuffle($rand);
        $idx = 0;
        $productos = [];
        foreach ($listadoGralArticulos as $i => $articulo) {
                $listadoGralArticulos[$i]->DESCRIPCION = utf8_encode( $listadoGralArticulos[$i]->DESCRIPCION );
                if ( !isset( $productos[$listadoGralArticulos[$i]->FAMILIA."_ ".$listadoGralArticulos[$i]->SUBFAMILIA] ) ) {
                    $productos[$listadoGralArticulos[$i]->FAMILIA."_ ".$listadoGralArticulos[$i]->SUBFAMILIA] = [ $listadoGralArticulos[$i ] ];
                }else{
                    array_push( $productos[$listadoGralArticulos[$i]->FAMILIA."_ ".$listadoGralArticulos[$i]->SUBFAMILIA] , $listadoGralArticulos[$i ]);
                }
        }
        //obteniendo los 50 items que se presentarán en el inventario aleatorio de la sucursal
        krsort( $productos ); //se ordenan los datos por familia y subfamilia

        $lista_articulos_inventariar = [];

        foreach ($productos as $items) {
            foreach ($items as $producto) {
                if ( $idx < 50) {
                    array_push($lista_articulos_inventariar, $producto);
                } else {
                    break;
                }
                
            }
        }

        return ( $lista_articulos_inventariar  );
    }
}

InventarioController::getArticulos( 10772 );