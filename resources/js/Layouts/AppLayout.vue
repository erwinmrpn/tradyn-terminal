<script setup lang="ts">
defineProps<{
  title: string
}>()
</script>

<template>
  <div class="min-h-screen bg-black text-white flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-zinc-950 p-6 hidden md:block">
      <h2 class="text-xl font-bold mb-6">Tradyne</h2>
      <nav class="space-y-3 text-gray-400">
        <a href="/dashboard" class="block hover:text-white">Dashboard</a>
        <a href="#" class="block hover:text-white">Trades</a>
        <a href="#" class="block hover:text-white">Analytics</a>
      </nav>
    </aside>

    <!-- Main -->
    <main class="flex-1 p-8">
      <h1 class="text-xl font-semibold mb-6">{{ title }}</h1>
      <slot />
    </main>
  </div>
</template>
