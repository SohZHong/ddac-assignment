import { QuizResponse } from './quiz';

export enum BookingStatus {
    PENDING = 0,
    CONFIRMED = 1,
    CANCELLED = 2,
}

export enum PatientRiskLevel {
    LOW = 0,
    MID = 1,
    HIGH = 2,
}

export interface BookingPatient {
    id: number;
    name: string;
    email: string;
}

export interface BookingHealthcare {
    id: string;
    name: string;
}
export interface Booking {
    id?: string;
    healthcare?: BookingHealthcare;
    schedule_id: string;
    patient_id?: string;
    patient: BookingPatient;
    start_time: string;
    end_time: string;
    status: BookingStatus;
    healthcare_comments: string;
    risk_level: PatientRiskLevel;
    has_assessment?: boolean;
    has_video_call?: boolean;
    video_call_status?: string;
    quizResponse?: QuizResponse;
}
