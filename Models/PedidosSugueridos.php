<?php
date_default_timezone_set("America/Mexico_City");
require_once $_SERVER['DOCUMENT_ROOT']."/inventarioSucursales/Config/Database.php";

class PedidosSugeridos 
{
    public $conexionIbase;

    public function __construct(){
         $this->conexionIbase = @ibase_pconnect(HOST,USER,PASS) or die("Error al conectarse a la base de datos: ".ibase_errmsg());
    }

    public function conexionMysql()
    {
        $conexionMysql = new mysqli(SERVERMYSQL,USERMYSQL,PASSMYSQL,DBMYSQL);
        $conexionMysql->query("SET NAMES 'utf8'");

        return $conexionMysql;
    }

    public function getSucursalesConAlmacen()
    {
        /*$querySucursales = "SELECT * FROM dbnomina.csucursal where idprediction > 0";
        $exeSucursales = $this->conexionMysql()->query( $querySucursales );
        return $exeSucursales->fetch_all( MYSQLI_ASSOC);*/
		$querySucursales = "SELECT ID,DESCRIPCION FROM CFG_ALMACENES WHERE ACTIVO='S'";
    
        $exeSucursales = ibase_query( $this->conexionIbase, $querySucursales);                                        
        

        return $this->fetchResults($exeSucursales);
    }

    public function getExistenciaArticulo( $codigo, $almacen )
    {
        $queryExistencia = "SELECT   (REF_ARTXALMACEN.EXISTOTAL - exispedidos -exisproceso) as STOCK
                                FROM CFG_ARTICULOS
                                INNER JOIN REF_ARTXALMACEN ON REF_ARTXALMACEN.FK1MCFG_ARTICULOS = CFG_ARTICULOS.ID
                                WHERE REF_ARTXALMACEN.FK1MCFG_ALMACENES = $almacen  and codigoarticulo = '$codigo' ";
                                echo $queryExistencia."<br><br>";
        $exeArticulo = ibase_query( $this->conexionIbase, $queryExistencia);      

        return $this->fetchResults( $exeArticulo );
    }

    public function getInfoArticulo( $codigo )
    {

        $queryArticulo = "SELECT 	                    
							CFGA.CODIGOARTICULO as CODIGO,
							(SELECT REPLACE(REPLACE(PA1.PVP1,'$',''),',','') FROM cfg_preciosxalmacenes PA1 WHERE PA1.fk1mcfg_articulos=CFGA.id AND PA1.fk1mcfg_almacenes=10754) as PVP1, ";
			for($i=2;$i<=3;$i++){
					$queryArticulo.= "(SELECT REPLACE(REPLACE(PA1.PVP".$i.",'$',''),',','') FROM cfg_preciosxalmacenes PA1 WHERE PA1.fk1mcfg_articulos=CFGA.id AND PA1.fk1mcfg_almacenes=10754) as PVP".$i.",";
			}			
	

		$queryArticulo.= "	RAXA.CTOPROMEDIO as COSTO
                            from 	   cfg_articulos CFGA 
                                    inner join ref_artxalmacen RAXA on CFGA.id=RAXA.fk1mcfg_articulos 
                                    inner join cfg_almacenes A2 on A2.id=RAXA.fk1mcfg_almacenes 
                            where 	CFGA.itemservicio=''
                                        AND 		(RAXA.existotal-RAXA.exispedidos-RAXA.exisproceso) >=0
                                        AND 		CFGA.familia NOT IN ('APARTADO','SERVICIO')
                                        AND         CFGA.CODIGOARTICULO = '$codigo' AND RAXA.CTOPROMEDIO> 0";
    
        $exeArticulo = ibase_query( $this->conexionIbase, $queryArticulo);                                        
        

        return $this->fetchResults($exeArticulo);;
    }
    public function getProductosVendidos( $data)
    {
        extract( $data );
        $queryProductosVendidos=   "select  DET.codigo as codigo,
                    A.descripcion as descripcion,
                    EXTRACT(MONTH FROM P.fecha) as mes,
                    EXTRACT(YEAR FROM P.fecha) as anio,
                    SUM(DET.CANTIDAD) as cantidad,
                    A.familia as familia,
                    A.subfamilia as subfamilia,
                    (SELECT SUM(aral.EXISTOTAL - (aral.EXISPEDIDOS + aral.EXISPROCESO)) FROM CFG_ALMACENES al, CFG_ARTICULOS ar, REF_ARTXALMACEN aral WHERE al.ID=aral.FK1MCFG_ALMACENES AND aral.FK1MCFG_ARTICULOS=ar.ID AND al.ACTIVO='S' AND ar.ACTIVO='S' AND ar.CODIGOARTICULO=DET.CODIGO GROUP BY ar.CODIGOARTICULO) as EXISTENCIA
            from    ref_pedidospresup P 
            inner join REF_DETPEDIDOSPRESUP DET on P.id=DET.fkpadref_pedidospresup 
            inner join CFG_ARTICULOS A ON DET.CODIGO=A.CODIGOARTICULO
            where   P.status in ('PEDIDO EMITIDO','PEDIDO FACTURADO') 
            and     P.fecha>=CAST('".$mes."/01/".$anio."' as date)
            and 	P.espedliqapartado<>'AA'
            and     P.SERDOCTO<>'CREDITO' AND A.familia != 'SERVICIO' AND FAMILIA IN ( 'LLANTA','RIN','COLISION','ACCESORIO')
            and 	P.FK1MCFG_ALMACENES LIKE '$sucursal%' 
            group by DET.codigo,EXTRACT(YEAR FROM P.fecha),EXTRACT(MONTH FROM P.fecha),A.DESCRIPCION,A.FAMILIA,A.SUBFAMILIA 
            order by DET.codigo,EXTRACT(YEAR FROM P.fecha),EXTRACT(MONTH FROM P.fecha) ASC";
        $exeProductosVendidos = ibase_query( $this->conexionIbase, $queryProductosVendidos);

        return $this->fetchResults( $exeProductosVendidos);
    }


    public function getComprasProductos( $data)
    {
        extract( $data );
        $queryCompras =   "select  DET.codigo as codigo,
                    A.descripcion as descripcion,
                    EXTRACT(MONTH FROM C.fecha) as mes,
                    EXTRACT(YEAR FROM C.fecha) as anio,
                    SUM(DET.CANTIDAD) as cantidad
            from    ref_comprastraspregs C 
            inner join REF_DETCOMPRASTRASPREGS DET on C.id=DET.fkpadref_comprastraspregs 
            inner join CFG_ARTICULOS A ON DET.CODIGO=A.CODIGOARTICULO
            where   C.status in ('COMPRA EMITIDO','ENTRADA EMITIDO') 
            and     C.fecha>=CAST('".$mes."/01/".$anio."' as date)
            and 	C.FK1MCFG_ALMACENES LIKE '$sucursal%' 
            group by DET.codigo,EXTRACT(YEAR FROM C.fecha),EXTRACT(MONTH FROM C.fecha),A.DESCRIPCION 
            order by DET.codigo,EXTRACT(YEAR FROM C.fecha),EXTRACT(MONTH FROM C.fecha) ASC";

            $exeCompras = ibase_query( $this->conexionIbase, $queryCompras);

            return fetchResults( $exeCompras);
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
