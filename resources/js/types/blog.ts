export interface Author {
    id: string;
    name: string;
}

export enum BlogStatus {
    STATUS_DRAFT = 0,
    STATUS_PUBLISHED = 1,
}

export interface Blog {
    id: string;
    title: string;
    slug: string;
    cover_image?: string;
    content?: string;
    author: Author;
    published_at: string;
}

export type BlogList = {
    data: Blog[];
    links: any;
    meta: any;
};
