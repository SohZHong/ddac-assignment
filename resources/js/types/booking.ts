export enum BookingStatus {
    PENDING = 0,
    CONFIRMED = 1,
    CANCELLED = 2,
}

export interface Booking {
    id?: string;
    schedule_id: string;
    patient_id: string;
    start_time: string;
    end_time: string;
    status: BookingStatus;
}
