# Web PDV

Sistema web de punto de venta para tiendas de abarrotes, diseñado para administrar ventas, inventario, clientes, crédito, cajas y comprobantes desde una interfaz sencilla y adaptable.

![PHP](https://img.shields.io/badge/PHP-MVC-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-Server-D22128?style=for-the-badge&logo=apache&logoColor=white)
![License](https://img.shields.io/badge/License-Private-lightgrey?style=for-the-badge)

## Descripción

Web PDV es una aplicación de gestión comercial desarrollada en PHP, con arquitectura MVC y base de datos MySQL.

El sistema permite controlar las operaciones principales de un negocio minorista:

- Registro y procesamiento de ventas.
- Búsqueda de productos mediante código de barras.
- Venta por unidad o por empaque.
- Control de inventario y ajustes de existencias.
- Gestión de categorías y productos.
- Administración de clientes y cuentas por cobrar.
- Registro de abonos de clientes.
- Apertura, movimientos y cierre de caja.
- Generación de tickets y comprobantes.
- Configuración de datos fiscales y comerciales.
- Reportes de ventas.
- Interfaz especial para cajeros móviles.

## Características principales

### Punto de venta

- Registro rápido de productos.
- Lectura de códigos de barras.
- Soporte para productos vendidos por unidad, peso o empaque.
- Manejo de precios de venta y precios por empaque.
- Cálculo de cambio.
- Métodos de pago en efectivo, tarjeta, transferencia y crédito.
- Asociación de ventas con clientes.
- Emisión de tickets.

### Inventario

- Control de existencias en tiempo real.
- Productos con cantidades decimales.
- Stock mínimo por producto.
- Alertas de productos con bajo inventario.
- Registro de entradas y salidas.
- Configuración de unidades de medida.
- Control de presentaciones y empaques.

### Clientes y crédito

- Registro de clientes.
- Datos de contacto y RTN o identidad.
- Límite de crédito.
- Saldo pendiente.
- Estado de cuenta.
- Registro de abonos.
- Impresión de tickets de abono.
- Búsqueda de clientes por RTN o identidad.

### Control de caja

- Apertura de caja.
- Fondo inicial.
- Registro de ingresos y egresos.
- Registro de abonos recibidos.
- Cierre y corte de caja.
- Cálculo de diferencias entre el efectivo esperado y el declarado.

### Usuarios y roles

El sistema contempla los siguientes perfiles:

| Rol | Descripción |
|---|---|
| Administrador | Acceso a configuración, usuarios, productos, inventario, caja, ventas y reportes |
| Cajero | Acceso a ventas y operaciones de caja |
| Cajero móvil | Acceso a la interfaz de ventas optimizada para dispositivos móviles |

## Tecnologías utilizadas

- PHP
- MySQL
- PDO
- HTML5
- CSS3
- JavaScript
- Arquitectura MVC
- Apache
- WAMP Server
- HTML5 QR Code
- Quagga.js para lectura de códigos de barras

## Estructura del proyecto

```text
web_pdv/
├── app/
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── config/
│   ├── app.php
│   └── database.php
├── core/
│   ├── Controller.php
│   └── Router.php
├── public/
│   ├── css/
│   ├── img/
│   ├── js/
│   └── index.php
├── sql/
│   ├── esquema.sql
│   └── migraciones/
├── index.php
└── respaldo_pdv.bat
