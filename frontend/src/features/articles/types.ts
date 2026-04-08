export interface ArticleCategory {
    id: number;
    name: string;
    slug: string;
    sortOrder: number;
}

export interface Article {
    id: number;
    authorId: number;
    title: string;
    slug: string;
    content: string;
    excerpt: string;
    coverImage: string | null;
    categoryId: number | null;
    categoryName: string | null;
    categorySlug: string | null;
    tags: string[];
    status: string;
    views: number;
    createdAt: string;
    updatedAt: string;
    publishedAt: string | null;
}

export interface ArticleFilters {
    page?: number;
    limit?: number;
    categorySlug?: string;
    categoryId?: number;
    tag?: string;
    status?: string;
}
