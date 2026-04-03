export type WhatsappConversationItem = {
    id: number;
    contact_id: number;
    manager_id: number | null;
    status: 'bot' | 'manager' | 'closed';
    current_step_slug: string | null;
    unresolved_count: number;
    last_message_at: string | null;
    manager_requested_at: string | null;
    contact: {
        id: number;
        name: string | null;
        phone: string;
        chat_id: string;
    };
    manager?: {
        id: number;
        name: string;
    } | null;
};

export type WhatsappMessageItem = {
    id: number;
    conversation_id: number;
    contact_id: number;
    user_id: number | null;
    direction: 'incoming' | 'outgoing';
    message_type: string;
    body: string | null;
    sent_at: string | null;
    created_at: string;
};

export type WhatsappMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type WhatsappSummary = {
    total_conversations: number;
    manager_conversations: number;
    bot_conversations: number;
};
