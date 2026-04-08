import { Button } from '@/components/ui/button';
import { User, Phone, Mail, ShieldCheck, MessageSquare } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface AgentContactProps {
    agentName?: string;
    agentPhone?: string;
    agentEmail?: string;
}

export function AgentContact({ 
    agentName = 'Ivan Ishyrko', 
    agentPhone = '+375 29 123 45 67',
    agentEmail = 'ishyrko@rnb.by' 
}: AgentContactProps) {
    return (
        <div className="bg-card rounded-2xl border border-border/50 p-6 shadow-sm sticky top-24 space-y-8">
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-display font-bold">Связаться с агентом</h3>
                <Badge variant="secondary" className="bg-green-500/10 text-green-600 border-none px-2 py-0 text-[10px] uppercase font-bold">
                    <ShieldCheck className="w-3 h-3 mr-1" />
                    Проверено
                </Badge>
            </div>

            <div className="flex items-center gap-4">
                <div className="relative">
                    <div className="h-16 w-16 rounded-2xl bg-gradient-primary p-0.5">
                        <div className="h-full w-full rounded-[14px] bg-card flex items-center justify-center overflow-hidden">
                             <User className="h-8 w-8 text-primary" />
                        </div>
                    </div>
                    <div className="absolute -bottom-1 -right-1 h-4 w-4 bg-green-500 border-2 border-card rounded-full" />
                </div>
                <div>
                    <h4 className="font-display font-bold text-lg">{agentName}</h4>
                    <p className="text-xs text-muted-foreground font-medium">Ведущий специалист</p>
                </div>
            </div>

            <div className="space-y-3">
                <Button className="w-full h-12 rounded-xl bg-primary hover:opacity-90 shadow-primary transition-all font-bold gap-2">
                    <Phone className="h-4 w-4" />
                    {agentPhone}
                </Button>
                
                <div className="grid grid-cols-2 gap-3">
                    <Button variant="outline" className="h-12 rounded-xl border-border/50 gap-2 font-medium hover:bg-muted transition-colors" asChild>
                        <a href={`mailto:${agentEmail}`}>
                            <Mail className="h-4 w-4" />
                            Email
                        </a>
                    </Button>
                    <Button variant="outline" className="h-12 rounded-xl border-border/50 gap-2 font-medium hover:bg-muted transition-colors">
                        <MessageSquare className="h-4 w-4" />
                        Чат
                    </Button>
                </div>
            </div>

            <div className="pt-6 border-t border-border/50">
                <p className="text-[11px] text-muted-foreground text-center leading-relaxed">
                    Нажимая кнопку «Связаться», вы соглашаетесь с правилами пользования сервисом и политикой конфиденциальности.
                </p>
            </div>
        </div>
    );
}
