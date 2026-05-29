# MSkinWellness

MSkinWellness es un SaaS de gestión para clínicas estéticas y salones de belleza. El proyecto está planteado como una aplicación multi-centro en la que cada centro trabaja con sus propios usuarios, sesiones, tratamientos, salas, máquinas, ventas, facturas, archivos e inventario dentro de una misma plataforma.

## Resumen del funcionamiento

La plataforma parte de una capa global de administración desde la que se crean centros y se les asigna un plan. A partir de ahí, cada centro gestiona su operativa diaria con sus propios datos y permisos. Adicionalmente, cualquier visitante puede registrar su propio centro desde la landing pública sin pasar por el superadmin (alta self-service), lo que crea en una sola transacción el centro, su administrador inicial, la asignación del rol y el correo de verificación.

Dentro de cada centro se contempla la gestión de:

- usuarios unificados, tanto trabajadores como clientes
- roles globales gestionados con `spatie/laravel-permission`
- horarios, ausencias y disponibilidades extra
- salas, máquinas y tratamientos
- sesiones (`appointments`) con trabajador principal, cliente principal y ayudantes
- fichas de cliente, histórico de evaluaciones, variaciones, aptitud y archivos
- ventas, pagos, facturas e inventario
- archivos y branding del centro para su página pública

La vista principal para trabajadores estará centrada en la agenda y el calendario de sesiones. Desde ahí podrán acceder al resto de módulos según su rol. Además, se plantea una vista tipo mapa del centro con GridStack para representar salas y ocupación de forma visual, persistiendo la disposición en la columna `rooms.grid_position` (JSONB). El mapa se refresca con F5 manual: no hay sincronización en tiempo real (sin Reverb/WebSockets).

Los pagos con Stripe (alta self-service del centro y suscripciones) forman parte del núcleo. El acceso online de clientes queda en modo lectura. Las reservas online del cliente y la página pública por centro quedan fuera del alcance de defensa.

## Objetivo del proyecto

El objetivo es construir un Proyecto Final serio y defendible que demuestre:

- análisis de un problema real del sector
- diseño de una base de datos relacional amplia y consistente
- separación clara entre backend, frontend y despliegue
- uso de una arquitectura moderna con Laravel, Angular y PostgreSQL
- visión de producto más allá de un conjunto de CRUDs aislados

## Tecnologías usadas

### Backend

- Laravel 12
- PHP 8.2
- Laravel Sanctum (token mode) para la autenticación de la API consumida por Angular
- `spatie/laravel-permission` (sin teams) para roles y permisos
- Laravel Cashier + Stripe para el alta self-service del centro y las suscripciones
- Blade para el panel de superadministración (guard `web`, sesión clásica)

### Frontend

- Angular 21
- TypeScript
- Tailwind CSS + SCSS para responsive y maquetación general (base del template TailAdmin)
- Reactive Forms
- Signals, computed y effect cuando proceda
- guards, interceptores, servicios y consumo de API REST
- GridStack para la vista visual del mapa del centro
- `almothafar/angular-signature-pad` para la firma del consentimiento y FullCalendar (vía TailAdmin) para el cuadrante

### Base de datos

- PostgreSQL

Se ha elegido PostgreSQL por su mejor encaje con un proyecto SaaS multi-centro con muchas relaciones, restricciones de integridad y necesidad de escalabilidad. Se aprovechan tipos como `TIMESTAMPTZ`, `JSONB` (para `rooms.grid_position`, snapshots de facturas y `metadata` de auditoría) y `UUID`.

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

- Superadmin: gestión global del SaaS, centros y planes. Vive en la tabla `users` con `center_id = NULL` y rol `superadmin`.
- Centro: operativa interna con sus usuarios, sesiones, fichas, ventas e inventario.
- Cliente: acceso online opcional según plan y estado de implantación.

### Multi-centro y aislamiento

El diseño gira alrededor de `center_id` como eje de aislamiento lógico. Cada entidad operativa pertenece a un centro y las claves foráneas relevantes se definen como compuestas (`(id, center_id)`) para impedir cruces entre centros a nivel relacional. Los catálogos globales (`session_statuses`, `payment_methods`, `skin_types`, etc.), `plans` y los archivos de centro son globales o pertenecen a la capa de `centers`.

Convención de borrados en cascada:

- `ON DELETE CASCADE ON UPDATE CASCADE` para FKs hacia el centro
- `ON DELETE CASCADE` para pivots
- `ON DELETE RESTRICT` para FKs hacia lookups globales
- `ON DELETE SET NULL` en `audit_logs` para preservar el rastro

### Login compartido (sin subdominios)

Todos los usuarios del centro (staff y clientes) se autentican contra el mismo backend desde una única ruta: `mskinwellness.com/login`. Un formulario único de email + password; el backend resuelve los roles y el frontend redirige al destino según rol. No hay subdominio por centro: la app vive en un único hostname y el tenant se resuelve en backend leyendo `center_id` del usuario autenticado. El branding del centro (logos, colores, `center_files`) se aplica dentro del panel, no en el login. La idea anterior de `centro-x.mskinwellness.com/login` queda descartada.

