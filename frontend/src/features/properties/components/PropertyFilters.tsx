'use client';

import { useRouter, useSearchParams } from 'next/navigation';
import { useState, useMemo } from 'react';
import { motion } from 'framer-motion';
import { Search, MapPin, Home, CircleDollarSign, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { buildCatalogUrl } from '@/features/catalog/slugs';

const cities = [
  { value: "all", label: "Все города" },
  { value: "minsk", label: "Минск" },
  { value: "brest", label: "Брест" },
  { value: "grodno", label: "Гродно" },
  { value: "gomel", label: "Гомель" },
  { value: "vitebsk", label: "Витебск" },
  { value: "mogilev", label: "Могилёв" },
];

const propertyTypes = [
  { value: "all", label: "Все типы" },
  { value: "apartment", label: "Квартира" },
  { value: "house", label: "Дом" },
  { value: "dacha", label: "Дача" },
];

export function PropertyFilters() {
    const router = useRouter();
    const searchParams = useSearchParams();

    const [type, setType] = useState(searchParams.get('type') || 'all');
    const [city, setCity] = useState(searchParams.get('city') || 'all');
    const [minPrice, setMinPrice] = useState(searchParams.get('minPrice') || '');
    const [maxPrice, setMaxPrice] = useState(searchParams.get('maxPrice') || '');

    const visiblePropertyTypes = useMemo(() => propertyTypes, []);

    const applyFilters = () => {
        const params = new URLSearchParams(searchParams.toString());
        params.set('page', '1');
        params.set('dealType', 'daily');

        if (type && type !== 'all') params.set('type', type);
        else params.delete('type');

        if (city && city !== 'all') params.set('city', city);
        else params.delete('city');

        if (minPrice) params.set('minPrice', minPrice);
        else params.delete('minPrice');

        if (maxPrice) params.set('maxPrice', maxPrice);
        else params.delete('maxPrice');

        const baseUrl = buildCatalogUrl({
            propertyType: type !== 'all' ? type : undefined,
        });
        router.push(`${baseUrl}?${params.toString()}`);
    };

    const clearFilters = () => {
        setType('all');
        setCity('all');
        setMinPrice('');
        setMaxPrice('');
        router.push(buildCatalogUrl());
    };

    return (
        <motion.div 
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            className="bg-card rounded-3xl border border-border/40 p-6 shadow-xl shadow-foreground/5 sticky top-24 overflow-hidden group"
        >
            <div className="flex items-center justify-between mb-6 relative z-10">
                <h3 className="text-xl font-display font-bold flex items-center gap-2">
                    Фильтры
                </h3>
                <button 
                    onClick={clearFilters}
                    className="text-xs font-bold uppercase tracking-widest text-muted-foreground hover:text-primary transition-all flex items-center gap-1.5"
                >
                    <X className="w-3.5 h-3.5" />
                    Сбросить
                </button>
            </div>

            <div className="space-y-6 relative z-10">
                <p className="text-xs text-muted-foreground px-1">
                    Посуточная аренда по всей Беларуси
                </p>

                {/* City */}
                <div className="space-y-2">
                    <Label className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground/80 flex items-center gap-2 px-1">
                        <MapPin className="w-3.5 h-3.5 text-primary" />
                        Город
                    </Label>
                    <Select value={city} onValueChange={setCity}>
                        <SelectTrigger className="bg-muted/40 border-none rounded-2xl h-11 px-4 hover:bg-muted/60 transition-colors">
                            <SelectValue placeholder="Выберите город" />
                        </SelectTrigger>
                        <SelectContent className="rounded-2xl border-border/50 shadow-2xl font-body">
                            {cities.map((c) => (
                                <SelectItem key={c.value} value={c.value} className="rounded-xl my-0.5">{c.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Property Type */}
                <div className="space-y-2">
                    <Label className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground/80 flex items-center gap-2 px-1">
                        <Home className="w-3.5 h-3.5 text-primary" />
                        Тип жилья
                    </Label>
                    <Select value={type} onValueChange={setType}>
                        <SelectTrigger className="bg-muted/40 border-none rounded-2xl h-11 px-4 hover:bg-muted/60 transition-colors">
                            <SelectValue placeholder="Любой тип" />
                        </SelectTrigger>
                        <SelectContent className="rounded-2xl border-border/50 shadow-2xl font-body">
                            {visiblePropertyTypes.map((t) => (
                                <SelectItem key={t.value} value={t.value} className="rounded-xl my-0.5">{t.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Price Range */}
                <div className="space-y-2">
                    <Label className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground/80 flex items-center gap-2 px-1">
                        <CircleDollarSign className="w-3.5 h-3.5 text-primary" />
                        Цена за сутки
                    </Label>
                    <div className="grid grid-cols-2 gap-2">
                        <div className="relative group">
                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground/40 font-black tracking-widest pointer-events-none uppercase">ОТ</span>
                            <Input
                                type="number"
                                placeholder="От"
                                value={minPrice}
                                onChange={(e) => setMinPrice(e.target.value)}
                                className="bg-muted/40 border-none rounded-xl pl-9 h-11 text-base sm:text-sm pt-2 hover:bg-muted/60 focus-visible:ring-primary/20 appearance-none font-medium"
                            />
                        </div>
                        <div className="relative group">
                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground/40 font-black tracking-widest pointer-events-none uppercase">ДО</span>
                            <Input
                                type="number"
                                placeholder="До"
                                value={maxPrice}
                                onChange={(e) => setMaxPrice(e.target.value)}
                                className="bg-muted/40 border-none rounded-xl pl-9 h-11 text-base sm:text-sm pt-2 hover:bg-muted/60 focus-visible:ring-primary/20 appearance-none font-medium"
                            />
                        </div>
                    </div>
                </div>

                <div className="pt-4 border-t border-border/50">
                    <Button 
                        onClick={applyFilters} 
                        className="w-full h-12 rounded-2xl bg-primary text-primary-foreground shadow-lg hover:opacity-95 transform active:scale-[0.98] transition-all font-bold group border-0 text-sm"
                    >
                        <Search className="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
                        Применить
                    </Button>
                </div>
            </div>
        </motion.div>
    );
}
