import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import type { SubmitEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import courses from '@/routes/admin/courses';

interface LevelOption {
    value: string;
    label: string;
}

interface Props {
    levels: LevelOption[];
}

interface FormData {
    title: string;
    slug: string;
    description: string;
    syllabus: string;
    level: string;
    base_price: string | number;
    offer_price: string | number;
    duration_months: string | number;
    extension_price_month: string | number;
    is_published: boolean;
}

export default function Create({ levels }: Props) {
    const { data, setData, post, processing, errors, reset } =
        useForm<FormData>({
            title: '',
            slug: '',
            description: '',
            syllabus: '',
            level: '',
            base_price: '',
            offer_price: '',
            duration_months: '',
            extension_price_month: '',
            is_published: false,
        });

    const handleSubmit = (e: SubmitEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(courses.store.url(), {
            onSuccess: () => reset(),
        });
    };

    return (
        <>
            <Head title="Crear curso" />

            <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Nuevo curso
                        </h1>
                        <p className="text-muted-foreground">
                            Completá los datos para publicar un nuevo curso.
                        </p>
                    </div>
                    <Link
                        href={courses.index()}
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Volver al listado
                    </Link>
                </div>

                <form onSubmit={handleSubmit}>
                    <CardContent>
                        <CardHeader>
                            <CardTitle>Información del curso</CardTitle>
                            <CardDescription>
                                Los campos marcados con * son obligatorios.
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="title">
                                    Título{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData('title', e.target.value)
                                    }
                                    placeholder="Ej: Inglés para negocios intermedio"
                                    className={
                                        errors.title ? 'border-red-500' : ''
                                    }
                                />
                                {errors.title && (
                                    <p className="text-sm text-red-500">
                                        {errors.title}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="slug">
                                    Slug (URL amigable)
                                </Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) =>
                                        setData('slug', e.target.value)
                                    }
                                    placeholder="Se genera automáticamente desde el título"
                                    className={
                                        errors.slug ? 'border-red-500' : ''
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Si lo dejás vacío, se generará
                                    automáticamente:{' '}
                                    <code>ingles-negocios-intermedio</code>
                                </p>
                                {errors.slug && (
                                    <p className="text-sm text-red-500">
                                        {errors.slug}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Descripción</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                    placeholder="Resumen del curso que verán los estudiantes..."
                                    rows={4}
                                    className={
                                        errors.description
                                            ? 'border-red-500'
                                            : ''
                                    }
                                />
                                {errors.description && (
                                    <p className="text-sm text-red-500">
                                        {errors.description}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="syllabus">
                                    Syllabus / Programa
                                </Label>
                                <Textarea
                                    id="syllabus"
                                    value={data.syllabus}
                                    onChange={(e) =>
                                        setData('syllabus', e.target.value)
                                    }
                                    placeholder="Detalle de los temas que se verán..."
                                    rows={4}
                                    className={
                                        errors.syllabus ? 'border-red-500' : ''
                                    }
                                />
                                {errors.syllabus && (
                                    <p className="text-sm text-red-500">
                                        {errors.syllabus}
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="level">
                                        Nivel{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        value={data.level}
                                        onValueChange={(value) =>
                                            setData('level', value)
                                        }
                                    >
                                        <SelectTrigger
                                            className={
                                                errors.level
                                                    ? 'border-red-500'
                                                    : ''
                                            }
                                        >
                                            <SelectValue placeholder="Seleccionar nivel" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {levels.map((lvl) => (
                                                <SelectItem
                                                    key={lvl.value}
                                                    value={lvl.value}
                                                >
                                                    {lvl.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.level && (
                                        <p className="text-sm text-red-500">
                                            {errors.level}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="duration_months">
                                        Duración (meses){' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="duration_months"
                                        type="number"
                                        min={1}
                                        max={24}
                                        value={data.duration_months}
                                        onChange={(e) =>
                                            setData(
                                                'duration_months',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="3"
                                        className={
                                            errors.duration_months
                                                ? 'border-red-500'
                                                : ''
                                        }
                                    />
                                    {errors.duration_months && (
                                        <p className="text-sm text-red-500">
                                            {errors.duration_months}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="base_price">
                                        Precio base ($){' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="base_price"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        value={data.base_price}
                                        onChange={(e) =>
                                            setData(
                                                'base_price',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="99.99"
                                        className={
                                            errors.base_price
                                                ? 'border-red-500'
                                                : ''
                                        }
                                    />
                                    {errors.base_price && (
                                        <p className="text-sm text-red-500">
                                            {errors.base_price}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="offer_price">
                                        Precio de oferta ($)
                                    </Label>
                                    <Input
                                        id="offer_price"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        value={data.offer_price}
                                        onChange={(e) =>
                                            setData(
                                                'offer_price',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="79.99"
                                        className={
                                            errors.offer_price
                                                ? 'border-red-500'
                                                : ''
                                        }
                                    />
                                    {errors.offer_price && (
                                        <p className="text-sm text-red-500">
                                            {errors.offer_price}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="extension_price_month">
                                        Precio extensión mensual ($)
                                    </Label>
                                    <Input
                                        id="extension_price_month"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        value={data.extension_price_month}
                                        onChange={(e) =>
                                            setData(
                                                'extension_price_month',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="9.99"
                                        className={
                                            errors.extension_price_month
                                                ? 'border-red-500'
                                                : ''
                                        }
                                    />
                                    {errors.extension_price_month && (
                                        <p className="text-sm text-red-500">
                                            {errors.extension_price_month}
                                        </p>
                                    )}
                                </div>

                                <div className="flex items-center space-x-3 pt-6">
                                    <Checkbox
                                        id="is_published"
                                        checked={data.is_published}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'is_published',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <div className="space-y-1 leading-none">
                                        <Label htmlFor="is_published">
                                            Publicar inmediatamente
                                        </Label>
                                        <p className="text-xs text-muted-foreground">
                                            Si lo marcás, el curso será visible
                                            en el catálogo público.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-end gap-4 border-t pt-4">
                                <Link
                                    href={courses.index()}
                                    className={`text-sm text-muted-foreground transition-colors hover:text-foreground ${
                                        processing
                                            ? 'pointer-events-none opacity-50'
                                            : ''
                                    }`}
                                >
                                    Cancelar
                                </Link>
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <>
                                            <Spinner className="mr-2 h-4 w-4" />
                                            Guardando...
                                        </>
                                    ) : (
                                        <>
                                            <Save className="mr-2 h-4 w-4" />
                                            Crear curso
                                        </>
                                    )}
                                </Button>
                            </div>
                        </CardContent>
                    </CardContent>
                </form>
            </div>
        </>
    );
}
