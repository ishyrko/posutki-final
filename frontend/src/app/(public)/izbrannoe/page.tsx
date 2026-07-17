'use client';

import { motion } from 'framer-motion';
import { Heart } from 'lucide-react';
import { CatalogPropertyCard } from '@/features/properties/components/CatalogPropertyCard';
import { useFavoritesPage } from '@/features/properties/hooks';

export default function PublicFavoritesPage() {
    const { properties, isLoading, total } = useFavoritesPage();

    return (
        <div className="container py-6 md:py-8">
            <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.3 }}
            >
                <h1 className="font-display text-2xl font-bold text-foreground mb-1">Избранное</h1>
                <p className="text-sm text-muted-foreground mb-6">
                    {isLoading ? 'Загрузка...' : `Сохранённых объявлений: ${total}`}
                </p>

                {isLoading ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        {[...Array(6)].map((_, i) => (
                            <div key={i} className="h-[360px] bg-muted/50 animate-pulse rounded-xl" />
                        ))}
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
                    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        {properties.map((property, index) => (
                            <CatalogPropertyCard key={property.id} property={property} index={index} />
                        ))}
                    </div>
                )}
            </motion.div>
        </div>
    );
}
