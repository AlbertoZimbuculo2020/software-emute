<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    Microscope, Plus, Search, Edit3, Trash2, 
    X, CheckCircle, AlertCircle, FileText, 
    DollarSign, Tag, Layers, Save, Info
} from 'lucide-vue-next';

const props = defineProps({
    exames: Array,
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
    Descricao: '',
    Valor: 0,
    Categoria: '',
    Tipo: 'NORMAL',
    Exame_Fora: 'False',
});

const filteredExames = computed(() => {
    if (!searchTerm.value) return props.exames;
    const term = searchTerm.value.toLowerCase();
    return props.exames.filter(e => 
        e.Descricao.toLowerCase().includes(term) ||
        e.Codigo.toLowerCase().includes(term)
    );
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.Codigo = props.nextId;
    isModalOpen.value = true;
};

const openEditModal = (ex) => {
    isEditing.value = true;
    editingId.value = ex.Id;
    form.Codigo = ex.Codigo;
    form.Descricao = ex.Descricao;
    form.Valor = ex.Valor;
    form.Categoria = ex.Categoria || '';
    form.Tipo = ex.Tipo || 'NORMAL';
    form.Exame_Fora = ex.Exame_Fora || 'False';
    isModalOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('hospitalar.exames.update', editingId.value), {
            onSuccess: () => {
                showNotification('Exame atualizado com sucesso!');
                isModalOpen.value = false;
            }
        });
    } else {
        form.post(route('hospitalar.exames.store'), {
            onSuccess: () => {
                showNotification('Exame cadastrado com sucesso!');
                isModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const deleteExame = (id) => {
    if (!confirm('Deseja realmente remover este exame?')) return;
    
    router.delete(route('hospitalar.exames.destroy', id), {
        onSuccess: () => showNotification('Exame removido com sucesso!')
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(value);
};
</script>

<template>
    <Head title="Cadastro de Exames" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8">
            <div class="max-w-[1600px] mx-auto space-y-8">
                
                <!-- Header Moderno -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-200/60">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-4">
                            <div class="p-3 bg-indigo-600 rounded-2xl text-white shadow-xl shadow-indigo-200">
                                <Microscope class="w-8 h-8" />
                            </div>
                            EXAMES
                        </h1>
                        <p class="text-slate-500 text-sm font-medium mt-2 ml-1">Gerenciamento do catálogo de exames e serviços laboratoriais</p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="relative hidden lg:block">
                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="searchTerm" placeholder="Pesquisar exame..." class="bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold text-slate-700 w-80 transition-all shadow-sm" />
                        </div>
                        <button @click="openCreateModal" class="flex items-center gap-3 px-8 py-4 bg-indigo-600 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-200 active:scale-95 uppercase">
                            <Plus class="w-5 h-5" /> Registar Exame
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
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Descrição do Exame</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Categoria / Tipo</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Preço Unitário</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Origem</th>
                                    <th class="px-8 py-6 text-right text-[11px] font-black text-slate-400 uppercase tracking-widest">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="ex in filteredExames" :key="ex.Id" class="group hover:bg-indigo-50/30 transition-all duration-300">
                                    <td class="px-8 py-6">
                                        <span class="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black tracking-widest">{{ ex.Codigo }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-slate-800 tracking-tight">{{ ex.Descricao }}</span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase mt-1">{{ ex.Tipo }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-[9px] font-black uppercase tracking-widest">
                                            {{ ex.Categoria || 'GERAL' }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="text-sm font-black text-slate-700">{{ formatCurrency(ex.Valor) }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span :class="ex.Exame_Fora === 'True' ? 'text-amber-600 bg-amber-50' : 'text-emerald-600 bg-emerald-50'" class="px-2 py-0.5 rounded text-[9px] font-black uppercase">
                                            {{ ex.Exame_Fora === 'True' ? 'EXTERNO' : 'INTERNO' }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex justify-end gap-3">
                                            <button @click="openEditModal(ex)" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 rounded-xl transition-all shadow-sm">
                                                <Edit3 class="w-4 h-4" />
                                            </button>
                                            <button @click="deleteExame(ex.Id)" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 rounded-xl transition-all shadow-sm">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredExames.length === 0">
                                    <td colspan="6" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center gap-4 opacity-20">
                                            <Microscope class="w-16 h-16" />
                                            <p class="text-sm font-black uppercase tracking-widest">Nenhum exame cadastrado</p>
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
                
                <div class="relative bg-white w-full max-w-2xl rounded-[3rem] shadow-2xl overflow-hidden animate-modalIn">
                    <div class="p-10 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                                <FileText class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ isEditing ? 'Editar Exame' : 'Novo Exame' }}</h3>
                                <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Configure as propriedades do serviço</p>
                            </div>
                        </div>
                        <button @click="isModalOpen = false" class="p-2 hover:bg-slate-100 rounded-xl transition-colors">
                            <X class="w-5 h-5 text-slate-400" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-10 space-y-8">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Código do Sistema</label>
                                <div class="relative">
                                    <Tag class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <input v-model="form.Codigo" disabled class="w-full bg-slate-50 border-transparent rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-400 cursor-not-allowed" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Preço (AOA)</label>
                                <div class="relative">
                                    <DollarSign class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <input type="number" v-model="form.Valor" required class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-700 transition-all" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Descrição do Exame</label>
                            <div class="relative">
                                <FileText class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                <input v-model="form.Descricao" required placeholder="EX: HEMOGRAMA COMPLETO" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-700 transition-all placeholder:text-slate-300" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Categoria</label>
                                <div class="relative">
                                    <Layers class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" />
                                    <select v-model="form.Categoria" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-700 transition-all appearance-none cursor-pointer">
                                        <option value="">GERAL</option>
                                        <option value="SANGUE">SANGUE</option>
                                        <option value="URINA">URINA</option>
                                        <option value="IMAGEM">IMAGEM</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipo de Exame</label>
                                <select v-model="form.Tipo" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all appearance-none cursor-pointer">
                                    <option value="NORMAL">NORMAL</option>
                                    <option value="URGENTE">URGENTE</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <input type="checkbox" v-model="form.Exame_Fora" true-value="True" false-value="False" class="w-5 h-5 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500" />
                            <div>
                                <p class="text-xs font-black text-slate-700 uppercase">Exame Externo</p>
                                <p class="text-[10px] font-bold text-slate-400">Marque se o exame é realizado fora da clínica</p>
                            </div>
                        </div>

                        <div class="pt-6 flex gap-4">
                            <button type="button" @click="isModalOpen = false" class="flex-grow py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-[11px] tracking-widest hover:bg-slate-200 transition-all uppercase">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing" class="flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-200 disabled:opacity-50 uppercase flex items-center justify-center gap-3">
                                <Save class="w-4 h-4" /> {{ isEditing ? 'Guardar Alterações' : 'Confirmar Cadastro' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Notification -->
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
