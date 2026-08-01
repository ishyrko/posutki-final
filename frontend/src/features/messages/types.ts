export interface Message {
    id: number;
    conversationId: number;
    senderId: number;
    text: string;
    isRead: boolean;
    createdAt: string;
}

export interface ConversationBookingInquiry {
    id: string;
    status: 'new' | 'replied' | 'accepted' | 'declined';
    checkIn?: string | null;
    checkOut?: string | null;
    guests?: number | null;
    createdAt: string;
}

export interface Conversation {
    id: number;
    propertyId: number;
    propertyTitle: string | null;
    propertyImage: string | null;
    propertyType?: string | null;
    propertyCitySlug?: string | null;
    propertyRegionName?: string | null;
    propertyPriceAmount?: number | null;
    propertyPriceCurrency?: string | null;
    propertyAddress?: string | null;
    propertyAvailable?: boolean;
    propertyLinkAvailable?: boolean;
    sellerId: number;
    sellerName: string | null;
    buyerId: number;
    buyerName: string | null;
    lastMessageText: string | null;
    lastMessageAt: string | null;
    unread: number;
    createdAt: string;
    bookingInquiry?: ConversationBookingInquiry | null;
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
    buyerId?: number;
    bookingInquiryId?: string;
}

export interface SendMessageResponse {
    conversationId: number;
    messageId: number;
}
