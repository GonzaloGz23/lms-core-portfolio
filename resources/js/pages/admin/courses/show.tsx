import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Layers, Pencil } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatFullName } from '@/lib/utils';
import { dashboard } from '@/routes/admin';
import courseRoutes from '@/routes/admin/courses';
import type { CourseCreator, CourseLevel, CourseModule } from '@/types';
import { courseLevelLabels } from '@/types';

interface Props {
    course: {
        id: number;
        title: string;
        description: string | null;
        syllabus: string | null;
        level: CourseLevel;
        base_price: string;
        offer_price: string | null;
        duration_months: number;
        is_published: boolean;
        created_by: number;
        creator: CourseCreator;
        modules: CourseModule[];
    };
}

export default function Show({ course }: Props) {
    const creatorName = formatFullName(
        course.creator.profile.first_name,
        course.creator.profile.last_name,
    );

    return (
        <>
            <Head title="Detalle del curso" />
            <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            {course.title}
                        </h1>
                        <div className="mt-2 flex items-center gap-2">
                            <Badge variant="secondary">
                                {courseLevelLabels[course.level]}
                            </Badge>
                            {course.is_published ? (
                                <Badge>Publicado</Badge>
                            ) : (
                                <Badge variant="outline">Borrador</Badge>
                            )}
                            <span className="text-sm text-muted-foreground">
                                {creatorName}
                            </span>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={courseRoutes.edit(course.id)}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Editar
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={courseRoutes.index()}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={courseRoutes.content(course.id)}>
                                <Layers className="mr-2 h-4 w-4" />
                                Administrar contenido
                            </Link>
                        </Button>
                    </div>
                </div>
                <Separator className="mb-6" />
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
                            <CardDescription>
                                Datos comerciales y de duración.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <InfoItem
                                label="Nivel"
                                value={courseLevelLabels[course.level]}
                            />
                            <InfoItem
                                label="Duración"
                                value={`${course.duration_months} ${course.duration_months === 1 ? 'mes' : 'meses'}`}
                            />
                            <div className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    Precio
                                </p>
                                <Price
                                    basePrice={course.base_price}
                                    offerPrice={course.offer_price}
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Descripción</CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm leading-relaxed text-foreground">
                            {course.syllabus ?? 'Sin programa definido.'}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Layers className="h-4 w-4" />
                                Módulos ({course.modules.length})
                            </CardTitle>
                            <CardDescription>
                                Lecciones agrupadas por módulo.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {course.modules.length > 0 ? (
                                <ul className="space-y-3">
                                    {course.modules.map((mod) => (
                                        <li
                                            key={mod.id}
                                            className="flex items-center justify-between rounded-lg border px-4 py-3"
                                        >
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {mod.title}
                                                </p>
                                                {mod.description && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {mod.description}
                                                    </p>
                                                )}
                                            </div>
                                            <Badge variant="secondary">
                                                {mod.lessons.length}{' '}
                                                {mod.lessons.length === 1
                                                    ? 'lección'
                                                    : 'lecciones'}
                                            </Badge>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Este curso todavía no tiene módulos.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function InfoItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="space-y-1">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="text-sm font-medium text-foreground">{value}</p>
        </div>
    );
}

function Price({
    basePrice,
    offerPrice,
}: {
    basePrice: string;
    offerPrice: string | null;
}) {
    return (
        <div className="flex items-baseline gap-2">
            {offerPrice ? (
                <>
                    <span className="text-sm text-muted-foreground line-through">
                        ${offerPrice}
                    </span>
                    <span className="text-lg font-semibold text-foreground">
                        ${basePrice}
                    </span>
                </>
            ) : (
                <span className="text-lg font-semibold text-foreground">
                    ${basePrice}
                </span>
            )}
        </div>
    );
}

Show.layout = {
    breadcrumbs: [
        { title: 'Admin', href: dashboard() },
        { title: 'Cursos', href: courseRoutes.index() },
        { title: 'Detalle del curso', href: courseRoutes.index() },
    ],
};
