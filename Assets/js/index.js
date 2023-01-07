let globalInventario;

let vmDashboard = new Vue({
    el: "#dashboard",
    data: {
        sucursal: -1,
        tipoInventario: -1,
        familia: -1,
        subfamilia: '%',
        sucursales: [],
        familias: [],
        subfamilias: [],
        inventario: [],
        loading: false,
        visibleDashboard: true,
        visibleIndividual: false,
        confirmar: true,
        programadoDisable: false,
        time_in_minutes: 30,
        current_time: 0,
        deadline: 0,
        transcurrido: 0,
        guardadoPresionado: false,
        cancelarPresionado: false,
        statusInputsFamililiaSubFam: true,
        observacionesHerramientas: '',
        showModalObservacion: false,
        esNuevoConteo: true
    },
    methods: {

        time_remaining: function(endtime) {
            var t = Date.parse(endtime) - Date.parse(new Date());
            var seconds = Math.floor((t / 1000) % 60);
            var minutes = Math.floor((t / 1000 / 60) % 60);
            var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
            var days = Math.floor(t / (1000 * 60 * 60 * 24));
            return { 'total': t, 'days': days, 'hours': hours, 'minutes': minutes, 'seconds': seconds };
        },
        run_clock: function(id, endtime) {
            var clock = document.getElementById(id);
            console.log(clock);
            let _this = this;

            function update_clock() {
                var t = _this.time_remaining(endtime);
                clock.innerHTML = 'Restante<br> ' + t.minutes + ': ' + t.seconds;
                _this.transcurrido++;
                if (t.minutes == 0 && t.seconds == 0) {
                    alert("Tiempo agotado");
                    _this.guardaInventario();
                }
                if (t.total <= 0) { clearInterval(timeinterval); }
                if (_this.guardadoPresionado) {
                    _this.guardadoPresionado = !_this.guardadoPresionado
                    clearInterval(timeinterval);
                }
                if (_this.cancelarPresionado) {
                    _this.cancelarPresionado = !_this.cancelarPresionado;
                    clearInterval(timeinterval);
                }
            }
            update_clock(); // run function once at first to avoid delay
            var timeinterval = setInterval(update_clock, 1000);
        },
        showInventario: function() {
            let _this = this;
            let tipoDeInventario = "aleatorio";
            if (this.tipoInventario == 1) {
                tipoDeInventario = "general";
            } else if (this.tipoInventario == 5) {
                tipoDeInventario = "herramienta";
            }
            axios.get("/inventarioSucursales/Controllers/inventarios.php", {
                params: {
                    opc: tipoDeInventario,
                    familia: this.familia,
                    subfamilia: this.subfamilia,
                    sucursal: this.sucursal
                }
            }).then((result) => {
                if (tipoDeInventario != 'general' && tipoDeInventario != 'herramienta') {
                    this.current_time = Date.parse(new Date())
                    this.deadline = new Date(this.current_time + this.time_in_minutes * 60 * 1000)
                    _this.run_clock('clockdiv', _this.deadline);
                }

                let response = result.data;
                let inventario = document.getElementsByClassName('inventario');
                var inStock = document.getElementsByClassName('stock');
                let inDiferencia = document.getElementsByClassName('diferencia');

                Array.prototype.forEach.call(inStock, function(item, i) {
                    inventario[i].value = ""
                });
                Array.prototype.forEach.call(inventario, function(item, i) {
                    inStock[i].value = ""
                });
                Array.prototype.forEach.call(inDiferencia, function(item, i) {
                    inDiferencia[i].value = ""
                });
                if (response.error == undefined) {
                    if (response.length > 0) {
                        this.visibleDashboard = !this.visibleDashboard
                        if (localStorage.sucursal) {

                            if (localStorage.sucursal == this.sucursal) {

                                if (localStorage.familia == this.familia) {

                                    if (localStorage.tipo == this.tipoInventario) {
                                        this.inventario = JSON.parse(localStorage.inventario);


                                    } else {
                                        this.inventario = result.data;
                                        localStorage.setItem("inventario", JSON.stringify(this.inventario));
                                        localStorage.setItem("sucursal", (this.sucursal));
                                        localStorage.setItem("tipo", this.tipoInventario);
                                        localStorage.setItem('familia', this.familia);
                                    }
                                } else {
                                    this.inventario = result.data;
                                    localStorage.setItem("inventario", JSON.stringify(this.inventario));
                                    localStorage.setItem("sucursal", (this.sucursal));
                                    localStorage.setItem("tipo", this.tipoInventario);
                                    localStorage.setItem('familia', this.familia);
                                }
                            } else {
                                this.inventario = result.data;
                                localStorage.setItem("inventario", JSON.stringify(this.inventario));
                                localStorage.setItem("sucursal", (this.sucursal));
                                localStorage.setItem("tipo", this.tipoInventario);
                                localStorage.setItem('familia', this.familia);
                            }
                        } else {
                            this.inventario = result.data;
                            localStorage.setItem("inventario", JSON.stringify(this.inventario));
                            localStorage.setItem("sucursal", (this.sucursal));
                            localStorage.setItem("tipo", this.tipoInventario);
                            localStorage.setItem('familia', this.familia);
                            //Verificando si es el segundo o x conteo
                            if (this.inventario[0].id == undefined && this.tipoInventario == 5) {
                                this.esNuevoConteo = false;
                            } else {
                                this.esNuevoConteo = true;
                            }
                        }

                    } else {
                        alert("No hay Productos de la familia seleccionada en la sucursal.");
                        console.log("hola");
                        console.log(this.familia + "" + this.subfamilia + '' + this.sucursal);

                    }
                } else {
                    alert("Ya has realizado un inventario de esta familia en la sucursal seleccionada");
                }
            }).catch((err) => {

            });
        },
        resetInventario: function() {
            localStorage.clear();
            alert("Caché  de progreso de inventario eliminado");
            location.reload();
        },
        cargaSucursales: function() {
            axios.get('/inventarioSucursales/Controllers/inventarios.php', {
                params: {
                    opc: 'sucursales'
                }
            }).then((response) => {
                this.sucursales = response.data;
            }).catch((err) => {
                console.log("Error");
            });
        },
        cargaFamilias: function() {
            axios.get('/inventarioSucursales/Controllers/inventarios.php', {
                params: {
                    opc: 'familias',
                    tipoinventario: this.tipoInventario,
                    sucursal: this.sucursal
                }
            }).then((result) => {
                this.familias = result.data;
            }).catch((err) => {

            });
        },
        cargaSubfamilias: function() {
            axios.get("/inventarioSucursales/Controllers/inventarios.php", {
                params: {
                    opc: 'subfamilias',
                    tipoinventario: this.tipoInventario,
                    familia: this.familia,
                    sucursal: this.sucursal
                }
            }).then((result) => {
                this.subfamilias = result.data;
            }).catch((err) => {

            })
        },
        guardaInventario: function(conObservacion = false) {
            //deteniendo el contador de tiempo 


            let self = this
            let inventario = document.getElementsByClassName('inventario');
            var inStock = document.getElementsByClassName('stock');
            let inDiferencia = document.getElementsByClassName('diferencia');
            let resultadoInventario = [];
            this.loading = !this.loading;
            Array.prototype.forEach.call(inventario, function(item, i) {
                inStock[i].value = self.inventario[i].STOCK;
                inDiferencia[i].value = isNaN(self.inventario[i].STOCK - parseInt(item.value)) ? 0 : self.inventario[i].STOCK - parseInt(item.value);
                resultadoInventario.push({
                    descripcion: self.inventario[i].DESCRIPCION,
                    codigo: self.inventario[i].CODIGOARTICULO,
                    subfamilia: self.inventario[i].SUBFAMILIA,
                    stock: self.inventario[i].STOCK,
                    stkIngresado: item.value,
                    tipo: self.tipoInventario,
                    sucursal: self.sucursal,
                    familia: self.inventario[i].FAMILIA,
                    campo: self.inventario[i].campo == undefined ? 0 : self.inventario[i].campo,
                    id: self.inventario[i].id == undefined ? 0 : self.inventario[i].id,
                    tiempoTranscurrido: self.tipoInventario == 1 ? -1 : self.transcurrido,
                });
            });

            action = self.inventario[0].id == undefined ? 0 : self.inventario[0].id
            axios.post('/inventarioSucursales/Controllers/inventarios.php', { inventario: resultadoInventario, action: action, tipo: self.tipoInventario, observacion: conObservacion == false ? '-' : this.observacionesHerramientas }).then((result) => {
                console.log(result);
                if (parseInt(result.data) > 0) {
                    alert("Se registró correctamente")
                    this.loading = !this.loading;
                    localStorage.removeItem('inventario');
                    localStorage.removeItem('sucursal');
                    localStorage.removeItem('familia');
                    localStorage.removeItem('tipo');

                    // this.visibleDashboard = !this.visibleDashboard
                    this.confirmar = !this.confirmar;
                    this.guardadoPresionado = !this.guardadoPresionado;
                    this.transcurrido = 0;
                } else {
                    alert("Ocurrió un error, por favor intentelo nuevamente");
                }

            }).catch((err) => {

            });
        },
        guardaInventarioConComentarios: function() {
            if (!this.esNuevoConteo) {
                this.showModalObservacion = true;
            } else {
                this.guardaInventario();
            }
        },
        cancelaInventario: function() {
            this.visibleDashboard = !this.visibleDashboard;
            this.cancelarPresionado = !this.cancelarPresionado;
            if (!this.confirmar)
                this.confirmar = !this.confirmar;
            document.getElementsByClassName("productosList").innerHTML = "";
        },
        agregaManual: function() {
            this.loading = !this.loading;
            let inManual = document.getElementsByClassName('manual');
            let productos = [];
            productos.push({
                descripcion: inManual[1].value,
                codigo: inManual[0].value,
                subfamilia: inManual[2].value,
                stock: 0,
                stkIngresado: inManual[3].value,
                tipo: this.tipoInventario,
                sucursal: this.sucursal,
                familia: this.familia
            });

            axios.post('/inventarioSucursales/Controllers/inventarios.php', {
                inventario: productos
            }).then((result) => {
                this.loading = !this.loading;
                alert("Articulo contabilizado correctamente");
            }).catch((err) => {

            });
        },
        showIndividual: function() {
            this.visibleIndividual = !this.visibleIndividual;
        },
        enviaReporte: function() {
            confirmEnvio = confirm("Se recomienda enviar el reporte solo al final del día, cuando hayas terminado con las sucursales visitadas\n ¿Continuar?");
            if (confirmEnvio == true) {
                this.loading = !this.loading;
                axios.get("/inventario/Controllers/reporteInventario.php").then((result) => {
                    if (result.data == 'Enviado') {
                        this.loading = !this.loading;
                        alert("En breve recibirá el reporte del inventario en la bandeja de entrada de su correo");
                    } else {
                        alert("El inventario ha sido realizado con exito. \nEnviando reporte via email...");
                        this.loading = !this.loading;
                        alert("No se pudo realizar el envío, por favor reintentalo nuevamente. Error: " + result.data);
                    }
                }).catch((err) => {

                });
            } else {
                console.log(1)
            }

        },
        verificaInventarioProgramado: function() {

        },
        handleOk: function(bvModalEvt) {
            // Prevent modal from closing
            bvModalEvt.preventDefault()
                // Trigger submit handler
                //Si el inventario ya fue registrado por primera vez ya no vuelve a solicitar observaciones
            this.guardaInventario(true);


            this.$nextTick(() => {
                this.$refs.modal.hide()
            })
        },

    },
    watch: {
        familia: function(newValue) {
            this.cargaSubfamilias();
        },
        tipoInventario: function(newValue) {
            this.cargaFamilias();
            if (newValue == 1) {
                this.statusInputsFamililiaSubFam = !this.statusInputsFamililiaSubFam;
            } else if (newValue == 3) {
                this.statusInputsFamililiaSubFam = true;
            }

        },
        sucursal: function(newSucursalSelected) {
            axios.get('/inventarioSucursales/Controllers/inventarios.php', {
                params: {
                    sucursal: this.sucursal,
                    opc: 'getProgramado'
                }
            }).then((result) => {
                if (result.data.length > 0) {
                    this.programadoDisable = true
                    let programado = result.data;
                    programado.forEach((item) => {
                        this.familia = item.familia;
                        this.subfamilia = item.subfamilia;
                        this.tipoInventario = item.tipo_inventario
                    });
                } else {
                    this.programadoDisable = false
                    this.cargaFamilias();
                }
            }).catch((err) => {

            });
        }
    }
})

vmDashboard.cargaSucursales();
vmDashboard.cargaFamilias();

function guardaAvance(articulo, input) {
    globalInventario = JSON.parse(localStorage.inventario);

    if (input.value != '') {
        Array.prototype.forEach.call(globalInventario, function(item, i) {
            if (item.CODIGOARTICULO == articulo) {
                globalInventario[i].ingresado = input.value;
                localStorage.setItem('inventario', JSON.stringify(globalInventario));

            }
        });
    }


}