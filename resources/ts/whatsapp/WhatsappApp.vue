<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import type { PropType } from 'vue';
import AdminShell from '../shared/AdminShell.vue';
import type { AdminNavItem, CurrentUser } from '../shared/types';
import { useWhatsappStore } from './stores/whatsapp';

const ACTIVE_CONVERSATIONS_POLL_MS = 5000;
const ACTIVE_MESSAGES_POLL_MS = 3000;
const IDLE_POLL_MS = 15000;

const props = defineProps({
    conversationsEndpoint: {
        type: String,
        required: true,
    },
    botBuilderPageEndpoint: {
        type: String,
        required: true,
    },
    logoutEndpoint: {
        type: String,
        required: true,
    },
    currentUser: {
        type: Object as PropType<CurrentUser>,
        required: true,
    },
});

const whatsappStore = useWhatsappStore();

const summaryTotal = computed(() => new Intl.NumberFormat('ru-RU').format(whatsappStore.summary.total_conversations));
const summaryManager = computed(() => new Intl.NumberFormat('ru-RU').format(whatsappStore.summary.manager_conversations));
const summaryBot = computed(() => new Intl.NumberFormat('ru-RU').format(whatsappStore.summary.bot_conversations));
const selectedConversation = computed(() => whatsappStore.selectedConversation);
const navItems = computed<AdminNavItem[]>(() => [
    { key: 'orders', label: 'Заказы', href: '/order-list', active: false },
    { key: 'customers', label: 'Покупатели', href: '/customer-list', active: false },
    { key: 'remains', label: 'Остатки', href: '/product-remains', active: false },
    { key: 'whatsapp', label: 'WhatsApp диалоги', href: '/whatsapp', active: true },
    { key: 'whatsapp-bot-builder', label: 'Сценарий WhatsApp', href: props.botBuilderPageEndpoint, active: false },
]);

let conversationsPollTimer: number | null = null;
let messagesPollTimer: number | null = null;

function onPerPageChange(event: Event): void {
    const target = event.target as HTMLSelectElement | null;
    if (!target) {
        return;
    }

    whatsappStore.changePerPage(props.conversationsEndpoint, Number(target.value));
}

function clearPollingTimers(): void {
    if (conversationsPollTimer !== null) {
        window.clearInterval(conversationsPollTimer);
        conversationsPollTimer = null;
    }

    if (messagesPollTimer !== null) {
        window.clearInterval(messagesPollTimer);
        messagesPollTimer = null;
    }
}

function setupPolling(): void {
    clearPollingTimers();

    const isVisible = document.visibilityState === 'visible';
    const conversationsDelay = isVisible ? ACTIVE_CONVERSATIONS_POLL_MS : IDLE_POLL_MS;
    const messagesDelay = isVisible ? ACTIVE_MESSAGES_POLL_MS : IDLE_POLL_MS;

    conversationsPollTimer = window.setInterval(() => {
        void whatsappStore.fetchConversations(props.conversationsEndpoint, whatsappStore.currentPage, {
            silent: true,
            includeMessages: false,
        });
    }, conversationsDelay);

    messagesPollTimer = window.setInterval(() => {
        if (whatsappStore.selectedConversationId === null) {
            return;
        }

        void whatsappStore.fetchMessages(
            props.conversationsEndpoint,
            whatsappStore.selectedConversationId,
            { silent: true }
        );
    }, messagesDelay);
}

function handleVisibilityChange(): void {
    setupPolling();
}

