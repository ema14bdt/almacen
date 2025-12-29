# Almacén EGP - Sistema de Punto de Venta

Sistema de gestión de inventario y punto de venta (POS) desarrollado con Django, diseñado específicamente para almacenes y pequeños comercios en Argentina.

## 🚀 Características Principales

### Punto de Venta (POS)
- Escaneo rápido de productos mediante código de barras
- Carrito de compras en tiempo real con HTMX
- Múltiples métodos de pago (Efectivo/Transferencia)
- Cálculo automático de vuelto
- Interfaz optimizada para uso continuo

### Gestión de Stock
- CRUD completo de productos
- Búsqueda y filtrado de productos
- Control de stock en tiempo real
- Activación/desactivación de productos (soft delete)
- Actualización incremental de stock
- Paginación de resultados

### Panel de Control
- Estadísticas de ventas diarias y mensuales
- Historial de ventas con filtro por fecha
- Detalle completo de cada venta
- Vista de totales por método de pago

## 🛠️ Tecnologías Utilizadas

- **Backend:** Django 6.0
- **Frontend:** 
  - Tailwind CSS (vía CDN)
  - HTMX 1.9.10 (interactividad sin JavaScript)
  - Alpine.js 3.13.3 (reactividad del lado del cliente)
- **Base de Datos:** SQLite (desarrollo) / Compatible con PostgreSQL
- **Deployment:** Hostinger (FTP) con GitHub Actions

## 📋 Requisitos Previos

- Python 3.13+
- pip (gestor de paquetes de Python)
- Git

## 🔧 Instalación Local

### 1. Clonar el repositorio

```bash
git clone https://github.com/ema14bdt/almacen/tree/master
cd almacen
```

### 2. Crear entorno virtual

```bash
python -m venv venv

# En Windows
venv\Scripts\activate

# En Linux/Mac
source venv/bin/activate
```

### 3. Instalar dependencias

```bash
pip install -r requirements.txt
```

### 4. Configurar variables de entorno

Crear un archivo `.env` en la raíz del proyecto basándose en `.env.example`:

```env
DEBUG=True
SECRET_KEY=tu-clave-secreta-aqui-generada-aleatoriamente
ALLOWED_HOSTS=127.0.0.1,localhost
```

**Generar una SECRET_KEY segura:**

```bash
python -c "from django.core.management.utils import get_random_secret_key; print(get_random_secret_key())"
```

### 5. Aplicar migraciones

```bash
python manage.py migrate
```

### 6. Crear superusuario

```bash
python manage.py createsuperuser
```

### 7. (Opcional) Poblar base de datos con datos de prueba

```bash
python manage.py populate_products
```

### 8. Recolectar archivos estáticos

```bash
python manage.py collectstatic --noinput
```

### 9. Ejecutar servidor de desarrollo

```bash
python manage.py runserver
```

Acceder a: `http://127.0.0.1:8000`

## 📁 Estructura del Proyecto

```
almacen/
├── config/                 # Configuración del proyecto Django
│   ├── settings.py        # Configuración principal
│   ├── urls.py            # URLs raíz
│   └── wsgi.py            # Punto de entrada WSGI
├── pos/                   # Aplicación principal
│   ├── migrations/        # Migraciones de base de datos
│   ├── templates/         # Templates HTML
│   │   ├── pos/          
│   │   │   ├── partials/ # Componentes reutilizables (HTMX)
│   │   │   ├── base.html
│   │   │   ├── dashboard.html
│   │   │   ├── inventory.html
│   │   │   └── pos.html
│   │   └── registration/
│   ├── management/        # Comandos personalizados
│   │   └── commands/
│   ├── models.py          # Modelos de datos
│   ├── views.py           # Vistas y lógica de negocio
│   ├── forms.py           # Formularios Django
│   └── urls.py            # URLs de la app
├── static/                # Archivos estáticos (si los hay)
├── .github/workflows/     # CI/CD con GitHub Actions
├── manage.py              # CLI de Django
├── passenger_wsgi.py      # Configuración para Hostinger
├── requirements.txt       # Dependencias Python
└── .env.example           # Plantilla de variables de entorno
```

## 🗄️ Modelos de Datos

### Product
- `codigo_barra` (PK): Código de barras único
- `nombre`: Nombre del producto
- `precio`: Precio unitario
- `stock`: Cantidad disponible
- `fecha_creacion`: Fecha de creación
- `activo`: Estado del producto (soft delete)

### Sale
- `fecha`: Fecha y hora de la venta
- `total`: Monto total de la venta
- `metodo_pago`: Efectivo o Transferencia
- `usuario`: Usuario que realizó la venta (FK)

### SaleDetail
- `venta`: Venta asociada (FK)
- `producto`: Producto vendido (FK)
- `cantidad`: Cantidad vendida
- `precio_unitario_congelado`: Precio al momento de la venta
- `subtotal`: Subtotal de la línea

## 🔐 Seguridad

- Autenticación requerida para todas las operaciones
- CSRF protection habilitado
- Validación de stock antes de confirmar ventas
- Soft delete para productos (no se eliminan físicamente)
- Protección contra eliminación de productos con ventas asociadas

## 🚀 Deployment en Hostinger

El proyecto incluye configuración automática de deployment vía GitHub Actions.

### Configurar Secrets en GitHub

En tu repositorio de GitHub, ve a `Settings > Secrets and variables > Actions` y agrega:

- `FTP_SERVER`: Servidor FTP de Hostinger
- `FTP_USERNAME`: Usuario FTP
- `FTP_PASSWORD`: Contraseña FTP
- `SECRET_KEY`: Clave secreta de Django para producción

### Configuración en Hostinger

1. **Archivo `.env` en producción:**
```env
DEBUG=False
SECRET_KEY=<tu-secret-key-de-produccion>
ALLOWED_HOSTS=tu-dominio.com,www.tu-dominio.com
```

2. **Base de datos:** El proyecto usa SQLite por defecto. Para producción se recomienda PostgreSQL o MySQL.

3. **Python App Setup:** Configurar en el panel de Hostinger como aplicación Python con `passenger_wsgi.py` como punto de entrada.

## 📱 Uso del Sistema

### Flujo de Trabajo - Punto de Venta

1. Acceder a la sección "POS" desde el menú
2. Escanear o ingresar código de barras del producto
3. El producto se agrega automáticamente al carrito
4. Ajustar cantidades si es necesario
5. Hacer clic en "Finalizar Venta"
6. Seleccionar método de pago
7. Si es efectivo, ingresar monto recibido (calcula vuelto automáticamente)
8. Confirmar pago

### Flujo de Trabajo - Gestión de Stock

**Agregar Producto:**
1. Click en "Nuevo Producto"
2. Escanear o ingresar código de barras
3. Completar nombre, precio y stock inicial
4. Guardar

**Editar Producto:**
1. Buscar producto en la lista
2. Click en "Editar"
3. Modificar campos necesarios
4. Para actualizar stock: ingresar cantidad a agregar
5. Opción de "Vaciar Stock" para resetear a 0
6. Guardar cambios

**Eliminar Producto:**
1. Editar producto
2. Click en "Eliminar Producto"
3. Confirmar eliminación
4. El producto se desactiva (soft delete)

## 📄 Licencia

Este proyecto fue desarrollado por Emanuel Romero para uso comercial privado.

## 👨‍💻 Autor

**Emanuel Romero**
- Website: [emanuel-romero.vercel.app](https://emanuel-romero.vercel.app/)

---

**Nota:** Este sistema está optimizado para el mercado argentino con formato de precios en pesos argentinos ($) y configuración regional para Argentina (timezone, formato de fecha, idioma español).
