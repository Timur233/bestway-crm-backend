<script setup lang="ts">
import { onMounted, ref } from 'vue';
import QRCode from 'qrcode';

const props = defineProps<{
    orderCode: string;
    contactUrl: string;
}>();

const qrImageDataUrl = ref('');
const error = ref('');

onMounted(async () => {
    if (props.contactUrl.trim() === '') {
        error.value = 'Ссылка для QR не найдена.';
        return;
    }

    try {
        qrImageDataUrl.value = await QRCode.toDataURL(props.contactUrl, {
            width: 360,
            margin: 1,
        });
    } catch (err: unknown) {
        error.value = err instanceof Error ? err.message : 'Не удалось сгенерировать QR.';
    }
});
</script>

<template>
    <main class="orders-qr-page">
        <section class="orders-qr-page-card">
            <p class="orders-focus-kicker">QR для связи с клиентом</p>
            <h1>{{ orderCode }}</h1>

            <div v-if="error" class="orders-error">
                {{ error }}
            </div>
            <div v-else-if="!qrImageDataUrl" class="orders-state">
                Генерирую QR...
            </div>
            <div v-else class="orders-qr-page-body">
                <img :src="qrImageDataUrl" :alt="`QR для заказа ${orderCode}`">
                <a
                    :href="contactUrl"
                    class="orders-primary-button orders-link-button"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Связаться с клиентом
                </a>
            </div>
        </section>
    </main>
</template>
