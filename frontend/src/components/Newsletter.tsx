"use client";

import { motion } from "framer-motion";
import { Mail, ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useState } from "react";

const Newsletter = () => {
  const [email, setEmail] = useState("");

  return (
    <section className="py-20 bg-gradient-dark relative overflow-hidden">
      {/* Decorative elements */}
      <div className="absolute top-0 left-1/4 w-96 h-96 bg-primary/5 rounded-full blur-3xl" />
      <div className="absolute bottom-0 right-1/4 w-64 h-64 bg-primary/8 rounded-full blur-3xl" />

      <div className="container mx-auto px-4 relative">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="max-w-xl mx-auto text-center"
        >
          <div className="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-6">
            <Mail className="w-5 h-5 text-primary" />
          </div>
          <h2 className="text-3xl font-bold text-dark-fg font-display mb-3">
            Будьте в курсе
          </h2>
          <p className="text-dark-fg/50 mb-8">
            Получайте лучшие предложения и аналитику рынка недвижимости прямо на вашу почту.
          </p>

          <div className="flex gap-2 max-w-md mx-auto">
            <div className="flex-1 flex items-center bg-dark-card rounded-xl px-4 border border-dark-card hover:border-primary/30 transition-colors">
              <Mail className="w-4 h-4 text-dark-muted flex-shrink-0 mr-3" />
              <input
                type="email"
                placeholder="Например: name@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="bg-transparent text-dark-fg placeholder:text-dark-muted text-sm w-full outline-none py-3"
              />
            </div>
            <Button className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity rounded-xl px-5 border-0">
              <ArrowRight className="w-4 h-4" />
            </Button>
          </div>

          <p className="text-xs text-dark-fg/30 mt-4">
            Без спама. Отписаться можно в любой момент.
          </p>
        </motion.div>
      </div>
    </section>
  );
};

export default Newsletter;