### Auditoría

El sistema mantiene una tabla `audit_logs` mínima centrada en el ciclo de vida del centro: alta, cambio de plan, desactivación y reactivación del centro (estos tres últimos guiados por los webhooks de Stripe según el estado de la suscripción), más alta, baja y reactivación de usuarios del centro. No se utiliza para impersonation, catálogos globales ni eventos del cliente online.

## Modelo funcional resumido

### Superadmin

Permite crear centros, asignar planes y administrar la configuración global del SaaS. Puede acceder al panel de un centro como administrador (impersonation) a nivel de aplicación, sin que quede rastro en `audit_logs`.

### Trabajadores

Acceden a la aplicación principal del centro según sus roles. Un mismo usuario puede acumular varios roles compatibles (recepcionista + diagnosticador, dermo_esteticien + fisioterapeuta, etc.) mediante `assignRole()` de Spatie.

### Clientes

Pueden tener acceso online cuando el plan del centro lo permita. Hay dos vías de alta:

- **Auto-registro online** desde la landing pública del SaaS (planes que lo permitan).
- **Activación por el centro** de un cliente walk-in existente, mediante correo de activación.

El acceso online del cliente es de **lectura**: ve sus datos personales, sus próximas citas y su histórico, sus fichas técnicas, sus consentimientos firmados (con descarga del PDF) y el escaparate de productos del centro. Las reservas online (pedir o cancelar cita) quedan fuera del alcance de defensa.

## Roles del sistema

Los roles se cargan desde un seeder y son fijos (los gestiona Spatie sin teams):

- `superadmin` — dueño del SaaS, sin centro
- `administrador` — gestiona todo dentro de un centro
- `recepcionista` — agenda, ocupación de salas, alta de clientes
- `rrhh` — trabajadores, horarios y disponibilidades
- `diagnosticador` — ficha clínica del cliente
- `dermo_esteticien` — tratamientos manuales / faciales
- `fisioterapeuta` — tratamientos con maquinaria
- `manicurista` — tratamientos de manicura / pedicura
- `cliente` — cliente del centro (acceso online opcional)

## Planes

El sistema contempla una tabla global `plans` relacionada con `centers.plan_id`. Las capacidades se modelan como flags:

- `max_workers` — límite de trabajadores
- `allows_online_clients` — acceso online del cliente
- `allows_emails` — correos automáticos
- `allows_public_page` — página pública del centro (fuera del alcance de defensa; sin login por subdominio)

Sobre esos flags se han definido tres niveles funcionales: **Starter**, **Professional** y **Premium**.

## Módulos principales

- gestión de centros y planes
- usuarios y roles (Spatie)
- horarios, ausencias y disponibilidades extra
- salas, máquinas y tratamientos (con asignación de roles compatibles por tratamiento)
- sesiones (`appointments`), ayudantes y calendario
- ficha clínica, evaluaciones históricas, variaciones, aptitud para tratamientos
- archivos del cliente y del centro
- ventas, pagos y facturación
- inventario y movimientos de stock (con consumo automático en sesión)
- auditoría del ciclo de vida del centro

## Alcance

### Núcleo del Proyecto Final

- multi-centro y planes
- superadmin (Blade) de solo lectura sobre el dominio del centro
- autenticación con Sanctum y autorización con Spatie
- alta self-service del centro con Stripe Checkout + Cashier + verificación de email
- gestión interna del centro
- sesiones y cuadrante (FullCalendar)
- mapa del centro con GridStack
- wizard del consentimiento con firma + PDF
- fichas clínicas y archivos del cliente
- inventario (tres tablas) con descuento al finalizar la cita
- ventas, sale_lines e invoices
- horarios, ausencias y disponibilidades extra
- frontend Angular responsive completo
- despliegue funcional en VPS
- datos demo para defensa

### Funcionalidades con posible cierre parcial

- acceso online del cliente (lectura de sus datos; sin reservas online)
- inventario avanzado (lo básico ya está; analíticas opcionales)
- facturación avanzada (lo básico ya está; modelos fiscales opcionales)

### Fuera del Proyecto Final

- reservas online del cliente desde el portal
- página pública por centro y dominios/subdominios por centro
- CMS para páginas públicas
- automatizaciones avanzadas de negocio
- tiempo real con Reverb / WebSockets (el mapa se refresca con F5 manual)

## Nombre del tablero de planificación

`MSkinWellness - Planificación y tiempos`

## Estado del proyecto

Se trata del proyecto final del ciclo, enfocado a construir una base técnica sólida y con proyección realista de crecimiento. La prioridad es cerrar un Proyecto Final coherente, bien defendido y alineado con una necesidad real del sector.
