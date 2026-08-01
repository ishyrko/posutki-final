'use client';

import Link from 'next/link';
import { Ban } from 'lucide-react';
import { buildPropertyUrlFromRegionName } from '@/features/catalog/slugs';
import { formatPrice, normalizeCurrency } from '@/features/properties/price-display';
import type { Conversation } from '@/features/messages/types';
import { cn } from '@/lib/utils';

function isPropertyAvailable(conversation: Conversation): boolean {
    if (conversation.propertyAvailable === true) {
        return true;
    }
    if (conversation.propertyAvailable === false) {
        return false;
    }
    return Boolean(conversation.propertyTitle || conversation.propertyImage);
}

function canLinkToProperty(conversation: Conversation): boolean {
    if (!isPropertyAvailable(conversation) || conversation.propertyId <= 0) {
        return false;
    }
    if (conversation.propertyLinkAvailable === false) {
        return false;
    }
    if (conversation.propertyLinkAvailable === true) {
        return true;
    }
    return conversation.propertyAvailable === true
        || conversation.propertyAvailable == null;
}

interface ConversationContextCardProps {
    conversation: Conversation;
}

function PropertyCardContent({
    conversation,
    linked = false,
}: {
    conversation: Conversation;
    linked?: boolean;
}) {
    const priceLabel = conversation.propertyPriceAmount != null
        && conversation.propertyPriceCurrency
        ? formatPrice(
            conversation.propertyPriceAmount,
            normalizeCurrency(conversation.propertyPriceCurrency),
        )
        : null;

    return (
        <>
            {conversation.propertyImage ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                    src={conversation.propertyImage}
                    alt={conversation.propertyTitle ?? 'Объявление'}
                    className="w-16 h-16 rounded-lg object-cover shrink-0"
                />
            ) : (
                <div className="w-16 h-16 rounded-lg bg-muted shrink-0" />
            )}
            <div className="min-w-0 flex-1">
                <p className={cn(
                    'text-sm font-medium text-foreground line-clamp-2',
                    linked && 'hover:text-primary',
                )}
                >
                    {conversation.propertyTitle || 'Объявление'}
                </p>
                {priceLabel && (
                    <p className="text-sm font-semibold text-foreground mt-1">{priceLabel}</p>
                )}
                {conversation.propertyAddress && (
                    <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
                        {conversation.propertyAddress}
                    </p>
                )}
            </div>
        </>
    );
}

function UnavailablePropertyCard({ conversation }: { conversation: Conversation }) {
    return (
        <div className="flex items-start gap-3 rounded-lg border border-dashed border-border bg-muted/40 p-3">
            {conversation.propertyImage ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                    src={conversation.propertyImage}
                    alt=""
                    className="w-16 h-16 rounded-lg object-cover shrink-0 opacity-50 grayscale"
                />
            ) : (
                <div className="w-16 h-16 rounded-lg bg-muted shrink-0 flex items-center justify-center">
                    <Ban className="w-6 h-6 text-muted-foreground" />
                </div>
            )}
            <div className="min-w-0 flex-1">
                {conversation.propertyTitle && (
                    <p className="text-sm font-medium text-muted-foreground line-clamp-2">
                        {conversation.propertyTitle}
                    </p>
                )}
                <p className={cn(
                    'text-sm text-muted-foreground',
                    conversation.propertyTitle && 'mt-1',
                )}
                >
                    Объявление больше недоступно
                </p>
            </div>
        </div>
    );
}

export function ConversationContextCard({ conversation }: ConversationContextCardProps) {
    const propertyAvailable = isPropertyAvailable(conversation);
    const canLink = canLinkToProperty(conversation);

    const propertyHref = buildPropertyUrlFromRegionName(
        conversation.propertyType ?? undefined,
        conversation.propertyId,
        conversation.propertyRegionName ?? undefined,
        conversation.propertyCitySlug ?? undefined,
    );

    const propertyCardClassName = 'flex items-start gap-3 rounded-lg border border-border bg-card p-3';

    return (
        <div className="shrink-0 border-b border-border bg-muted/30 px-4 py-3">
            {propertyAvailable ? (
                canLink ? (
                    <Link
                        href={propertyHref}
                        className={cn(propertyCardClassName, 'hover:border-primary/40 transition-colors')}
                    >
                        <PropertyCardContent conversation={conversation} linked />
                    </Link>
                ) : (
                    <div className={propertyCardClassName}>
                        <PropertyCardContent conversation={conversation} />
                    </div>
                )
            ) : (
                <UnavailablePropertyCard conversation={conversation} />
            )}
        </div>
    );
}
