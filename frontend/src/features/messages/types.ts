export interface Message {
    id: number;
    conversationId: number;
    senderId: number;
    text: string;
    isRead: boolean;
    createdAt: string;
}

export interface Conversation {
    id: number;
    propertyId: number;
    propertyTitle: string | null;
    propertyImage: string | null;
    sellerId: number;
    sellerName: string | null;
    buyerId: number;
    buyerName: string | null;
    lastMessageText: string | null;
    lastMessageAt: string | null;
    unread: number;
    createdAt: string;
}

export interface ConversationListResponse {
    data: Conversation[];
    pagination: {
        total: number;
        page: number;
        limit: number;
        pages: number;
    };
}

export interface MessageListResponse {
    data: Message[];
    pagination: {
        total: number;
        page: number;
        limit: number;
        pages: number;
    };
}

export interface SendMessagePayload {
    text: string;
    propertyId?: number;
    conversationId?: number;
}

export interface SendMessageResponse {
    conversationId: number;
    messageId: number;
}
