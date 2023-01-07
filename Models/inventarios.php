<?php
date_default_timezone_set("America/Mexico_City");
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Config/Database.php";
class Inventario
{
    public $conexionIbase;
    protected $conexion;

    public function __construct(){
        
         $this->conexionIbase = @ibase_pconnect(HOST,USER,PASS) or die("Error al conectarse a la base de datos: ".ibase_errmsg());
         
    }

    public function checkUser( $usuario, $password)
    {
        $query = "SELECT * 
                FROM dbnomina.pusuarios 
                where username='$usuario' and password='$password' and status=1";

                // echo $password;
        $sql = $this->conexion()->query( $query);

        return $sql->fetch_all(MYSQLI_ASSOC);
    }

    public function getFamiliaAssignadaUsuario( $usuario)
    {
        $query = "SELECT * 
                FROM dbnomina.roles_inventarios 
                where username='$usuario' and status=1";

                // echo $password;
        $sql = $this->conexion()->query( $query);

        return $sql->fetch_all(MYSQLI_ASSOC);
    }

    protected function conexionFirebird()
    {
        $this->conexionIbase = @ibase_pconnect(HOST,USER,PASS) or die("Error al conectarse a la base de datos: ".ibase_errmsg());
         return $this->conexionIbase ;        
    }

    protected function conexion(){
        $this->conexion = new mysqli(SERVERMYSQL,USERMYSQL,PASSMYSQL,DBMYSQL);
        $this->conexion->query("SET NAMES 'utf8'");

        return $this->conexion;
    }

    public function getSucursalesConAlmacen()
    {
        $querySucursales = "SELECT r.ID, r.DESCRIPCION, r.ACTIVO, r.PWD, r.ZONA, r.FACTORK, r.TPVREM
                                        FROM CFG_ALMACENES r
                                        WHERE PWD IN ('LEON','MATRIX','OXIFUEL','CEDIM') AND ZONA IN ('CENTRO','ALTOS','COSTA')";
                                            
        $exeSucursales = ibase_query($this->conexionIbase, $querySucursales );
        return $this->fetchResults($exeSucursales);
    }


    public function getSucursalesConAlmacenActivo()
    {
        $querySucursales = "SELECT r.ID, r.DESCRIPCION, r.ACTIVO, r.PWD, r.ZONA, r.FACTORK, r.TPVREM
                                        FROM CFG_ALMACENES r
                                        WHERE PWD IN ('MATRIX','CEDIM') AND ZONA IN ('CENTRO','ALTOS','COSTA') 
                                        AND ACTIVO = 'S'; ";
                                            
        $exeSucursales = ibase_query($this->conexionIbase, $querySucursales );
        return $this->fetchResults($exeSucursales);
    }


    public function getAlmacenesHerramientas( )
    {
        $queryAlmacenHerramienta = "SELECT r.ID, r.DESCRIPCION, r.ACTIVO, r.PWD, r.ZONA, r.FACTORK, r.TPVREM 
                                                                 FROM CFG_ALMACENES  r where r.zona = 'HERRAMIENTAS' AND r.ACTIVO = 'S'";

        $exeAlmacenesHerramientas = ibase_query( $this->conexionIbase , $queryAlmacenHerramienta );

        return $this->fetchResults( $exeAlmacenesHerramientas );
    }

    public function getInventariosConPistolaBarra( $mes , $anio )
    {
        $queryInventario = "SELECT csucursal.descripcion as sucursal,csucursal.idprediction, familia,subfamilia,fechaCaptura, count(fechaCaptura) AS cantidad, inventarios.usuario_id as usuario
                                            FROM inventarios
                                            INNER JOIN csucursal ON csucursal.idprediction = inventarios.sucursal_id
                                            WHERE inventarios.tipo = 4 AND MONTH(fechaCaptura)  = '$mes' AND year(fechaCaptura) = $anio  AND inventarios.usuario_id IS NOT null 
                                            GROUP BY  csucursal.idprediction, familia,subfamilia,fechaCaptura
                                            ORDER BY fechaCaptura,familia,subfamilia asc";
        $exeInvetarioBarras = $this->conexion()->query( $queryInventario );

        return $exeInvetarioBarras->fetch_all( MYSQLI_ASSOC );
    }

    
    public function getInventarioRealizado( $data)
    {
        extract( $data );
        // $year = substr($fecha, 0,4);
        // $month = substr($fecha, 5,2);
        $getInventarioSucursal = "SELECT *  FROM dbnomina.inventarios where fechaCaptura like '$fecha%' AND tipo = $tipo AND sucursal_id = $sucursal AND familia = '$familia'  and subfamilia = '$subfamilia' ";

        // $getInventarioSucursal = "SELECT * FROM dbnomina.inventarios where month (fechaCaptura) = $month and year (fechaCaptura) = $year and tipo = $tipo and sucursal_id = $sucursal";
        $exeInventarioSucursal = $this->conexion()->query( $getInventarioSucursal);
        return $exeInventarioSucursal->fetch_all( MYSQLI_ASSOC);
    }

    public function esInventarioHerramientaRealizado( $idAlmacen , $fecha )
    {
        $getInventarioSucursal = "SELECT * 
        FROM dbnomina.inventarios 
        where fechaCaptura like '$fecha%' AND tipo = 5 AND sucursal_id = $idAlmacen ";

        $exeInventarioSucursal = $this->conexion()->query( $getInventarioSucursal);

        return $exeInventarioSucursal->fetch_all( MYSQLI_ASSOC);
    }



    public function getInventarioCurrentDay($fecha){
        $getInventarioSucursal = "SELECT * 
        FROM dbnomina.inventarios 
        where fechaCaptura like '$fecha%' ";
        $exeInventarioSucursal = $this->conexion()->query( $getInventarioSucursal);

        return $exeInventarioSucursal->fetch_all( MYSQLI_ASSOC);
    }

