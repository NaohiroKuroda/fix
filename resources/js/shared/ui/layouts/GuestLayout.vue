<script setup lang="ts">
// 未認証ページ（ログイン等）用のシンプルな全画面レイアウト。
</script>

<template>
    <main class="h-screen w-full flex flex-col bg-background text-foreground overflow-hidden">
        <slot />
    </main>
</template>
