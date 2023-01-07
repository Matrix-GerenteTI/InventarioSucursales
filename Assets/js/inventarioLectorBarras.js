let root = new Vue({
    el:"#app",
    data:{
        sucursal: -1,
        sucursales: [],
        fam: -1,
        familias: [],
        subfam: -1,
        subfamilias: [],
        codigo: '',
        listaItems:[ ],
        recovery: [],
        finalizado: false,
        showModal: false,
        listaItemsRevision: [],
        ultimoLeido : {},
        usuario: '0'
    },
    methods: {
		checaSesion: function(){
			axios.get("/inventarioSucursales/Controllers/inventarios.php",{
                params:{
                    opc: 'checaSesion'
                }
            }).then((result) => {
					console.log(result.data)
                if(result.data==0){
					document.location.href = "http://servermatrixxxb.ddns.net:8181/inventarioSucursales/inventario.php?closeSession";
				}
            }).catch((err) => {
                
            });
		},
        cantidadActualizar: function ( codigo  ) {
			this.checaSesion();
            // Obteniendo el valor del input correspondiente del articulo
            let newValue = document.getElementById(`item_${codigo}`).value;
            console.log( this.listaItems );
            let _this = this;
            Array.prototype.forEach.call(  this.listaItems, function (item , i) { 
                if ( item.CODIGOARTICULO == codigo) {
                    _this.listaItems[i].CANTIDAD = parseFloat( newValue );
                    _this.listaItems[i].DIFERENCIA = parseFloat( newValue ) - _this.listaItems[i].STOCK;
                    localStorage.setItem("progresoInventario", JSON.stringify({ sucursal: _this.sucursal, items: _this.listaItems, familia: this.fam, subfamilia: this.subfam }) );
                    document.getElementById("incodigo").focus();
                    return 0;
                }
         } );
          },
        getUsuario: function () {
            console.log("Entra aqui alv")
            axios.get("/inventarioSucursales/Controllers/inventarios2.php",{
                params:{
                    opc: 'getUser'
                }
            }).then((result) => {
                console.log(result.data)
                console.log("sptm")
                this.usuario = result.data;         
				document.getElementById("nameuser").innerHTML = this.usuario;
            }).catch((err) => {
                
            });
        },
        cargaSucursales: function () {
            this.checaSesion();
			axios.get('/inventarioSucursales/Controllers/inventarios2.php', {
                params: {
                    opc: 'sucursales'
                }
            }).then((response) => {
                this.sucursales = response.data;
            }).catch((err) => {
                console.log("Error");
            });
        },
        cargaFamilias: function () {
			this.checaSesion();
            axios.get('/inventarioSucursales/Controllers/inventarios2.php', {
                params: {
                    opc: 'familias',
                    tipoinventario: 2,
                    sucursal: this.sucursal
                }
            }).then((response) => {
                this.familias = response.data;
            }).catch((err) => {
                console.log("Error");
            });
        },
        cargaSubfamilias: function () {
			this.checaSesion();
            axios.get('/inventarioSucursales/Controllers/inventarios2.php', {
                params: {
                    opc: 'subfamilias',
                    tipoinventario: 2,
                    sucursal: this.sucursal,
                    familia: this.fam
                }
            }).then((response) => {
                this.subfamilias = response.data;
            }).catch((err) => {
                console.log("Error");
            });
        },
        findArticulo: function () {
			this.checaSesion();
            if ( this.recovery != [] ) {
                
                
                if (this.sucursal != this.recovery.sucursal  && ( this.sucursal != -1 && this.recovery == [] ) ){
                    let confirmacion = confirm("Ya tienes un inventario sin concluir en otra sucursal\n ¿Estas seguro de eliminar el progreso?");
                    if ( confirmacion ) {
                        this.recovery = [];
                        localStorage.removeItem('progresoInventario' );
                    } 
                }else if( this.recovery.sucursal != undefined) {
                    console.log( this.recovery );
                    
                    this.sucursal = this.recovery.sucursal;
                    this.listaItems = this.recovery.items;
                    this.recovery = [];
                }
            }

            
            this.codigo = this.codigo.replace(/-/g,"/")
            console.log( this.codigo );
            this.codigo = this.codigo.replace(/'/g, "-")
            console.log( this.codigo );
            //this.ultimoLeido.codigo = this.codigo;
            // this.codigo = this.codigo.replace(/'/g,"-")
            
            let itemAgregado = ( this.localFindItem() );
            if ( itemAgregado != -1) {
                this.ultimoLeido.codigo = this.codigo;
                console.log(this.listaItems[ itemAgregado ].CANTIDAD)
                this.listaItems[ itemAgregado ].CANTIDAD += 1;
                this.ultimoLeido.cantidad = this.listaItems[ itemAgregado ].CANTIDAD;
                this.ultimoLeido.descripcion = this.listaItems[ itemAgregado ].DESCRIPCION;
                this.listaItems[ itemAgregado ].DIFERENCIA = this.listaItems[ itemAgregado ].CANTIDAD - this.listaItems[ itemAgregado ].STOCK;
                localStorage.setItem("progresoInventario", JSON.stringify({ sucursal: this.sucursal, items: this.listaItems, familia: this.fam, subfamilia: this.subfam }) );
                window.location.href = "#item_"+this.listaItems[ itemAgregado ].CODIGOARTICULO;
                document.getElementById("incodigo").focus();
            } else {
                //if(confirm("El producto leido no se encuentra en el valuado de la sucursal, ¿Desea registrarlo?")){
                    axios.get("/inventarioSucursales/Controllers/inventarios2.php",{
                        params:{
                            opc: "buscarArticulo",
                            codigo:  this.codigo,
                            sucursal: this.sucursal
                        }
                    }).then( (result ) =>{
                        console.log(result.data.length)
                        if(result.data.length>0){
                            this.ultimoLeido.codigo = this.codigo;
                            let _this = this;
                            Array.prototype.forEach.call(  result.data, function (item , i) { 
                                _this.ultimoLeido.descripcion = item.DESCRIPCION;
                                _this.ultimoLeido.cantidad = 1;
                                    item.CANTIDAD = 1;
                                    item.STOCK = 0;
                                    item.DIFERENCIA = item.CANTIDAD - item.STOCK
                                    _this.listaItems.push( item );
                            } );
                            localStorage.setItem("progresoInventario", JSON.stringify({ sucursal: this.sucursal, items: this.listaItems, familia: this.fam, subfamilia: this.subfam }) );
                            element = document.getElementById("containerInventario")
                            element.scrollTop = document.getElementById("tablaListado").scrollHeight+5;
                            
                        }else{
                            alert("Producto Inexistente")
                            this.ultimoLeido.codigo = this.codigo;
                            this.ultimoLeido.descripcion = "Producto Inexistente";
                            this.ultimoLeido.cantidad = 0;
                        }
                         document.getElementById("incodigo").focus();
                         
                    }).catch( (error ) =>{
    
                    })
                //}else{
                //    document.getElementById("incodigo").focus();
                //}
                /*
                ;
                */
            }
            this.codigo ='';
        },
        localFindItem : function () {
            _this = this;
           return  _this.listaItems.findIndex(  function ( element , index) {
                //_this.ultimoLeido.descripcion = element.DESCRIPCION;
                return ( element.CODIGOARTICULO == _this.codigo.toUpperCase() ) || element.ID == _this.codigo.toUpperCase().replace(/"/g, "") ;
            })
        },
        verificaDatosGuardados: function () {  
			this.checaSesion();
            if ( localStorage.getItem("progresoInventario") != undefined) {
                this.recovery = JSON.parse(localStorage.getItem("progresoInventario"));
                this.listaItems =  this.recovery.items;
                this.sucursal = this.recovery.sucursal;                
                this.familia = this.recovery.familia;
                this.subfamilia = this.recovery.subfamilia;
            }
        },
        reset: function () {
			this.checaSesion();			
            const confirmacion  = confirm("Todo el progeso hasta el momento se perdera");
            if (confirmacion ) {
                this.listaItems = [];
                this.recovery = [];
                this.finalizado = false;
                localStorage.removeItem("progresoInventario");
            }
         },
         editaCapturado: function (param) { 
            alert("")
          },
        guardar: function () {  
			this.checaSesion();
            let parseData = [];
            const _this = this;
            Array.prototype.forEach.call( this.listaItems , function (item, i) {
                
                    parseData.push({
                        descripcion: item.DESCRIPCION,
                        codigo: item.CODIGOARTICULO,
                        subfamilia: item.SUBFAMILIA,
                        familia: item.FAMILIA,
                        stock: item.STOCK,
                        stkIngresado: item.CANTIDAD,
                        tipo: 4,
                        sucursal: _this.sucursal,
                        campo: item.FISICO2 == null ? 'fisico2' : 'fisico3',
                        id: item.IDINVENTARIO != undefined ? item.IDINVENTARIO : 0,
                        tiempoTranscurrido: -1,
                        usuario: _this.usuario
                    });
              });

            axios.post("/inventario/Controllers/inventarios2.php",
            {
                action: "codBarras",
                tipo: 4,
                inventario: parseData,
                sucursal: this.sucursal
            }).then( ( result ) =>{
                if ( result.data == 1) {
                    alert("Inventario registrado");
                    this.finalizado = true;
					window.open("http://servermatrixxxb.ddns.net:8181/intranet/controladores/reportes/valuados/inventario_vs_valuado.php?sucursal="+this.sucursal+"&usuario="+this.usuario,"_blank")
                    
                }else if( result.data == 0){
                    alert("Hay un error en los datos ingresados");
                }else{
                    alert("Ocurrió el siguiente error en el servidor: "+ result.data );
                }

            }).catch( ( error )=>{

            } );
        },
        getDiferencias: function () {  
            this.showModal = false;
            this.listaItems = this.listaItemsRevision;
        },
        getValuado:function () {
			this.checaSesion();
            let _this = this;
            let tipoDeInventario = "general"
            
            axios.get("/inventarioSucursales/Controllers/inventarios.php",{
                params:{
                    opc: tipoDeInventario,
                    familia: this.fam,
                    subfamilia: this.subfam,
                    sucursal: this.sucursal
                }
            }).then((result) => {
                console.log(result, "retornando");
                let response =result.data;
                if( response.error == undefined){
                    console.log("dentro del primer if")
                    if (response.length > 0) {
                        console.log("dentro del segundo if")
                        Array.prototype.forEach.call(  result.data, function (item , i) { 
                            console.log("dentro del foreach", item)
                            item.CANTIDAD = 0;
                            item.DIFERENCIA = item.CANTIDAD - item.STOCK
                            _this.listaItems.push( item );
                         } );
                         this.listaItems = result.data;
                         console.log("saliendo del foreach", this.listaItems)
                        localStorage.setItem("progresoInventario", JSON.stringify({ sucursal: this.sucursal, items: this.listaItems, familia: this.fam, subfamilia: this.subfam}) );
 
                    } else {
                        alert("No hay Productos de la familia y/o subfamilia seleccionada en la sucursal.");
                        //console.log(this.familia + ""+ this.subfamilia + ''+  this.sucursal);
                        
                    }
                }
                console.log("saliendo del primer if")
            })
            .catch((err) => {
                console.log(err)
            });
        },
    },
    watch: {
        fam: function(){
            //console.log(this.sucursal)
            this.cargaSubfamilias()
        },
        sucursal: function () {
            this.cargaFamilias()
            
            /*
            axios.get("/inventario/controllers/inventarios2.php", {
                params :{
                    sucursal: this.sucursal,
                    opc :"getRevisiones"
                }
            }).then( result => {
                if ( result.data.length > 0) {
                    this.showModal = true;
                    this.listaItemsRevision = result.data;
                }
            }).catch( err => {

            })
            */

        }
    },
})

root.getUsuario();
root.cargaSucursales();
root.verificaDatosGuardados();
root.cargaFamilias();
  
// document.getElementById("incodigo").focus();
function preventFocusOut( e) {
    // document.getElementById("incodigo").focus();
    // e.preventDefault();
}

function setFocusCantidad( element) {
    //element.focus();
    
}

function closeSession(){
	window.open("http://servermatrixxxb.ddns.net:8181/inventarioSucursales/inventario.php?closeSession")
}