    public function getFamilias( $tipoinventario, $sucursal)
    {
		$arrFamilias = array();
        $queryFamilias = "SELECT 	CFGA.FAMILIA
                FROM 	   (cfg_articulos CFGA 
                        inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                        inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                WHERE 	CFGA.itemservicio=''  AND RAXA.FK1MCFG_ALMACENES = $sucursal
                AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0
				AND 	CFGA.familia IN ('ACCESORIO','COLISION','LLANTA','RIN','REFACCION','HERRAMIENTA','OXIFUEL')
                GROUP BY CFGA.FAMILIA";
        $exeFamilias = ibase_query($this->conexionIbase, $queryFamilias);
		$rowsFamilias = $this->fetchResults( $exeFamilias);
        $familias = $this->fecthrows($rowsFamilias);
		$numrows = count($familias) - 1;
		$n = rand(0, $numrows);
		$familia = array();
		array_push( $familia, $rowsFamilias[$n]);
		
		if( $tipoinventario == 3){
			return $familia;
		}else{
			return $rowsFamilias;
		}
    }
    
    public function getInsercionesInventariosTmp( $poolCodigos, $sucursal )
    {

        $conUsuario = "''''";
        if ( session_status() == PHP_SESSION_ACTIVE && sizeof( $_SESSION) > 0 ) {
            $idUsuario = $_SESSION['usuario'];
            $conUsuario = "''$idUsuario''";
        }

        $queryArticulos = "SELECT ('(' || CFG_ARTICULOS.ID || ',$sucursal ,$conUsuario)' ) AS VALUESSQL
                            FROM CFG_ARTICULOS
                            where CFG_ARTICULOS.CODIGOARTICULO IN ( $poolCodigos)";

        $exeArticulos = ibase_query($this->conexionFirebird(), $queryArticulos );
        return $this->fetchResults( $exeArticulos );
    }

    public function registraInventarioTemporal( $queryInsert )
    {
        // $queryTemporal = "INSERT INTO REF_INVENTARIOSTMP VALUES $values";
        // echo $queryInsert;
        return $exeArticulos = ibase_query($this->conexionFirebird(), $queryInsert );
    }

    public function reiniciaInventariosDeSucursal( $sucursal )
    {
        $conUsuario = " USUARIO=''";
        if ( session_status() == PHP_SESSION_ACTIVE && sizeof( $_SESSION) > 0 ) {
            $idUsuario = $_SESSION['usuario'];
            $conUsuario = " USUARIO ='$idUsuario' ";
        }

        $queryReiniciaInventario = "DELETE FROM REF_INVENTARIOSTMP WHERE FKCFG_ALMACENES = $sucursal AND $conUsuario";

        $exeReinicioInventario = ibase_query( $this->conexionFirebird(), $queryReiniciaInventario );

        return $exeReinicioInventario;
    }

    public function getSubfamilias( $tipoinventario, $sucursal, $familia )
    {
		$querySubfamilias = "SELECT 	CFGA.SUBFAMILIA
                FROM 	   (cfg_articulos CFGA 
                        inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                        inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                WHERE 	CFGA.itemservicio=''  AND RAXA.FK1MCFG_ALMACENES = $sucursal
                AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0
				AND 	CFGA.familia='$familia'
                GROUP BY CFGA.SUBFAMILIA";
        $exeSubfamilias = ibase_query($this->conexionIbase, $querySubfamilias);
		$rowsSubfamilias = $this->fetchResults( $exeSubfamilias);
        $subfamilias = $this->fecthrows($rowsSubfamilias);
		$numrows = count($subfamilias) - 1;
		$n = rand(0, $numrows);
		$subfamilia = array();
		array_push( $subfamilia, $rowsSubfamilias[$n]);
		if( $tipoinventario == 3){
			return $subfamilia;
		}else{
			return $rowsSubfamilias;
		}
    }

    public function getStockFamilia( $sucursal, $familia , $subfamilia)
    {
        $queryStockFamilias = "SELECT 	CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,
                        (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK
                FROM 	   (cfg_articulos CFGA 
                        inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                        inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                WHERE 	CFGA.itemservicio=''  AND RAXA.FK1MCFG_ALMACENES = $sucursal
                AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 AND CFGA.FAMILIA='$familia' AND CFGA.SUBFAMILIA LIKE '%$subfamilia%'
                GROUP BY CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,STOCK
                ORDER 	by (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) DESC ";

        $exeStockFamilia = ibase_query( $this->conexionIbase, $queryStockFamilias);
        return $this->fetchResults( $exeStockFamilia);
    }

    public function getCostoArticulo( $codigo)
    {
                $queryCosto = "SELECT 	RAXA.CTOPROMEDIO as COSTO
                FROM 	   (cfg_articulos CFGA 
                        inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                        inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                WHERE 	CFGA.CODIGOARTICULO='$codigo' and (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 ";

        $exeCosto = ibase_query( $this->conexionIbase, $queryCosto);
        $costo = $this->fetchResults( $exeCosto);
        $costo = isset( $costo[0]->COSTO  ) ? $costo[0]->COSTO : 0;
        return $costo;
    }

    // public function getStockGeneral( $sucursal, $familia, $subfamilia)
    // {
    //     if ($subfamilia ==-1 && $familia == -1) {
    //         $familia = '';
    //         $subfamilia = '';
    //         if($sucursal != 10755 && $sucursal != 10757){// LIBRAMIENTO 755  PALMERAS 757
    //             $queryStockGeneral = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,CFGA.FAMILIA,
    //                             (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK,
    //                             CFGA.ID as ID
    //                     FROM 	   (cfg_articulos CFGA 
    //                             inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
    //                             inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
    //                             WHERE CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal
    //                             AND CFGA.FAMILIA LIKE '$familia%'
    //                             -- AND CFGA.FAMILIA IN ('ACCESORIO', 'COLISION', 'HERRAMIENTA', 'LLANTA', 'REFACCION', 'RIN') 
    //                             AND CFGA.SUBFAMILIA IN ('CALAVERA', 'COFRE', 'CUARTO', 'FARO', 'FARO AUXILIAR', 'FASCIA', 'MANIJA', 'MARCO RADIADOR', 'MOTOVENTILADOR', 'PARRILLA', 'RADIADOR') /* ESPEJO*/ 
    //                     AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 
    //                     GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,STOCK,CFGA.ID
    //                     ORDER 	by CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO";
            
    //         $exeStockGeneralFamilia = ibase_query( $this->conexionIbase, $queryStockGeneral); 
    //         }
    //         else if($sucursal == 10755){ 
    //             $queryStockGeneral = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,CFGA.FAMILIA,
    //                             (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK,
    //                             CFGA.ID as ID
    //                     FROM 	   (cfg_articulos CFGA 
    //                             inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
    //                             inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
    //                             WHERE CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal
    //                             AND CFGA.FAMILIA LIKE '$familia%'
    //                             AND CFGA.SUBFAMILIA NOT NULL 
    //                     AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 
    //                     GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,STOCK,CFGA.ID
    //                     ORDER 	by CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO";
            
    //         $exeStockGeneralFamilia = ibase_query( $this->conexionIbase, $queryStockGeneral); 
    //         }
    //         else if($sucursal == 10757){ 
    //             $queryStockGeneral = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,CFGA.FAMILIA,
    //                             (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK,
    //                             CFGA.ID as ID
    //                     FROM 	   (cfg_articulos CFGA 
    //                             inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
    //                             inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
    //                             WHERE CFGA.itemservicio='' RAXA.FK1MCFG_ALMACENES = $sucursal
    //                             AND CFGA.FAMILIA IN ('ACCESORIO', 'COLISION', 'HERRAMIENTA', 'LLANTA', 'REFACCION', 'RIN') 
    //                             AND CFGA.SUBFAMILIA IN ('COFRE','CUARTO', 'FARO', 'FARO AUXILIAR', 'FASCIA', 'MANIJA', 'MARCO RADIADOR', 'MOTOVENTILADOR', 'PARRILLA', 'RADIADOR', 'ESPEJO') /* CALAVERA */
    //                     AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 
    //                     GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,STOCK,CFGA.ID
    //                     ORDER 	by CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO";
            
    //         $exeStockGeneralFamilia = ibase_query( $this->conexionIbase, $queryStockGeneral); 
    //         }
    //     }
    //     else{
    //         // WHERE 	CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal AND CFGA.FAMILIA LIKE '%$familia%' AND CFGA.SUBFAMILIA LIKE '%$subfamilia%'
    //         $queryStockGeneral = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,CFGA.FAMILIA,
    //                         (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK,
	// 						CFGA.ID as ID
    //                 FROM 	   (cfg_articulos CFGA 
    //                         inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
    //                         inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
    //                         WHERE CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal AND CFGA.FAMILIA LIKE '%$familia%' AND CFGA.SUBFAMILIA LIKE '%$subfamilia%'
    //                 AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 
    //                 GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,STOCK,CFGA.ID
    //                 ORDER 	by CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO";
        
    //     $exeStockGeneralFamilia = ibase_query( $this->conexionIbase, $queryStockGeneral);
    // }
    
    // return $this->fetchResults( $exeStockGeneralFamilia);
    // }

    public function getStockGeneral( $sucursal, $familia, $subfamilia)
    {
        if($subfamilia==-1)
            $subfamilia = '';
        if($familia==-1)
            $familia = '';
            if ($sucursal == 10755 && $familia =='ACCESORIO' && $subfamilia == '') {

                //756
                // AND CFGA.SUBFAMILIA IN('ARRASTRE', 'BARRA LED', 'BARRAS PORTA EQUIPAJE', 'BASE', 'BEDLINER', 'CABLE', 'CANASTILLA', 'CATALOGO', 'CINTA', 'CONTRAPESO', 'CUBIERTA', 'ESTRIBO', 
                //                                     'FARO LED', 'FILAMENTO HALOGENO', 'FUSIBLE AUTOMOTRIZ', 'GANCHO', 'HERRAJE', 'LED', 'LUZ LED', 'PARRILLA', 'PASAMANOS', 'POLVERA', 'RELEVADOR', 'REMOLQUE',
                //                                     'ROLLBAR', 'TAPA PLEGABLE', 'TERMINAL', 'TERMINALES', 'TIRON', 'TORNILLERIA', 'TUERCA' ) /* TUMBABURRO*/


                $queryStockGeneral = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,CFGA.FAMILIA,
                            (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK,
							CFGA.ID as ID
                    FROM 	   (cfg_articulos CFGA 
                            inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                            inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                            WHERE CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal AND CFGA.FAMILIA LIKE '%$familia%' 
                            AND CFGA.SUBFAMILIA IN('BARRAS PORTA EQUIPAJE', 'BEDLINER', 'BODY', 'BOLA REMOLQUE', 'CABLE', 'CAJA DE HERRAMIENTA', 'CANASTILLA', 'CANTONERA', 'CATALOGO', 'CINTA', 'CONECTOR',
                                                    'CONTRAPESO', 'DEFENSA', 'DEFLECTOR', 'ELEVACION', 'EMBLEMA', 'ESCALERA','ESTRIBO','ESTRIBOS','ESTROBO','FARO', 'FARO AUXILIAR','FARO HALOGENO',
                                                    'FARO NIEBLA', 'FARO SONAR', 'FASCIA', 'FILAMENTO HALOGENO', 'FOCO', 'FOCO LED', 'FUSIBLE AUTOMOTRIZ', 'GANCHO', 'GRAPA', 'GUIA FASCIA',
                                                    'HERRAJE', 'LED', 'MOSQUITERO', 'PARRILLA', 'PASAMANOS', 'PIE', 'POLARIZADO', 'PORTA FUSIBLE', 'PROTECTOR', 'RACK', 'REJILLA', 'RELEVADOR', 'ROLLBAR', 'SEGURO',
                                                    'SILICON', 'SNORKEL', 'SOCKET', 'SOPORTE', 'SPOILER', 'TAPA','TAPA PLEGABLE','TERMINAL', 'TERMINALES', 'TIRON',
                                                    'TOMAS DE AIRE', 'TORNILLERIA', 'TUERCA', 'BIRLO', 'CENTRADOR', 'LLAVE', 'SEPARADOR RIN', 'VALVULA') /* BARRA LED FARO LED LUZ LED TUMBABURRO UÑA*/
                    AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 
                    GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,STOCK,CFGA.ID
                    ORDER 	by CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO";
            }
            else if ($sucursal == 10755 && $familia =='RIN' && $subfamilia == '') {
                // var_dump($subfamilia);
                $queryStockGeneral = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,CFGA.FAMILIA,
                            (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK,
							CFGA.ID as ID
                    FROM 	   (cfg_articulos CFGA 
                            inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                            inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                            WHERE CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal AND CFGA.FAMILIA LIKE '%$familia%' 
                            AND CFGA.SUBFAMILIA IN('RIN 13', 'RIN 15','RIN 16','RIN 17','RIN 19','RIN 19.5','RIN 20','RIN 22','RIN 22.5') /* RIN 14 RIN 18 */
                    AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 
                    GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,STOCK,CFGA.ID
                    ORDER 	by CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO";
            }

            else if ($sucursal == 10755 && $familia =='COLISION' && $subfamilia == '') {
                // var_dump($subfamilia);
                $queryStockGeneral = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,CFGA.FAMILIA,
                            (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK,
							CFGA.ID as ID
                    FROM 	   (cfg_articulos CFGA 
                            inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                            inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                            WHERE CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal AND CFGA.FAMILIA LIKE '%$familia%' 
                            AND CFGA.SUBFAMILIA IN('ALERON', 'AMORTIGUADOR', 'ANTIIMPACTO', 'BASE FARO', 'BIGOTERA', 'BISAGRA', 'BISEL', 'BRAZO', 'CANTONERA', 'CHAPA', 'CILINDRO', 'CONDENSADOR', 'CUARTO',
                                'DEPOSITO', 'ELEVADOR', 'ESTRIBO', 'EXTENSION', 'FARO AUXILIAR', 'FARO HALOGENO', 'FOCO HELLA', 'GUIA', 'GUIA FASCIA', 'HORQUILLA', 'HULE', 'HULE DEFENSA', 'LAMINA', 'LIENZO',
                                'LODERA', 'MARCO FARO', 'MARCO RADIADOR', 'MOLDURA ARCO', 'MOTOVENTILADOR', 'PLACA', 'PUERTA', 'REFLEJANTE', 'REFUERZO', 'SPOILER', 'TAPA') 
                                /* ALMA FASCIA CALAVERA COFRE DEFENSA ESPEJO FARO FARO NIEBLA FASCIA MANIJA LUNA ESPEJO MOLDURA PARRILLA RADIADOR REJILLA SALPICADERA TOLVA*/
                    AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 
                    GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,STOCK,CFGA.ID
                    ORDER 	by CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO";
            }


            else{
                // var_dump("ptm");
                // WHERE 	CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal AND CFGA.FAMILIA LIKE '%$familia%' AND CFGA.SUBFAMILIA LIKE '%$subfamilia%'
                $queryStockGeneral = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,CFGA.FAMILIA,
                            (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK,
							CFGA.ID as ID
                    FROM 	   (cfg_articulos CFGA 
                            inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                            inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                            WHERE CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal AND CFGA.FAMILIA LIKE '%$familia%' AND CFGA.SUBFAMILIA LIKE '%$subfamilia%'
                    AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 
                    GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,STOCK,CFGA.ID
                    ORDER 	by CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO";
            }
        
        $exeStockGeneralFamilia = ibase_query( $this->conexionIbase, $queryStockGeneral);

        return $this->fetchResults( $exeStockGeneralFamilia);

    }

    public function getStockHerramienta( $idAlmacen )
    {
        $queryStockHerramientas = "SELECT codigoarticulo,cfg_articulos.DESCRIPCION,( EXISTOTAL - (EXISPEDIDOS + EXISPROCESO) ) as stock,familia,subfamilia
                                    FROM CFG_ALMACENES
                                    INNER JOIN REF_ARTXALMACEN ON REF_ARTXALMACEN.FK1MCFG_ALMACENES = CFG_ALMACENES.ID
                                    INNER JOIN CFG_ARTICULOS ON CFG_ARTICULOS.ID = REF_ARTXALMACEN.FK1MCFG_ARTICULOS
                                    WHERE CFG_ARTICULOS.FAMILIA='HERRAMIENTA' AND CFG_ALMACENES.ID = $idAlmacen AND ( EXISTOTAL - (EXISPEDIDOS + EXISPROCESO) ) > 0";
        $exeStockHerramienta = ibase_query( $this->conexionIbase ,  $queryStockHerramientas );

        return $this->fetchResults( $exeStockHerramienta );
    }

    public function getStockPorSubfamilia( $sucursal, $familia)
    {
                $queryStockGeneral = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,CFGA.FAMILIA,
                            (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK
                    FROM 	   (cfg_articulos CFGA 
                            inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                            inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                    WHERE 	CFGA.itemservicio='' AND RAXA.FK1MCFG_ALMACENES = $sucursal AND CFGA.FAMILIA='$familia'
                    AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 
                    GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,STOCK
                    ORDER 	by CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO";
        
        
        $exeStockGeneralFamilia = ibase_query( $this->conexionIbase, $queryStockGeneral);

        return $this->fetchResults( $exeStockGeneralFamilia);
    }
	
	public function getTraspasos( $sucursal )
    {
                $queryStockGeneral = "SELECT ";
        
        
        $exeStockGeneralFamilia = ibase_query( $this->conexionIbase, $queryStockGeneral);

        return $this->fetchResults( $exeStockGeneralFamilia);
    }
	
    public function getInventariosSucursal( $data)
    {
        extract( $data);
        $queryInventario = "SELECT inventarios.*, csucursal.descripcion as sucursal 
                                FROM inventarios
                                INNER JOIN csucursal ON csucursal.idprediction = inventarios.sucursal_id  OR csucursal.idherramienta = inventarios.sucursal_id
                                where date(fechaCaptura) = '2020-07-03' AND sucursal_id = $sucursal 
                                order by familia,subfamilia,descripcion desc";
                                
        $exeInventarioSucursal = $this->conexion()->query( $queryInventario);                                

        return $exeInventarioSucursal->fetch_all( MYSQLI_ASSOC );
    }

    public function getLastInventarioRandom ( $sucursal )
    {
        $conUsuario = "";
        if ( session_status() == PHP_SESSION_ACTIVE && sizeof( $_SESSION) > 0) {
            $idUsuario = $_SESSION['usuario'];
            $conUsuario = " AND usuario_id='$idUsuario' ";
        }

        $queryLastInventario = "SELECT *
                                                FROM inventarios 
                                                WHERE inventarios.tipo = 3  and  
                                                    fechacaptura = (SELECT MAX(fechaCaptura) FROM inventarios where sucursal_id = $sucursal and tipo = 3 )
                                                    AND sucursal_id = $sucursal  $conUsuario 
                                                    order by id desc";

        $exeLastInventario = $this->conexion()->query( $queryLastInventario );                                                    
        
        return $exeLastInventario->fetch_all( MYSQLI_ASSOC );
    }

    public function getFamiliaArticulo( $codigo )
    {
        $queryFamilia = "SELECT FAMILIA FROM CFG_ARTICULOS WHERE CODIGOARTICULO = '$codigo' ";
        $exeFamilias = ibase_query( $this->conexionFirebird(), $queryFamilia );

        return $this->fetchResults( $exeFamilias );
    }

    public function getInfoArticulo( $codigo , $sucursal)
    {
        $queryFind = "CFG_ARTICULOS.CODIGOARTICULO = '$codigo'";
        if( strpos($codigo , "\"") === 0 ){
            
            $codigo = str_replace("\"","", $codigo);
            
            $queryFind ="CFG_ARTICULOS.ID = '$codigo'";
        }
        
        
        $queryArticulo = "SELECT CFG_ARTICULOS.FAMILIA,CFG_ARTICULOS.SUBFAMILIA,CFG_ARTICULOS.DESCRIPCION,CFG_ARTICULOS.ID,
                                CFG_ARTICULOS.CODIGOARTICULO,( REF_ARTXALMACEN.EXISTOTAL - (REF_ARTXALMACEN.EXISPEDIDOS + REF_ARTXALMACEN.EXISPROCESO) ) AS EXISTENCIA
                            FROM CFG_ARTICULOS
                            LEFT JOIN REF_ARTXALMACEN ON CFG_ARTICULOS.ID = REF_ARTXALMACEN.FK1MCFG_ARTICULOS
                            WHERE REF_ARTXALMACEN.FK1MCFG_ALMACENES = '$sucursal' AND ( $queryFind ) ";
                            
                            
          $exeArticulo = ibase_query( $this->conexionFirebird() , $queryArticulo );
          return $this->fetchResults( $exeArticulo ) ;
    }

    public function stockAllAlmacenesbyFamilias( $familia)
    {
        $queryStockFamilia ="SELECT 	A2.descripcion as ALMACEN,
                                    (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK, CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.CODIGOARTICULO,CFGA.DESCRIPCION
                            FROM 	   (cfg_articulos CFGA 
                                    inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                                    inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                            WHERE 	CFGA.itemservicio='' 
                            AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 AND CFGA.FAMILIA = 'RIN' AND 	CFGA.familia NOT IN ('APARTADO','SERVICIO')
                            GROUP BY CFGA.FAMILIA,CFGA.SUBFAMILIA,CFGA.DESCRIPCION,CFGA.CODIGOARTICULO,ALMACEN,STOCK
                            ORDER 	by A2.descripcion,(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) DESC";
        $exeStockFamilia = ibase_query( $this->conexionIbase, $queryStockFamilia);

        return $this->fetchResults( $exeStockFamilia );
    }

    public function getInventarioProgramadoSucursal( $sucursal, $fecha )
    {
        $queryInventarioProgramado = "SELECT * FROM programacion_inventarios WHERE sucursal_id = $sucursal AND fecha = '$fecha' ";
        $exeInventarioProgramado = $this->conexion()->query( $queryInventarioProgramado );

        return $exeInventarioProgramado->fetch_all(MYSQLI_ASSOC);
    }

    public function getArticulosNoInventariados( $sucursal , $filtraFamilia= null )
    {

        $conUsuario = " USUARIO=''";
        $familiaUsuario = "('RIN','COLISION','LLANTA','ACCESORIO')";

        if ( session_status() == PHP_SESSION_ACTIVE && ( sizeof( $_SESSION) > 0 ) ) {
            $idUsuario = $_SESSION['usuario'];
            $conUsuario = " USUARIO ='$idUsuario' ";
        }

        if ( $filtraFamilia != null) {
            $familiaUsuario = "('$filtraFamilia')" ;
        }

        
        $queryArticulos = "SELECT CFG_ARTICULOS.CODIGOARTICULO,CFG_ARTICULOS.DESCRIPCION,CFG_ARTICULOS.FAMILIA,CFG_ARTICULOS.SUBFAMILIA,
                                                (REF_ARTXALMACEN.existotal-REF_ARTXALMACEN.exispedidos-REF_ARTXALMACEN.exisproceso) as STOCK 
                                            FROM CFG_ARTICULOS  
                                            INNER JOIN REF_ARTXALMACEN ON REF_ARTXALMACEN.FK1MCFG_ARTICULOS = CFG_ARTICULOS.ID
                                            WHERE CFG_ARTICULOS.ID NOT IN (SELECT FKCFG_ARTICULOS 
                                                                            FROM REF_INVENTARIOSTMP 
                                                                            WHERE REF_INVENTARIOSTMP.FKCFG_ALMACENES = $sucursal and $conUsuario ) AND REF_ARTXALMACEN.FK1MCFG_ALMACENES = $sucursal 
                                                AND CFG_ARTICULOS.FAMILIA  IN $familiaUsuario AND (REF_ARTXALMACEN.existotal-REF_ARTXALMACEN.exispedidos-REF_ARTXALMACEN.exisproceso)>0
                                            ORDER BY SUBFAMILIA,FAMILIA DESC ";
                                            
        //die( $queryArticulos );

        $exeArticulos = ibase_query($this->conexionFirebird(), $queryArticulos );
        
        return $this->fetchResults( $exeArticulos );
    }

    public function getSucursalesInventariosHechos( $mes , $tipo = 3 )
    {
        $queryInventarios = " SELECT inventarios.sucursal_id, count(fechaCaptura) as cant,day( fechaCaptura ) as dia , fechaCaptura
                                            FROM inventarios INNER JOIN csucursal ON inventarios.sucursal_id=csucursal.idprediction
                                            WHERE tipo = $tipo and month(fechaCaptura) = $mes
                                            AND    csucursal.status=1
                                            AND     idprediction>0
                                            GROUP BY dia,sucursal_id
                                            ORDER BY dia asc";
        $exeInventarioSucursal = $this->conexion()->query( $queryInventarios);                                

        return $exeInventarioSucursal->fetch_all( MYSQLI_ASSOC );
    }
    
    public function getSucursalesChecklist( $fechaInicio, $fechaFin )
    {   
        $a = $fechaInicio->format('Y-m-d');
        $fechaHoy =  date("Y")."-".date("m")."-".date("d");

        $queryCheckList = " SELECT * FROM checklist RIGHT JOIN csucursal ON checklist.sucursal = csucursal.id 
                              AND DATE(checklist.fecha) BETWEEN $a AND $fechaHoy
                              WHERE csucursal.status = 1 GROUP BY descripcion ORDER BY fecha ASC ";
        
        //echo $queryCheckList;
        $exeInventarioSucursal = $this->conexion()->query( $queryCheckList);                                

        return $exeInventarioSucursal->fetch_all( MYSQLI_ASSOC );
    }
	
	public function getSucursalesInventariosOmitidos( $anio, $mes, $dia, $sucursal, $tipo = "A" )
    {

        $join = $tipo == "A" ?  " INNER JOIN csucursal s ON s.idprediction=i.sucursal_id" : "INNER JOIN csucursal s ON s.idherramienta=i.sucursal_id ";
        $whereAlmacen = $tipo =="A" ? " s.idprediction=$sucursal " :" s.idherramienta = $sucursal ";
        $whereNotHerramienta = $tipo =="A" ? "AND 	i.subfamilia NOT IN ('HERRAMIENTA')" : " ";
        $array = [ 1 => 0 , 2=> null];
        $queryInventarios = " 	SELECT 	COUNT(*) AS cant, TIME(i.fechaCaptura) as fechaCaptura
								FROM 	inventarios i
								$join
								WHERE 	YEAR(i.fechaCaptura)=$anio
								AND 	MONTH(i.fechaCaptura)=$mes
								AND 	DAY(i.fechaCaptura)=$dia
								AND 	$whereAlmacen
								$whereNotHerramienta
                                GROUP BY DAY(i.fechaCaptura),s.descripcion";
        $exeInventarioSucursal = $this->conexion()->query( $queryInventarios);  
        


		$ok = 0;
		while($row = $exeInventarioSucursal->fetch_assoc()){

			if($row['cant']>0){
                $array[1] += 1;

            }
                
            
            $array[2] = $row["fechaCaptura"];
		}
        

        //Checa tabla de excepciones
        $joinExcepcion = $tipo == "A" ?  " INNER JOIN csucursal s ON s.idprediction=e.almacen" : "INNER JOIN csucursal s ON s.idherramienta=e.almacen ";
		$queryInventarios2 = " 	SELECT 	COUNT(*) AS cant
								FROM 	inventario_excepciones e
								$joinExcepcion
								WHERE 	YEAR(e.fecha)=$anio
								AND 	MONTH(e.fecha)=$mes
								AND 	DAY(e.fecha)=$dia
								AND 	$whereAlmacen";
        $exeInventarioSucursal2 = $this->conexion()->query( $queryInventarios2);   
		//echo $queryInventarios;
		while($row2 = $exeInventarioSucursal2->fetch_assoc()){
			if($row2['cant']>0){
                $array[1] += 1;
                $array[2] = strtotime("09:00:00");
            }
		}
		
        return $array;
    }

    public function getAuditoresInventariosOmitidos($anio,$mes,$dia,$auditor,$tipo="A")
    {
        $whereNotHerramienta = $tipo =="A" ? "AND 	i.subfamilia NOT IN ('HERRAMIENTA')" : " ";
        $array = [ 1 => 0 , 2=> null];
        $queryInventarios = "SELECT COUNT(*) AS cant, TIME(i.fechaCaptura) as fechaCaptura
        FROM inventarios i WHERE YEAR(i.fechaCaptura)=$anio AND MONTH(i.fechaCaptura)=$mes
        AND DAY(i.fechaCaptura)=$dia $whereNotHerramienta AND auditor='$auditor' GROUP BY DAY(i.fechaCaptura),i.auditor";
        $exeInventarioSucursal = $this->conexion()->query( $queryInventarios);
        $ok = 0;
        while($row = $exeInventarioSucursal->fetch_assoc())
        {
            if ($row['cant'] > 0) {
                $array[1] += 1;
            }
            $array[2] = $row["fechaCaptura"];
            }
        return $array;
    }


    public function getSucursalesInventariosHora($anio, $mes, $dia, $sucursal){
        $queryInventarios = " 	SELECT i.fechaCaptura
								FROM 	inventarios i
								INNER JOIN csucursal s ON s.idprediction=i.sucursal_id
								WHERE 	YEAR(i.fechaCaptura)=$anio
								AND 	MONTH(i.fechaCaptura)=$mes
								AND 	DAY(i.fechaCaptura)=$dia
								AND 	s.idprediction=$sucursal
								AND 	i.subfamilia NOT IN ('HERRAMIENTA')
								GROUP BY DAY(i.fechaCaptura),s.descripcion";
        $exeInventario = $this->conexion()->query( $queryInventarios); 
        return $exeInventario->fetch_all( MYSQLI_ASSOC );
    }
    
    public function getInventarioHerramienta( $sucursal , $fecha )
    {
         $queryInventarios = "SELECT  * , padre_inventario.id as idpadre, pempleado.nip,pempleado.idsucursal,pcontrato.idpuesto
                            FROM padre_inventario
                            INNER JOIN csucursal ON csucursal.idherramienta = padre_inventario.idalmacen
                            INNER JOIN inventarios ON inventarios.idpadre_inventario = padre_inventario.id
                            INNER JOIN pempleado ON pempleado.idsucursal = csucursal.id
                            INNER JOIN pcontrato ON pcontrato.nip = pempleado.nip
                            WHERE padre_inventario.fecha = '$fecha' AND csucursal.idherramienta = '$sucursal' and padre_inventario.accion_correctiva = 0
                                AND pempleado.status = 1 AND pcontrato.idpuesto = 47
                            ORDER BY padre_inventario.id desc";


        $exeInventario  =  $this->conexion()->query( $queryInventarios);

        return $exeInventario->fetch_all( MYSQLI_ASSOC );
    }

    public function setAccionCorrectivaInventario( $idLogInventario , $estado )
    {
        $queryAccionCorrectiva = "UPDATE padre_inventario SET  accion_correctiva = $estado  WHERE id = $idLogInventario ";

        return $this->conexion()->query( $queryAccionCorrectiva );
    }
    public function getDiferenciasAlmacen ( $sucursal ,$fecha )
    {
        $queryDiferencia = "SELECT * 
                            FROM dbnomina.inventarios
                            WHERE sucursal_id = $sucursal AND cast( fechaCaptura as date ) >= '$fecha' and tipo = 4 AND ( fisico2 is null OR fisico3 IS NULL )
                            ORDER BY id desc";
        $exeDiferencias =  $this->conexion()->query( $queryDiferencia );

        return $exeDiferencias->fetch_all( MYSQLI_ASSOC );
    }

    public function getCostoHerramienta( $codigo , $almacen )
    {
        $queryCostoHerramienta = "SELECT CTOPROMEDIO, CFG_PRECIOSXALMACENES.PVP1,CFG_PRECIOSXALMACENES.PVP4
        from CFG_ARTICULOS
        INNER JOIN REF_ARTXALMACEN ON REF_ARTXALMACEN.FK1MCFG_ARTICULOS = CFG_ARTICULOS.ID
        INNER JOIN CFG_PRECIOSXALMACENES ON CFG_PRECIOSXALMACENES.FK1MCFG_ARTICULOS = CFG_ARTICULOS.ID
        where CODIGOARTICULO = '$codigo' AND ( REF_ARTXALMACEN.EXISTOTAL- (EXISPEDIDOS+EXISPROCESO) ) > 0 AND REF_ARTXALMACEN.FK1MCFG_ALMACENES = $almacen" ; 
    
        $exeCosto =  ibase_query( $this->conexionIbase , $queryCostoHerramienta );

        return $this->fetchResults( $exeCosto );
    }

	public function getSucursalesInventariosOmitidosH( $anio, $mes, $dia, $sucursal )
    {
        $queryInventarios = " 	SELECT 	COUNT(*) AS cant
								FROM 	inventarios i
								INNER JOIN csucursal s ON s.idprediction=i.sucursal_id
								WHERE 	YEAR(i.fechaCaptura)=$anio
								AND 	MONTH(i.fechaCaptura)=$mes
								AND 	DAY(i.fechaCaptura)=$dia
								AND 	s.idprediction=$sucursal
								AND 	i.subfamilia  IN ('HERRAMIENTA')
								GROUP BY DAY(i.fechaCaptura),s.descripcion";
        $exeInventarioSucursal = $this->conexion()->query( $queryInventarios);   
		//echo $queryInventarios;
		$ok = 0;
		while($row = $exeInventarioSucursal->fetch_assoc())
			$ok++;
		
        return $ok;
    }

    public function registraInventario($data)
    {
        extract( $data );
        $fisico2 = "NULL";
        $fisico3 = "NULL";
        
        if ( $stock == $stkIngresado) {
            $fisico2 = $fisico3 = $stkIngresado;
        }
        if(isset($usuario))
            $queryRegistraInventario = "INSERT INTO inventarios VALUES ('','$codigo','$descripcion','$familia','$subfamilia','$stkIngresado',$fisico2,$fisico3,'$stock','$tipo','$sucursal', now(),NULL,'$usuario',1,NULL,NULL)";
        else
            $queryRegistraInventario = "INSERT INTO inventarios VALUES ('','$codigo','$descripcion','$familia','$subfamilia','$stkIngresado',$fisico2,$fisico3,'$stock','$tipo','$sucursal', now(),NULL,NULL,1,NULL,NULL)";
        $exeRegistraInventario = $this->conexion()->query( $queryRegistraInventario);
        echo $query;
        return $this->conexion()->insert_id;
    }

    public function RegistraInventarioGeneral( $data , $idHistorialInventario = -1)
    {
        $queryRegistraInventario = "";
        $cantRegistros = sizeof( $data );
        foreach ($data as $i => $producto) {
            extract( $producto );
            $fisico2 = "NULL";
            $fisico3 = "NULL";
            if($stock>=0){
                $stkIngresado = $stkIngresado;
            }else{
                $stkIngresado = 0;
            }   

            if ( $stock == $stkIngresado) {
                $fisico2 = $fisico3 = $stkIngresado;
            }
                     
            if(isset($usuario) || ( session_status() == PHP_SESSION_ACTIVE && sizeof( $_SESSION) > 0 )  ){
                $usuario = isset( $usuario) ? $usuario : $_SESSION['usuario'];
                $queryRegistraInventario .="('','$codigo','$descripcion','$familia','$subfamilia','$stkIngresado',$fisico2,$fisico3,'$stock','$tipo','$sucursal', now(),'$tiempoTranscurrido','$usuario',1,NULL,NULL)";
            }
            else{
                $usuario = isset( $usuario) ? $usuario : $_SESSION['usuario'];
                $queryRegistraInventario .="('','$codigo','$descripcion','$familia','$subfamilia','$stkIngresado',$fisico2,$fisico3,'$stock','$tipo','$sucursal', now(),'$tiempoTranscurrido','$usuario',1,".( $idHistorialInventario != -1 ? $idHistorialInventario : 'NULL') . ",NULL)";
            }
            if ( $i < $cantRegistros-1) {
                $queryRegistraInventario .=",";
            }
        }
        
        
        $registrado = $this->conexion()->query( "INSERT INTO inventarios VALUES $queryRegistraInventario" );
       // echo "INSERT INTO inventarios VALUES $queryRegistraInventario";
        if ( $registrado) {
            
            return 1;
        } else {
            return 0;
        }
        
        
    }

    public function registraLogInventario( $data )
    {
        extract( $data );

        $queryLogInventario = "INSERT INTO padre_inventario VALUES('',$almacen,'$fecha', '$observacion',0) ";

        $registrado = $this->conexion()->query( $queryLogInventario );
        
        if ( $registrado ) {
            return  $this->conexion->insert_id;
        } else {
            return -1;
        }
        
        
    }

    public function registarInventarioCodBarras( $query )
    {
        $registrado = $this->conexion()->query("INSERT INTO inventarios VALUES $query");
        if ( $registrado ) {
            return 1;
        }

        return 0;
    }

    public function actualizaRevisionInventarioCodigoBarra( $productos)
    {
        $conect = $this->conexion();
        foreach ( $productos as $i => $producto) {
            if( $producto->FISICO2 == null ){
                $conect->query("UPDATE inventarios set fisico2 = $producto->CANTIDAD WHERE id = $producto->IDINVENTARIO ");
            }elseif ( $producto->FISICO3 == null) {
                $conect->query("UPDATE inventarios set fisico3 = $producto->CANTIDAD WHERE id = $producto->IDINVENTARIO");
            }
        }
    }

    public function actualizaRevisionInventario($productos)
    {
        $conect = $this->conexion();
        $queryActualizaRevision = "";
        $queryActualizaRevisionOk = "";
        // $sucursal = $productos[0]['sucursal'];
        // $familia =  $productos[0]['familia'];
        $sentenceORok ="";
        $sentenceOR = "";

        $cantRegistros = sizeof( $productos );
        foreach ($productos as $i => $producto) {
            extract( $producto );
            if ( $stkIngresado == '') {
                $stkIngresado = 0;
            }
            $campoOk = "";
            if ($stkIngresado == $stock) {
                $campoOk =" , fisico3=$stkIngresado ";
                $queryActualizaRevisionOk .= " WHEN $id THEN $stkIngresado";
                $sentenceORok .= " id=$id OR";
            }else{
                $queryActualizaRevision .= " WHEN $id THEN $stkIngresado";
                $sentenceOR.= " id=$id OR";
            }

        }

            $cuentaStockOk = 0;
            $sentenceOR = substr($sentenceOR,0,-2);
            $sentenceORok = substr( $sentenceORok, 0,-2);
            if ( strlen( $queryActualizaRevisionOk) > 0) {
                $queryActualizaRevisionOk .=" END, fisico3 = CASE id";
                foreach ($productos as $i => $producto) {
                    extract( $producto );
                    if ( $stkIngresado == '') {
                        $stkIngresado = 0;
                    }
                    $queryActualizaRevisionOk .= " WHEN $id THEN $stkIngresado";
                }
                $queryActualizaRevisionOk .=" END";

                $conect->query("UPDATE inventarios set fisico2 = CASE id $queryActualizaRevisionOk WHERE $sentenceORok");
                
                $cuentaStockOk = $conect->affected_rows;
                
            }
            $totalActualizados = $cuentaStockOk;
            if ( strlen($queryActualizaRevision) > 0) {
                    
                    $queryActualizaRevision .= " END";
                    $conect->query("UPDATE inventarios set $campo = CASE id $queryActualizaRevision WHERE $sentenceOR");
                     $totalActualizados = $cuentaStockOk + $conect->affected_rows;
            }
        return $totalActualizados;
    }

    public function fetchResults( $executedQuery)
    {
        $arrayResultados = array();
        while ( $item =  ibase_fetch_object( $executedQuery )) {
            array_push( $arrayResultados, $item);
        }

        return $arrayResultados;
    }
	
	public function fecthrows( $arrayFetch)
    {
        $fetchrows = array();
        foreach($arrayFetch as $row){
            $fetchrows[] = $row;
        }

        return $fetchrows;
    }
}
