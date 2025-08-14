export interface Author {
    id: string;
    name: string;
}

export interface Blog {
    id: string;
    title: string;
    slug: string;
    cover_image?: string;
    author: Author;
    published_at: string;
}

export type BlogList = {
    data: Blog[];
    links: any;
    meta: any;
};
