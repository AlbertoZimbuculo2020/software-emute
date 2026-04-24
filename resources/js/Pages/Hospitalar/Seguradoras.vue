<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    ShieldCheck, Plus, Search, Edit3, Trash2, 
    X, CheckCircle, AlertCircle, Building2, 
    Phone, MapPin, Hash, Save, RotateCcw
} from 'lucide-vue-next';

const props = defineProps({
    seguradoras: Array,
    nextId: String
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const searchTerm = ref('');

const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => notification.value.show = false, 4000);
};

const form = useForm({
    Codigo: props.nextId,
    Nome: '',
    Contribuinte: '',
    Telefone: '',
    Cidade: '',
});

const filteredSeguradoras = computed(() => {
    if (!searchTerm.value) return props.seguradoras;
    const term = searchTerm.value.toLowerCase();
    return props.seguradoras.filter(s => 
        s.Nome.toLowerCase().includes(term) ||
        s.Codigo.toLowerCase().includes(term) ||
        s.Contribuente.toLowerCase().includes(term)
    );
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.Codigo = props.nextId;
    isModalOpen.value = true;
};

const openEditModal = (seg) => {
    isEditing.value = true;
    editingId.value = seg.Id;
    form.Codigo = seg.Codigo;
    form.Nome = seg.Nome;
    form.Contribuinte = seg.Contribuente;
    form.Telefone = seg.Telefone;
    form.Cidade = seg.Cidade;
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('hospitalar.seguradoras.update', editingId.value), {
            onSuccess: () => {
                showNotification('Seguradora atualizada com sucesso!');
                isModalOpen.value = false;
            }
        });
    } else {
        form.post(route('hospitalar.seguradoras.store'), {
            onSuccess: () => {
                showNotification('Seguradora cadastrada com sucesso!');
                isModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const deleteSeguradora = (id) => {
    if (!confirm('Deseja realmente remover esta seguradora?')) return;
    
    router.delete(route('hospitalar.seguradoras.destroy', id), {
        onSuccess: () => showNotification('Seguradora removida com sucesso!')
    });
};
</script>

<template>
    <Head title="Gestão de Seguradoras" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8">
            <div class="max-w-[1600px] mx-auto space-y-8">
                
                <!-- Header Moderno -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-200/60">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-4">
                            <div class="p-3 bg-blue-600 rounded-2xl text-white shadow-xl shadow-blue-200">
                                <ShieldCheck class="w-8 h-8" />
                            </div>
                            SEGURADORAS
                        </h1>
                        <p class="text-slate-500 text-sm font-medium mt-2 ml-1">Gerenciamento de convênios e parceiros institucionais</p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="relative hidden lg:block">
                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="searchTerm" placeholder="Pesquisar seguradora..." class="bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold text-slate-700 w-80 transition-all shadow-sm" />
                        </div>
                        <button @click="openCreateModal" class="flex items-center gap-3 px-8 py-4 bg-blue-600 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 active:scale-95 uppercase">
                            <Plus class="w-5 h-5" /> Registar Seguradora
                        </button>
                    </div>
                </div>

                <!-- Table Bento -->
                <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/40 border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-separate border-spacing-0">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Código</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Instituição / Seguradora</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Contribuinte (NIF)</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Contacto</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Cidade</th>
                                    <th class="px-8 py-6 text-right text-[11px] font-black text-slate-400 uppercase tracking-widest">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="seg in filteredSeguradoras" :key="seg.Id" class="group hover:bg-blue-50/30 transition-all duration-300">
                                    <td class="px-8 py-6">
                                        <span class="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black tracking-widest">{{ seg.Codigo }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-black text-xs">
                                                {{ seg.Nome.substring(0, 2).toUpperCase() }}
                                            </div>
                                            <span class="text-sm font-black text-slate-800 tracking-tight">{{ seg.Nome }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-sm font-bold text-slate-500">{{ seg.Contribuente }}</td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-2 text-sm font-bold text-slate-500">
                                            <Phone class="w-3.5 h-3.5 text-slate-300" /> {{ seg.Telefone || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-sm font-bold text-slate-500">{{ seg.Cidade || 'N/A' }}</td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex justify-end gap-3">
                                            <button @click="openEditModal(seg)" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 rounded-xl transition-all shadow-sm">
                                                <Edit3 class="w-4 h-4" />
                                            </button>
                                            <button @click="deleteSeguradora(seg.Id)" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 rounded-xl transition-all shadow-sm">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredSeguradoras.length === 0">
                                    <td colspan="6" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center gap-4 opacity-20">
                                            <ShieldCheck class="w-16 h-16" />
                                            <p class="text-sm font-black uppercase tracking-widest">Nenhuma seguradora encontrada</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Cadastro/Edição -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isModalOpen = false"></div>
                
                <div class="relative bg-white w-full max-w-xl rounded-[3rem] shadow-2xl overflow-hidden animate-modalIn">
                    <div class="p-10 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                                <Building2 class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ isEditing ? 'Editar Seguradora' : 'Nova Seguradora' }}</h3>
                                <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Preencha os dados da instituição</p>
                            </div>
                        </div>
                        <button @click="isModalOpen = false" class="p-2 hover:bg-slate-100 rounded-xl transition-colors">
                            <X class="w-5 h-5 text-slate-400" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-10 space-y-8">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Código ID</label>
                                <div class="relative">
                                    <Hash class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <input v-model="form.Codigo" disabled class="w-full bg-slate-50 border-transparent rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-400 cursor-not-allowed" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Contribuinte (NIF)</label>
                                <div class="relative">
                                    <Hash class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <input v-model="form.Contribuinte" required placeholder="999.999.999" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nome da Instituição / Seguradora</label>
                            <div class="relative">
                                <Building2 class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                <input v-model="form.Nome" required placeholder="EX: ENSA - SEGUROS DE ANGOLA" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Telefone</label>
                                <div class="relative">
                                    <Phone class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <input v-model="form.Telefone" placeholder="+244 9..." class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Cidade</label>
                                <div class="relative">
                                    <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <input v-model="form.Cidade" placeholder="EX: LUANDA" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 flex gap-4">
                            <button type="button" @click="isModalOpen = false" class="flex-grow py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-[11px] tracking-widest hover:bg-slate-200 transition-all uppercase">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing" class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 disabled:opacity-50 uppercase flex items-center justify-center gap-3">
                                <Save class="w-4 h-4" /> {{ isEditing ? 'Guardar Alterações' : 'Confirmar Cadastro' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Elegant Notification -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[1000] flex items-center gap-4 px-6 py-4 bg-slate-900 text-white rounded-2xl shadow-2xl border border-white/10 backdrop-blur-xl">
                <div :class="notification.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'" class="p-1.5 rounded-lg">
                    <CheckCircle v-if="notification.type === 'success'" class="w-4 h-4 text-white" />
                    <AlertCircle v-else class="w-4 h-4 text-white" />
                </div>
                <p class="text-[10px] font-black uppercase tracking-widest">{{ notification.message }}</p>
            </div>
        </Transition>
    </DashboardLayout>
</template>

<style scoped>
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-modalIn {
    animation: modalIn 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
</style>
