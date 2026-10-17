# Arquitectura

Documento de referencia técnica: cómo está estructurado el proyecto, por qué se
tomaron ciertas decisiones y qué trade-offs se asumieron.

## Visión general

Panel de administración para una plataforma de cursos. Cubre el ciclo de vida
completo del contenido —alta, edición, publicación, ordenación y borrado— más la
autenticación y la autorización por roles.

Casos de uso principales:

- Un **teacher** crea y gestiona sus propios cursos y su contenido.
- Un **admin** supervisa y gestiona todos los cursos.
- Un **student** se autentica (email/contraseña o Google) y accede a su panel.

## Stack

| Capa           | Tecnología                                                   |
| -------------- | ------------------------------------------------------------ |
| Backend        | PHP 8.4+, Laravel, Fortify (auth), Socialite (Google OAuth)  |
| Frontend       | React 19, Inertia, TypeScript, Tailwind CSS, shadcn/ui       |
| Base de datos  | PostgreSQL (dev/prod) · SQLite en memoria (tests)            |
| Build          | Vite, Wayfinder (rutas tipadas de Laravel → TypeScript)      |
| Calidad        | PHPUnit (feature tests), Pint, Larastan/PHPStan, ESLint, Prettier, `tsc` |
| Entorno        | DDEV (contenedores reproducibles)                            |

## Modelo de dominio

```
User ──1:1── Profile
 │
 └──1:N── Course ──1:N── Module ──1:N── Lesson
```

- **User**: autenticación (`email`, `password`, `google_id`) y rol
  (`student` | `teacher` | `admin`, como enum PHP backed).
- **Profile**: datos personales (`first_name`, `last_name`, `avatar`, …) separados
  de las credenciales. Se crea junto con el User.
- **Course**: título, `slug` único, descripción, syllabus, nivel (enum), precios,
  duración, `is_published` y `created_by` (dueño). El precio de oferta, si existe,
  debe ser menor que el base.
- **Module**: pertenece a un curso, con `order` para la secuencia.
- **Lesson**: pertenece a un módulo, con `order`, `is_free_preview` y campos
  preparados para vídeo (`video_url`, `video_duration_sec`, `external_video_id`).

Los cuatro modelos usan **soft deletes**.

## Autorización

La lógica de autorización está centralizada en `App\Policies\CoursePolicy`:

- `before()` concede acceso total a los **admin** (devuelve `null` para el resto,
  de forma que no se deniegue al dueño cuando no es admin).
- Un **teacher** solo puede ver y gestionar los cursos cuyo `created_by` es suyo.
- `manageContent` controla módulos y lecciones a través del curso padre.

Además, un `RoleMiddleware` protege las rutas de administración y los
`FormRequest` (`StoreCourseRequest`, `UpdateCourseRequest`) refuerzan la
autorización con `authorize()`.

## Decisiones de diseño

### 1. Autorización en una Policy, no repartida por los controladores

Concentrar las reglas en `CoursePolicy` evita duplicar la lógica de "¿este usuario
es el dueño?" en cada acción. Los controladores se limitan a invocar `authorize()`
y la policy es la única fuente de verdad. Añadir un rol nuevo (por ejemplo,
coordinador con acceso de lectura global) se resuelve en un único lugar.

### 2. Orden del contenido: simplicidad primero, con red de seguridad documentada

Al crear un módulo o una lección se calcula `max(order) + 1`. Es simple y
suficiente para el uso previsto. El trade-off es un *race condition* clásico
(TOCTOU): dos altas simultáneas en el mismo curso podrían leer el mismo máximo e
insertar con el mismo `order`.

Se asume de forma consciente porque el escenario es improbable en esta aplicación.
La evolución prevista, si la concurrencia lo exigiera, es doble:

1. **Red de seguridad:** un índice único en `(course_id, order)` y
   `(module_id, order)` —igual que ya se hace con `slug`— para que la BD rechace
   duplicados.
2. **Serialización correcta:** envolver el cálculo en una transacción con
   `lockForUpdate()` sobre la fila padre, de modo que `move()` y las altas
   compartan el mismo bloqueo.

### 3. Cascada de borrado con eventos de modelo, no con FK en BD

Las claves foráneas usan `onDelete('cascade')`, pero eso solo aplica al borrado
**físico**. Como el dominio usa soft deletes, la cascada se implementa en los
eventos `deleting`/`restoring` de `Course` y `Module`: borrar un curso elimina
lógicamente sus módulos, y estos a su vez sus lecciones. En la restauración ocurre
lo inverso.

El motivo es la coherencia: un curso eliminado no debe dejar lecciones huérfanas
"vivas", ni un curso restaurado debe recuperar solo una parte de su contenido.

### 4. Google OAuth sin inventar datos

`GoogleAuthController` resuelve el usuario en tres pasos: por `google_id`, si no
por `email` (vinculando la cuenta existente y refrescando el avatar), y si no,
creando usuario y perfil. El rol y `email_verified_at` se asignan explícitamente
porque no forman parte de `$fillable`.

### 5. Slugs únicos generados en el servidor

El slug se deriva del título y, si ya existe, se le añade un sufijo numérico
(`mi-curso-2`, `mi-curso-3`…). Se genera en el backend para garantizar la
unicidad, y solo se regenera en la edición si cambia el título y el usuario no
envió uno propio.

### 6. Rutas tipadas de extremo a extremo

Wayfinder genera funciones TypeScript a partir de las rutas nombradas de Laravel.
El frontend llama `courseRoutes.show(id)` en lugar de escribir URLs a mano: si una
ruta cambia, el error aparece en tiempo de compilación, no en producción.

## Limitaciones conocidas

- **Etiquetas de nivel duplicadas:** los textos ("Principiante", "Intermedio",
  "Avanzado") viven tanto en el enum PHP como en un mapa en el frontend. La
  solución es exponerlos siempre desde el backend.
- **Concurrencia en `order`:** descrita en la decisión 2; pendiente la constraint
  única como red de seguridad.
- **Navegación hacia atrás tras el login:** las rutas de Fortify no aplican el
  middleware `guest`, así que el botón "atrás" puede mostrar el login a un usuario
  ya autenticado. La corrección requiere registrar las rutas de Fortify
  manualmente para aplicar `guest` a todas menos a `logout`.
