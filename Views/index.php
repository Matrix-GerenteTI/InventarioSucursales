<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- <link rel="stylesheet" type="text/css" href="/inventario/Assets/css/bootstrap.min.css"> -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link
          type="text/css"
          rel="stylesheet"
          href="https://unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.css"
        />
    <link rel="stylesheet" type="text/css" href="/inventarioSucursales/Assets/css/loading.css">
    <title>Inventarios</title>
</head> 
<body>

    <div class="container" id="dashboard">
<template>
  <div>
    <b-modal
      v-model="showModalObservacion"
      id="modal-prevent-closing"
      ref="modal"
      title="Agregar observaciones"
      @ok="handleOk"
    >
      <form ref="form" @submit.stop.prevent="handleSubmit">
        <b-form-group
          label="Observaciones"
          label-for="name-input"
        >
        <b-form-textarea
            id="textarea"
            v-model="observacionesHerramientas"
            placeholder="Escribe tus observaciones..."
            rows="3"
            max-rows="8"
            ></b-form-textarea>
        </b-form-group>
      </form>
    </b-modal>
  </div>
</template>

        <div class="row">
            <div class="col-md-12" v-show="loading">
                    <div class="loading " > 
                        <div>
                            <div class="c1"></div>
                            <div class="c2"></div>
                            <div class="c3"></div>
                            <div class="c4"></div>
                        </div>
                        <span>Cargando</span>
                    </div>            
            </div>
        </div>     
        <header>
            <table>
                <tr>
                    <td><img src="/inventario/Assets/images/logo.png" alt=""></td>
                    <td><h2>Sistema de Control de Inventario</h2></td>
                    <td></td>
                </tr>
            </table>
        </header>
        <div class="row" v-show="visibleDashboard">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <div class="form-group">
                                <label for="">Sucursal</label>
                                <select class="form-control" v-model="sucursal" >
                                    <option value="-1">Selecciona una sucursal</option>
                                    <option v-for="sucursal in sucursales"  :value="sucursal.ID">{{sucursal.DESCRIPCION}}</option>
                                </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <div class="form-group">
                            <label for="">Tipo de inventario</label>
                            <select class="form-control" v-model="tipoInventario" :disabled="programadoDisable">
                                <option value="-1">Seleciona tipo inventario</option>
                                <option value="1">General</option>
                                <!--<option value="2">Aleatorio</option>-->
                                <option value="3" >Aleatorio</option>
                                <option value="5">Herramientas</option>
                            </select>                
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <div class="form-group">
                            <label for="">Familia a inventariar</label>
                            <select class="form-control" v-model="familia" :disabled='statusInputsFamililiaSubFam' >
                                <option value="-1">Selecciona Familia</option>
                                <option v-for="familia in familias" :value="familia.FAMILIA" >{{familia.FAMILIA}}  </option>
                            </select>                
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <div class="form-group">
                            <label for="">Subfamilia a inventariar</label>
                            <select class="form-control" v-model="subfamilia" :disabled='statusInputsFamililiaSubFam' >
                                <option value="%">Selecciona Subfamilia</option>
                                <option v-for="subfamilia in subfamilias" :value="subfamilia.SUBFAMILIA">{{subfamilia.SUBFAMILIA}}</option>
                            </select>                
                        </div>
                    </div>
                </div>                
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <button type="button" class="btn btn-warning" @click="resetInventario">Reiniciar</button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <button type="button" class="btn btn-primary" @click="showInventario">Continuar</button>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>                
        <div class="row" v-show="!visibleDashboard">
            <div class="row">
                <div class="col-md-1" style="position:fixed;background:white;z-index:1;margin-left:0px;">
                    <div id="clockdiv" style="font-size:18px;font-weight:bold"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <table class=" table table-striped" id="listado">
                        <thead>
                            <tr>
                            <th>IMAGEN</th>
                            <th>CODIGO</th>
                            <th>DESCRIPCION</th>
                            <th>FAMILIA</th>
                            <th>SUBFAMILIA</th>
                            <th>FISICO</th>
                            <th>SISTEMA</th>
                            <th>DIFERENCIA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tbody is="tr-inventario" :otro="inventario"  >
                                
                                <!-- <tr-inventario v-for="producto in inventario" :otro="producto"></tr-inventario> -->
                            </tbody>
                        </tbody>
                        <tfoot>
                            <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><button type="button" class="btn btn-danger" @click="cancelaInventario">Cerrar Lista</button></td>
                            <td v-show="tipoInventario != 5"><button type="button" class="btn btn-success" @click="guardaInventario" v-show="confirmar" >Confirmar Inventario</button></td>
                            <td v-show="tipoInventario == 5"><button type="button" class="btn btn-success" @click="guardaInventarioConComentarios" v-show="confirmar" >Confirmar Inventario</button></td>
                            </tr>
                        </tfoot>
                        
                    </table>
                </div>
            </div>
            
        </div>
            <div class="row">
                <div class="col-md-12">
                    <a href="#" @click="enviaReporte">Enviar Reporte</a>
                </div>
            </div>
            <div class="row" v-if="tipoInventario == 1">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-12"><a href="#" @click="showIndividual">¿No están todos los articulos en la lista?</a></div>
                    </div>
                    <div class="row" v-show="visibleIndividual">
                        <div class="col-md-8">
                            <table class="table table-bordered">
                                <tr>
                                    <td>
                                        Código:<br>
                                        <input type="text"   class="form-control manual" >
                                    </td>
                                    <td>
                                        Descripción:<br>
                                        <input type="text"   class="form-control manual" >                                        
                                    </td>
                                    <td>
                                        Subfamilia:<br>
                                        <input type="text"  class="form-control manual" >
                                    </td>
                                    <td>
                                        Físico:<br>
                                        <input type="text"   class="form-control manual" >
                                    </td>
                                    <td>
                                        <br>
                                        <button @click="agregaManual" class="btn-warning btn">Agregar</button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
        <!-- <script src="/inventario/Assets/js/jquery-1.11.2.min.js"></script> -->
        
    <script src="/inventarioSucursales/Assets/js/vue.min.js"></script>
    <script src="https://unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js"></script>
    <script src="/inventarioSucursales/Assets/js/index.js"></script>
    
    <script src="/inventarioSucursales/Assets/js/components/option-select.js"></script>
</body>
</html>