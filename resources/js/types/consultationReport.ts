import { User } from '.';

export interface ConsultationReport {
    id: number;
    booking_id: number;
    user_id: number; // patient
    report_url: string;
    notes?: string;
    created_at: string;
    updated_at: string;

    // Relations
    patient?: User; // optional preload
    healthcare?: User;
}
