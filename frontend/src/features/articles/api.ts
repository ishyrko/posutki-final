import api from '@/lib/api';
import { trackViewOnce } from '@/lib/view-tracking';
import { Article, ArticleCategory, ArticleFilters } from './types';

export const getArticleCategories = async (): Promise<ArticleCategory[]> => {
    const response = await api.get<{ data: ArticleCategory[] }>('/article-categories');
    return response.data.data;
};

export const getArticles = async (filters: ArticleFilters = {}): Promise<Article[]> => {
    const params = new URLSearchParams();

    if (filters.page) params.append('page', filters.page.toString());
    if (filters.limit) params.append('limit', filters.limit.toString());
    if (filters.categorySlug) params.append('categorySlug', filters.categorySlug);
    if (filters.categoryId) params.append('categoryId', filters.categoryId.toString());
    if (filters.tag) params.append('tag', filters.tag);
    if (filters.status) params.append('status', filters.status);

    const response = await api.get<{ data: Article[] }>(`/articles?${params.toString()}`);
    return response.data.data;
};

export const getArticleBySlug = async (slug: string): Promise<Article> => {
    const response = await api.get<{ data: Article }>(`/articles/${slug}`);
    return response.data.data;
};

export const trackArticleView = async (
    slug: string,
): Promise<{ views: number; counted: boolean } | null> => {
    return trackViewOnce(`article:${slug}`, (visitorId) =>
        api
            .post<{ data: { views: number; counted: boolean } }>(
                `/articles/${encodeURIComponent(slug)}/view`,
                { visitorId },
            )
            .then((response) => response.data.data),
    );
};

export const getCategoryBySlug = async (slug: string): Promise<ArticleCategory> => {
    const response = await api.get<{ data: ArticleCategory }>(`/article-categories/${slug}`);
    return response.data.data;
};
