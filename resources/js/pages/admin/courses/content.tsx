import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Layers, Pencil, Plus, Trash2 } from 'lucide-react';
import type { SubmitEvent } from 'react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes/admin';
import courseRoutes from '@/routes/admin/courses';
import lessonRoutes from '@/routes/admin/lessons';
import moduleRoutes from '@/routes/admin/modules';
import type { CourseContent, CourseLesson, CourseModule } from '@/types';

interface Props {
    course: CourseContent;
}

interface ModuleFormData {
    title: string;
    description: string;
}

interface LessonFormData {
    title: string;
    content: string;
    is_free_preview: boolean;
}

export default function Content({ course }: Props) {
    /* ---------- estado de los modales ---------- */
    const [moduleDialogOpen, setModuleDialogOpen] = useState(false);
    const [editingModule, setEditingModule] = useState<CourseModule | null>(
        null,
    );
    const [moduleToDelete, setModuleToDelete] = useState<CourseModule | null>(
        null,
    );
    const [deletingModule, setDeletingModule] = useState(false);

    const [lessonDialogOpen, setLessonDialogOpen] = useState(false);
    const [editingLesson, setEditingLesson] = useState<CourseLesson | null>(
        null,
    );
    const [lessonParent, setLessonParent] = useState<CourseModule | null>(null);
    const [lessonToDelete, setLessonToDelete] = useState<CourseLesson | null>(
        null,
    );
    const [deletingLesson, setDeletingLesson] = useState(false);

    /* ---------- estado del reordenamiento ↑↓ ---------- */
    // true mientras hay un move en vuelo: bloquea TODAS las flechas
    const [reordering, setReordering] = useState(false);

    /* ---------- formulario de módulo ---------- */
    const moduleForm = useForm<ModuleFormData>({ title: '', description: '' });

    const openCreateModule = () => {
        moduleForm.reset();
        moduleForm.clearErrors();
        setEditingModule(null);
        setModuleDialogOpen(true);
    };

    const openEditModule = (module: CourseModule) => {
        moduleForm.setData({
            title: module.title,
            description: module.description ?? '',
        });
        moduleForm.clearErrors();
        setEditingModule(module);
        setModuleDialogOpen(true);
    };

    const submitModule = (e: SubmitEvent<HTMLFormElement>) => {
        e.preventDefault();

        if (editingModule) {
            moduleForm.put(moduleRoutes.update(editingModule.id).url, {
                preserveScroll: true,
                onSuccess: () => setModuleDialogOpen(false),
            });
        } else {
            moduleForm.post(moduleRoutes.store(course.id).url, {
                preserveScroll: true,
                onSuccess: () => setModuleDialogOpen(false),
            });
        }
    };

    /* ---------- formulario de lección ---------- */
    const lessonForm = useForm<LessonFormData>({
        title: '',
        content: '',
        is_free_preview: false,
    });

    const openCreateLesson = (module: CourseModule) => {
        lessonForm.reset();
        lessonForm.clearErrors();
        setEditingLesson(null);
        setLessonParent(module);
        setLessonDialogOpen(true);
    };

    const openEditLesson = (module: CourseModule, lesson: CourseLesson) => {
        lessonForm.setData({
            title: lesson.title,
            content: lesson.content ?? '',
            is_free_preview: lesson.is_free_preview,
        });
        lessonForm.clearErrors();
        setEditingLesson(lesson);
        setLessonParent(module);
        setLessonDialogOpen(true);
    };

    const submitLesson = (e: SubmitEvent<HTMLFormElement>) => {
        e.preventDefault();

        if (editingLesson) {
            lessonForm.put(lessonRoutes.update(editingLesson.id).url, {
                preserveScroll: true,
                onSuccess: () => setLessonDialogOpen(false),
            });
        } else if (lessonParent) {
            lessonForm.post(moduleRoutes.lessons.store(lessonParent.id).url, {
                preserveScroll: true,
                onSuccess: () => setLessonDialogOpen(false),
            });
        }
    };

    /* ---------- orden ↑↓ ---------- */
    const moveModule = (module: CourseModule, direction: 'up' | 'down') => {
        if (reordering) {
            return;
        }

        setReordering(true);
        router.post(
            moduleRoutes.move(module.id).url,
            { direction },
            {
                preserveScroll: true,
                onFinish: () => setReordering(false),
            },
        );
    };

    const moveLesson = (lesson: CourseLesson, direction: 'up' | 'down') => {
        if (reordering) {
            return;
        }

        setReordering(true);
        router.post(
            lessonRoutes.move(lesson.id).url,
            { direction },
            {
                preserveScroll: true,
                onFinish: () => setReordering(false),
            },
        );
    };

    /* ---------- borrado ---------- */
    const confirmDeleteModule = () => {
        if (!moduleToDelete) return;

        setDeletingModule(true);
        router.delete(moduleRoutes.destroy(moduleToDelete.id).url, {
            preserveScroll: true,
            onFinish: () => {
                setDeletingModule(false);
                setModuleToDelete(null);
            },
        });
    };

    const confirmDeleteLesson = () => {
        if (!lessonToDelete) return;

        setDeletingLesson(true);
        router.delete(lessonRoutes.destroy(lessonToDelete.id).url, {
            preserveScroll: true,
            onFinish: () => {
                setDeletingLesson(false);
                setLessonToDelete(null);
            },
        });
    };

    return (
        <>
            <Head title="Contenido del curso" />

            <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Encabezado */}
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Contenido del curso
                        </h1>
                        <p className="text-muted-foreground">{course.title}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button onClick={openCreateModule}>
                            <Plus className="mr-2 h-4 w-4" />
                            Agregar módulo
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={courseRoutes.show(course.id)}>
                                Volver al detalle
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Listado de módulos */}
                <div className="space-y-6">
                    {course.modules.length === 0 ? (
                        <Card>
                            <CardContent className="py-10 text-center text-sm text-muted-foreground">
                                Este curso todavía no tiene módulos. Creá el
                                primero.
                            </CardContent>
                        </Card>
                    ) : (
                        course.modules.map((module, moduleIndex) => (
                            <Card key={module.id}>
                                <CardHeader>
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <CardTitle className="flex items-center gap-2">
                                            <Layers className="h-4 w-4" />
                                            {module.title}
                                        </CardTitle>
                                        <div className="flex items-center gap-1">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                disabled={
                                                    moduleIndex === 0 ||
                                                    reordering
                                                }
                                                onClick={() =>
                                                    moveModule(module, 'up')
                                                }
                                            >
                                                <ArrowUp className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                disabled={
                                                    moduleIndex ===
                                                        course.modules.length -
                                                            1 || reordering
                                                }
                                                onClick={() =>
                                                    moveModule(module, 'down')
                                                }
                                            >
                                                <ArrowDown className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                onClick={() =>
                                                    openEditModule(module)
                                                }
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                onClick={() =>
                                                    setModuleToDelete(module)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </div>
                                    {module.description && (
                                        <p className="text-sm text-muted-foreground">
                                            {module.description}
                                        </p>
                                    )}
                                </CardHeader>

                                <CardContent className="space-y-2">
                                    {module.lessons.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            Sin lecciones todavía.
                                        </p>
                                    ) : (
                                        <ul className="space-y-2">
                                            {module.lessons.map(
                                                (lesson, lessonIndex) => (
                                                    <li
                                                        key={lesson.id}
                                                        className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2"
                                                    >
                                                        <div className="flex min-w-0 items-center gap-2">
                                                            <span className="text-xs text-muted-foreground">
                                                                {lessonIndex +
                                                                    1}
                                                            </span>
                                                            <span className="truncate text-sm font-medium">
                                                                {lesson.title}
                                                            </span>
                                                            {lesson.is_free_preview && (
                                                                <Badge variant="secondary">
                                                                    Vista previa
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <div className="flex items-center gap-1">
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                disabled={
                                                                    lessonIndex ===
                                                                        0 ||
                                                                    reordering
                                                                }
                                                                onClick={() =>
                                                                    moveLesson(
                                                                        lesson,
                                                                        'up',
                                                                    )
                                                                }
                                                            >
                                                                <ArrowUp className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                disabled={
                                                                    lessonIndex ===
                                                                        module
                                                                            .lessons
                                                                            .length -
                                                                            1 ||
                                                                    reordering
                                                                }
                                                                onClick={() =>
                                                                    moveLesson(
                                                                        lesson,
                                                                        'down',
                                                                    )
                                                                }
                                                            >
                                                                <ArrowDown className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    openEditLesson(
                                                                        module,
                                                                        lesson,
                                                                    )
                                                                }
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    setLessonToDelete(
                                                                        lesson,
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 className="h-4 w-4 text-destructive" />
                                                            </Button>
                                                        </div>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}

                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => openCreateLesson(module)}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Agregar lección
                                    </Button>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>
            </div>

            {/* ---------- Modal módulo ---------- */}
            <Dialog
                open={moduleDialogOpen}
                onOpenChange={(open) => {
                    if (!open && !moduleForm.processing)
                        setModuleDialogOpen(false);
                }}
            >
                <DialogContent>
                    <form onSubmit={submitModule}>
                        <DialogHeader>
                            <DialogTitle>
                                {editingModule
                                    ? 'Editar módulo'
                                    : 'Nuevo módulo'}
                            </DialogTitle>
                            <DialogDescription>
                                Agrupá las lecciones del curso en módulos.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="module-title">
                                    Título{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="module-title"
                                    value={moduleForm.data.title}
                                    onChange={(e) =>
                                        moduleForm.setData(
                                            'title',
                                            e.target.value,
                                        )
                                    }
                                    className={
                                        moduleForm.errors.title
                                            ? 'border-red-500'
                                            : ''
                                    }
                                />
                                {moduleForm.errors.title && (
                                    <p className="text-sm text-red-500">
                                        {moduleForm.errors.title}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="module-description">
                                    Descripción
                                </Label>
                                <Textarea
                                    id="module-description"
                                    value={moduleForm.data.description}
                                    onChange={(e) =>
                                        moduleForm.setData(
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setModuleDialogOpen(false)}
                                disabled={moduleForm.processing}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={moduleForm.processing}
                            >
                                {moduleForm.processing && (
                                    <Spinner className="mr-2 h-4 w-4" />
                                )}
                                {editingModule
                                    ? 'Guardar cambios'
                                    : 'Crear módulo'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ---------- Modal lección ---------- */}
            <Dialog
                open={lessonDialogOpen}
                onOpenChange={(open) => {
                    if (!open && !lessonForm.processing)
                        setLessonDialogOpen(false);
                }}
            >
                <DialogContent>
                    <form onSubmit={submitLesson}>
                        <DialogHeader>
                            <DialogTitle>
                                {editingLesson
                                    ? 'Editar lección'
                                    : 'Nueva lección'}
                            </DialogTitle>
                            <DialogDescription>
                                {lessonParent?.title}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="lesson-title">
                                    Título{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="lesson-title"
                                    value={lessonForm.data.title}
                                    onChange={(e) =>
                                        lessonForm.setData(
                                            'title',
                                            e.target.value,
                                        )
                                    }
                                    className={
                                        lessonForm.errors.title
                                            ? 'border-red-500'
                                            : ''
                                    }
                                />
                                {lessonForm.errors.title && (
                                    <p className="text-sm text-red-500">
                                        {lessonForm.errors.title}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="lesson-content">
                                    Contenido
                                </Label>
                                <Textarea
                                    id="lesson-content"
                                    value={lessonForm.data.content}
                                    onChange={(e) =>
                                        lessonForm.setData(
                                            'content',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="lesson-free"
                                    checked={lessonForm.data.is_free_preview}
                                    onCheckedChange={(checked) =>
                                        lessonForm.setData(
                                            'is_free_preview',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label htmlFor="lesson-free">
                                    Vista previa gratuita
                                </Label>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setLessonDialogOpen(false)}
                                disabled={lessonForm.processing}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={lessonForm.processing}
                            >
                                {lessonForm.processing && (
                                    <Spinner className="mr-2 h-4 w-4" />
                                )}
                                {editingLesson
                                    ? 'Guardar cambios'
                                    : 'Crear lección'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ---------- Confirmar borrado módulo ---------- */}
            <Dialog
                open={moduleToDelete !== null}
                onOpenChange={(open) => {
                    if (!open && !deletingModule) setModuleToDelete(null);
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar módulo</DialogTitle>
                        <DialogDescription>
                            ¿Seguro que querés eliminar “{moduleToDelete?.title}
                            ”? Se eliminarán también sus lecciones.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setModuleToDelete(null)}
                            disabled={deletingModule}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={confirmDeleteModule}
                            disabled={deletingModule}
                        >
                            {deletingModule && (
                                <Spinner className="mr-2 h-4 w-4" />
                            )}
                            {deletingModule ? 'Eliminando...' : 'Eliminar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ---------- Confirmar borrado lección ---------- */}
            <Dialog
                open={lessonToDelete !== null}
                onOpenChange={(open) => {
                    if (!open && !deletingLesson) setLessonToDelete(null);
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Eliminar lección</DialogTitle>
                        <DialogDescription>
                            ¿Seguro que querés eliminar “{lessonToDelete?.title}
                            ”?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setLessonToDelete(null)}
                            disabled={deletingLesson}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={confirmDeleteLesson}
                            disabled={deletingLesson}
                        >
                            {deletingLesson && (
                                <Spinner className="mr-2 h-4 w-4" />
                            )}
                            {deletingLesson ? 'Eliminando...' : 'Eliminar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

Content.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Cursos', href: courseRoutes.index() },
        { title: 'Contenido', href: courseRoutes.index() },
    ],
};
