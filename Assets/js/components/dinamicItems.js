Vue.component("tr-item", {
    props:['items', 'estado'],
    template: ` <tbody >
                            <tr v-for="(item,index) in items">
                                <td>{{ item.CODIGOARTICULO}}</td>
                                <td>{{ item.DESCRIPCION}}</td>
                                <td><input onclick="setFocusCantidad( this)" type="number" :value="item.CANTIDAD"  class="form-control" v-on:change="$emit('edita-capturado',item.CODIGOARTICULO)" :id="'item_'+item.CODIGOARTICULO"></td>
                                <td>{{ item.STOCK }}</td>
                                <td>{{ item.DIFERENCIA }}</td>
                                </tr> 
                        <tbody>`
})


// v-on:keyup.13="editaCapturado('item.CODIGOARTICULO')"