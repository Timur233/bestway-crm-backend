import { defineStore } from 'pinia';
import axios from 'axios';
import type { WhatsappConversationItem, WhatsappMessageItem, WhatsappMeta, WhatsappSummary } from '../types';

type ConversationsResponse = {
    data: WhatsappConversationItem[];
    meta: WhatsappMeta;
    summary: WhatsappSummary;
};

type MessagesResponse = {
    data: WhatsappMessageItem[];
};

type FetchOptions = {
    silent?: boolean;
    includeMessages?: boolean;
};

type WhatsappState = {
    conversations: WhatsappConversationItem[];
    messages: WhatsappMessageItem[];
    loading: boolean;
    messagesLoading: boolean;
    sending: boolean;
    error: string | null;
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
    filters: {
        query: string;
        status: string;
    };
    summary: WhatsappSummary;
    selectedConversationId: number | null;
    outgoingMessage: string;
};

export const useWhatsappStore = defineStore('whatsapp', {
    state: (): WhatsappState => ({
        conversations: [],
        messages: [],
        loading: false,
        messagesLoading: false,
        sending: false,
        error: null,
        currentPage: 1,
        lastPage: 1,
        perPage: 25,
        total: 0,
        from: null,
        to: null,
        filters: {
            query: '',
            status: '',
        },
        summary: {
            total_conversations: 0,
            manager_conversations: 0,
            bot_conversations: 0,
        },
        selectedConversationId: null,
        outgoingMessage: '',
    }),

    getters: {
        selectedConversation(state): WhatsappConversationItem | null {
            return state.conversations.find((item) => item.id === state.selectedConversationId) ?? null;
        },
    },

    actions: {
        conversationsChanged(nextItems: WhatsappConversationItem[]): boolean {
            if (this.conversations.length !== nextItems.length) {
                return true;
            }

            return nextItems.some((item, index) => {
                const current = this.conversations[index];

                return !current
                    || current.id !== item.id
                    || current.status !== item.status
                    || current.current_step_slug !== item.current_step_slug
                    || current.last_message_at !== item.last_message_at
                    || current.contact.name !== item.contact.name
                    || current.contact.phone !== item.contact.phone;
            });
        },

        messagesChanged(nextItems: WhatsappMessageItem[]): boolean {
            if (this.messages.length !== nextItems.length) {
                return true;
            }

            return nextItems.some((item, index) => {
                const current = this.messages[index];

                return !current
                    || current.id !== item.id
                    || current.direction !== item.direction
                    || current.body !== item.body
                    || current.sent_at !== item.sent_at;
            });
        },

        async fetchConversations(endpoint: string, page?: number, options: FetchOptions = {}): Promise<void> {
            if (this.loading && !options.silent) {
                return;
            }

            const { silent = false, includeMessages = true } = options;

            if (!silent) {
                this.loading = true;
            }
            if (!silent) {
                this.error = null;
            }
            const targetPage = page ?? this.currentPage;

            try {
                const response = await axios.get<ConversationsResponse>(endpoint, {
                    params: {
                        page: targetPage,
                        per_page: this.perPage,
                        query: this.filters.query || undefined,
                        status: this.filters.status || undefined,
                    },
                });

                const nextConversations = response.data.data ?? [];

                if (!silent || this.conversationsChanged(nextConversations)) {
                    this.conversations = nextConversations;
                }
                this.currentPage = response.data.meta?.current_page ?? targetPage;
                this.lastPage = response.data.meta?.last_page ?? 1;
                this.perPage = response.data.meta?.per_page ?? this.perPage;
                this.total = response.data.meta?.total ?? 0;
                this.from = response.data.meta?.from ?? null;
                this.to = response.data.meta?.to ?? null;
                this.summary = response.data.summary ?? this.summary;

                if (this.selectedConversationId === null && this.conversations.length > 0) {
                    this.selectedConversationId = this.conversations[0].id;
                }

                if (includeMessages && this.selectedConversationId !== null) {
                    await this.fetchMessages(endpoint, this.selectedConversationId, { silent });
                }
            } catch (error: unknown) {
                if (!silent) {
                    this.error = this.resolveError(error, 'Не удалось загрузить диалоги WhatsApp.');
                }
            } finally {
                if (!silent) {
                    this.loading = false;
                }
            }
        },

        async fetchMessages(endpoint: string, conversationId: number, options: FetchOptions = {}): Promise<void> {
            if (this.messagesLoading && !options.silent) {
                return;
            }

            const { silent = false } = options;

            if (!silent) {
                this.messagesLoading = true;
            }
            this.selectedConversationId = conversationId;

            try {
                const response = await axios.get<MessagesResponse>(`${endpoint}/${conversationId}/messages`);
                const nextMessages = (response.data.data ?? []).reverse();

                if (!silent || this.messagesChanged(nextMessages)) {
                    this.messages = nextMessages;
                }
            } catch (error: unknown) {
                if (!silent) {
                    this.error = this.resolveError(error, 'Не удалось загрузить сообщения.');
                }
            } finally {
                if (!silent) {
                    this.messagesLoading = false;
                }
            }
        },

        async applyFilters(endpoint: string): Promise<void> {
            await this.fetchConversations(endpoint, 1);
        },

        async resetFilters(endpoint: string): Promise<void> {
            this.filters = {
                query: '',
                status: '',
            };

            await this.fetchConversations(endpoint, 1);
        },

        async goToPage(endpoint: string, page: number): Promise<void> {
            if (page < 1 || page > this.lastPage || page === this.currentPage) {
                return;
            }

            await this.fetchConversations(endpoint, page);
        },

        async changePerPage(endpoint: string, perPage: number): Promise<void> {
            this.perPage = perPage;
            await this.fetchConversations(endpoint, 1);
        },

        async sendMessage(endpoint: string): Promise<void> {
            if (this.selectedConversationId === null || this.outgoingMessage.trim() === '') {
                return;
            }

            this.sending = true;
            this.error = null;

            try {
                await axios.post(`${endpoint}/${this.selectedConversationId}/send-message`, {
                    message: this.outgoingMessage,
                });

                this.outgoingMessage = '';
                await this.fetchConversations(endpoint, this.currentPage);
            } catch (error: unknown) {
                this.error = this.resolveError(error, 'Не удалось отправить сообщение.');
            } finally {
                this.sending = false;
            }
        },

        async switchMode(endpoint: string, mode: 'bot' | 'manager'): Promise<void> {
            if (this.selectedConversationId === null) {
                return;
            }

            try {
                await axios.post(`${endpoint}/${this.selectedConversationId}/switch-mode`, {
                    mode,
                });

                await this.fetchConversations(endpoint, this.currentPage);
            } catch (error: unknown) {
                this.error = this.resolveError(error, 'Не удалось переключить режим.');
            }
        },

        resolveError(error: unknown, fallback: string): string {
            if (axios.isAxiosError(error)) {
                return (error.response?.data as { message?: string } | undefined)?.message
                    ?? error.message
                    ?? fallback;
            }

            if (error instanceof Error) {
                return error.message;
            }

            return fallback;
        },
    },
});
