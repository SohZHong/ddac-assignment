export enum QuestionType {
    MCQ = 0,
    TRUE_FALSE = 1,
    TEXT = 2,
}

export interface Quiz {
    id: number;
    healthcare_id: number;
    title: string;
    description?: string;
    questions?: QuizQuestion[];
}

export interface QuizQuestion {
    id: number;
    quiz_id: number;
    question_text: string;
    question_type: QuestionType;
    options?: string[]; // only used for MCQ questions
}

export interface QuizResponse {
    id: number;
    quiz_id: number;
    booking_id: string;
    // key = question_id, value = answer
    answers: Record<number, string | boolean>;
    completed_at: string;
    booking?: {
        id: string;
        patient: {
            id: number;
            name: string;
            email: string;
        };
    };
}
