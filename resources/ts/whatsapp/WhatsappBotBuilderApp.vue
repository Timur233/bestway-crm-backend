<script setup lang="ts">
import { computed, onMounted, reactive, watch } from 'vue';
import type { PropType } from 'vue';
import AdminShell from '../shared/AdminShell.vue';
import type { AdminNavItem, CurrentUser } from '../shared/types';
import WhatsappBotTreeNode from './WhatsappBotTreeNode.vue';
import type { WhatsappBotOption, WhatsappBotStepItem, WhatsappBotTreeNodeItem } from './types';
import { useWhatsappStore } from './stores/whatsapp';

type BotStepForm = {
    slug: string;
    title: string;
    reply_text: string;
    trigger_keywords_text: string;
    fallback_step_slug: string;
    is_entry: boolean;
    transfer_to_manager: boolean;
    is_active: boolean;
    sort_order: number;
    options: Array<{
        keywords_text: string;
        next_step_slug: string;
    }>;
};

const props = defineProps({
    whatsappPageEndpoint: {
        type: String,
        required: true,
    },
    botStepsEndpoint: {
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

const stepForm = reactive<BotStepForm>({
    slug: '',
    title: '',
    reply_text: '',
    trigger_keywords_text: '',
    fallback_step_slug: '',
    is_entry: false,
    transfer_to_manager: false,
    is_active: true,
    sort_order: 0,
    options: [],
});
const isCreatingStep = reactive({ value: false });

const botSteps = computed(() => whatsappStore.botSteps);
const selectedBotStep = computed(() => whatsappStore.selectedBotStep);
const stepSlugOptions = computed(() => whatsappStore.botSteps.map((step) => ({
    value: step.slug,
    label: `${step.title} (${step.slug})`,
})));
const entryStepHint = computed(() => {
    const entryStep = whatsappStore.botSteps.find((step) => step.is_entry);

    return entryStep ? `${entryStep.title} (${entryStep.slug})` : 'Не выбран';
});
const navItems = computed<AdminNavItem[]>(() => [
    { key: 'orders', label: 'Заказы', href: '/order-list', active: false },
    { key: 'customers', label: 'Покупатели', href: '/customer-list', active: false },
    { key: 'remains', label: 'Остатки', href: '/product-remains', active: false },
    { key: 'whatsapp', label: 'WhatsApp диалоги', href: props.whatsappPageEndpoint, active: false },
    { key: 'whatsapp-bot-builder', label: 'Сценарий WhatsApp', href: '/whatsapp/bot-builder', active: true },
]);

function splitLines(value: string): string[] {
    return value
        .split('\n')
        .map((item) => item.trim())
        .filter((item) => item !== '');
}

function fillStepForm(step: WhatsappBotStepItem | null): void {
    stepForm.slug = step?.slug ?? '';
    stepForm.title = step?.title ?? '';
    stepForm.reply_text = step?.reply_text ?? '';
    stepForm.trigger_keywords_text = (step?.trigger_keywords ?? []).join('\n');
    stepForm.fallback_step_slug = step?.fallback_step_slug ?? '';
    stepForm.is_entry = step?.is_entry ?? false;
    stepForm.transfer_to_manager = step?.transfer_to_manager ?? false;
    stepForm.is_active = step?.is_active ?? true;
    stepForm.sort_order = step?.sort_order ?? 0;
    stepForm.options = (step?.options ?? []).map((option: WhatsappBotOption) => ({
        keywords_text: (option.keywords ?? []).join('\n'),
        next_step_slug: option.next_step_slug ?? '',
    }));
}

function startCreatingStep(): void {
    isCreatingStep.value = true;
    whatsappStore.selectedBotStepId = null;
    fillStepForm(null);
    stepForm.is_active = true;
    stepForm.sort_order = botSteps.value.length > 0
        ? Math.max(...botSteps.value.map((step: WhatsappBotStepItem) => step.sort_order)) + 10
        : 10;
}

function cancelCreatingStep(): void {
    isCreatingStep.value = false;

    if (botSteps.value.length > 0 && whatsappStore.selectedBotStepId === null) {
        whatsappStore.selectedBotStepId = botSteps.value[0].id;
    }
}

function selectStep(stepId: number): void {
    isCreatingStep.value = false;
    whatsappStore.selectBotStep(stepId);
}

function addOption(): void {
    stepForm.options.push({
        keywords_text: '',
        next_step_slug: '',
    });
}

function removeOption(index: number): void {
    stepForm.options.splice(index, 1);
}

function parseOptions(): WhatsappBotOption[] {
    return stepForm.options.map((option) => ({
        keywords: splitLines(option.keywords_text),
        next_step_slug: option.next_step_slug || null,
    }));
}

async function saveSelectedBotStep(): Promise<void> {
    const payload = {
        slug: stepForm.slug.trim(),
        title: stepForm.title.trim(),
        reply_text: stepForm.reply_text,
        trigger_keywords: splitLines(stepForm.trigger_keywords_text),
        fallback_step_slug: stepForm.fallback_step_slug || null,
        is_entry: stepForm.is_entry,
        transfer_to_manager: stepForm.transfer_to_manager,
        is_active: stepForm.is_active,
        sort_order: Number(stepForm.sort_order) || 0,
        options: parseOptions(),
    };

    if (isCreatingStep.value) {
        await whatsappStore.createBotStep(props.botStepsEndpoint, payload);
        isCreatingStep.value = false;

        return;
    }

    if (!selectedBotStep.value) {
        return;
    }

    await whatsappStore.saveBotStep(props.botStepsEndpoint, selectedBotStep.value.id, payload);
}

function collectReferencedStepSlugs(steps: WhatsappBotStepItem[]): Set<string> {
    const referenced = new Set<string>();

    steps.forEach((step) => {
        (step.options ?? []).forEach((option) => {
            if (option.next_step_slug) {
                referenced.add(option.next_step_slug);
            }
        });
    });

    return referenced;
}

function buildTreeNode(
    step: WhatsappBotStepItem,
    childrenMap: Map<string, WhatsappBotTreeNodeItem[]>,
    labelFromParent: string | null,
    trail: Set<string>
): WhatsappBotTreeNodeItem {
    const nextTrail = new Set(trail);
    nextTrail.add(step.slug);

    const children = (childrenMap.get(step.slug) ?? [])
        .filter((child) => !nextTrail.has(child.step.slug))
        .map((child) => buildTreeNode(child.step, childrenMap, child.labelFromParent, nextTrail));

    return {
        step,
        labelFromParent,
        children,
    };
}

const botStepTree = computed<WhatsappBotTreeNodeItem[]>(() => {
    const steps = botSteps.value;
    const stepMap = new Map(steps.map((step) => [step.slug, step]));
    const childrenMap = new Map<string, WhatsappBotTreeNodeItem[]>();

    steps.forEach((step) => {
        (step.options ?? []).forEach((option) => {
            if (!option.next_step_slug) {
                return;
            }

            const childStep = stepMap.get(option.next_step_slug);
            if (!childStep) {
                return;
            }

            const label = option.keywords?.length
                ? option.keywords.join(', ')
                : 'Переход';

            const current = childrenMap.get(step.slug) ?? [];
            current.push({
                step: childStep,
                labelFromParent: label,
                children: [],
            });
            childrenMap.set(step.slug, current);
        });
    });

    const referenced = collectReferencedStepSlugs(steps);
    const roots = steps.filter((step) => step.is_entry || !referenced.has(step.slug));

    return roots.map((root) => buildTreeNode(root, childrenMap, null, new Set()));
});

const detachedBotSteps = computed(() => {
    const slugsInTree = new Set<string>();
    const queue = [...botStepTree.value];

    while (queue.length > 0) {
        const current = queue.shift();

        if (!current) {
            continue;
        }

        slugsInTree.add(current.step.slug);
        queue.push(...current.children);
    }

    return botSteps.value.filter((step) => !slugsInTree.has(step.slug));
});

watch(selectedBotStep, (step) => {
    if (isCreatingStep.value) {
        return;
    }

    fillStepForm(step);
}, { immediate: true });

onMounted(() => {
    void whatsappStore.fetchBotSteps(props.botStepsEndpoint);
});
</script>

<template>
    <AdminShell
        title="Сценарий WhatsApp"
        subtitle="Отдельный экран для настройки ответов бота и просмотра переходов между шагами в виде иерархии."
        :current-user="currentUser"
        :logout-endpoint="logoutEndpoint"
        :nav-items="navItems"
    >
        <template #summary>
            <section class="orders-summary">
                <article class="orders-summary-card">
                    <span>Всего шагов</span>
                    <strong>{{ botSteps.length }}</strong>
                </article>
                <article class="orders-summary-card">
                    <span>Точка входа</span>
                    <strong>{{ entryStepHint }}</strong>
                </article>
                <article class="orders-summary-card">
                    <span>Активных шагов</span>
                    <strong>{{ botSteps.filter((step) => step.is_active).length }}</strong>
                </article>
            </section>
        </template>

        <section class="orders-panel">
            <div class="wa-bot-editor-header">
                <div>
                    <strong>Сценарий WhatsApp-бота</strong>
                    <p>Иерархия строится по переходам между шагами. Можно править тексты, ключевые слова и связи прямо из интерфейса.</p>
                </div>
                <div class="orders-toolbar-actions">
                    <button type="button" class="orders-primary-button" @click="startCreatingStep">
                        Новый шаг
                    </button>
                    <button
                        v-if="isCreatingStep.value"
                        type="button"
                        class="orders-secondary-button"
                        @click="cancelCreatingStep"
                    >
                        Отмена
                    </button>
                </div>
            </div>

            <div v-if="whatsappStore.error" class="orders-error">
                {{ whatsappStore.error }}
            </div>

            <div v-if="whatsappStore.botStepsLoading" class="orders-state">
                Загружаю шаги сценария...
            </div>

            <div v-else class="wa-bot-layout">
                <aside class="wa-bot-tree-panel">
                    <div v-if="botStepTree.length === 0" class="orders-state">
                        Шаги бота пока не найдены.
                    </div>

                    <template v-else>
                        <WhatsappBotTreeNode
                            v-for="node in botStepTree"
                            :key="node.step.id"
                            :node="node"
                            :selected-step-id="whatsappStore.selectedBotStepId"
                            @select="selectStep"
                        />

                        <div v-if="detachedBotSteps.length > 0" class="wa-bot-detached">
                            <span>Вне дерева</span>
                            <button
                                v-for="step in detachedBotSteps"
                                :key="step.id"
                                type="button"
                                class="wa-bot-tree-button"
                                :class="{ 'is-active': whatsappStore.selectedBotStepId === step.id }"
                                @click="selectStep(step.id)"
                            >
                                <strong>{{ step.title }}</strong>
                                <span>{{ step.slug }}</span>
                            </button>
                        </div>
                    </template>
                </aside>

                <div class="wa-bot-editor-panel">
                    <div v-if="!selectedBotStep && !isCreatingStep.value" class="orders-state">
                        Выбери шаг слева, чтобы настроить ответ и переходы.
                    </div>

                    <template v-else-if="selectedBotStep || isCreatingStep.value">
                        <div class="editable-card">
                            <div class="editable-card-header">
                                <strong>{{ isCreatingStep.value ? 'Новый шаг' : selectedBotStep?.title }}</strong>
                                <span>{{ isCreatingStep.value ? 'Создание нового шага сценария' : `Slug: ${selectedBotStep?.slug}` }}</span>
                            </div>

                            <div class="wa-bot-form-grid">
                                <label class="wa-bot-field">
                                    <span>Slug</span>
                                    <input
                                        v-model="stepForm.slug"
                                        type="text"
                                        class="orders-search"
                                        :disabled="!isCreatingStep.value"
                                        placeholder="Например: delivery_city"
                                    >
                                </label>

                                <label class="wa-bot-field">
                                    <span>Название шага</span>
                                    <input v-model="stepForm.title" type="text" class="orders-search">
                                </label>

                                <label class="wa-bot-field">
                                    <span>Порядок</span>
                                    <input v-model.number="stepForm.sort_order" type="number" min="0" class="orders-search">
                                </label>

                                <label class="wa-bot-field wa-bot-field-full">
                                    <span>Текст ответа</span>
                                    <textarea v-model="stepForm.reply_text" class="editable-textarea" rows="7" />
                                </label>

                                <label class="wa-bot-field">
                                    <span>Ключевые слова запуска</span>
                                    <textarea
                                        v-model="stepForm.trigger_keywords_text"
                                        class="editable-textarea compact-textarea"
                                        rows="6"
                                        placeholder="Одно ключевое слово на строку"
                                    />
                                </label>

                                <label class="wa-bot-field">
                                    <span>Fallback шаг</span>
                                    <select v-model="stepForm.fallback_step_slug" class="orders-page-size">
                                        <option value="">Без fallback</option>
                                        <option
                                            v-for="option in stepSlugOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </label>
                            </div>

                            <div class="wa-bot-flags">
                                <label class="wa-bot-toggle">
                                    <input v-model="stepForm.is_entry" type="checkbox">
                                    <span>Точка входа</span>
                                </label>
                                <label class="wa-bot-toggle">
                                    <input v-model="stepForm.transfer_to_manager" type="checkbox">
                                    <span>Переводить на менеджера</span>
                                </label>
                                <label class="wa-bot-toggle">
                                    <input v-model="stepForm.is_active" type="checkbox">
                                    <span>Активен</span>
                                </label>
                            </div>
                        </div>

                        <div class="editable-card">
                            <div class="editable-card-header">
                                <strong>Переходы из шага</strong>
                                <span>Каждый вариант содержит набор ключевых слов и следующий шаг.</span>
                            </div>

                            <div v-if="stepForm.options.length === 0" class="orders-state">
                                У этого шага пока нет вариантов перехода.
                            </div>

                            <div v-for="(option, index) in stepForm.options" :key="index" class="wa-bot-option-row">
                                <label class="wa-bot-field">
                                    <span>Ключевые слова</span>
                                    <textarea
                                        v-model="option.keywords_text"
                                        class="editable-textarea compact-textarea"
                                        rows="4"
                                        placeholder="Например: 1, каталог, бассейн"
                                    />
                                </label>

                                <label class="wa-bot-field">
                                    <span>Следующий шаг</span>
                                    <select v-model="option.next_step_slug" class="orders-page-size">
                                        <option value="">Выбери шаг</option>
                                        <option
                                            v-for="stepOption in stepSlugOptions"
                                            :key="stepOption.value"
                                            :value="stepOption.value"
                                        >
                                            {{ stepOption.label }}
                                        </option>
                                    </select>
                                </label>

                                <button
                                    type="button"
                                    class="orders-secondary-button"
                                    @click="removeOption(index)"
                                >
                                    Удалить
                                </button>
                            </div>

                            <div class="orders-toolbar-actions">
                                <button type="button" class="orders-secondary-button" @click="addOption">
                                    Добавить переход
                                </button>
                                <button
                                    type="button"
                                    class="orders-primary-button"
                                    :disabled="whatsappStore.botStepsSaving"
                                    @click="saveSelectedBotStep"
                                >
                                    Сохранить шаг
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>
    </AdminShell>
</template>
