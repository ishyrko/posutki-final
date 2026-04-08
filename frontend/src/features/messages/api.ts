import api from '@/lib/api';
import {
    ConversationListResponse,
    MessageListResponse,
    SendMessagePayload,
    SendMessageResponse,
} from './types';

export const getConversations = async (
    page = 1,
    limit = 20
): Promise<ConversationListResponse> => {
    const response = await api.get('/messages/conversations', {
        params: { page, limit },
    });
    return response.data;
};

export const getMessages = async (
    conversationId: number,
    page = 1,
    limit = 50
): Promise<MessageListResponse> => {
    const response = await api.get(`/messages/conversations/${conversationId}`, {
        params: { page, limit },
    });
    return response.data;
};

export const sendMessage = async (
    payload: SendMessagePayload
): Promise<SendMessageResponse> => {
    const response = await api.post('/messages/send', payload);
    return response.data.data;
};

export const markConversationRead = async (
    conversationId: number
): Promise<void> => {
    await api.post(`/messages/conversations/${conversationId}/read`);
};

export const getUnreadCount = async (): Promise<number> => {
    const response = await api.get('/messages/unread-count');
    return response.data.data.unreadCount;
};
