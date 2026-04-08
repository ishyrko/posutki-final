import api from '@/lib/api';
import { isAxiosError } from 'axios';
import {
    City,
    CitySearchResult,
    Street,
    CreatePropertyPayload,
    CreatePropertyResponse,
    UploadResponse,
} from './types';

export class FileTooLargeError extends Error {
    constructor(fileName: string) {
        super(`${fileName}: файл слишком большой`);
        this.name = 'FileTooLargeError';
    }
}

const FALLBACK_CITIES: City[] = [
    { id: 1, name: 'Минск', slug: 'minsk', region: 'Минская' },
    { id: 2, name: 'Гомель', slug: 'gomel', region: 'Гомельская' },
    { id: 3, name: 'Брест', slug: 'brest', region: 'Брестская' },
    { id: 4, name: 'Гродно', slug: 'grodno', region: 'Гродненская' },
    { id: 5, name: 'Витебск', slug: 'vitebsk', region: 'Витебская' },
    { id: 6, name: 'Могилёв', slug: 'mogilev', region: 'Могилёвская' },
];

export const getCities = async (): Promise<City[]> => {
    try {
        const response = await api.get<{ data: City[] }>('/cities');
        return response.data?.data?.length ? response.data.data : FALLBACK_CITIES;
    } catch {
        return FALLBACK_CITIES;
    }
};

export const searchCities = async (query: string): Promise<CitySearchResult[]> => {
    const response = await api.get<{ data: CitySearchResult[] }>('/address/cities/search', {
        params: { q: query },
    });
    return response.data?.data ?? [];
};

export const searchStreets = async (cityId: number, query: string): Promise<Street[]> => {
    const response = await api.get<{ data: Street[] }>(`/address/cities/${cityId}/streets`, {
        params: { q: query },
    });
    return response.data?.data ?? [];
};

export const createProperty = async (payload: CreatePropertyPayload): Promise<CreatePropertyResponse> => {
    const response = await api.post<{ data: CreatePropertyResponse }>('/properties', payload);
    return response.data.data;
};

export const uploadFile = async (file: File): Promise<string> => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('scope', 'properties');

    try {
        const response = await api.postForm<{ data: UploadResponse }>('/upload', formData);
        return response.data.data.url;
    } catch (error: unknown) {
        if (isAxiosError(error) && error.response?.status === 413) {
            throw new FileTooLargeError(file.name);
        }
        throw error;
    }
};
