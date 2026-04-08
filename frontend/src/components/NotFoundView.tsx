"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { motion } from "framer-motion";
import { Home, Search, BookOpen, Phone } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

const quickLinks = [
  { href: "/", icon: Home, label: "Главная страница" },
  { href: "/kvartiry/", icon: Search, label: "Каталог объявлений" },
  { href: "/stati/", icon: BookOpen, label: "Статьи и гиды" },
  { href: "/kontakty", icon: Phone, label: "Контакты" },
] as const;

interface NotFoundViewProps {
  className?: string;
}

export default function NotFoundView({ className }: NotFoundViewProps) {
  const pathname = usePathname();

  return (
    <div
      className={cn(
        "flex min-h-[calc(100svh-11rem)] flex-col items-center justify-center bg-gradient-to-br from-background via-muted/30 to-background py-20",
        className,
      )}
    >
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
          className="text-center max-w-2xl mx-auto"
        >
          <motion.div
            initial={{ scale: 0.8, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ delay: 0.2, duration: 0.5 }}
            className="mb-8"
          >
            <h1 className="text-9xl font-bold text-primary mb-4">404</h1>
            <div className="h-1 w-32 bg-primary mx-auto rounded-full mb-6" />
          </motion.div>

          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.4, duration: 0.5 }}
          >
            <h2 className="text-3xl md:text-4xl font-bold mb-4">Страница не найдена</h2>
            <p className="text-lg text-muted-foreground mb-8">
              К сожалению, запрашиваемая страница не существует или была перемещена. Попробуйте
              вернуться на главную или воспользуйтесь навигацией ниже.
            </p>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.6, duration: 0.5 }}
            className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8"
          >
            {quickLinks.map((link, index) => {
              const Icon = link.icon;
              return (
                <motion.div
                  key={link.href}
                  initial={{ opacity: 0, x: -20 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: 0.7 + index * 0.1, duration: 0.4 }}
                >
                  <Button
                    variant="outline"
                    className="w-full h-auto py-4 px-6 flex items-center justify-start gap-3 hover:bg-primary hover:text-primary-foreground transition-all duration-300"
                    asChild
                  >
                    <Link href={link.href}>
                      <Icon className="h-5 w-5" />
                      <span className="text-base">{link.label}</span>
                    </Link>
                  </Button>
                </motion.div>
              );
            })}
          </motion.div>

          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 1, duration: 0.5 }}
          >
            <p className="text-sm text-muted-foreground">
              Неверный URL: <code className="bg-muted px-2 py-1 rounded">{pathname}</code>
            </p>
          </motion.div>
        </motion.div>
      </div>
    </div>
  );
}
