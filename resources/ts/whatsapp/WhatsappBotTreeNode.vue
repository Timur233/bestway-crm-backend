<script setup lang="ts">
import type { PropType } from 'vue';
import type { WhatsappBotTreeNodeItem } from './types';

defineProps({
    node: {
        type: Object as PropType<WhatsappBotTreeNodeItem>,
        required: true,
    },
    selectedStepId: {
        type: Number as PropType<number | null>,
        default: null,
    },
});

const emit = defineEmits<{
    (event: 'select', stepId: number): void;
}>();
</script>

<template>
    <div class="wa-bot-tree-node">
        <button
            type="button"
            class="wa-bot-tree-button"
            :class="{ 'is-active': selectedStepId === node.step.id }"
            @click="emit('select', node.step.id)"
        >
            <span v-if="node.labelFromParent" class="wa-bot-tree-branch">{{ node.labelFromParent }}</span>
            <strong>{{ node.step.title }}</strong>
            <span>{{ node.step.slug }}</span>
        </button>

        <div v-if="node.children.length > 0" class="wa-bot-tree-children">
            <WhatsappBotTreeNode
                v-for="child in node.children"
                :key="`${node.step.id}-${child.step.id}-${child.labelFromParent ?? 'branch'}`"
                :node="child"
                :selected-step-id="selectedStepId"
                @select="emit('select', $event)"
            />
        </div>
    </div>
</template>
