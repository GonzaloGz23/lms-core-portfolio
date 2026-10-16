import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { formatFullName } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import courseRoutes from '@/routes/admin/courses';
import type { CourseLevel } from '@/types';
import { courseLevelLabels } from '@/types';

interface CourseItem {
    id: number;
    title: string;
    slug: string;
    level: CourseLevel;
    base_price: string;
    offer_price: string | null;
    is_published: boolean;
    created_by: number;
    modules_count: number;
    creator: {
        id: number;
        email: string;
        profile: {
            first_name: string;
            last_name: string;
        };
    } | null;
}

interface PaginatedCourses {
    data: CourseItem[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface Props {
    courses: PaginatedCourses;
}

export default function Index({ courses }: Props) {
    const auth = usePage().props.auth;
    const isAdmin = auth.user.role === 'admin';
    const currentUserId = auth.user.id;

    const [courseToDelete, setCourseToDelete] = useState<CourseItem | null>(
        null,
    );
    const [deletingCourse, setDeletingCourse] = useState(false);

    const canManageCourse = (course: CourseItem): boolean =>
        isAdmin || course.created_by === currentUserId;

    const hasActions = courses.data.some(canManageCourse);

    const confirmDelete = () => {
        if (!courseToDelete) {
            return;
        }

        setDeletingCourse(true);
        router.delete(courseRoutes.destroy(courseToDelete.id).url, {
            preserveScroll: true,
            onFinish: () => {
                setDeletingCourse(false);
                setCourseToDelete(null);
            },
        });
    };

    return (
        <>
            <Head title="Cursos" />

            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Cursos
                        </h1>
                        <p className="text-muted-foreground">
                            Administrá los cursos del catálogo.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={courseRoutes.create()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo curso
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {courses.data.length === 0 ? (
                            <p className="p-8 text-center text-muted-foreground">
                                Todavía no hay cursos. Creá el primero.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="px-4 py-3 font-medium">
                                                Título
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Nivel
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Precio
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Módulos
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Creador
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Estado
                                            </th>
                                            {hasActions && (
                                                <th className="px-4 py-3 text-right font-medium">
                                                    Acciones
                                                </th>
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {courses.data.map((course) => (
                                            <tr
                                                key={course.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-3 font-medium">
                                                    <Link
                                                        href={courseRoutes.show(
                                                            course.id,
                                                        )}
                                                        className="transition-colors hover:text-primary hover:underline"
                                                    >
                                                        {course.title}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="secondary">
                                                        {
                                                            courseLevelLabels[
                                                                course.level
                                                            ]
                                                        }
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    ${course.base_price}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {course.modules_count}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {course.creator?.profile
                                                        ? formatFullName(
                                                              course.creator
                                                                  .profile
                                                                  .first_name,
                                                              course.creator
                                                                  .profile
                                                                  .last_name,
                                                          )
                                                        : (course.creator
                                                              ?.email ?? '-')}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {course.is_published ? (
                                                        <Badge>Publicado</Badge>
                                                    ) : (
                                                        <Badge variant="outline">
                                                            Borrador
                                                        </Badge>
                                                    )}
                                                </td>
                                                {canManageCourse(course) && (
                                                    <td className="px-4 py-3">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Button
                                                                asChild
                                                                size="sm"
                                                                variant="outline"
                                                                title="Ver"
                                                                aria-label={`Ver ${course.title}`}
                                                            >
                                                                <Link
                                                                    href={courseRoutes.show(
                                                                        course.id,
                                                                    )}
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                asChild
                                                                size="sm"
                                                                variant="outline"
                                                                title="Editar"
                                                                aria-label={`Editar ${course.title}`}
                                                            >
                                                                <Link
                                                                    href={courseRoutes.edit(
                                                                        course.id,
                                                                    )}
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="destructive"
                                                                title="Eliminar"
                                                                aria-label={`Eliminar ${course.title}`}
                                                                onClick={() =>
                                                                    setCourseToDelete(
                                                                        course,
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {courses.last_page > 1 && (
                    <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            Página {courses.current_page} de {courses.last_page}{' '}
                            ({courses.total} en total)
                        </span>
                        <div className="flex gap-2">
                            {courses.current_page > 1 ? (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={courseRoutes.index({
                                            query: {
                                                page: courses.current_page - 1,
                                            },
                                        })}
                                    >
                                        Anterior
                                    </Link>
                                </Button>
                            ) : (
                                <Button size="sm" variant="outline" disabled>
                                    Anterior
                                </Button>
                            )}
                            {courses.current_page < courses.last_page ? (
                                <Button asChild size="sm" variant="outline">
                                    <Link
                                        href={courseRoutes.index({
                                            query: {
                                                page: courses.current_page + 1,
                                            },
                                        })}
                                    >
                                        Siguiente
                                    </Link>
                                </Button>
                            ) : (
                                <Button size="sm" variant="outline" disabled>
                                    Siguiente
                                </Button>
                            )}
                        </div>
                    </div>
                )}
                <Dialog
                    open={courseToDelete !== null}
                    onOpenChange={(open) => {
                        if (!open && !deletingCourse) {
                            setCourseToDelete(null);
                        }
                    }}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Eliminar curso</DialogTitle>
                            <DialogDescription>
                                ¿Seguro que querés eliminar “
                                {courseToDelete?.title}”? Esta acción no se
                                puede deshacer.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() => setCourseToDelete(null)}
                                disabled={deletingCourse}
                            >
                                Cancelar
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={confirmDelete}
                                disabled={deletingCourse}
                            >
                                {deletingCourse && (
                                    <Spinner className="mr-2 h-4 w-4" />
                                )}
                                {deletingCourse ? 'Eliminando...' : 'Eliminar'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: dashboard(),
        },
        {
            title: 'Cursos',
            href: courseRoutes.index(),
        },
    ],
};
