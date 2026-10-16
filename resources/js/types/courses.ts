export type CourseLevel = 'beginner' | 'intermediate' | 'advanced';

export const courseLevelLabels: Record<CourseLevel, string> = {
    // genérico<tipo de las llaves, tipo de los valores>
    beginner: 'Principiante',
    intermediate: 'Intermedio',
    advanced: 'Avanzado',
};

export type CourseCreator = {
    id: number;
    email: string;
    profile: {
        first_name: string;
        last_name: string;
    };
};

export type CourseLesson = {
    id: number;
    module_id: number;
    title: string;
    content: string | null;
    // video_url: string | null;
    video_duration_sec: number | null;
    is_free_preview: boolean;
    order: number;
};

export type CourseModule = {
    id: number;
    course_id: number;
    title: string;
    description: string | null;
    order: number;
    lessons: CourseLesson[];
};

export type CourseContent = {
    id: number;
    title: string;
    modules: CourseModule[];
};
