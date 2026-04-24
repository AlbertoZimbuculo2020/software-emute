<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    Briefcase, Plus, Search, Edit3, Trash2, 
    X, CheckCircle, AlertCircle, ShoppingBag,
    DollarSign, Tag, Layers, Save, Image as ImageIcon,
    Percent, ShieldAlert, FileText, LayoutGrid
} from 'lucide-vue-next';

const props = defineProps({
    servicos: Array,
    subCategorias: Array,
    impostos: Array,
    motivosIsencao: Array,
    nextId: String
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const searchTerm = ref('');
const imagePreview = ref(null);

const notification = ref({ show: false, message: '', type: 'success' });
const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => notification.value.show = false, 4000);
};

const form = useForm({
    CODIGO: props.nextId,
    DESCRICAO: '',
    PC: 0,
    PV: 0,
    ID_SUBCATEGORIA: '',
    ID_IMPOSTO: '',
    ID_MOTIVO_ISENCAO: '',
    IMAGEM: null
});

const filteredServicos = computed(() => {
    if (!searchTerm.value) return props.servicos;
    const term = searchTerm.value.toLowerCase();
    return props.servicos.filter(s => 
        s.DESCRICAO.toLowerCase().includes(term) ||
        s.CODIGO.toLowerCase().includes(term)
    );
});

const getSubCategoriaName = (codigo) => {
    const cat = props.subCategorias.find(c => c.CODIGO === codigo);
    return cat ? cat.DESCRICAO : 'SEM CATEGORIA';
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.CODIGO = props.nextId;
    imagePreview.value = null;
    isModalOpen.value = true;
};

const openEditModal = (s) => {
    isEditing.value = true;
    editingId.value = s.ID_ARTIGO;
    form.CODIGO = s.CODIGO;
    form.DESCRICAO = s.DESCRICAO;
    form.PC = s.PC || 0;
    form.PV = s.PV || 0;
    form.ID_SUBCATEGORIA = s.ID_SUBCATEGORIA || '';
    form.ID_IMPOSTO = s.ID_IMPOSTO || '';
    imagePreview.value = s.IMAGEM ? `data:image/png;base64,${s.IMAGEM}` : null;
    isModalOpen.value = true;
};

