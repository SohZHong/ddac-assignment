export interface Author {
    id: string;
    name: string;
}

export type BlogStatus = true | false;

export interface Blog {
    id: string;
    title: string;
    slug: string;
    cover_image?: string;
    content?: string;
    author: Author;
    published_at?: string;
    status: BlogStatus;
}
