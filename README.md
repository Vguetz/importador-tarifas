# Importador de Tarifas de Proveedores

## Cómo levantarlo

Clonar el repo, entrás a la carpeta y de ahí:

- `composer install` para bajar las dependencias.
- Copiás el `.env.example` a `.env` y completás los datos de la base.
- `php artisan migrate` para correr las migraciones.
- `php artisan serve` para levantar el server.

Si lo querés probar directo por consola, hay un comando para eso:

```
php artisan importar:excel {archivo} {codigo} {id}
```

## El modelo de datos

Fui por algo relacional y bastante clásico, más que nada para no terminar duplicando datos. Quedó así:

- **Proveedores**: donde arranca todo.
- **Productos**: la info base, lo mínimo indispensable.
- **ProductoPrecios**: los precios por volumen. Es una relación 1:N, así que cada producto puede tener todos los tramos que haga falta.
- **ProductoImpuestos**: lo saqué aparte para no ensuciar la tabla de productos. Así los impuestos pueden variar según el país o la unidad sin tener que tocar nada más.

## Por qué lo hice así

Toda la lógica de importación la metí en `ImportacionService`. No me gusta cargar el controlador con todo y que termine siendo un bodrio imposible de mantener.

Para el mapeo de columnas dejé un array de configuración en `config/proveedores.php`, y acá fue donde más dudé. Lo "correcto" para un sistema grande sería tener una clase por proveedor, pero para el alcance de este challenge me pareció matar moscas a cañonazos: teniendo todos los esquemas en un solo archivo veo enseguida cómo viene cada uno y lo ajusto al toque, en vez de andar saltando entre veinte archivos casi vacíos. Igual no me cierro la puerta a futuro: el service consume el array y nada más, así que si algún proveedor llega a necesitar una transformación más rara (más allá de mapear columnas), paso ese caso puntual a una clase propia sin reescribir toda la importación.

Otra cosa que cuidé fue la tolerancia a los archivos rotos. Usé `??` e `isset` por todos lados, porque los Excel de los proveedores nunca vienen prolijos: siempre falta una columna o hay alguna celda vacía. Con eso me aseguro de que un campo en blanco no me tire abajo todo el proceso.

Y para no matar la performance metí `with()` (eager loading), así no quedo con mil queries por cada producto, más un `paginate(50)`. Si pedís 5.000 productos de una le hacés explotar la memoria a la base.

## Tests

Hice tests de integración, sobre todo para quedarme tranquilo de que si toco algo en el `ImportacionService` no rompo toda la importación sin darme cuenta. Apunté a dos cosas:

- Que un archivo entre, se procese y termine guardado bien en la base.
- Que la API de consulta filtre como corresponde por marca y referencia.

No me puse a cubrir casos súper rebuscados porque el tiempo era el que era, y preferí dejar firme el flujo principal, que es el que se usa el 99% de las veces.

## Cosas que dejé pendientes

Si tuviera más tiempo, por acá seguiría:

- **Mandarlo a una queue.** Hoy, si el archivo es un monstruo de 100MB, el navegador se queda esperando la respuesta del server, que es medio horrible. Lo suyo sería tirar la importación a una Laravel Queue y avisarle al usuario cuando terminó.
- **Mejorar el mapeo.** El array cumple para esta etapa, pero me gustaría pasar a algo basado en esquemas, donde pueda definir reglas por columna (limpiar strings, formatear fechas, convertir unidades) de forma centralizada y sin ensuciar el service.
- **Validar el esquema del archivo.** Ahora confío en que el Excel trae las columnas que espero. Estaría bueno chequear la estructura apenas llega y devolver un error claro si no respeta el formato, antes de empezar a insertar nada en la base.
- **Búsqueda indexada.** Si el catálogo se va a millones de registros, los `WHERE` van a empezar a sufrir. Ahí metería un motor de búsqueda indexado por detrás, manteniendo el endpoint igual pero bajando los tiempos a milisegundos.
- **Mejores logs.** Que si falla una fila dentro de un archivo de 10.000, quede registrado qué pasó exactamente y el proceso siga con el resto, en vez de abortar todo de una.#
