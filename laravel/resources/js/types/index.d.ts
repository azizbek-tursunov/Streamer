import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    permissions?: string[];
    items?: {
        title: string;
        href: string;
    }[];
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    permissions?: string[];
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Camera {
    id: number;
    name: string;
    username?: string;
    password?: string;
    ip_address: string;
    port: number;
    stream_path: string;
    rtsp_url: string; // appended accessor
    youtube_url?: string;
    is_active: boolean;
    is_public: boolean;
    is_streaming_to_youtube: boolean;
    rotation?: number;
    branch_id?: number;
    floor_id?: number;
    faculty_id?: number;
    branch?: Branch;
    floor?: Floor;
    faculty?: Faculty;
    created_at: string;
    updated_at: string;
    snapshot_url?: string | null;
}

export interface Branch {
    id: number;
    name: string;
}

export interface Floor {
    id: number;
    name: string;
    branch_id: number | null;
    branch?: Branch;
}

export interface Faculty {
    id: number;
    name: string;
    hemis_id?: number | null;
    code?: string | null;
    active?: boolean;
    auditoriums_count?: number;
}

export interface AuditoriumType {
    code: string;
    name: string;
}

export interface Building {
    id: number;
    name: string;
}

export interface Auditorium {
    id: number;
    code: number;
    name: string;
    auditoriumType: AuditoriumType;
    volume: number;
    active: boolean;
    building: Building;
    camera_id?: number | null;
    camera?: Camera | null;
    camera_snapshot?: string | null;
    current_lesson?: any | null;
    people_count?: number | null;
    faculty_id?: number | null;
    faculty?: Faculty | null;
}

export interface LessonPair {
    code: string;
    name: string;
    start_time: string;
    end_time: string;
}

export interface Employee {
    id: number;
    name: string;
}

export interface Subject {
    id: number;
    name: string;
    code: string;
}

export interface Group {
    id: number;
    name: string;
}

export interface Lesson {
    id: number;
    subject: Subject;
    employee: Employee;
    group: Group;
    auditorium: Auditorium;
    lessonPair: LessonPair;
    lesson_date: number;
    start_timestamp: number;
    end_timestamp: number;
    trainingType: {
        code: string;
        name: string;
    };
}

