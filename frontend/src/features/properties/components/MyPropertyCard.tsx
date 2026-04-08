import { useMemo } from 'react';
import Link from 'next/link';
import type { ExchangeRates } from '../api';
import { Property, formatAddress } from '../types';
import { useExchangeRates } from '../hooks';
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from '../price-display';
import { Card, CardFooter, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { BarChart3, Edit, Eye, Heart, Phone, Trash2 } from 'lucide-react';
import { buildPropertyUrl } from '@/features/catalog/slugs';

const STATUS_LABELS: Record<Property['status'], string> = {
    draft: 'Черновик',
    moderation: 'Ожидает модерации',
    rejected: 'Отклонено',
    published: 'Опубликовано',
    archived: 'В архиве',
    deleted: 'Удалено',
};

const STATUS_VARIANT: Record<Property['status'], 'default' | 'secondary' | 'destructive' | 'outline'> = {
    draft: 'outline',
    moderation: 'secondary',
    rejected: 'destructive',
    published: 'default',
    archived: 'secondary',
    deleted: 'outline',
};

interface MyPropertyCardProps {
    property: Property;
    onDelete?: (id: number) => void;
}

export function MyPropertyCard({ property, onDelete }: MyPropertyCardProps) {
    const coverImage = property.images && property.images.length > 0
        ? (property.images[0].thumbnailUrl || property.images[0].url)
        : 'https://placehold.co/600x400?text=No+Image';

    const { data: rates } = useExchangeRates();
    const exchangeRates: ExchangeRates = useMemo(
        () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
        [rates],
    );
    const { primary: pricePrimary, secondary: priceSecondary } = formatPropertyPrices(property, exchangeRates);

    const views = property.views ?? 0;
    const phoneViews = property.phoneViews ?? 0;
    const favoritesCount = property.favoritesCount ?? 0;
    const propertyHref = buildPropertyUrl(property.type, property.id);

    return (
        <Card className="overflow-hidden flex flex-col h-full group">
            <div className="relative aspect-video overflow-hidden">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                    src={coverImage}
                    alt={property.title}
                    className="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300"
                />
                <Badge className="absolute top-2 right-2" variant={STATUS_VARIANT[property.status]}>
                    {STATUS_LABELS[property.status]}
                </Badge>
                <Badge variant="secondary" className="absolute top-2 left-2 uppercase">
                    {property.dealType}
                </Badge>
            </div>

            <CardHeader className="p-4 pb-2 flex-grow">
                <div className="space-y-0.5">
                    <h3 className="text-lg font-bold text-primary truncate">
                        {pricePrimary}
                    </h3>
                    <p className="text-xs text-muted-foreground truncate">{priceSecondary}</p>
                </div>
                <Link href={propertyHref} className="hover:underline">
                    <h4 className="font-semibold truncate text-base">{property.title}</h4>
                </Link>
                <p className="text-sm text-muted-foreground truncate">
                    {formatAddress(property.address)}
                </p>
                <div className="mt-3 flex items-center gap-3 text-xs text-muted-foreground">
                    <span className="inline-flex items-center gap-1">
                        <Eye className="h-3.5 w-3.5" />
                        {views}
                    </span>
                    <span className="inline-flex items-center gap-1">
                        <Phone className="h-3.5 w-3.5" />
                        {phoneViews}
                    </span>
                    <span className="inline-flex items-center gap-1">
                        <Heart className="h-3.5 w-3.5" />
                        {favoritesCount}
                    </span>
                </div>
            </CardHeader>

            <CardFooter className="p-4 pt-0 gap-2 mt-auto">
                <Button asChild variant="outline" size="sm" className="flex-1">
                    <Link href={`/kabinet/redaktirovat/${property.id}/`}>
                        <Edit className="h-4 w-4 mr-1" />
                        Edit
                    </Link>
                </Button>
                <Button asChild variant="secondary" size="sm" className="flex-1">
                    <Link href={propertyHref}>
                        <Eye className="h-4 w-4 mr-1" />
                        View
                    </Link>
                </Button>
                <Button asChild variant="outline" size="sm" className="flex-1">
                    <Link href={`/kabinet/statistika/${property.id}/`}>
                        <BarChart3 className="h-4 w-4 mr-1" />
                        Stats
                    </Link>
                </Button>
                {onDelete && (
                    <Button
                        variant="destructive"
                        size="icon"
                        className="h-8 w-8"
                        onClick={() => onDelete(property.id)}
                    >
                        <Trash2 className="h-4 w-4" />
                    </Button>
                )}
            </CardFooter>
        </Card>
    );
}
