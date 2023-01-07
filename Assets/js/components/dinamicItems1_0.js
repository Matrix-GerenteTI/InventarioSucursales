// Vue.component("tr-item", {
//     props:['items', 'estado'],
//     template: ` <tbody>
//                             <tr v-for="item in items">
//                                 <td>{{ item.CODIGOARTICULO}}</td>
//                                 <td>{{ item.DESCRIPCION}}</td>
//                                 <td><input type="text" :value="item.CANTIDAD" class="form-control"></td>
//                                 <td v-if="estado">{{ item.EXISTENCIA }}</td>
//                                 <td v-if="estado">{{ item.DIFERENCIA }}</td>
//                                 </tr> 
//                         <tbody>`
// })


// v-on:keyup.13="editaCapturado('item.CODIGOARTICULO')"




Vue.component("tr-item", {
    props:['items', 'estado'],
    template: ` <tbody >
                            <tr v-for="item in items">
                                <td>{{ item.CODIGOARTICULO}}</td>
                                <td>{{ item.DESCRIPCION}}</td>
                                <td><input onclick="setFocusCantidad( this)" type="number" :value="item.CANTIDAD"  class="form-control" v-on:keyup.13="$emit('edita-capturado',item.CODIGOARTICULO)" :id="'item_'+item.CODIGOARTICULO"></td>
                                <td v-if="estado">{{ item.EXISTENCIA }}</td>
                                <td v-if="estado">{{ item.DIFERENCIA }}</td>
                                </tr> 
                        <tbody>`
})
