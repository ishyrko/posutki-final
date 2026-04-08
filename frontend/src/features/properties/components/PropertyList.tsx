'use client';

import { Property } from '../types';
import { PropertyCard } from './PropertyCard';
import { motion } from 'framer-motion';

interface PropertyListProps {
    properties: Property[];
    isLoading?: boolean;
}

const container = {
  hidden: { opacity: 0 },
  show: {
    opacity: 1,
    transition: {
      staggerChildren: 0.1
    }
  }
};

const item = {
  hidden: { opacity: 0, y: 20 },
  show: { opacity: 1, y: 0 }
};

export function PropertyList({ properties, isLoading }: PropertyListProps) {
    if (isLoading) {
        return (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {[...Array(6)].map((_, i) => (
                    <div key={i} className="h-[400px] w-full bg-muted/40 animate-pulse rounded-3xl" />
                ))}
            </div>
        );
    }

    if (properties.length === 0) {
        return (
            <motion.div 
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                className="text-center py-20 bg-card rounded-3xl border border-dashed border-border/60"
            >
                <p className="text-muted-foreground text-lg font-display">Объекты не найдены.</p>
                <p className="text-muted-foreground/60 text-sm mt-1">Попробуйте изменить параметры фильтрации.</p>
            </motion.div>
        );
    }

    return (
        <motion.div 
            variants={container}
            initial="hidden"
            animate="show"
            className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
            {properties.map((property, idx) => (
                <motion.div key={property.id} variants={item}>
                    <PropertyCard property={property} index={idx} />
                </motion.div>
            ))}
        </motion.div>
    );
}
