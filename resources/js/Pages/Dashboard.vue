<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import { Bell, ClipboardCheck, Clock } from '@lucide/vue'
import { computed } from 'vue'

const page = usePage()
const ui = computed(() => page.props.translations?.ui || {})
</script>

<template>
    <Head title="Dashboard" />
    <div class="space-y-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-foreground">Nice! We have a lot of orders 😄</h1>
            <p class="text-sm text-muted-foreground font-medium">{{ new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- New Orders Card -->
            <div class="bg-primary p-6 rounded-[20px] text-white shadow-lg shadow-primary/20 relative overflow-hidden group">
                <div class="flex justify-between items-start relative z-10">
                    <div>
                        <p class="text-sm font-medium opacity-90">New Orders</p>
                        <h3 class="text-5xl font-bold mt-4">16</h3>
                        <p class="text-[10px] mt-4 opacity-80 font-medium">* Updated every new order</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center">
                        <Bell class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Total Orders Card -->
            <div class="bg-white p-6 rounded-[20px] border border-border/50 shadow-sm relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Total Orders</p>
                        <h3 class="text-5xl font-bold mt-4 text-foreground">86</h3>
                        <p class="text-[10px] mt-4 font-bold text-[#30C55D]">+2,5% <span class="text-muted-foreground font-medium">than usual</span></p>
                    </div>
                    <div class="w-12 h-12 bg-teal-secondary/10 rounded-xl flex items-center justify-center text-teal-primary">
                        <ClipboardCheck class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Waiting List Card -->
            <div class="bg-white p-6 rounded-[20px] border border-border/50 shadow-sm relative overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Waiting List</p>
                        <h3 class="text-5xl font-bold mt-4 text-foreground">9</h3>
                        <p class="text-[10px] mt-4 font-bold text-[#30C55D]">+3,2% <span class="text-muted-foreground font-medium">than usual</span></p>
                    </div>
                    <div class="w-12 h-12 bg-mustard/10 rounded-xl flex items-center justify-center text-mustard">
                        <Clock class="w-6 h-6" />
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card class="lg:col-span-2">
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Order List</h3>
                        <div class="flex gap-2">
                            <div class="h-8 w-8 bg-muted rounded-lg"></div>
                        </div>
                    </div>
                </template>
                <div class="h-64 flex items-center justify-center text-muted-foreground italic">
                    Order list content matches screenshot structure...
                </div>
            </Card>

            <Card>
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold">Popular Dishes</h3>
                        <button class="text-xs font-bold text-primary">View All</button>
                    </div>
                </template>
                <div class="space-y-4">
                    <div v-for="i in 4" :key="i" class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-muted rounded-lg"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold truncate">Menu Item {{ i }}</p>
                            <p class="text-[10px] text-muted-foreground">Orders: {{ 20 - i }}</p>
                        </div>
                        <div class="text-xs font-bold text-muted-foreground">0{{ i }}</div>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>

