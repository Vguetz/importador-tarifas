# Importador de Tarifas de Proveedores

## Cómo levantarlo

Clonar el repo, entrar a la carpeta y desde ahí:

- `composer install` para instalar las dependencias.
- Copiar el `.env.example` a `.env` y completar los datos de la base.
- `php artisan migrate` para correr las migraciones.
- `php artisan serve` para levantar el servidor.

Si lo querés probar por consola, hay un comando para eso:

```
php artisan importar:excel {archivo} {codigo} {id}

Ejemplo: php artisan importar:excel PROVEEDOR_A.xlsx lucgom_global 1   
```

## El modelo de datos

Opté por un modelo relacional clásico, principalmente para evitar datos duplicados. Quedó dividido así:

- **Proveedores**: el punto de entrada.
- **Productos**: la información base, solo lo indispensable.
- **ProductoPrecios**: los precios por volumen. Es una relación 1:N, así que cada producto puede tener todos los tramos que necesite.
- **ProductoImpuestos**: lo separé para no mezclar los impuestos con la tabla de productos. De esta forma pueden variar según el país o la unidad sin afectar al resto.

## Decisiones de diseño

Toda la lógica de importación quedó en `ImportacionService`. Preferí mantenerla ahí en lugar de cargar el controlador y terminar con algo difícil de mantener.

Para el mapeo de columnas usé un array de configuración en `config/proveedores.php`. Para un sistema grande lo más prolijo sería una clase por proveedor, pero para el alcance de este challenge me pareció excesivo, y probablemente me quitaria demasiado tiempo. Teniendo todos los esquemas en un solo archivo es más fácil ver de un vistazo cómo viene cada proveedor y ajustarlo, en vez de repartir la lógica en muchos archivos casi vacíos.El service consume el array de forma transparente, así que si en algún momento un proveedor necesita una transformación más compleja (más allá de mapear columnas), se puede pasar ese caso a una clase propia sin reescribir toda la importación.

También cuidé la tolerancia a archivos incompletos. Usé `??` e `isset` en los puntos clave, porque los Excel de los proveedores rara vez vienen perfectos: suele faltar una columna o venir alguna celda vacía. Así un campo en blanco no corta todo el proceso.

Por el lado de la performance, usé `with()` (eager loading) para no terminar con una consulta por cada producto, y `paginate(50)` para no intentar cargar miles de registros de una sola vez y saturar la memoria.

## Tests

Hice tests de integración, sobre todo para asegurarme de que un cambio en `ImportacionService` no rompa la importación sin que me dé cuenta. Me enfoqué en dos cosas:

- Que un archivo se procese de punta a punta y quede bien guardado en la base.
- Que la API de consulta filtre correctamente por marca y referencia.

No cubrí casos muy puntuales por una cuestión de tiempo, y preferí dejar sólido el flujo principal, que es el que se usa la mayor parte del tiempo.

## Mejoras pendientes

Con más tiempo, seguiría por acá:

- **Procesamiento en segundo plano.** Hoy, si el archivo es muy grande, el navegador queda esperando la respuesta del servidor. Lo ideal sería mover la importación a una Laravel Queue y avisarle al usuario cuando termina.
- **Mapeo más flexible.** El array cumple para esta etapa, pero me gustaría pasar a un esquema que permita definir reglas por columna (limpiar strings, formatear fechas, convertir unidades) de forma centralizada y sin ensuciar el service.
- **Validación de esquema.** Por ahora confío en que el Excel trae las columnas esperadas. Estaría bueno validar la estructura apenas llega el archivo y devolver un error claro si no respeta el formato, antes de insertar nada.
- **Búsqueda indexada.** Si el catálogo crece a millones de registros, las consultas con `WHERE` van a empezar a ser bastante mas lentas. Ahí integraría un motor de búsqueda indexado por atras, manteniendo el endpoint actual pero bajando los tiempos de respuesta.
- **Mejor trazabilidad.** Que si falla una fila dentro de un archivo de 10.000, quede registrado el motivo exacto y el proceso continúe con el resto, en lugar de abortar por completo.
