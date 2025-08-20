export enum QuestionType {
    MCQ = 0,
    TRUE_FALSE = 1,
    TEXT = 2,
}

export interface Quiz {
    id: string;
    healthcare_id: string;
    title: string;
    description?: string;
    questions?: QuizQuestion[];
    active: boolean;
}

export interface QuizQuestion {
    id: string;
    quiz_id: string;
    question_text: string;
    type: QuestionType;
    options?: string[]; // only used for MCQ questions
}

export interface QuizResponse {
    id: string;
    quiz_id: string;
    booking_id: string;
    // key = question_id, value = answer
    answers: { question_id: string | number; answer: string }[];
    completed_at: string;
    booking?: {
        id: string;
        patient: {
            id: string;
            name: string;
            email: string;
        };
    };
}
