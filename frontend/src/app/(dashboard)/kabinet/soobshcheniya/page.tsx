'use client';

import { useState, useRef, useEffect } from 'react';
import { motion } from 'framer-motion';
import { MessageSquare, Send, ArrowLeft, Check, CheckCheck, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useConversations, useMessages, useSendMessage, useMarkRead } from '@/features/messages/hooks';
import { useUser } from '@/features/auth/hooks';
import { Conversation } from '@/features/messages/types';

function formatTime(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const isToday = date.toDateString() === now.toDateString();
    if (isToday) {
        return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    }
    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
}

function ConversationList({
    conversations,
    selectedId,
    onSelect,
    currentUserId,
}: {
    conversations: Conversation[];
    selectedId: number | null;
    onSelect: (id: number) => void;
    currentUserId: number;
}) {
    return (
        <div className="space-y-1">
            {conversations.map((conv) => {
                const otherName = conv.sellerId === currentUserId ? conv.buyerName : conv.sellerName;
                const isSelected = selectedId === conv.id;
                return (
                    <button
                        key={conv.id}
                        onClick={() => onSelect(conv.id)}
                        className={`w-full text-left p-3 rounded-xl transition-colors ${
                            isSelected
                                ? 'bg-primary/10 border border-primary/30'
                                : 'hover:bg-muted border border-transparent'
                        }`}
                    >
                        <div className="flex items-start gap-3">
                            <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold flex-shrink-0">
                                {(otherName || '?').charAt(0)}
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center justify-between gap-2">
                                    <span className="font-medium text-foreground text-sm truncate">
                                        {otherName || 'Пользователь'}
                                    </span>
                                    {conv.lastMessageAt && (
                                        <span className="text-xs text-muted-foreground whitespace-nowrap">
                                            {formatTime(conv.lastMessageAt)}
                                        </span>
                                    )}
                                </div>
                                <p className="text-xs text-muted-foreground truncate mt-0.5">
                                    {conv.propertyTitle || 'Объект'}
                                </p>
                                <div className="flex items-center justify-between gap-2 mt-1">
                                    <p className="text-sm text-foreground/70 truncate">
                                        {conv.lastMessageText || 'Нет сообщений'}
                                    </p>
                                    {conv.unread > 0 && (
                                        <span className="bg-primary text-primary-foreground text-xs rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5 font-medium flex-shrink-0">
                                            {conv.unread}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </button>
                );
            })}
        </div>
    );
}

function ChatView({
    conversationId,
    conversation,
    currentUserId,
    onBack,
}: {
    conversationId: number;
    conversation: Conversation;
    currentUserId: number;
    onBack: () => void;
}) {
    const { data, isLoading } = useMessages(conversationId);
    const sendMessageMutation = useSendMessage();
    const markRead = useMarkRead();
    const [text, setText] = useState('');
    const messagesEndRef = useRef<HTMLDivElement>(null);

    const messages = data?.data || [];

    useEffect(() => {
        if (conversation.unread > 0) {
            markRead.mutate(conversationId);
        }
    }, [conversationId, conversation.unread, markRead]);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages.length]);

    const handleSend = () => {
        const trimmed = text.trim();
        if (!trimmed) return;
        sendMessageMutation.mutate(
            { text: trimmed, conversationId },
            { onSuccess: () => setText('') }
        );
    };

    const otherName = conversation.sellerId === currentUserId
        ? conversation.buyerName
        : conversation.sellerName;

    return (
        <div className="flex flex-col h-full">
            {/* Header */}
            <div className="p-4 border-b border-border flex items-center gap-3">
                <button onClick={onBack} className="lg:hidden p-1.5 rounded-lg hover:bg-muted">
                    <ArrowLeft className="w-5 h-5" />
                </button>
                <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold flex-shrink-0">
                    {(otherName || '?').charAt(0)}
                </div>
                <div className="min-w-0">
                    <p className="font-medium text-foreground text-sm truncate">
                        {otherName || 'Пользователь'}
                    </p>
                    <p className="text-xs text-muted-foreground truncate">
                        {conversation.propertyTitle}
                    </p>
                </div>
            </div>

            {/* Messages */}
            <div className="flex-1 overflow-y-auto p-4 space-y-3">
                {isLoading && (
                    <div className="flex justify-center py-8">
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                    </div>
                )}
                {messages.map((msg) => {
                    const isMine = msg.senderId === currentUserId;
                    return (
                        <div key={msg.id} className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
                            <div
                                className={`max-w-[75%] px-4 py-2.5 rounded-2xl text-sm ${
                                    isMine
                                        ? 'bg-primary text-primary-foreground rounded-br-md'
                                        : 'bg-muted text-foreground rounded-bl-md'
                                }`}
                            >
                                <p className="whitespace-pre-wrap">{msg.text}</p>
                                <div className={`flex items-center gap-1 justify-end mt-1 ${
                                    isMine ? 'text-primary-foreground/60' : 'text-muted-foreground'
                                }`}>
                                    <span className="text-xs">{formatTime(msg.createdAt)}</span>
                                    {isMine && (
                                        msg.isRead
                                            ? <CheckCheck className="w-3.5 h-3.5" />
                                            : <Check className="w-3.5 h-3.5" />
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
                <div ref={messagesEndRef} />
            </div>

            {/* Input */}
            <div className="p-4 border-t border-border">
                <form
                    onSubmit={(e) => { e.preventDefault(); handleSend(); }}
                    className="flex gap-2"
                >
                    <Input
                        placeholder="Введите сообщение..."
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        className="flex-1"
                        autoFocus
                    />
                    <Button
                        type="submit"
                        size="icon"
                        disabled={!text.trim() || sendMessageMutation.isPending}
                        className="bg-gradient-primary text-primary-foreground border-0 flex-shrink-0"
                    >
                        <Send className="w-4 h-4" />
                    </Button>
                </form>
            </div>
        </div>
    );
}

export default function MessagesPage() {
    const { data: user } = useUser();
    const { data: conversationsData, isLoading } = useConversations();
    const [selectedId, setSelectedId] = useState<number | null>(null);

    const conversations = conversationsData?.data || [];
    const selectedConversation = conversations.find((c) => c.id === selectedId);

    if (!user) {
        return (
            <div className="flex items-center justify-center h-[calc(100vh-14rem)]">
                <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
            </div>
        );
    }

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
        >
            <h1 className="text-2xl font-display font-bold text-foreground mb-6">Сообщения</h1>

            <div className="bg-card rounded-xl border border-border overflow-hidden shadow-card h-[calc(100vh-14rem)]">
                {isLoading ? (
                    <div className="flex items-center justify-center h-full">
                        <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
                    </div>
                ) : conversations.length === 0 ? (
                    <div className="flex items-center justify-center h-full">
                        <div className="text-center">
                            <MessageSquare className="w-10 h-10 text-muted-foreground/40 mx-auto mb-4" />
                            <p className="text-muted-foreground">У вас пока нет сообщений</p>
                            <p className="text-sm text-muted-foreground/60 mt-1">
                                Сообщения появятся, когда вы начнёте общение с продавцами
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="flex h-full">
                        {/* Conversation list */}
                        <div className={`w-full lg:w-80 xl:w-96 border-r border-border overflow-y-auto p-2 ${
                            selectedId ? 'hidden lg:block' : ''
                        }`}>
                            <ConversationList
                                conversations={conversations}
                                selectedId={selectedId}
                                onSelect={setSelectedId}
                                currentUserId={user.id}
                            />
                        </div>

                        {/* Chat area */}
                        <div className={`flex-1 ${!selectedId ? 'hidden lg:flex' : 'flex'}`}>
                            {selectedConversation ? (
                                <div className="flex-1 flex flex-col">
                                    <ChatView
                                        conversationId={selectedConversation.id}
                                        conversation={selectedConversation}
                                        currentUserId={user.id}
                                        onBack={() => setSelectedId(null)}
                                    />
                                </div>
                            ) : (
                                <div className="flex-1 flex items-center justify-center">
                                    <div className="text-center">
                                        <MessageSquare className="w-10 h-10 text-muted-foreground/30 mx-auto mb-3" />
                                        <p className="text-muted-foreground text-sm">
                                            Выберите диалог для начала общения
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </motion.div>
    );
}
