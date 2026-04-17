# MSkinWellness

MSkinWellness es un SaaS de gestión para clínicas estéticas y salones de belleza. El proyecto está planteado como una aplicación multi-centro en la que cada centro trabaja con sus propios usuarios, sesiones, tratamientos, salas, máquinas, ventas, facturas, archivos e inventario dentro de una misma plataforma.

## Resumen del funcionamiento

La plataforma parte de una capa global de administración desde la que se crean centros y se les asigna un plan. A partir de ahí, cada centro gestiona su operativa diaria con sus propios datos y permisos.

Dentro de cada centro se contempla la gestión de:

- usuarios unificados, tanto trabajadores como clientes
- roles globales asignados a usuarios
- horarios, ausencias y disponibilidades extra
- salas, máquinas y tratamientos
- sesiones con trabajador principal, cliente principal y ayudantes
- fichas de cliente, histórico de evaluaciones, variaciones y archivos
- ventas, pagos, facturas e inventario
- archivos y branding del centro para su página pública

La vista principal para trabajadores estará centrada en la agenda y el calendario de sesiones. Desde ahí podrán acceder al resto de módulos según su rol. Además, se plantea una vista tipo mapa del centro con GridStack para representar salas y ocupación de forma visual.

El sistema también queda preparado para acceso online de clientes, página pública por centro, correos automáticos, reservas online y pagos con Stripe, aunque algunas de estas partes pueden quedar parciales según el tiempo disponible.

## Objetivo del proyecto

El objetivo es construir un MVP serio y defendible que demuestre:

- análisis de un problema real del sector
- diseño de una base de datos relacional amplia y consistente
- separación clara entre backend, frontend y despliegue
- uso de una arquitectura moderna con Laravel, Angular y PostgreSQL
- visión de producto más allá de un conjunto de CRUDs aislados

## Tecnologías usadas

### Backend

- Laravel 12
- PHP 8.2
- Laravel Sanctum para autenticación
- Blade para el panel de superadministración

### Frontend

- Angular 21
- TypeScript
- Bootstrap 5 para responsive y maquetación general
- Reactive Forms
- Signals, computed y effect cuando proceda
- guards, interceptores, servicios y consumo de API REST
- GridStack para la vista visual del mapa del centro

### Base de datos

- PostgreSQL

Se ha elegido PostgreSQL por su mejor encaje con un proyecto SaaS multi-centro con muchas relaciones, restricciones de integridad y necesidad de escalabilidad.

### Despliegue

- VPS con dominio propio
- Docker Compose
- contenedores para backend, frontend y base de datos

## Arquitectura

El proyecto sigue una arquitectura separada por capas:

- Laravel como backend y API principal
- Angular como aplicación principal del centro
- Blade para el CRUD de superadmin
- PostgreSQL como base de datos central
- Docker Compose para entorno local y despliegue en VPS

### Distribución general

- Superadmin: gestión global del SaaS, centros y planes
- Centro: operativa interna con sus usuarios, sesiones, fichas, ventas e inventario
- Cliente: acceso online opcional según plan y estado de implantación

### Multi-centro

El diseño gira alrededor de `centro_id` como eje de aislamiento lógico. Cada entidad operativa pertenece a un centro, mientras que algunos elementos como roles, planes y tablas lookup son globales.

También se contempla página pública por centro mediante slug, subdominio o ruta equivalente durante el MVP.

## Modelo funcional resumido

### Superadmin

Permite crear centros, asignar planes y administrar la configuración global del SaaS.

### Trabajadores

Acceden a la aplicación principal del centro según sus permisos. Podrán gestionar sesiones, consultar fichas de clientes, revisar agenda, usar el mapa del centro y trabajar con módulos internos según rol.

### Clientes

Podrán tener acceso online cuando el centro lo permita. La arquitectura queda preparada para login, gestión de citas, verificación y posibles reservas online.

## Planes

El sistema contempla una tabla global `planes` relacionada con `centros.plan_id`. Los planes limitan o habilitan funcionalidades como:

- número máximo de trabajadores
- acceso online de clientes
- correos automáticos
- página pública del centro
- dominio personalizado

## Módulos principales

- gestión de centros y planes
- usuarios y roles
- horarios y ausencias
- salas y máquinas
- tratamientos
- sesiones y calendario
- ficha global de cliente y evaluaciones históricas
- archivos de cliente
- ventas, pagos y facturación
- inventario y movimientos de stock

## Alcance

### Núcleo del MVP

- multi-centro
- planes
- superadmin funcional
- autenticación y roles
- gestión interna del centro
- sesiones y calendario
- fichas y archivos del cliente
- frontend responsive
- despliegue funcional en VPS

### Funcionalidades previstas con posible cierre parcial

- acceso online del cliente
- página pública por centro
- reservas online
- correos automáticos
- pagos con Stripe
- facturación más avanzada
- inventario funcional ampliado

## Nombre del tablero de planificación

`MSkinWellness - Planificación y tiempos`

## Estado del proyecto

Se trata del proyecto final del ciclo, enfocado a construir una base técnica sólida y con proyección realista de crecimiento. La prioridad es cerrar un MVP coherente, bien defendido y alineado con una necesidad real del sector.
