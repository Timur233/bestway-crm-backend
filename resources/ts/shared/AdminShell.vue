<script setup lang="ts">
import type { PropType } from 'vue';
import type { AdminNavItem, CurrentUser } from './types';

defineProps({
    kicker: {
        type: String,
        default: 'Bestway CRM',
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        required: true,
    },
    currentUser: {
        type: Object as PropType<CurrentUser>,
        required: true,
    },
    logoutEndpoint: {
        type: String,
        required: true,
    },
    navItems: {
        type: Array as PropType<AdminNavItem[]>,
        required: true,
    },
});

const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
</script>

<template>
    <main class="orders-page">
        <header class="admin-header">
            <div class="admin-brand">
                <p class="orders-kicker">{{ kicker }}</p>
                <strong>Админ-панель</strong>
            </div>

            <nav class="admin-nav">
                <a
                    v-for="item in navItems"
                    :key="item.key"
                    :href="item.href"
                    class="admin-nav-link"
                    :class="{ 'is-active': item.active }"
                >
                    {{ item.label }}
                </a>
            </nav>

            <div class="orders-user-card admin-user-card">
                <p class="orders-user-label">Администратор</p>
                <strong>{{ currentUser.name }}</strong>
                <span>{{ currentUser.email }}</span>

                <form :action="logoutEndpoint" method="POST" class="orders-logout">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button type="submit" class="orders-secondary-button">Выйти</button>
                </form>
            </div>
        </header>

        <section class="orders-hero admin-hero">
            <div class="orders-hero-copy">
                <p class="orders-kicker">{{ kicker }}</p>
                <h1>{{ title }}</h1>
                <p class="orders-subtitle">{{ subtitle }}</p>
            </div>
        </section>

        <slot name="summary" />
        <slot />
    </main>
</template>
