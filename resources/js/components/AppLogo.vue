<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-white dark:bg-gray-800"
    >
        <AppLogoIcon class="size-7" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold"
            >FDCLeave</span
        >
        <span class="text-xs text-muted-foreground"
            >Leave Management</span
        >
    </div>
</template>
