'use client';

import { useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import {
  Calendar,
  CalendarCheck,
  CheckCircle,
  MessageCircle,
  Phone,
  Send,
} from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { TelegramIcon, ViberIcon, WhatsAppIcon } from "@/components/ContactMessengerIcons";
import { trackPhoneView } from "@/features/properties/api";
import { trackPropertyContactEvent, trackPropertyEngagementEvent } from "@/lib/gtag";
import { useSendMessage } from "@/features/messages/hooks";
import type { Property } from "@/features/properties/types";
import { telegramHref, viberChatHref, whatsAppHref } from "@/lib/contactLinks";
import { cn } from "@/lib/utils";

function initialsFromContactName(name: string): string {
  const trimmed = name.trim();
  if (!trimmed) return "?";
  const words = trimmed.split(/\s+/).filter(Boolean);
  if (words.length >= 2) {
    const first = words[0][0];
    const last = words[words.length - 1][0];
    return `${first}${last}`.toUpperCase();
  }
  return trimmed.slice(0, Math.min(2, trimmed.length)).toUpperCase();
}

/** Preview line before reveal: +375 XX ***-**-67 from stored phone digits. */
function maskContactPhone(phone: string): string {
  const digits = phone.replace(/\D/g, "");
  if (digits.length < 2) return "Телефон";
  const last2 = digits.slice(-2);
  if (digits.startsWith("375") && digits.length >= 11) {
    const op = digits.slice(3, 5);
    return `+375 ${op} ***-**-${last2}`;
  }
  if (digits.startsWith("80") && digits.length >= 11) {
    const op = digits.slice(2, 4);
    return `+375 ${op} ***-**-${last2}`;
  }
  if (digits.length <= 4) {
    return `***${last2}`;
  }
  return `***-**-${last2}`;
}

/** Build tel: href for dialing; aligns 80… Belarus numbers with +375 like maskContactPhone. */
function phoneToTelHref(phone: string): string {
  const digits = phone.replace(/\D/g, "");
  if (!digits) return "#";
  let d = digits;
  if (d.startsWith("80") && d.length >= 11) {
    d = `375${d.slice(2)}`;
  }
  return `tel:+${d}`;
}

type ContactPhoneEntry = {
  phone: string;
  hasViber: boolean;
  hasWhatsapp: boolean;
};

function getContactPhones(property: Property): ContactPhoneEntry[] {
  const fromApi = property.contact?.phones;
  if (fromApi && fromApi.length > 0) {
    return fromApi
      .map((p) => ({
        phone: p.phone?.trim() ?? "",
        hasViber: !!p.hasViber,
        hasWhatsapp: !!p.hasWhatsapp,
      }))
      .filter((p) => p.phone !== "");
  }
  const legacy = property.contact?.phone?.trim();
  if (legacy) {
    return [{ phone: legacy, hasViber: false, hasWhatsapp: false }];
  }
  return [];
}

export function getPropertySellerName(property: Property): string {
  return property.contact?.name?.trim() || "Продавец";
}

type PropertyOwnerContactPanelProps = {
  property: Property;
  isOwner: boolean;
  loggedIn: boolean;
  loginWithReturnHref: string;
  onOpenBooking: () => void;
  className?: string;
};

export function PropertyOwnerContactPanel({
  property,
  isOwner,
  loggedIn,
  loginWithReturnHref,
  onOpenBooking,
  className,
}: PropertyOwnerContactPanelProps) {
  const [phoneRevealed, setPhoneRevealed] = useState(false);
  const [messageOpen, setMessageOpen] = useState(false);
  const [messageText, setMessageText] = useState("");
  const [messageSent, setMessageSent] = useState(false);
  const sendMessageMutation = useSendMessage();

  const sellerName = getPropertySellerName(property);
  const sellerInitials = property.contact?.name?.trim()
    ? initialsFromContactName(property.contact.name)
    : "?";
  const contactPhones = getContactPhones(property);
  const primaryContactPhone = contactPhones[0]?.phone ?? "";
  const hasContactPhones = contactPhones.length > 0;
  const contactTelegram = property.contact?.telegram?.trim() ?? "";
  const canBookInquiry = property.contact?.hasEmail === true;
  const allowsMessagesAndInquiries = property.contact?.allowsMessagesAndInquiries !== false;
  const allowsGuestInquiries = property.contact?.allowsGuestInquiries !== false;
  const canSubmitBookingInquiry =
    canBookInquiry && allowsMessagesAndInquiries && (loggedIn || allowsGuestInquiries);

  return (
    <div className={cn(className)}>
      <div className="flex items-center gap-3 mb-5">
        <div className="w-14 h-14 rounded-xl bg-gradient-primary flex items-center justify-center text-primary-foreground font-bold text-lg">
          {sellerInitials}
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Контакт:</p>
          <p className="font-semibold text-foreground">{sellerName}</p>
        </div>
      </div>

      <p className="text-xs text-muted-foreground leading-relaxed mb-5">
        Для уточнения деталей свяжитесь с владельцем и сообщите, что вы нашли объявление на{" "}
        <span className="font-medium text-foreground">Посутки.by</span>.
      </p>

      <div className="space-y-2.5 mb-5">
        {!phoneRevealed ? (
          <Button
            className="w-full bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 border-0 h-11"
            disabled={!hasContactPhones}
            onClick={() => {
              if (!hasContactPhones) return;
              void trackPhoneView(property.id);
              trackPropertyContactEvent("show_phone", property);
              setPhoneRevealed(true);
            }}
          >
            <Phone className="w-4 h-4 mr-2" />
            {!hasContactPhones
              ? "Телефон не указан"
              : `${maskContactPhone(primaryContactPhone)} · Показать`}
          </Button>
        ) : (
          <>
            {contactPhones.map((entry, index) => {
              const showTelegram = index === 0 && !!contactTelegram;
              const showMessengers =
                entry.hasViber || entry.hasWhatsapp || showTelegram;

              return (
                <div key={`${entry.phone}-${index}`} className="flex gap-2">
                  <Button
                    className="min-w-0 flex-1 basis-0 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 border-0 h-11"
                    asChild
                  >
                    <a
                      href={phoneToTelHref(entry.phone)}
                      onClick={() => trackPropertyContactEvent("click_phone", property)}
                    >
                      <Phone className="w-4 h-4 shrink-0" />
                      <span className="truncate">{entry.phone}</span>
                    </a>
                  </Button>
                  <div className="flex w-[9.25rem] shrink-0 justify-start gap-2">
                    {showMessengers && (
                      <>
                        {entry.hasViber && (
                          <Button
                            variant="outline"
                            size="icon"
                            className="h-11 w-11 shrink-0 hover:bg-muted"
                            asChild
                          >
                            <a
                              href={viberChatHref(entry.phone)}
                              target="_blank"
                              rel="noopener noreferrer"
                              aria-label="Написать в Viber"
                              onClick={() => trackPropertyContactEvent("click_viber", property)}
                            >
                              <ViberIcon className="h-7 w-7" />
                            </a>
                          </Button>
                        )}
                        {entry.hasWhatsapp && (
                          <Button
                            variant="outline"
                            size="icon"
                            className="h-11 w-11 shrink-0 text-[#25D366] hover:text-[#25D366] hover:bg-[#25D366]/10"
                            asChild
                          >
                            <a
                              href={whatsAppHref(entry.phone)}
                              target="_blank"
                              rel="noopener noreferrer"
                              aria-label="Написать в WhatsApp"
                              onClick={() => trackPropertyContactEvent("click_whatsapp", property)}
                            >
                              <WhatsAppIcon className="!h-7 !w-7" />
                            </a>
                          </Button>
                        )}
                        {showTelegram && (
                          <Button
                            variant="outline"
                            size="icon"
                            className="h-11 w-11 shrink-0 text-[#229ED9] hover:text-[#229ED9] hover:bg-[#229ED9]/10"
                            asChild
                          >
                            <a
                              href={telegramHref(contactTelegram)}
                              target="_blank"
                              rel="noopener noreferrer"
                              aria-label="Написать в Telegram"
                              onClick={() => trackPropertyContactEvent("click_telegram", property)}
                            >
                              <TelegramIcon className="!h-7 !w-7" />
                            </a>
                          </Button>
                        )}
                      </>
                    )}
                  </div>
                </div>
              );
            })}
          </>
        )}
        {!isOwner && allowsMessagesAndInquiries && canSubmitBookingInquiry && (
          <Button
            variant="outline"
            className="w-full h-11"
            onClick={onOpenBooking}
          >
            <CalendarCheck className="w-4 h-4 mr-2" />
            Заявка на бронирование
          </Button>
        )}
        {!isOwner && allowsMessagesAndInquiries && canBookInquiry && !allowsGuestInquiries && !loggedIn && (
          <Button variant="outline" className="w-full h-11" asChild>
            <Link href={loginWithReturnHref}>
              <CalendarCheck className="w-4 h-4 mr-2" />
              Войти, чтобы оставить заявку
            </Link>
          </Button>
        )}
        {!isOwner && allowsMessagesAndInquiries && loggedIn && (
          <Button
            variant="outline"
            className="w-full h-11"
            onClick={() => { setMessageOpen(true); setMessageSent(false); }}
          >
            <MessageCircle className="w-4 h-4 mr-2" />
            Написать сообщение
          </Button>
        )}
        {!isOwner && allowsMessagesAndInquiries && !loggedIn && (
          <Button variant="outline" className="w-full h-11" asChild>
            <Link href={loginWithReturnHref}>
              <MessageCircle className="w-4 h-4 mr-2" />
              Войти, чтобы написать
            </Link>
          </Button>
        )}
      </div>

      <AnimatePresence>
        {!isOwner && allowsMessagesAndInquiries && loggedIn && messageOpen && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: "auto", opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            className="overflow-hidden mb-5"
          >
            {messageSent ? (
              <div className="flex flex-col items-center gap-2 py-4 text-center">
                <CheckCircle className="w-8 h-8 text-primary" />
                <p className="text-sm font-medium text-foreground">Сообщение отправлено!</p>
                <p className="text-xs text-muted-foreground">Продавец получит уведомление</p>
                <Button variant="ghost" size="sm" onClick={() => setMessageOpen(false)} className="mt-1 text-xs">Закрыть</Button>
              </div>
            ) : (
              <div className="space-y-3 pt-3 border-t border-border">
                <p className="text-sm font-medium text-foreground">Сообщение продавцу</p>
                <Textarea
                  placeholder="Например: Здравствуйте! Интересует ваш объект..."
                  value={messageText}
                  onChange={(e) => setMessageText(e.target.value)}
                  className="min-h-[80px] resize-none text-sm border border-border ring-0 ring-offset-0 focus-visible:ring-1 focus-visible:ring-primary focus-visible:ring-offset-0"
                  maxLength={1000}
                />
                <div className="flex items-center gap-2">
                  <Button
                    className="flex-1 bg-gradient-primary text-primary-foreground border-0 h-9"
                    disabled={!messageText.trim() || sendMessageMutation.isPending}
                    onClick={() => {
                      sendMessageMutation.mutate(
                        { text: messageText, propertyId: property.id },
                        {
                          onSuccess: () => {
                            trackPropertyEngagementEvent("send_owner_message", property);
                            setMessageSent(true);
                            setMessageText("");
                          },
                        }
                      );
                    }}
                  >
                    <Send className="w-3.5 h-3.5 mr-1.5" />
                    Отправить
                  </Button>
                  <Button variant="ghost" size="sm" onClick={() => setMessageOpen(false)} className="h-9">
                    Отмена
                  </Button>
                </div>
              </div>
            )}
          </motion.div>
        )}
      </AnimatePresence>

      <div className="pt-4 border-t border-border">
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
          <Calendar className="w-4 h-4" />
          <span>ID: {property.id}</span>
        </div>
      </div>
    </div>
  );
}
