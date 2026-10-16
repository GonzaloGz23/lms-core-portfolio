# Demo

Grabaciones del flujo principal, en **WebP animado**: se embeben con sintaxis de
imagen (`![](…)`) y se reproducen en el README, pesando una fracción de un GIF
equivalente.

| Archivo                       | Qué muestra                                                        |
| ----------------------------- | ------------------------------------------------------------------ |
| `01-crear-curso.webp`         | Alta de un curso (título, `slug` automático, precios, publicación). |
| `02-editar-curso.webp`        | Edición de un curso existente.                                     |
| `03-crear-y-editar-modulo.webp` | Crear un módulo y editarlo desde el builder.                     |
| `04-crear-lecciones.webp`     | Añadir lecciones a un módulo.                                      |
| `05-editar-y-reordenar.webp`  | Editar lecciones y reordenar módulos y lecciones con ↑↓.           |
| `06-eliminar-contenido.webp`  | Borrado lógico en cascada de lecciones, módulos y cursos.          |

## Cómo se generaron

Las grabaciones originales son 1920×1080 a 25 fps. Recodificadas a 1100 px de ancho
y 12 fps para bajar el peso sin pérdida perceptible en el texto de la interfaz:

```bash
ffmpeg -i entrada.gif -vf "fps=12,scale=1100:-1:flags=lanczos" \
  -c:v libwebp -loop 0 -q:v 75 salida.webp
```

Notas:

- GitHub **no** reproduce `<video>` ni MP4 embebidos en el README (y con ruta
  relativa tampoco), así que la demo tiene que ser una imagen animada.
- Un GIF equivalente a estos clips pesaría ~10× más (decenas de MB cada uno).
