<template>
<div>
<header class="bg-blue-800 text-white p-4">
    <nav class="container mx-auto">
    <div class="flex items-center justify-between">
        <a href="/" class="text-lg font-bold">Home</a>
        <a href="/login" class="text-lg font-bold">Login</a>
    </div>
    </nav>
</header>

<main class="container mx-auto py-8">
    <slot></slot>
</main>
</div>
</template>