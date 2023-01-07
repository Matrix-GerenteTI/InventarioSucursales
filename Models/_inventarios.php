<?php
date_default_timezone_set("America/Mexico_City");
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Config/Database.php";
class Inventario
{
    public $conexionIbase;

    public function __construct(){
         $this->conexionIbase = @ibase_pconnect(HOST,USER,PASS) or die("Error al conectarse a la base de datos: ".ibase_errmsg());
    }

    protected function conexion(){
        $conexion = new mysqli(SERVERMYSQL,USERMYSQL,PASSMYSQL,DBMYSQL);
        $conexion->query("SET NAMES 'utf8'");

        return $conexion;
    }

    public function getSucursalesConAlmacen()
    {
        $querySucursales = "SELECT r.ID, r.DESCRIPCION, r.ACTIVO, r.PWD, r.ZONA, r.FACTORK, r.TPVREM
                                        FROM CFG_ALMACENES r
                                        WHERE ZONA NOT IN ('RESERVADOS','','HERRAMIENTAS','MERMA') 
                                            AND DESCRIPCION NOT IN('PRESTAMOS CENTRO','SEMINUEVOS','APARTADOS_LEONSCLC')";
        $exeSucursales = ibase_query($this->conexionIbase, $querySucursales );
        return $this->fetchResults($exeSucursales);
    }

    public function getInventarioRealizado( $data)
    {
        extract( $data );
        $getInventarioSucursal = "SELECT * 
        FROM dbnomina.inventarios 
        where fechaCaptura like '$fecha%' AND tipo = $tipo AND sucursal_id = $sucursal AND familia = '$familia' ";
        $exeInventarioSucursal = $this->conexion()->query( $getInventarioSucursal);

        return $exeInventarioSucursal->fetch_all( MYSQLI_ASSOC);
    }
    public function getFamilias()
    {
        $queryFamilias = "SELECT FAMILIA
                                        FROM CFG_ARTICULOS 
                                        where FAMILIA != 'APARTADO' AND FAMILIA !='' AND FAMILIA !='SERVICIO'
                                        group by familia";
        $exeFamilias = ibase_query($this->conexionIbase, $queryFamilias);

        return $this->fetchResults( $exeFamilias);
    }
    
    public function getStockFamilia( $sucursal, $familia )
    {
        $queryStockFamilias = "SELECT 	CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,
                        (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) as STOCK
                FROM 	   (cfg_articulos CFGA 
                        inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                        inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes ) 
                WHERE 	CFGA.itemservicio=''  AND RAXA.FK1MCFG_ALMACENES = $sucursal
                AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso)>0 AND CFGA.FAMILIA='$familia'
                GROUP BY CFGA.CODIGOARTICULO,CFGA.DESCRIPCION,CFGA.SUBFAMILIA,STOCK
                ORDER 	by (RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) DESC ";

        $exeStockFamilia = ibase_query( $this->conexionIbase, $queryStockFamilias);
        return $this->fetchResults( $exeStockFamilia);
    }

    public function getStockGeneral( $sucursal, $familia)
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
    public function getInventariosSucursal( $data)
    {
        extract( $data);
        $queryInventario = "SELECT inventarios.*, csucursal.descripcion as sucursal 
                                FROM inventarios
                                INNER JOIN csucursal ON csucursal.idprediction = inventarios.sucursal_id
                                where fechaCaptura like '$fecha%' AND sucursal_id = $sucursal 
                                order by familia,subfamilia,descripcion desc";
                                
        $exeInventarioSucursal = $this->conexion()->query( $queryInventario);                                

        return $exeInventarioSucursal->fetch_all( MYSQLI_ASSOC );
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
    public function registraInventario($data)
    {
        extract( $data );
        $fisico2 = "NULL";
        $fisico3 = "NULL";
        
        if ( $stock == $stkIngresado) {
            $fisico2 = $fisico3 = $stkIngresado;
        }
        $queryRegistraInventario = "INSERT INTO inventarios VALUES ('','$codigo','$descripcion','$familia','$subfamilia','$stkIngresado',$fisico2,$fisico3,'$stock','$tipo','$sucursal', now())";
        $exeRegistraInventario = $this->conexion()->query( $queryRegistraInventario);
        return $this->conexion()->insert_id;
    }

    public function RegistraInventarioGeneral( $data)
    {
        $queryRegistraInventario = "";
        $cantRegistros = sizeof( $data );
        foreach ($data as $i => $producto) {
            extract( $producto );
            $fisico2 = "NULL";
            $fisico3 = "NULL";
            
            if ( $stock == $stkIngresado) {
                $fisico2 = $fisico3 = $stkIngresado;
            }            

            $queryRegistraInventario .="('','$codigo','$descripcion','$familia','$subfamilia','$stkIngresado',$fisico2,$fisico3,'$stock','$tipo','$sucursal', now())";
            if ( $i < $cantRegistros-1) {
                $queryRegistraInventario .=",";
            }
        }
        
        
        $registrado = $this->conexion()->query( "INSERT INTO inventarios VALUES $queryRegistraInventario" );
        if ( $registrado) {
            return 1;
        } else {
            return 0;
        }
        
        
    }

    public function actualizaRevisionInventario($data)
    {
        extract( $data );
        $campoOk = "";
        if ($stkIngresado == $stock) {
            $campoOk =" , fisico3=$stkIngresado ";
        }
        $queryActualizaRevision = "UPDATE inventarios SET $campo ='$stkIngresado' $campoOk WHERE id = $id ";
        echo $queryActualizaRevision;
        $exeActualizaRevision = $this->conexion()->query( $queryActualizaRevision);
        return $this->conexion()->affected_rows;
    }

    public function fetchResults( $executedQuery)
    {
        $arrayResultados = array();
        while ( $item =  ibase_fetch_object( $executedQuery )) {
            array_push( $arrayResultados, $item);
        }

        return $arrayResultados;
    }
}
