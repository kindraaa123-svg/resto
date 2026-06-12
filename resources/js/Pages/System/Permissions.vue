<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import { ShieldCheck, Save, AlertCircle } from 'lucide-vue-next'
import { ref, onMounted } from 'vue'

const props = defineProps({
    roles: Array,
    permissions: Array,
})

const form = useForm({
    roles: []
})

// Initialize form data with current permissions
onMounted(() => {
    form.roles = props.roles.map(role => ({
        id: role.id,
        name: role.name,
        permissions: role.name === 'Superadmin' 
            ? props.permissions.map(p => p.name) 
            : role.permissions.map(p => p.name)
    }))
})

const savePermissions = () => {
    form.post(route('system.permissions.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: Show a toast notification if available
        }
    })
}

// Group permissions by their prefix (e.g., 'orders.view' -> 'orders')
const groupedPermissions = ref({})

onMounted(() => {
    const groups = {}
    props.permissions.forEach(permission => {
        const parts = permission.name.split('.')
        const groupName = parts[0]
        if (!groups[groupName]) {
            groups[groupName] = []
        }
        groups[groupName].push(permission)
    })
    groupedPermissions.value = groups
})

</script>

<template>
    <AppLayout>
        <Head title="Role Permissions" />

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Role Permissions</h2>
                    <p class="text-muted-foreground text-sm">Manage access control matrix for different roles.</p>
                </div>
                
                <Button @click="savePermissions" :disabled="form.processing" class="flex items-center gap-2">
                    <Save class="w-4 h-4" />
                    <span>{{ form.processing ? 'Saving...' : 'Save Permissions' }}</span>
                </Button>
            </div>

            <!-- Flash Message if any (handled via global layout if setup, else local) -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-green-50 text-green-700 rounded-lg flex items-center gap-3">
                <ShieldCheck class="w-5 h-5" />
                <span class="font-medium text-sm">{{ $page.props.flash.success }}</span>
            </div>

            <Card class="bg-card/50 backdrop-blur-sm border-none shadow-sm" classContent="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-muted-foreground uppercase bg-secondary/30 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-4 font-semibold w-1/4 bg-card/80 backdrop-blur-md">Permission \ Role</th>
                                <th v-for="role in form.roles" :key="role.id" class="px-4 py-4 font-semibold text-center min-w-[120px]">
                                    <div class="inline-flex items-center justify-center bg-primary/10 text-primary px-3 py-1 rounded-full">
                                        {{ role.name }}
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary/50">
                            <template v-for="(perms, groupName) in groupedPermissions" :key="groupName">
                                <!-- Group Header -->
                                <tr class="bg-secondary/10">
                                    <td :colspan="form.roles.length + 1" class="px-6 py-2 font-bold text-foreground capitalize">
                                        {{ groupName }}
                                    </td>
                                </tr>
                                <!-- Permissions -->
                                <tr v-for="permission in perms" :key="permission.id" class="hover:bg-secondary/20 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-muted-foreground/30"></div>
                                            <span class="font-medium text-muted-foreground">{{ permission.name }}</span>
                                        </div>
                                    </td>
                                    <td v-for="(role, roleIndex) in form.roles" :key="role.id" class="px-4 py-4 text-center">
                                        <label class="inline-flex items-center justify-center cursor-pointer group">
                                            <input 
                                                type="checkbox" 
                                                v-model="form.roles[roleIndex].permissions"
                                                :value="permission.name"
                                                class="w-5 h-5 rounded border-secondary text-primary focus:ring-primary/20 transition-all cursor-pointer"
                                                :disabled="role.name === 'Superadmin'"
                                            />
                                        </label>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </Card>

            <div class="flex items-start gap-2 p-4 bg-yellow-50 text-yellow-800 rounded-lg text-sm mt-4">
                <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
                <div>
                    <span class="font-bold">Note:</span> The 'Superadmin' role typically bypasses permission checks implicitly in the code, but you can still check these boxes for visibility.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
