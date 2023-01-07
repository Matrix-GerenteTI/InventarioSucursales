<?php
session_start();
date_default_timezone_set("America/Mexico_City");
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";

class InventariosController 
{
	
    public static function getSucursales(){
        $inventarios = new Inventario;
        $sucursales = $inventarios->getSucursalesConAlmacen();
        foreach ($sucursales as $i => $sucursal) {
            $sucursales[$i]->DESCRIPCION = utf8_decode( $sucursal->DESCRIPCION ); 
        }
        return $sucursales;
    }

    public static function getFamilias( $tipoinventario, $sucursal ){
        $inventarios = new Inventario;
        return $inventarios->getFamilias($tipoinventario, $sucursal);
    }

    public function getSubfamilias( $tipoinventario, $sucursal, $familia )
    {
        $inventarios = new Inventario;
        $subfamilias =  $inventarios->getSubfamilias( $tipoinventario, $sucursal, $familia );
        foreach ($subfamilias as $i => $subfamilia) {
            $subfamilias[$i]->SUBFAMILIA = utf8_decode( $subfamilia->SUBFAMILIA );
        }
        return $subfamilias;
    }

    public function getInfoArticulo( $codigo , $sucursal)
    {
        $inventarios = new Inventario;
        $codigo = str_replace("'", "-", $codigo );
        
        $item = $inventarios->getInfoArticulo( strtoupper( $codigo ), $sucursal );
        foreach ($item as $index => $articulo) {
            $item[$index]->DESCRIPCION = utf8_decode( $articulo->DESCRIPCION );
            $item[$index]->SUBFAMILIA = utf8_decode( $articulo->SUBFAMILIA );
        }

        return $item;
    }

    public function getInventarioProgramadoSucursal( $sucursal )
    {
        $inventario = new Inventario;
        $fechaActual = date('Y-m-d');
        return $inventario->getInventarioProgramadoSucursal( $sucursal, $fechaActual );
    }

    public function esInventarioRepetido( $sucursal, $familia, $subfamilia, $tipo)
    {
        $inventarios = new Inventario;
        $inventarioRealizado = $inventarios->getInventarioRealizado( array('sucursal' => $sucursal,
                                                'familia' =>$familia, 'subfamilia' => $subfamilia , 'tipo' => $tipo, 'fecha' => date('Y-m-d') ));

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

    public function generaInventarioRandom($sucursal, $familia , $subfamilia)
    {
        $selectionCount = 0;
        $inventarios = new Inventario;
        $productos = $inventarios->getStockFamilia($sucursal, $familia, $subfamilia);

        $inventarioRegistrado = $this->esInventarioRepetido($sucursal, $familia, $subfamilia, 3);

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

            if ( sizeof($productos) === 0 ) {
                return array();
            }
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
        sort( $arrayProductosMuestra);
        return ( $arrayProductosMuestra);

    }

        public function getArticulosInventarioAleatorio( $sucursal)
    {
        $inventarioModel = new Inventario;

        $revisionInventario = $this->getStatusRealizacionAleatorio( $sucursal);

        if ( $revisionInventario['inventario'] == 1) {
            
            return $revisionInventario['items'];
        }

        $listadoGralArticulos = ( $inventarioModel->getArticulosNoInventariados( $sucursal ) );
        
        
        // verificando que si no hay articulos por inventariar, en caso de ser verdadero elimina de la tabla temporal de inventarios los articulos  y vuelve a cargar la lista
        if ( sizeof( $listadoGralArticulos ) === 0) {
            $inventarioModel->reiniciaInventariosDeSucursal( $sucursal );
        }
        

        //generando los indices aleatorios 
        // $rand = range(0, sizeof( ( $listadoGralArticulos))-1 );
        // shuffle($rand);
        $idx = 0;
        $productos = [];
        foreach ($listadoGralArticulos as $i => $articulo) {
                $listadoGralArticulos[$i]->DESCRIPCION = utf8_encode( $listadoGralArticulos[$i]->DESCRIPCION );
                $listadoGralArticulos[$i]->CODIGOARTICULO = utf8_encode( $listadoGralArticulos[$i]->CODIGOARTICULO );
                $listadoGralArticulos[$i]->SUBFAMILIA = utf8_encode( $listadoGralArticulos[$i]->SUBFAMILIA );

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
                    $idx++;
                    array_push($lista_articulos_inventariar, $producto);
                } else {
                    break;
                }
                
            }
        }
        

