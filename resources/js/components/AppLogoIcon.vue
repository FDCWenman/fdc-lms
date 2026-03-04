<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 200 200"
        :class="className"
        v-bind="$attrs"
    >
        <!-- 40°C Logo Design -->
        <!-- Number 4 (Orange) -->
        <path
            d="M 35 40 L 35 100 L 70 100 L 70 150 L 85 150 L 85 100 L 95 100 L 95 85 L 85 85 L 85 40 L 70 40 L 70 85 L 50 85 L 50 55 Z"
            fill="#FF8C00"
        />
        <!-- Degree symbol (Red) -->
        <circle cx="115" cy="50" r="12" fill="none" stroke="#DC143C" stroke-width="4" />
        <!-- Letter C (Red) -->
        <path
            d="M 145 80 Q 125 80 115 95 Q 105 110 105 130 Q 105 150 115 165 Q 125 180 145 180 L 150 180 L 150 165 L 145 165 Q 132 165 125 155 Q 120 145 120 130 Q 120 115 125 105 Q 132 95 145 95 L 150 95 L 150 80 Z"
            fill="#DC143C"
        />
    </svg>
</template>
