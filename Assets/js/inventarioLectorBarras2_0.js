let root = new Vue({
    el:"#app",
    data:{
        sucursal: -1,
        sucursales: [],
        codigo: '',
        listaItems:[ ],
        recovery: [],
        finalizado: false,
        showModal: false,
        listaItemsRevision: [],
        ultimoLeido : {}
        
    },
    methods: {
        cantidadActualizar: function ( codigo  ) {
            // Obteniendo el valor del input correspondiente del articulo
            let newValue = document.getElementById(`item_${codigo}`).value;
            console.log( this.listaItems );
            let _this = this;
            Array.prototype.forEach.call(  this.listaItems, function (item , i) { 
                if ( item.CODIGOARTICULO == codigo) {
                    _this.listaItems[i].CANTIDAD = parseFloat( newValue );
                    localStorage.setItem("progresoInventario", JSON.stringify({ sucursal: _this.sucursal, items: _this.listaItems }) );
                    document.getElementById("incodigo").focus();
                    return 0;
                }
         } );
          },
        cargaSucursales: function () {
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
        findArticulo: function () {
            
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
            this.ultimoLeido.codigo = this.codigo;
            // this.codigo = this.codigo.replace(/'/g,"-")
            
            let itemAgregado = ( this.localFindItem() );
            if ( itemAgregado != -1) {
                
                
                this.listaItems[ itemAgregado ].CANTIDAD += 1;
                this.ultimoLeido.cantidad = this.listaItems[ itemAgregado ].CANTIDAD;

                localStorage.setItem("progresoInventario", JSON.stringify({ sucursal: this.sucursal, items: this.listaItems }) );
            } else {
                axios.get("/inventarioSucursales/Controllers/inventarios2.php",{
                    params:{
                        opc: "buscarArticulo",
                        codigo:  this.codigo,
                        sucursal: this.sucursal
                    }
                }).then( (result ) =>{
                     let _this = this;
                    Array.prototype.forEach.call(  result.data, function (item , i) { 
                        _this.ultimoLeido.descripcion = item.DESCRIPCION;
                        _this.ultimoLeido.cantidad = 1;
                            item.CANTIDAD = 1;
                            _this.listaItems.push( item );
                     } );
                    element = document.getElementById("containerInventario")
                    element.scrollTop = document.getElementById("tablaListado").scrollHeight+15;
                     localStorage.setItem("progresoInventario", JSON.stringify({ sucursal: this.sucursal, items: this.listaItems }) );
                    //  console.log( JSON.stringify( this.listaItems ) );
                     
                }).catch( (error ) =>{

                });
            }
            this.codigo ='';
        },
        localFindItem : function () {
            _this = this;
           return  _this.listaItems.findIndex(  function ( element , index) {
                _this.ultimoLeido.descripcion = element.DESCRIPCION;
                return ( element.CODIGOARTICULO == _this.codigo.toUpperCase() ) || element.ID == _this.codigo.toUpperCase().replace(/"/g, "") ;
            })
        },
        verificaDatosGuardados: function () {  
            if ( localStorage.getItem("progresoInventario") != undefined) {
                this.recovery = JSON.parse(localStorage.getItem("progresoInventario"));
                this.listaItems =  this.recovery.items;
                this.sucursal = this.recovery.sucursal;
            }
        },
        reset: function () { 
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
            let parseData = [];
            const _this = this;
            Array.prototype.forEach.call( this.listaItems , function (item, i) {
                
                    parseData.push({
                        descripcion: item.DESCRIPCION,
                        codigo: item.CODIGOARTICULO,
                        subfamilia: item.SUBFAMILIA,
                        familia: item.FAMILIA,
                        stock: item.EXISTENCIA,
                        stkIngresado: item.CANTIDAD,
                        tipo: 4,
                        sucursal: _this.sucursal,
                        campo: item.FISICO2 == null ? 'fisico2' : 'fisico3',
                        id: item.IDINVENTARIO != undefined ? item.IDINVENTARIO : 0,
                        tiempoTranscurrido: -1,
                    });
              });

            axios.post("/inventarioSucursales/Controllers/inventarios2.php",
            {
                action: "codBarras",
                tipo: 4,
                inventario: parseData,
                sucursal: this.sucursal
            }).then( ( result ) =>{
                if ( result.data == 1) {
                    alert("Inventario registrado");
                    this.finalizado = true;
                    let _this = this;
                    // localStorage.removeItem("progresoInventario");
                    Array.prototype.forEach.call( this.listaItems , function ( item, index){
                        _this.listaItems[index].DIFERENCIA =   item.CANTIDAD - item.EXISTENCIA ;
                        
                    });
                    
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
        }
    },
    watch: {
        sucursal: function () {
            
            
            
            axios.get("/inventarioSucursales/controllers/inventarios2.php", {
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

          }
    },
})

root.cargaSucursales();
root.verificaDatosGuardados();


  
// document.getElementById("incodigo").focus();
function preventFocusOut( e) {
    // document.getElementById("incodigo").focus();
    // e.preventDefault();
}

function setFocusCantidad( element) {
    element.focus();
    
}