        return ( $lista_articulos_inventariar  );
    }

    public function getStatusRealizacionAleatorio($sucursal)
    {
        $inventario = new Inventario();
        $ultimoInventario = $inventario->getLastInventarioRandom( $sucursal );
        $realizacionInventario = 0;

        if ( sizeof( $ultimoInventario ) ) {
            foreach ($ultimoInventario as  $itemInventario) {
                
                $fechaCapturaMilis = strtotime( $itemInventario['fechaCaptura'] );
                $fecha = date( "Y-m-d", $fechaCapturaMilis );
                if ( $fecha == date('Y-m-d') ) {
                    $realizacionInventario = 1;
                    break;
                }else{
                    $inventarioRealizado = 2;
                    break;
                }
            }
        } 

        if ( $realizacionInventario == 1) { //se está haciendo un inventario aleatorio en el dia
            $productoStockDiferente = array();
            foreach ($ultimoInventario as $idx => $itemRealizado) {
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

            return ['inventario' => 1, 'items' => $productoStockDiferente ];
        } else {
            
            return ['inventario' => 0];
        }
    
    }


    public function generaInventarioGeneral( $sucursal, $familia, $subfamilia)
    {
        $inventarios = new Inventario;
        $productos = $inventarios->getStockGeneral( $sucursal, $familia, $subfamilia);
        $inventarioRegistrado = $this->esInventarioRepetido($sucursal, $familia, $subfamilia ,1);
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

    public function actualizaRevisionInventarioCodigoBarra( $data)
    {
        $inventario = new Inventario;

        return $inventario->actualizaRevisionInventarioCodigoBarra( $data );
    }
    


    public function guardaProductosInventariados( $items )
    {
        $inventarios = new Inventario;
        // creando una cadena que especifique los articulos que se quieren coincidan 
        $queryIn = "";
        $sucursal = '';
        foreach ($items as $item) {
            $queryIn .= "'".$item['codigo']."',";
            $sucursal = $item['sucursal'];
        }
        $queryIn = substr( $queryIn,0,-1);
        // Obteniendo la informacion de los articulos que están en la cadena que se generó anteriormente
        $listaArticulos =  $inventarios->getInsercionesInventariosTmp( $queryIn, $sucursal );
        // Concatenando los valores de insercion y posteriormente insertar
        $valuesInsert = '';
        foreach ($listaArticulos as $valoresAInsertar ) {
            $valuesInsert .= "INSERT INTO REF_INVENTARIOSTMP VALUES".$valoresAInsertar->VALUESSQL.';';
        }
        $queryInsert = "EXECUTE BLOCK AS BEGIN $valuesInsert  END ";

        $inventarios->registraInventarioTemporal( $queryInsert );
    }

    public function getDiferenciasEnInventario( $sucursal)
    {
        $inventario = new Inventario;
        $listaDiferencias = $inventario->getDiferenciasAlmacen( $sucursal , date("Y-m-d") );

        $diferencias = [];
        foreach ($listaDiferencias as $i => $item) {
            $infoArticulo = self::getInfoArticulo( $item['codigo'] , $sucursal );
            $infoArticulo[0]->CANTIDAD = 0;
            $infoArticulo[0]->IDINVENTARIO = $item['id'];
            $infoArticulo[0]->FISICO2 = $item['fisico2'];
            $infoArticulo[0]->FISICO3 = $item['fisico3'];
            array_push( $diferencias , $infoArticulo[0]);
        }

        return $diferencias;
    }
}


if ( isset($_GET['opc']))  {
    $inventario = new InventariosController;
    switch ($_GET['opc']) {
        case 'sucursales':
            echo json_encode( InventariosController::getSucursales() );
            break;
        case 'familias':
            echo json_encode( InventariosController::getFamilias($_GET['tipoinventario'], $_GET['sucursal'] ) );
            break;
        case 'aleatorio':

            // echo json_encode ( $inventario->generaInventarioRandom($_GET['sucursal'], $_GET['familia'], $_GET['subfamilia']) );
                echo json_encode ( $inventario->getArticulosInventarioAleatorio($_GET['sucursal']) );
            break;
        case 'general':
            echo json_encode( $inventario->generaInventarioGeneral($_GET['sucursal'], $_GET['familia'], $_GET['subfamilia'] ) );
            break;
        case 'subfamilias': echo json_encode( InventariosController::getSubfamilias($_GET['tipoinventario'], $_GET['sucursal'], $_GET['familia']) );
            break;
        case 'getProgramado':
                  echo json_encode( InventariosController::getInventarioProgramadoSucursal($_GET['sucursal']) );
            break;
        case 'buscarArticulo':
                echo json_encode ( InventariosController::getInfoArticulo($_GET['codigo'] , $_GET['sucursal']) );
            break;
        case 'getRevisiones':{
            echo json_encode ( InventariosController::getDiferenciasEnInventario( $_GET['sucursal']) );
            break;
        }
        case 'getUser':{
            echo $_SESSION['usuario'];
            break;
        }
        default:
            # code...
            break;
    }
}else{
   $inventarioData =  ( json_decode(file_get_contents('php://input'), true) );
   $inventarioContent = $inventarioData['inventario'];
    $tipoOperacion = $inventarioData['action'];
    // var_dump( $tipoOperacion);
   $cuentaRegistros = 0;
   
   if ($tipoOperacion > 0) {
        
        echo (InventariosController::actualizaRevisionInventario($inventarioContent) );
            
   } else if( $tipoOperacion != 'codBarras' && is_numeric( $tipoOperacion ) ) {
        echo InventariosController::RegistraInventarioGeneral($inventarioContent);
        if ( $inventarioData['tipo'] == 3) {
            // Registrando en la tabla temporal los articulos
            InventariosController::guardaProductosInventariados( $inventarioContent);
        }
        
   }else{
       
       $sucursal = $inventarioData['sucursal'];
       $tipo = $inventarioData['tipo'];
       $registros = '';
       $cantidadRegistros = sizeof( $inventarioContent );
       $listaDeRevision = [];
       
       foreach ($inventarioContent as $i => $item) {
           extract( $item );
           if ( $id != 0) {
               
               array_push( $listaDeRevision , $item );
               //unset( $inventarioContent[$i] );
               continue;
           }
       }

       $registros = substr( $registros , 0, -1);
       $contador = 0;
       if ( sizeof( $listaDeRevision)  > 0 ) {
        // $contador = InventariosController::actualizaRevisionInventario($listaDeRevision) ;

       }
       
       echo InventariosController::RegistraInventarioGeneral($inventarioContent) + $contador;
    //    $params = [
    //        'descripcion' => $inventarioContent['DESCRIPCION'],
    //        'codigo' => $inventarioContent['CODIGOARTICULO'],
    //        'subfamilia' => $inventarioContent['SUBFAMILIA'],
    //        'familia' => $inventarioContent['FAMILIA'],
    //        'stock' => $inventarioContent['EXISTENCIA'],
    //        'stkIngresado' => $inventarioContent['CANTIDAD'],
    //        'sucursal' => $sucursal,
    //        'campo' => 0,
    //        'id' => 0,
    //        'tiempoTranscurrido' => -1
    //    ];

       
   }
   

   

}