onMounted(() => {
    void whatsappStore.fetchConversations(props.conversationsEndpoint);
    setupPolling();
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    clearPollingTimers();
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <AdminShell
        title="WhatsApp"
        subtitle="Диалоги покупателей, ручные ответы менеджера и контроль того, на каком шаге сейчас находится бот."
        :current-user="currentUser"
        :logout-endpoint="logoutEndpoint"
        :nav-items="navItems"
    >
        <template #summary>
            <section class="orders-summary">
                <article class="orders-summary-card">
                    <span>Всего диалогов</span>
                    <strong>{{ summaryTotal }}</strong>
                </article>
                <article class="orders-summary-card">
                    <span>У менеджера</span>
                    <strong>{{ summaryManager }}</strong>
                </article>
                <article class="orders-summary-card">
                    <span>У бота</span>
                    <strong>{{ summaryBot }}</strong>
                </article>
            </section>
        </template>

        <section class="orders-panel">
            <div class="orders-toolbar">
                <div class="orders-toolbar-main">
                    <input
                        v-model="whatsappStore.filters.query"
                        type="search"
                        class="orders-search"
                        placeholder="Поиск по имени, телефону, статусу"
                        @keyup.enter="whatsappStore.applyFilters(conversationsEndpoint)"
                    >

                    <select v-model="whatsappStore.filters.status" class="orders-page-size">
                        <option value="">Все статусы</option>
                        <option value="bot">Бот</option>
                        <option value="manager">Менеджер</option>
                        <option value="closed">Закрыт</option>
                    </select>
                </div>

                <div class="orders-toolbar-actions">
                    <select class="orders-page-size" :value="whatsappStore.perPage" @change="onPerPageChange">
                        <option :value="10">10 / стр</option>
                        <option :value="25">25 / стр</option>
                        <option :value="50">50 / стр</option>
                        <option :value="100">100 / стр</option>
                        <option :value="500">500 / стр</option>
                    </select>

                    <button type="button" class="orders-primary-button" @click="whatsappStore.applyFilters(conversationsEndpoint)">
                        Применить
                    </button>
                    <button type="button" class="orders-secondary-button" @click="whatsappStore.resetFilters(conversationsEndpoint)">
                        Сбросить
                    </button>
                </div>
            </div>

            <div v-if="whatsappStore.error" class="orders-error">
                {{ whatsappStore.error }}
            </div>

            <div class="whatsapp-layout">
                <aside class="whatsapp-sidebar">
                    <button
                        v-for="conversation in whatsappStore.conversations"
                        :key="conversation.id"
                        type="button"
                        class="whatsapp-conversation-item"
                        :class="{ 'is-active': whatsappStore.selectedConversationId === conversation.id }"
                        @click="whatsappStore.fetchMessages(conversationsEndpoint, conversation.id)"
                    >
                        <strong>{{ conversation.contact.name || conversation.contact.phone }}</strong>
                        <span>{{ conversation.contact.phone }}</span>
                        <span>Статус: {{ conversation.status }}</span>
                        <span v-if="conversation.current_step_slug">Шаг: {{ conversation.current_step_slug }}</span>
                    </button>
                </aside>

                <div class="whatsapp-chat">
                    <div v-if="!selectedConversation" class="orders-state">
                        Выбери диалог слева.
                    </div>

                    <template v-else>
                        <div class="whatsapp-chat-header">
                            <div>
                                <strong>{{ selectedConversation?.contact.name || selectedConversation?.contact.phone }}</strong>
                                <p>{{ selectedConversation?.contact.chat_id }}</p>
                            </div>

                            <div class="orders-toolbar-actions">
                                <button type="button" class="orders-secondary-button" @click="whatsappStore.switchMode(conversationsEndpoint, 'bot')">
                                    В бота
                                </button>
                                <button type="button" class="orders-primary-button" @click="whatsappStore.switchMode(conversationsEndpoint, 'manager')">
                                    К менеджеру
                                </button>
                            </div>
                        </div>

                        <div v-if="whatsappStore.messagesLoading" class="orders-state">
                            Загружаю сообщения...
                        </div>

                        <div v-else class="whatsapp-messages">
                            <div
                                v-for="message in whatsappStore.messages"
                                :key="message.id"
                                class="whatsapp-message"
                                :class="message.direction === 'outgoing' ? 'is-outgoing' : 'is-incoming'"
                            >
                                <div class="whatsapp-message-body">{{ message.body || '[пустое сообщение]' }}</div>
                                <div class="whatsapp-message-meta">
                                    {{ message.direction }} · {{ message.sent_at || message.created_at }}
                                </div>
                            </div>
                        </div>

                        <div class="whatsapp-composer">
                            <textarea
                                v-model="whatsappStore.outgoingMessage"
                                class="editable-textarea"
                                rows="3"
                                placeholder="Ответ менеджера..."
                                @keydown.enter="whatsappStore.sendMessage(conversationsEndpoint)"
                            />

                            <button
                                type="button"
                                class="orders-primary-button"
                                :disabled="whatsappStore.sending"
                                @click="whatsappStore.sendMessage(conversationsEndpoint)"
                            >
                                Отправить
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="orders-pagination">
                <p class="orders-pagination-info">
                    Показано
                    <strong>{{ whatsappStore.from ?? 0 }}</strong>
                    -
                    <strong>{{ whatsappStore.to ?? 0 }}</strong>
                    из
                    <strong>{{ whatsappStore.total }}</strong>
                </p>

                <div class="orders-pagination-controls">
                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="whatsappStore.loading || whatsappStore.currentPage <= 1"
                        @click="whatsappStore.goToPage(conversationsEndpoint, whatsappStore.currentPage - 1)"
                    >
                        Назад
                    </button>
                    <span class="orders-pagination-page">
                        Страница {{ whatsappStore.currentPage }} / {{ whatsappStore.lastPage }}
                    </span>
                    <button
                        type="button"
                        class="orders-secondary-button"
                        :disabled="whatsappStore.loading || whatsappStore.currentPage >= whatsappStore.lastPage"
                        @click="whatsappStore.goToPage(conversationsEndpoint, whatsappStore.currentPage + 1)"
                    >
                        Вперед
                    </button>
                </div>
            </div>
        </section>
    </AdminShell>
</template>
