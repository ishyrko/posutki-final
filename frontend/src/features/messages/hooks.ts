'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    getConversations,
    getMessages,
    sendMessage,
    markConversationRead,
    getUnreadCount,
} from './api';
import { SendMessagePayload } from './types';
import { isAuthenticated } from '@/lib/auth';

export const useConversations = (page = 1, limit = 20) => {
    return useQuery({
        queryKey: ['conversations', page, limit],
        queryFn: () => getConversations(page, limit),
        enabled: isAuthenticated(),
    });
};

export const useMessages = (conversationId: number, page = 1, limit = 50) => {
    return useQuery({
        queryKey: ['messages', conversationId, page, limit],
        queryFn: () => getMessages(conversationId, page, limit),
        enabled: isAuthenticated() && conversationId > 0,
        refetchInterval: 10000,
    });
};

export const useSendMessage = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (payload: SendMessagePayload) => sendMessage(payload),
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: ['conversations'] });
            queryClient.invalidateQueries({
                queryKey: ['messages', data.conversationId],
            });
            queryClient.invalidateQueries({ queryKey: ['unread-count'] });
        },
    });
};

export const useMarkRead = () => {
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (conversationId: number) => markConversationRead(conversationId),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['conversations'] });
            queryClient.invalidateQueries({ queryKey: ['unread-count'] });
        },
    });
};

export const useUnreadCount = () => {
    return useQuery({
        queryKey: ['unread-count'],
        queryFn: getUnreadCount,
        enabled: isAuthenticated(),
        refetchInterval: 30000,
    });
};
