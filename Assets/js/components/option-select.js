Vue.component("tr-inventario", {
    props: ['otro'],
    template: ` <tbody class="productosList">
                        <tr v-for="producto in otro" >
                            <td><img :src="'http://servermatrixxxb.ddns.net:9898/thumbnails.php?file='+producto.CODIGOARTICULO.replace('/','_SLASH_')+'.jpg&width=200'" style="width:130px;height:auto;"></td>
                            <td>{{producto.CODIGOARTICULO}}</td>
                            <td>{{producto.DESCRIPCION}}</td>
                            <td>{{producto.FAMILIA}}</td>
                            <td>{{producto.SUBFAMILIA}}</td>
                            <td v-if="producto.ingresado == undefined"><input type="number" class="form-control inventario" :onfocusout="'guardaAvance(&quot;'+producto.CODIGOARTICULO+'&quot;,this)'"></td>
                            <td v-else><input type="number" class="form-control inventario" :onfocusout="'guardaAvance(&quot;'+producto.CODIGOARTICULO+'&quot;,this)'"  :value="producto.ingresado"></td>
                            <td><input class="form-control stock" disabled></td>
                            <td><input class="form-control diferencia" disabled></td>
                         </tr>
                    </tbody>
                    </tfoot>
                    `
});