<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/Components/ui/Card.vue'
import { Activity, Search, Trash2 } from 'lucide-vue-next'
import { ref, watch } from 'vue'

const props = defineProps({
    logs: Object,
    roles: Array,
    filters: Object,
})

const selectedRole = ref(props.filters?.role || '')

watch(selectedRole, (value) => {
    router.get(route('system.activity-log'), { role: value }, {
        preserveState: true,
        replace: true
    })
})

const formatChanges = (properties) => {
    if (!properties) return ''
    
    // For spatie/laravel-activitylog format
    if (properties.attributes && properties.old) {
        const changes = []
        for (const key in properties.attributes) {
            if (properties.old[key] !== properties.attributes[key]) {
                changes.push(`changed ${key} from "${properties.old[key]}" to "${properties.attributes[key]}"`)
            }
        }
        if (changes.length > 0) return changes.join(', ')
    }
    
    // For custom route payloads
    if (properties.payload && Object.keys(properties.payload).length > 0) {
        return `Payload: ${JSON.stringify(properties.payload)}`
    }
    
    // Fallback just stringify
    if (Object.keys(properties).length > 0) {
        return JSON.stringify(properties)
    }
    
    return ''
}
</script>

<template>
    <AppLayout>
        <Head title="Activity Log" />

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Activity Log</h2>
                    <p class="text-muted-foreground text-sm">Monitor user actions across the system.</p>
                </div>
                <div class="w-full sm:w-64">
                    <select v-model="selectedRole" class="w-full h-10 px-3 bg-card border border-secondary rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all">
                        <option value="">All Roles</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                </div>
            </div>

            <Card class="bg-card/50 backdrop-blur-sm border-none shadow-sm" classContent="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-muted-foreground uppercase bg-secondary/30">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">User</th>
                                    <th class="px-6 py-4 font-semibold">Action</th>
                                    <th class="px-6 py-4 font-semibold">Details</th>
                                    <th class="px-6 py-4 font-semibold">IP Address</th>
                                    <th class="px-6 py-4 font-semibold">Location</th>
                                    <th class="px-6 py-4 font-semibold">Date & Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary/50">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-secondary/20 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold shrink-0">
                                                {{ log.user ? log.user.charAt(0).toUpperCase() : 'G' }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-medium text-foreground truncate">{{ log.user }}</div>
                                                <div class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider truncate">{{ log.role || 'System' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-foreground font-medium max-w-[200px] truncate" :title="log.action">{{ log.action }}</div>
                                        <div class="text-muted-foreground text-xs">{{ log.subject }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-muted-foreground text-xs max-w-xs break-words whitespace-normal line-clamp-3" :title="formatChanges(log.properties)">
                                            {{ formatChanges(log.properties) || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-muted-foreground">
                                        {{ log.ip_address || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-muted-foreground">
                                        <div v-if="log.latitude && log.longitude">
                                            <a :href="`https://www.google.com/maps/search/?api=1&query=${log.latitude},${log.longitude}`" target="_blank" class="hover:underline text-primary">
                                                {{ log.latitude }}, {{ log.longitude }}
                                            </a>
                                        </div>
                                        <div v-else>
                                            -
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-muted-foreground">
                                        {{ log.created_at }}
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-muted-foreground">
                                        No activity logs found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between px-6 py-4 bg-secondary/20 border-t border-secondary/50" v-if="logs.links && logs.links.length > 3">
                        <div class="text-sm text-muted-foreground">
                            Showing <span class="font-medium text-foreground">{{ logs.from }}</span> to <span class="font-medium text-foreground">{{ logs.to }}</span> of <span class="font-medium text-foreground">{{ logs.total }}</span> results
                        </div>
                        <div class="flex items-center gap-1">
                            <template v-for="(link, index) in logs.links" :key="index">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url)"
                                    class="px-3 py-1 text-sm rounded-md transition-colors"
                                    :class="link.active ? 'bg-primary text-primary-foreground font-medium' : 'text-muted-foreground hover:bg-secondary hover:text-foreground'"
                                    v-html="link.label"
                                />
                                <span v-else class="px-3 py-1 text-sm text-muted-foreground/50" v-html="link.label" />
                            </template>
                        </div>
                    </div>
            </Card>
        </div>
    </AppLayout>
</template>
