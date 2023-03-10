<!DOCTYPE html>
<html lang="en">
    <head>
        <title></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" type="text/css" href="/inventarioSucursales/Assets/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="/inventarioSucursales/Assets/css/loading.css">
        <style>
                .modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, .5);
  display: table;
  transition: opacity .3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
}

.modal-container {
  width: 300px;
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
  transition: all .3s ease;
  font-family: Helvetica, Arial, sans-serif;
}

.modal-header h3 {
  margin-top: 0;
  color: #42b983;
}

.modal-body {
  margin: 20px 0;
}

.modal-default-button {
  float: right;
}

/*
 * The following styles are auto-applied to elements with
 * transition="modal" when their visibility is toggled
 * by Vue.js.
 *
 * You can easily play with the modal transition by editing
 * these styles.
 */

.modal-enter {
  opacity: 0;
}

.modal-leave-active {
  opacity: 0;
}

.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row" style="background: #fafafa">
                <div class="col-md-12">
                    <div class="col-md-2 col-md-offset-1" >
                        <img src="/inventarioSucursales/Assets/images/logo.png" style="width:100%; height:auto;margin-top:10%;">
                    </div>                
                    <div class="col-md-4 text-center col-md-offset-1">
                        <h1>INVENTARIO</h1>
                    </div>                
                </div>
            </div>
            <div class="row" id="app" onmousedown=" preventFocusOut(event) ">
                <div class="row">
                    <div class="form-group">
                        <label for="">Selecciona UDN:</label>
                        <select v-model="sucursal"  class="form-control">
                            <option value="-1">Selecciona una opci??n</option>
                            <option v-for="sucursal in sucursales" :value="sucursal.ID">{{sucursal.DESCRIPCION}}</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="">C??digo</label>
                            <div class="input-group">
                            <input type="text" id="incodigo" v-model="codigo" class="form-control" placeholder="Introduce un codigo" @keydown.enter="findArticulo" >
                            <div class="input-group-addon" style="cursor:pointer" @click="findArticulo"><i class="glyphicon glyphicon-plus"> </i></div>
                            </div>                            
                        </div>                    
                    </div>
                </div>
                <div class="row table-responsive" id="containerInventario" style="height: 300px;">
                    <table class="table table-bordered" id="tablaListado">
                        <tr>
                            <th>C??DIGO</th>
                            <th>DESCRIPCI??N</th>
                            <th>CANTIDAD</th>
                            <th>EXISTENCIA</th>
                            <th>DIFERENCIA</th>
                        </tr>    
                        <tbody is="tr-item" :items="listaItems" :estado = "finalizado" v-on:edita-capturado="cantidadActualizar" >
                        </tbody>
                    </table>
                </div>
                <div class="row">
                <br>
                    <div class="form-inline" style="float:right">
                        <button class="btn btn-warning" @click="reset">
                            Reiniciar
                        </button>
                        <button class="btn btn-primary" @click = "guardar" :disabled="finalizado">
                            Terminar
                        </button>
                    </div>
                </div>


            <transition name="modal" v-if="showModal">
                <div class="modal-mask">
                <div class="modal-wrapper">
                    <div class="modal-container">

                    <div class="modal-header">
                        <slot name="header">
                            Confirmaci??n
                        </slot>
                    </div>

                    <div class="modal-body">
                        <slot name="body">
                            Tienes articulos por inventariar,??Deseas continuar con el mismo?
                        </slot>
                    </div>

                    <div class="modal-footer">
                        <slot name="footer">
                        
                        <button class="modal-default-button" >
                            No
                        </button>
                        <button class="modal-default-button" @click="getDiferencias()">
                            S??
                        </button>
                        </slot>
                    </div>
                    </div>
                </div>
                </div>
            </transition>                
                
            </div>
            
        </div>
    <script src="/inventarioSucursales/Assets/js/vue.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.js"></script>
    <script src="/inventarioSucursales/Assets/js/barra.js"></script>
    <script src="/inventarioSucursales/Assets/js/components/dos.js"></script>

    </body>
</html>