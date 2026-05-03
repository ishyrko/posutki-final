import { Property, formatAddress } from '../types';
import { normalizeCurrency } from '../price-display';
import { formatPropertyDealHeading } from '../property-deal-heading';
import { PriceInByn } from '@/components/BynCurrency';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { BedDouble, Maximize, MapPin, Calendar, Bath, Info } from 'lucide-react';

interface PropertyInfoProps {
    property: Property;
}

export function PropertyInfo({ property }: PropertyInfoProps) {
    const listingCurrency = normalizeCurrency(property.price.currency);
    const formattedPriceForeign = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: property.price.currency,
        maximumFractionDigits: 0,
    }).format(property.price.amount);

    const formattedDate = new Date(property.createdAt).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    return (
        <div className="space-y-10">
            {/* Title and Price Wrapper */}
            <div className="flex flex-col md:flex-row justify-between items-start gap-6">
                <div className="space-y-3">
                    <div className="flex gap-2">
                        <Badge variant="secondary" className="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary border-primary/20">
                            {formatPropertyDealHeading(property.dealType, property.type)}
                        </Badge>
                    </div>
                    <h1 className="text-4xl font-display font-bold text-foreground">
                        {property.title}
                    </h1>
                    <div className="flex items-center text-muted-foreground">
                        <MapPin className="h-4 w-4 mr-1.5 text-primary/60" />
                        <span className="text-sm font-medium">
                            {formatAddress(property.address)}
                        </span>
                    </div>
                </div>

                <div className="bg-muted/50 p-6 rounded-2xl border border-border/50 min-w-[200px] text-center md:text-right">
                    <div className="text-sm text-muted-foreground mb-1 font-medium">Цена</div>
                    <div className="text-3xl font-display font-bold text-foreground">
                        {listingCurrency === 'BYN' ? (
                            <PriceInByn amount={property.price.amount} className="font-display" />
                        ) : (
                            formattedPriceForeign
                        )}
                    </div>
                    {property.dealType === 'rent' && <div className="text-xs text-muted-foreground mt-1">в месяц</div>}
                </div>
            </div>

            {/* Premium Specs Grid */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {[
                    property.type === 'land'
                        ? { icon: Maximize, label: "Площадь участка", value: property.specifications.landArea ? `${property.specifications.landArea} сот.` : '-' }
                        : { icon: Maximize, label: "Площадь общая", value: `${property.specifications.area} м²` },
                    ...(property.type === 'house' || property.type === 'dacha'
                        ? [{ icon: MapPin, label: "Площадь участка", value: property.specifications.landArea ? `${property.specifications.landArea} сот.` : '-' }]
                        : []),
                    { icon: BedDouble, label: "Комнаты", value: property.specifications.rooms },
                    { icon: Bath, label: "Ванные", value: property.specifications.bathrooms ?? 1 },
                    { icon: Calendar, label: "Опубликовано", value: formattedDate },
                ].map((spec, i) => (
                    <Card key={i} className="p-5 border-border/50 bg-card hover:bg-muted/30 transition-colors shadow-sm rounded-2xl">
                        <div className="flex flex-col items-center text-center gap-3">
                            <div className="p-2 rounded-xl bg-primary/5 text-primary">
                                <spec.icon className="h-5 w-5" />
                            </div>
                            <div>
                                <div className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">{spec.label}</div>
                                <div className="text-lg font-bold text-foreground">{spec.value}</div>
                            </div>
                        </div>
                    </Card>
                ))}
            </div>

            {/* Description */}
            <div className="space-y-4">
                <div className="flex items-center gap-2 text-foreground">
                    <Info className="h-5 w-5 text-primary" />
                    <h3 className="text-xl font-display font-bold">Описание объекта</h3>
                </div>
                <div className="bg-card rounded-2xl border border-border/50 p-6 shadow-sm">
                    <div className="prose prose-sm max-w-none text-muted-foreground leading-relaxed whitespace-pre-line">
                        {property.description}
                    </div>
                </div>
            </div>

            {/* Features / Details could be added here */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div className="space-y-4">
                    <h4 className="font-display font-bold text-lg">Характеристики</h4>
                    <ul className="space-y-3">
                        {[
                            { label: "Тип", value: property.type === 'apartment' ? 'Квартира' : 'Дом' },
                            { label: "Состояние", value: "Отличное" },
                            {
                                label: "Этаж",
                                value:
                                    property.specifications.floor && property.specifications.totalFloors
                                        ? `${property.specifications.floor} из ${property.specifications.totalFloors}`
                                        : "-",
                            },
                            { label: "Год постройки", value: property.specifications.yearBuilt ?? "-" },
                        ].map((item, i) => (
                            <li key={i} className="flex justify-between text-sm py-2 border-b border-border/50 last:border-0">
                                <span className="text-muted-foreground">{item.label}</span>
                                <span className="font-bold text-foreground">{item.value}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </div>
    );
}
