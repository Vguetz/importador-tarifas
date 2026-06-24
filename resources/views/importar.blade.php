<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Importador de Tarifas</title>
</head>

<body>
    <h1>Importador de Tarifas de Proveedores</h1>

    @if(session('success'))
    <p style="color: green;"><strong>{{ session('success') }}</strong></p>
    @endif

    @if($errors->any())
    <ul style="color: red;">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    @endif

    <form action="{{ route('importar.web') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div>
            <label for="proveedor_id">Proveedor:</label>
            @if($proveedores->isEmpty())
            <p style="color: red;">
                no tenes proveedores cargados en la base de datos. corre el comando <code>php artisan db:seed</code> antes de importar.
            </p>
            @else
            <select name="proveedor_id" id="proveedor_id" required>
                @foreach($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                @endforeach
            </select>
            @endif
        </div>
        <br>

        <div>
            <label for="archivo">Seleccionar Archivo Excel o CSV:</label>
            <input type="file" name="archivo" id="archivo" required>
        </div>
        <br>

        <button type="submit">Iniciar Importación</button>
    </form>

    <hr style="margin: 40px 0;">
    <h2>Consultar Productos (por Marca, referencia o ambos)</h2>

    <form id="formConsulta">
        <div>
            <label for="filtro_marca">Marca:</label>
            <input type="text" id="filtro_marca" placeholder="Ej: Bosch, Samsung">
        </div>
        <br>
        <div>
            <label for="filtro_referencia">Referencia:</label>
            <input type="text" id="filtro_referencia" placeholder="Ej: IP-99X">
        </div>
        <br>
        <button type="submit">Buscar Productos</button>
    </form>

    <div id="resultados" style="margin-top: 20px;">
    </div>
    <script>
        function realizarConsulta(url) {
            document.getElementById('resultados').innerHTML = '<i>Cargando datos...</i>';

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error(`Error: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    const productos = data.data;

                    if (productos.length === 0) {
                        document.getElementById('resultados').innerHTML = '<p>No se encontraron productos.</p>';
                        return;
                    }


                    let html = '<table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%; margin-top:10px;">';
                    html += '<tr><th>ID</th><th>Proveedor</th><th>Marca</th><th>Referencia</th><th>Descripción</th><th>Precios</th></tr>';

                    productos.forEach(prod => {
                        let preciosHtml = prod.precios.map(p => `Mín ${p.cantidad_minima}u: $${p.precio}`).join('<br>');
                        let nombreProveedor = prod.proveedor ? prod.proveedor.nombre : 'Desconocido';

                        html += `<tr>
                                <td>${prod.id}</td>
                                <td>${nombreProveedor}</td>
                                <td>${prod.marca}</td>
                                <td>${prod.referencia_proveedor}</td>
                                <td>${prod.descripcion || '-'}</td>
                                <td>${preciosHtml || 'Sin precio'}</td>
                             </tr>`;
                    });
                    html += '</table>';


                    html += `
                    <div style="margin-top: 20px; display: flex; gap: 10px; align-items: center;">
                        <button onclick="realizarConsulta('${data.prev_page_url}')" ${!data.prev_page_url ? 'disabled' : ''}>Anterior</button>
                        <span>Página <b>${data.current_page}</b> de <b>${data.last_page}</b></span>
                        <button onclick="realizarConsulta('${data.next_page_url}')" ${!data.next_page_url ? 'disabled' : ''}>Siguiente</button>
                    </div>
                    <p><small>Total registros: ${data.total}</small></p>
                `;

                    document.getElementById('resultados').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('resultados').innerHTML = '<p style="color: red;">Error al consultar.</p>';
                    console.error(error);
                });
        }


        document.getElementById('formConsulta').addEventListener('submit', function(e) {
            e.preventDefault();

            const marca = document.getElementById('filtro_marca').value;
            const referencia = document.getElementById('filtro_referencia').value;

            const url = new URL(window.location.origin + '/api/productos');
            if (marca) url.searchParams.append('marca', marca);
            if (referencia) url.searchParams.append('referencia', referencia);

            realizarConsulta(url.toString());
        });
    </script>
</body>

</html>