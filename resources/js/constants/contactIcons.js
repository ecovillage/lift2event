import { Mail, MessageSquare, Phone } from '@lucide/vue';
import { siSignal, siTelegram, siWhatsapp } from 'simple-icons';

// Messenger icons use the actual brand marks (recognisability); email/SMS/call are generic channels.
export const contactIcons = {
    email:    { kind: 'lucide', component: Mail },
    signal:   { kind: 'brand', icon: siSignal },
    telegram: { kind: 'brand', icon: siTelegram },
    whatsapp: { kind: 'brand', icon: siWhatsapp },
    sms:      { kind: 'lucide', component: MessageSquare },
    call:     { kind: 'lucide', component: Phone },
};
