'use client';

import { motion } from 'framer-motion';
import { Heart, Loader2 } from 'lucide-react';
import { useFavorites } from '@/features/properties/hooks';
import { PropertyCard } from '@/features/properties/components/PropertyCard';

export default function FavoritesPage() {
    const { data, isLoading } = useFavorites();
    const properties = data?.data ?? [];
    const total = properties.length;

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
        >
            <h1 className="font-display text-2xl font-bold text-foreground mb-1">Избранное</h1>
            <p className="text-sm text-muted-foreground mb-6">
                {isLoading ? 'Загрузка...' : `${total} сохранённых объявлений`}
            </p>

            {isLoading ? (
                <div className="flex justify-center py-16">
                    <Loader2 className="w-8 h-8 animate-spin text-muted-foreground" />
                </div>
            ) : properties.length === 0 ? (
                <div className="text-center py-16 bg-card rounded-xl shadow-card">
                    <Heart className="w-10 h-10 text-muted-foreground/40 mx-auto mb-4" />
                    <p className="text-muted-foreground">
                        У вас пока нет избранных объявлений
                    </p>
                    <p className="text-sm text-muted-foreground/60 mt-1">
                        Нажмите ♥ на понравившемся объявлении, чтобы сохранить его
                    </p>
                </div>
            ) : (
                <div className="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-6">
                    {properties.map((property, index) => (
                        <PropertyCard key={property.id} property={property} index={index} />
                    ))}
                </div>
            )}
        </motion.div>
    );
}
