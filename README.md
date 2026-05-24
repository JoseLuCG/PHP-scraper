# Prueba Técnica — Scraper de VividSeats

Aplicación web que extrae y muestra listados de entradas de VividSeats.com utilizando Chrome headless.

---

## Estructura del proyecto

```
index.html                          ← Página principal (formulario de entrada)
partials/
  notes.php                         ← Fragmento HTML con notas musicales animadas
templates/
  results.php                       ← Plantilla de resultados (evento + tabla)
  waiting-template.js               ← Constante con HTML del spinner de carga
  error-template.js                 ← Función para generar mensajes de error
scripts/
  javascript/
    index.js                        ← Lógica del frontend (eventos del formulario)
    services.js                     ← Comunicación con el backend (fetch)
    extract-listings.js             ← Script de extracción ejecutado en Chrome
  php/
    index.php                       ← Endpoint que orquesta la validación y el scraping
    validators.php                  ← Validaciones (URL, método HTTP, resultado del scrape)
    scraper.php                     ← Clase VividSeatsScraper (Chrome headless)
styles/
  index.css                         ← Estilos globales, formulario, notas, loader
  results.css                       ← Estilos de la página de resultados
vendor/                             ← Dependencias de Composer
composer.json                       ← Dependencia: chrome-php/chrome ^1.15
```

---

## Requisitos

- **PHP 8.0+** con extensiones `curl` y `json`
- **Composer** (gestor de dependencias PHP)
- **Google Chrome** o **Chromium** instalado en el sistema

---

## Instalación

1.  Clonar el repositorio:
    ```bash
    git clone <url-del-repositorio>
    cd 0_PRUEBA_TECNICA
    ```

2.  Instalar dependencias de PHP con Composer:
    ```bash
    composer install
    ```

3.  Verificar que Chrome esté instalado. Por defecto el programa busca Chrome en:
    ```
    C:\Program Files\Google\Chrome\Application\chrome.exe
    ```
    Si está en otra ruta, configurar la variable de entorno `CHROME_PATH`:
    ```bash
    set CHROME_PATH=C:\ruta\a\chrome.exe
    ```

---

## Ejecución

Iniciar el servidor integrado de PHP desde la raíz del proyecto:

```bash
php -S localhost:8000
```

Abrir en el navegador:

```
http://localhost:8000
```

Pegar un enlace de VividSeats (ej: `https://www.vividseats.com/...`) y presionar **Submit**.

---

## Cómo funciona

### 1. Frontend (`index.html` + `scripts/javascript/`)

El usuario ingresa una URL de VividSeats y hace clic en Submit.

- `index.js` captura el evento `submit`, previene el envío tradicional y muestra un spinner de carga (`WAITING_TEMPLATE`).
- Llama a `fetchToBackend(formData)` (definida en `services.js`) que envía una petición POST al backend con la cabecera `X-Requested-With: XMLHttpRequest`.
- Cuando el backend responde, inyecta el HTML de resultados en `#results` y cambia el `body` a modo scrollable (`results-active`).

### 2. Backend PHP (`scripts/php/`)

`index.php` recibe la petición y:

1. **Valida** la entrada mediante `validators.php`:
   - Verifica que sea una petición POST con un campo `link`.
   - Valida que la URL tenga formato correcto.
   - Confirma que el dominio sea `vividseats.com`.

2. **Scrapea** los datos con `VividSeatsScraper` (`scraper.php`):
   - Lanza una instancia de Chrome headless.
   - Navega a la URL del evento.
   - Espera a que la red se estabilice (`NETWORK_IDLE`).
   - Espera a que aparezcan los elementos de listado (`[data-testid="listing-row-container"]`).
   - Hace scroll dentro del contenedor de listados para cargar todas las entradas.
   - Ejecuta `extract-listings.js` dentro de la página para extraer los datos.

3. **Responde**:
   - Si es una petición AJAX, devuelve solo el fragmento HTML de resultados (`templates/results.php`).
   - Si no, devuelve una página HTML completa con los resultados.

### 3. Scraping (`scraper.php`)

La clase `VividSeatsScraper`:

| Paso | Método | Descripción |
|------|--------|-------------|
| 1 | `scrape()` | Crea el navegador Chrome headless |
| 2 | `navigate()` | Abre la URL del evento |
| 3 | `waitForListings()` | Sondea cada 200ms hasta que aparecen listados (máx 6s) |
| 4 | `scrollToLoadAll()` | Hace scroll en el contenedor para cargar listados dinámicos (hasta 15 iteraciones, 600ms entre cada una) |
| 5 | `getExtractionScript()` | Lee `extract-listings.js` y lo ejecuta en el contexto de la página |
| 6 | Retorna | Array con datos del evento y array de listados |

### 4. Extracción de datos (`extract-listings.js`)

El script se ejecuta dentro de la página de VividSeats y extrae:

- **Datos del evento**: nombre, venue, fecha, precios mínimo/máximo/promedio, cantidad de listados y tickets (desde `__NEXT_DATA__`).
- **Listados**: sección, fila, precio (original y con descuento), cantidad de tickets, puntuación (`deal score`) y badge (`SALE!`, `Last Ticket`).

### 5. Presentación de resultados (`templates/results.php`)

Los datos se renderizan en:

- **Event card**: nombre del evento, venue, fecha/hora.
- **Stats grid**: total de listados, tickets, precio mínimo, máximo y promedio.
- **Tabla**: listados con sección, fila, precio, cantidad, puntuación y badge.
- Las puntuaciones se colorean: verde (≥ 9), amarillo (≥ 7), rojo (< 7).
- Los badges muestran "SALE!" en rojo y "Last Ticket" en amarillo.

### 6. Estilos

- `index.css`: fondo con gradiente animado, notas musicales flotantes, formulario glassmorphism, loader con spinner, clases utilitarias (`.glass`, `.error-msg`, `.error-msg-danger`).
- `results.css`: contenedor de resultados, tarjeta del evento, grid de estadísticas, tabla con tema oscuro, badges y puntuaciones.

---

## Forma de trabajo (AJAX)

El formulario se envía mediante JavaScript sin recargar la página:

1. El usuario pega un enlace y hace clic en Submit.
2. `index.js` muestra el spinner y envía la petición al backend.
3. El backend procesa y devuelve solo el HTML de resultados.
4. `index.js` reemplaza el spinner con los resultados y habilita el scroll.
5. El botón **Limpiar** restaura el estado inicial.

Si JavaScript falla, el formulario funciona como fallback mediante envío tradicional (carga completa de página).

---

## Personalización

### Variable de entorno

- `CHROME_PATH`: ruta al ejecutable de Chrome/Chromium.

### Ajustes del scraper

En `scraper.php` se pueden modificar:

- `windowSize` → tamaño de ventana del navegador headless.
- `userAgent` → user-agent para la petición.
- Tiempos de espera en `waitForListings()` y `scrollToLoadAll()`.
- Número máximo de iteraciones de scroll.

---

## Solución de problemas

| Problema | Posible causa | Solución |
|----------|---------------|----------|
| "No link provided" | Petición sin datos | Verificar que el formulario envíe el campo `link` |
| "Invalid URL format" | URL mal formada | Usar una URL válida (https://...) |
| Chrome no arranca | `CHROME_PATH` incorrecto | Configurar la variable de entorno con la ruta correcta |
| Timeout en el scrape | La página tarda en cargar | Aumentar tiempos de espera en `scraper.php` |
| No aparecen resultados | El DOM de VividSeats cambió | Revisar los selectores en `extract-listings.js` |
