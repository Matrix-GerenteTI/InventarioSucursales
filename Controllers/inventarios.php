<?php
session_start();
date_default_timezone_set("America/Mexico_City");
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Models/inventarios.php";

class InventariosController
{
    public static function checkSession(){
        $inventarios = new Inventario;
        if(isset($_POST['usuario']) && isset($_POST['password'])){
			//echo "Detecta envio de POST de usuario y pass";
			
            $usuario = $inventarios->checkUser($_POST['usuario'],$_POST['password']);
            //var_dump($usuario);
            if(sizeof($usuario)>0){
                $_SESSION['usuario'] = $usuario[0]['username'];
                $_SESSION['idempleado'] = $usuario[0]['idempleado'];
				//echo "Se crea la variable de session";
				//die();
                return 1;
            }else{
				//echo "Usuario y contraseña incorrectos";
				//die();
                return 0;
            }
        }else{
            if(isset($_SESSION['usuario'])){
				//echo "Esta creada la variable de sesion";
				//die();
                return 1;
            }else{
				//echo "No existe ni envio de POST ni variable de SESSION";
                return 0;
				//die();
			}
        }
        //return $sucursales;
    }

    public static function getSucursales(){
        $inventarios = new Inventario;
        $sucursales = $inventarios->getSucursalesConAlmacen();
        $sucursalesHerramienta = $inventarios->getAlmacenesHerramientas();
        foreach ($sucursales as $i => $sucursal) {
            $sucursales[$i]->DESCRIPCION = utf8_decode( $sucursal->DESCRIPCION ); 
        }

        foreach ($sucursalesHerramienta as $i => $sucursal) {
            $sucursalesHerramienta[$i]->DESCRIPCION = utf8_decode( $sucursal->DESCRIPCION ); 
        }
        $sucursales = array_merge( $sucursales , $sucursalesHerramienta);
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
        // var_dump($sucursal, $familia, $subfamilia, "tipo ".$tipo);
        $inventarios = new Inventario;
        $inventarioRealizado = [];
        if ( $tipo == 5 ) { // es un inventario de herramientas
            $inventarioRealizado = $inventarios->esInventarioHerramientaRealizado( $sucursal , date('Y-m-d') );
        }else{
            $inventarioRealizado = $inventarios->getInventarioRealizado( array('sucursal' => $sucursal,
            'familia' =>$familia, 'subfamilia' => $subfamilia , 'tipo' => $tipo, 'fecha' => date('Y-m-d') ));
        }
       

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
        $sizeMuestra = $totalProductos* 0.07;
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
                $sizeMuestra = round( ($totalProductos * 0.015), 1 );

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
    { //Funcion que genera el inventario aleatorio
        $inventarioModel = new Inventario;

        $revisionInventario = $this->getStatusRealizacionAleatorio( $sucursal);

        if ( $revisionInventario['inventario'] == 1) {
            
            return $revisionInventario['items'];
        }

        $soloFamilia = NULL;

        if ( session_status() == PHP_SESSION_ACTIVE && sizeof( $_SESSION)) {
            //obteniendo la familia que debe inventariar cada usuario
            $familiaAsignada = $inventarioModel->getFamiliaAssignadaUsuario( $_SESSION['usuario']);

            if ( sizeof( $familiaAsignada ) ) {
                $soloFamilia = $familiaAsignada[0]['familia'];
            }
        }

        $listadoGralArticulos = ( $inventarioModel->getArticulosNoInventariados( $sucursal , $soloFamilia ) );
        
        //var_dump(( $listadoGralArticulos));
        //die();
        
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
                if ( $idx < 30) {
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
        //var_dump($ultimoInventario);
        //die();
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
        
        if ( $realizacionInventario == 2) { //se está haciendo un inventario aleatorio en el dia
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
        //se genera el inventario de todos familias y subfamilias
        $inventarios = new Inventario;
        $productos = $inventarios->getStockGeneral( $sucursal, $familia, $subfamilia);
        // $inventarioRegistrado = $this->esInventarioRepetido($sucursal, $familia, $subfamilia ,1);
        // if ( isset($inventarioRegistrado['error']) ) {            
        //     return $inventarioRegistrado;
        // } elseif( isset($inventarioRegistrado[0]) ) {

        //     return $inventarioRegistrado;
        // }
        
        foreach ($productos as $i => $item) {
            $productos[$i]->DESCRIPCION = utf8_decode( $item->DESCRIPCION);
            $productos[$i]->CODIGOARTICULO = utf8_decode( $item->CODIGOARTICULO );
            $productos[$i]->SUBFAMILIA = utf8_decode( $item->SUBFAMILIA );
        }
        return $productos;
    }

    public function generaInventarioHerramienta( $idAlmacen )
    {
        $inventarios = new Inventario;
        $productos = $inventarios->getStockHerramienta( $idAlmacen );
        $inventarioRegistrado = $this->esInventarioRepetido($idAlmacen, 'N/A', 'N/A' , 5);
    
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
            $valuesInsert .= "INSERT INTO REF_INVENTARIOSTMP(FKCFG_ARTICULOS,FKCFG_ALMACENES,USUARIO) VALUES".$valoresAInsertar->VALUESSQL.';';
        }

        
        $queryInsert = "EXECUTE BLOCK AS BEGIN $valuesInsert  END ";

        echo $queryInsert;
        
        $inventarios->registraInventarioTemporal( $queryInsert );
    }

    public function setHistInventarioHerramienta( $dataInventario , $observacion)
    {
        //Registra en el log el inventario de las herramientas
        $inventarios = new Inventario;
        $idLogHerramienta = $inventarios->registraLogInventario([
            'fecha' => date('Y-m-d'),
            'almacen' => $dataInventario[0]['sucursal'],
            'observacion' =>  $observacion
        ]);

        //Ahora se liga el id del log  con el inventario realizado
        return $inventarios->RegistraInventarioGeneral( $dataInventario , $idLogHerramienta );
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
        case 'herramienta' :
                echo json_encode( $inventario->generaInventarioHerramienta( $_GET['sucursal'] ) );
            break;
        case 'subfamilias': echo json_encode( InventariosController::getSubfamilias($_GET['tipoinventario'], $_GET['sucursal'], $_GET['familia']) );
            break;
        case 'getProgramado':
                  echo json_encode( InventariosController::getInventarioProgramadoSucursal($_GET['sucursal']) );
            break;
        case 'buscarArticulo':
                echo json_encode ( InventariosController::getInfoArticulo($_GET['codigo'] , $_GET['sucursal']) );
            break;
        case 'checaSesion':
                echo InventariosController::checkSession();
            break;
        default:
            # code...
            break;
    }
}else{
   if(!isset($_POST['usuario'])){
    $inventarioData =  ( json_decode(file_get_contents('php://input'), true) );
    $inventarioContent = $inventarioData['inventario'];
        $tipoOperacion = $inventarioData['action'];

    $cuentaRegistros = 0;
    
    if ($tipoOperacion > 0) {
            
            echo (InventariosController::actualizaRevisionInventario($inventarioContent) );
                
    } else if( $tipoOperacion != 'codBarras' || $tipoOperacion >= 0){

            //Si es inventario por login
            $conUsuario = " NULL";

            if ( session_status() == PHP_SESSION_ACTIVE ) {
                $idUsuario = $_SESSION['usuario'];
                $conUsuario = "'$idUsuario'";
            }
    

            if ( $inventarioData['tipo'] == 3 && ( session_status() == PHP_SESSION_ACTIVE && sizeof($_SESSION) > 0  ) ) {

                
                echo InventariosController::RegistraInventarioGeneral($inventarioContent);
                InventariosController::guardaProductosInventariados( $inventarioContent);
            }else if ( $inventarioData['tipo'] == 3 ) {
                // Registrando en la tabla temporal los articulos
                echo InventariosController::RegistraInventarioGeneral($inventarioContent);
                InventariosController::guardaProductosInventariados( $inventarioContent);
            }else if( $inventarioData['tipo'] == 1 ){
                echo InventariosController::RegistraInventarioGeneral($inventarioContent);
            } else if( $inventarioData['tipo'] == 5) {
                //Es un inventario de herramientas
               echo  InventariosController::setHistInventarioHerramienta( $inventarioContent  , $inventarioData['observacion']);
            }

    }else{
        $sucursal = $inventarioData['sucursal'];
        $tipo = $inventarioData['tipo'];
        $registros = '';
        $cantidadRegistros = sizeof( $inventarioContent );
        foreach ($inventarioContent as $i => $item) {
            extract( $item );
            $registros .= "('','$CODIGOARTICULO','$DESCRIPCION','$FAMILIA','$SUBFAMILIA','$CANTIDAD',NULL,NULL,$EXISTENCIA,'$tipo' ,'$sucursal',now(),0 ,NULL,1,NULL)";
            if ( $i < $cantidadRegistros -1 ) {
                $registros .= ",";
            }
        }
        $inventarios = new Inventario;
        echo $inventarios->registarInventarioCodBarras( $registros );
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
   

}