const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (f) => {
            imagePreview.value = f.target.result;
            // Retirar o prefixo data:image/...;base64, para salvar no longblob
            form.IMAGEM = f.target.result.split(',')[1];
        };
        reader.readAsDataURL(file);
    }
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('hospitalar.servicos.update', editingId.value), {
            onSuccess: () => {
                showNotification('Serviço atualizado com sucesso!');
                isModalOpen.value = false;
            }
        });
    } else {
        form.post(route('hospitalar.servicos.store'), {
            onSuccess: () => {
                showNotification('Serviço cadastrado com sucesso!');
                isModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const deleteServico = (id) => {
    if (!confirm('Deseja realmente remover este serviço?')) return;
    
    router.delete(route('hospitalar.servicos.destroy', id), {
        onSuccess: () => showNotification('Serviço removido com sucesso!')
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(value);
};
</script>

<template>
    <Head title="Cadastro de Serviços" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8">
            <div class="max-w-[1600px] mx-auto space-y-8">
                
                <!-- Header Premium -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-200/60">
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-4">
                            <div class="p-3 bg-blue-600 rounded-2xl text-white shadow-xl shadow-blue-200">
                                <Briefcase class="w-8 h-8" />
                            </div>
                            SERVIÇOS
                        </h1>
                        <p class="text-slate-500 text-sm font-medium mt-2 ml-1">Gerenciamento de serviços hospitalares e procedimentos faturáveis</p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="relative hidden lg:block">
                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="searchTerm" placeholder="Pesquisar serviço..." class="bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-bold text-slate-700 w-80 transition-all shadow-sm" />
                        </div>
                        <button @click="openCreateModal" class="flex items-center gap-3 px-8 py-4 bg-blue-600 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 active:scale-95 uppercase">
                            <Plus class="w-5 h-5" /> Registar Serviço
                        </button>
                    </div>
                </div>

                <!-- Table View -->
                <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/40 border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-separate border-spacing-0">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Código</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Descrição</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Sub Categoria</th>
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Preço de Venda</th>
                                    <th class="px-8 py-6 text-right text-[11px] font-black text-slate-400 uppercase tracking-widest">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="s in filteredServicos" :key="s.ID_ARTIGO" class="group hover:bg-blue-50/30 transition-all duration-300">
                                    <td class="px-8 py-6">
                                        <span class="px-3 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black tracking-widest">{{ s.CODIGO }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden">
                                                <img v-if="s.IMAGEM" :src="`data:image/png;base64,${s.IMAGEM}`" class="w-full h-full object-cover" />
                                                <ShoppingBag v-else class="w-5 h-5 text-slate-300" />
                                            </div>
                                            <span class="text-sm font-black text-slate-800 tracking-tight">{{ s.DESCRICAO }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-black uppercase tracking-widest">
                                            {{ getSubCategoriaName(s.ID_SUBCATEGORIA) }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="text-sm font-black text-slate-700">{{ formatCurrency(s.PV) }}</span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex justify-end gap-3">
                                            <button @click="openEditModal(s)" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 rounded-xl transition-all shadow-sm">
                                                <Edit3 class="w-4 h-4" />
                                            </button>
                                            <button @click="deleteServico(s.ID_ARTIGO)" class="p-2.5 bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 rounded-xl transition-all shadow-sm">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredServicos.length === 0">
                                    <td colspan="5" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center gap-4 opacity-20">
                                            <Briefcase class="w-16 h-16" />
                                            <p class="text-sm font-black uppercase tracking-widest">Nenhum serviço encontrado</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Cadastro -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="isModalOpen = false"></div>
                
                <div class="relative bg-white w-full max-w-4xl rounded-[3rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-blue-600 text-white rounded-2xl">
                                <LayoutGrid class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ isEditing ? 'Editar Serviço' : 'Novo Cadastro de Serviço' }}</h3>
                                <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Configure os detalhes comerciais e fiscais</p>
                            </div>
                        </div>
                        <button @click="isModalOpen = false" class="p-2 hover:bg-slate-200 rounded-xl transition-colors">
                            <X class="w-5 h-5 text-slate-400" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="flex-grow overflow-y-auto p-10 custom-scrollbar">
                        <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-12 gap-10">
                            
                            <!-- Coluna Esquerda: Dados -->
                            <div class="md:col-span-8 space-y-8">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Código</label>
                                        <input v-model="form.CODIGO" disabled class="w-full bg-slate-50 border-transparent rounded-2xl px-6 py-4 text-sm font-black text-slate-400" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Sub Categoria</label>
                                        <select v-model="form.ID_SUBCATEGORIA" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all">
                                            <option value="">SEM SUB CATEGORIA</option>
                                            <option v-for="cat in subCategorias" :key="cat.CODIGO" :value="cat.CODIGO">{{ cat.DESCRICAO }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Descrição do Serviço</label>
                                    <input v-model="form.DESCRICAO" required placeholder="Ex: CONSULTA DE ESPECIALIDADE" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all uppercase" />
                                </div>

                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Imposto</label>
                                        <select v-model="form.ID_IMPOSTO" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all">
                                            <option v-for="imp in impostos" :key="imp.CODIGO" :value="imp.CODIGO">{{ imp.DESCRICAO }} ({{ imp.TAXA }}%)</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Motivo de Isenção</label>
                                        <select v-model="form.ID_MOTIVO_ISENCAO" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl px-6 py-4 text-[10px] font-bold text-slate-700 transition-all">
                                            <option value="">NENHUM</option>
                                            <option v-for="mot in motivosIsencao" :key="mot.ID" :value="mot.ID">{{ mot.DESCRICAO }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-6 bg-blue-50/50 p-8 rounded-[2.5rem] border border-blue-100/50">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-1">Preço de Custo</label>
                                        <div class="relative">
                                            <DollarSign class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-300" />
                                            <input type="number" v-model="form.PC" class="w-full bg-white border-transparent focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-slate-700" />
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest ml-1">Preço de Venda</label>
                                        <div class="relative">
                                            <DollarSign class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-400" />
                                            <input type="number" v-model="form.PV" class="w-full bg-white border-blue-200 focus:ring-4 focus:ring-blue-500/10 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-blue-700 shadow-sm" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Coluna Direita: Imagem -->
                            <div class="md:col-span-4 space-y-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Imagem do Serviço</label>
                                    <div class="relative group cursor-pointer aspect-square rounded-[2.5rem] bg-slate-50 border-2 border-dashed border-slate-200 flex flex-col items-center justify-center overflow-hidden transition-all hover:border-blue-400 hover:bg-blue-50/30">
                                        <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                                        <div v-else class="flex flex-col items-center gap-3 text-slate-400 group-hover:text-blue-500">
                                            <ImageIcon class="w-10 h-10" />
                                            <span class="text-[10px] font-black uppercase tracking-widest">Carregar Imagem</span>
                                        </div>
                                        <input type="file" @change="handleImageUpload" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" />
                                    </div>
                                </div>
                                <div class="p-6 bg-amber-50 rounded-2xl border border-amber-100 flex gap-3">
                                    <ShieldAlert class="w-5 h-5 text-amber-500 shrink-0" />
                                    <p class="text-[10px] font-bold text-amber-700 leading-relaxed uppercase">Certifique-se de definir o motivo de isenção caso o imposto seja 0% para conformidade fiscal.</p>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-8 bg-slate-50 border-t border-slate-100 flex gap-4">
                        <button type="button" @click="isModalOpen = false" class="flex-grow py-4 bg-white border border-slate-200 text-slate-500 rounded-2xl font-black text-[11px] tracking-widest hover:bg-slate-100 transition-all uppercase">
                            Cancelar
                        </button>
                        <button type="button" @click="submitForm" :disabled="form.processing" class="flex-[3] py-4 bg-blue-600 text-white rounded-2xl font-black text-[11px] tracking-[0.2em] hover:bg-blue-700 transition-all shadow-xl shadow-blue-100 disabled:opacity-50 uppercase flex items-center justify-center gap-3">
                            <Save class="w-5 h-5" /> {{ isEditing ? 'Guardar Alterações' : 'Confirmar e Gravar Serviço' }}
                        </button>
                    </div>
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
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
