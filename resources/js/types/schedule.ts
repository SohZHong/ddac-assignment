export enum DayOfWeek {
    SUNDAY = 0,
    MONDAY = 1,
    TUESDAY = 2,
    WEDNESDAY = 3,
    THURSDAY = 4,
    FRIDAY = 5,
    SATURDAY = 6,
}

export interface Schedule {
    id?: string;
    title?: string;
    start: string;
    end: string;
    day_of_week: number;
}
