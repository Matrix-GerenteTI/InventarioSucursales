<?php
date_default_timezone_set("America/Mexico_City");
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";

class InventariosController 
{
    public static function getSucursales(){
        $inventarios = new Inventario;
        return $inventarios->getSucursalesConAlmacen();
    }

    public static function getFamilias(){
        $inventarios = new Inventario;
        return $inventarios->getFamilias();
    }

    public function esInventarioRepetido( $sucursal, $familia, $tipo)
    {
        $inventarios = new Inventario;
        $inventarioRealizado = $inventarios->getInventarioRealizado( array('sucursal' => $sucursal,
                                                'familia' =>$familia, 'tipo' => $tipo, 'fecha' => date('Y-m-d') ));

        $productoStockDiferente = array();
        foreach ($inventarioRealizado as $idx => $itemRealizado) {
            if ( $itemRealizado['fisico2'] == NULL ) {
                if ($this->comparaStocks( $itemRealizado['fisico'], $itemRealizado['stock'])) {

                    array_push( $productoStockDiferente, $this->parseAttributesUppercase($itemRealizado, "fisico2"));
                }
            }elseif($itemRealizado['fisico3'] == NULL){
                if ($this->comparaStocks( $itemRealizado['fisico2'], $itemRealizado['stock'])) {
                    array_push( $productoStockDiferente, $this->parseAttributesUppercase($itemRealizado, "fisico3"));
                }                
            }
        }
        
        if ( sizeof($productoStockDiferente) ){
            return $productoStockDiferente;
        }elseif( sizeof($inventarioRealizado) == 0){
            return array("empty"=> '0');
        }else{
            return array("error"=> '1');
        }
        
    }

    public function parseAttributesUppercase( $item, $campo)
    {
            $producto['CODIGOARTICULO'] = $item['codigo'];
            $producto['DESCRIPCION'] = $item['descripcion'] ;
            $producto['FAMILIA'] = $item['familia'];
            $producto['SUBFAMILIA'] = $item['subfamilia'];
            $producto['STOCK'] = $item['stock'];
            $producto['id'] = $item['id'];
            $producto['campo'] = $campo;

            return $producto;
    }
    public function comparaStocks( $stockFisico, $stockSistema)
    {
        if ( $stockFisico != $stockSistema) {
            return true;
        }
        return false;
    }

    public function generaInventarioRandom($sucursal, $familia )
    {
        $selectionCount = 0;
        $inventarios = new Inventario;
        $productos = $inventarios->getStockFamilia($sucursal, $familia);

        $inventarioRegistrado = $this->esInventarioRepetido($sucursal, $familia, 2);

        if ( isset($inventarioRegistrado['error']) ) {
            
            return $inventarioRegistrado;
        } elseif( isset($inventarioRegistrado[0]) ) {
            return $inventarioRegistrado;
        }

        $totalProductos = sizeof( $productos);
        $sizeMuestra = $totalProductos* 0.1;
        $cuentaIndices = 0;
        $indicesMuestaList = array();
        $arrayProductosMuestra = array();

        foreach ($productos as $idx => $producto) {
            $productos[$idx]->DESCRIPCION = utf8_decode( $producto->DESCRIPCION);
            $productos[$idx]->CODIGOARTICULO = utf8_decode( $producto->CODIGOARTICULO );
            $productos[$idx]->SUBFAMILIA = utf8_decode( $producto->SUBFAMILIA );
        }
        
        $arrayProductosAgrupados =  array();
        if ( $familia == 'COLISION'|| $familia == 'ACCESORIO') {

            foreach ($productos as $i => $producto) {
                if ( isset($arrayProductosAgrupados[$familia][$producto->SUBFAMILIA]) ){
                     array_push($arrayProductosAgrupados[$familia][$producto->SUBFAMILIA], $producto);
                } else {
                   $arrayProductosAgrupados[$familia][$producto->SUBFAMILIA] = array($producto);
                }   
            }
            $subFamilias = $arrayProductosAgrupados[$familia];
            foreach ($subFamilias as $subfamilia => $contenio) {
                $totalProductos = sizeof($contenio);
                $sizeMuestra = round( ($totalProductos * 0.02), 1 );

                $countindex = 0;
                $arrayIndicesTemp = array();
                while ($countindex < $sizeMuestra) {
                    $indice = rand(0, $totalProductos-1);
                    if ( !in_array( $indice, $arrayIndicesTemp) ) {
                        array_push( $indicesMuestaList, $indice);
                        array_push( $arrayProductosMuestra,$subFamilias[$subfamilia][$indice]);
                        $countindex++;
                    }
                }
            }
        }
        else{
            while ( $cuentaIndices < $sizeMuestra) {
                $indice = rand( 0, $totalProductos-1 );
                if ( !in_array($indice, $indicesMuestaList) ) {
                    array_push( $indicesMuestaList, $indice);
                    $cuentaIndices++;
                }

            }

            foreach ($indicesMuestaList as $index) {
                array_push( $arrayProductosMuestra, $productos[$index]);
            }
      }
        
        return ( $arrayProductosMuestra);

    }

    public function generaInventarioGeneral( $sucursal, $familia)
    {
        $inventarios = new Inventario;
        $productos = $inventarios->getStockGeneral( $sucursal, $familia);
        $inventarioRegistrado = $this->esInventarioRepetido($sucursal, $familia, 1);
        if ( isset($inventarioRegistrado['error']) ) {
            
            return $inventarioRegistrado;
        } elseif( isset($inventarioRegistrado[0]) ) {
            return $inventarioRegistrado;
        }
        
        foreach ($productos as $i => $item) {
            $productos[$i]->DESCRIPCION = utf8_decode( $item->DESCRIPCION);
            $productos[$i]->CODIGOARTICULO = utf8_decode( $item->CODIGOARTICULO );
            $productos[$i]->SUBFAMILIA = utf8_decode( $item->SUBFAMILIA );
        }         
        
        return $productos;
    }
    public function registraInventario( $data)
    {
        $inventarios = new Inventario;
        return $inventarios->registraInventario( $data);
    }

    public function RegistraInventarioGeneral( $productos)
    {
        $inventarios = new Inventario;
        return $inventarios->RegistraInventarioGeneral( $productos);
    }
    public function actualizaRevisionInventario($data)
    {
        $inventarios = new Inventario;
        return $inventarios->actualizaRevisionInventario( $data);        
    }
    
}


if ( isset($_GET['opc']))  {
    $inventario = new InventariosController;
    switch ($_GET['opc']) {
        case 'sucursales':
            echo json_encode( InventariosController::getSucursales() );
            break;
        case 'familias':
            echo json_encode( InventariosController::getFamilias() );
            break;
        case 'aleatorio':

            echo json_encode ( $inventario->generaInventarioRandom($_GET['sucursal'], $_GET['familia']) );
            break;
        case 'general':
            echo json_encode( $inventario->generaInventarioGeneral($_GET['sucursal'], $_GET['familia']) );
            break;
        default:
            # code...
            break;
    }
}else{
   $inventarioData =  ( json_decode(file_get_contents('php://input'), true) );
   $inventarioContent = $inventarioData['inventario'];
    $tipoOperacion = $inventarioData['action'];
   $cuentaRegistros = 0;
   if ($tipoOperacion > 0) {
        foreach ($inventarioContent as $index => $item) {
            if (InventariosController::actualizaRevisionInventario($item) )
                $cuentaRegistros++;
        }
   } else {
        echo InventariosController::RegistraInventarioGeneral($inventarioContent);
    
   }
   

   

}