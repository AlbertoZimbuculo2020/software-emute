<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    Microscope, Plus, Search, Edit3, Trash2, 
    X, CheckCircle, AlertCircle, FileText, 
    DollarSign, Tag, Layers, Save, Info,
    ChevronDown, ChevronUp, Beaker, Activity
} from 'lucide-vue-next';

const props = defineProps({
    exames: Array,
    nextId: String
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const searchTerm = ref('');
const showSubExams = ref(true);

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
    Filhos: [],
    Referencia: '',
    Sugestao: '',
});

const filteredExames = computed(() => {
    if (!searchTerm.value) return props.exames;
    const term = searchTerm.value.toLowerCase();
    return props.exames.filter(e => 
        e.Descricao.toLowerCase().includes(term) ||
        e.Codigo.toLowerCase().includes(term)
    );
});

const addFilho = () => {
    form.Filhos.push({
        nome: '',
        de: '',
        ate: ''
    });
};

const removeFilho = (index) => {
    form.Filhos.splice(index, 1);
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.Codigo = props.nextId;
    form.Filhos = [];
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
    form.Referencia = ex.Referencia || '';
    form.Sugestao = ex.Sugestao || '';
    
    let parsedFilhos = [];
    if (ex.Filhos) {
        if (ex.Filhos.startsWith('[')) {
            // Compatibilidade com possíveis registros criados como JSON
            try {
                let jFilhos = JSON.parse(ex.Filhos);
                parsedFilhos = jFilhos.map(f => ({
                    nome: f.descricao || f.nome || '',
                    de: f.min || f.de || '',
                    ate: f.max || f.ate || ''
                }));
            } catch (e) {}
        } else {
            // Formato normal esperado: "Hemoglobina=de: 12 Até: 16|Glóbulos brancos=de: 4.5 Até: 11"
            const parts = ex.Filhos.split('|');
            for (const p of parts) {
                if (!p.trim()) continue;
                
                const eqIndex = p.indexOf('=de: ');
                if (eqIndex !== -1) {
                    const nome = p.substring(0, eqIndex).trim();
                    const rest = p.substring(eqIndex + 5);
                    const ateSplit = rest.split(' Até: ');
                    const de = ateSplit[0] ? ateSplit[0].trim() : '';
                    const ate = ateSplit[1] ? ateSplit[1].trim() : '';
                    parsedFilhos.push({ nome, de, ate });
                } else {
                    parsedFilhos.push({ nome: p.trim(), de: '', ate: '' });
                }
            }
        }
    }
    form.Filhos = parsedFilhos;
    
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
                                    <th class="px-8 py-6 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">Sub-itens</th>
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
                                        <div v-if="ex.Filhos" class="flex items-center gap-1">
                                            <span class="text-[10px] font-black text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full" :title="ex.Filhos">
                                                {{ (ex.Filhos.startsWith('[') ? JSON.parse(ex.Filhos).length : ex.Filhos.split('|').filter(Boolean).length) }} ITENS
                                            </span>
                                        </div>
                                        <span v-else class="text-[10px] font-bold text-slate-300 uppercase italic">Simples</span>
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Cadastro Completo -->
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
                
                <div class="relative bg-white w-full max-w-4xl rounded-[3rem] shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                    <!-- Modal Header -->
                    <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-indigo-600 text-white rounded-2xl">
                                <Activity class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">{{ isEditing ? 'Configurar Exame' : 'Novo Registro de Exame' }}</h3>
                                <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-widest">Defina as métricas e valores de referência</p>
                            </div>
                        </div>
                        <button @click="isModalOpen = false" class="p-2 hover:bg-slate-200 rounded-xl transition-colors">
                            <X class="w-5 h-5 text-slate-400" />
                        </button>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="flex-grow overflow-y-auto p-10 space-y-10 custom-scrollbar">
                        
                        <!-- Seção 1: Dados Básicos -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                            <div class="md:col-span-3 space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Código ID</label>
                                <input v-model="form.Codigo" disabled class="w-full bg-slate-50 border-transparent rounded-2xl px-6 py-4 text-sm font-black text-slate-400 cursor-not-allowed" />
                            </div>
                            <div class="md:col-span-6 space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Descrição Completa</label>
                                <input v-model="form.Descricao" required placeholder="EX: HEMOGRAMA COMPLETO" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all" />
                            </div>
                            <div class="md:col-span-3 space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Preço (AOA)</label>
                                <input type="number" v-model="form.Valor" required class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Categoria do Exame</label>
                                <select v-model="form.Categoria" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all">
                                    <option value="">GERAL / SEM CATEGORIA</option>
                                    <option value="SANGUE">SANGUE / HEMATOLOGIA</option>
                                    <option value="URINA">URINA / ANÁLISES</option>
                                    <option value="IMAGEM">IMAGEM / RADIOLOGIA</option>
                                    <option value="FEZES">PARASITOLOGIA</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipo de Serviço</label>
                                <select v-model="form.Tipo" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl px-6 py-4 text-sm font-black text-slate-700 transition-all">
                                    <option value="NORMAL">NORMAL</option>
                                    <option value="URGENTE">URGENTE</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-4 bg-slate-50/50 p-4 rounded-2xl border border-slate-100 cursor-pointer hover:bg-slate-100 transition-colors" @click="form.Exame_Fora = form.Exame_Fora === 'True' ? 'False' : 'True'">
                                <input type="checkbox" v-model="form.Exame_Fora" true-value="True" false-value="False" class="w-5 h-5 text-indigo-600 rounded cursor-pointer pointer-events-none" />
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest select-none">Exames do consultório ou de fora</span>
                            </div>
                        </div>

                        <hr class="border-slate-100" />

                        <!-- Seção 2: Categoria e Filhos (Onde a mágica acontece) -->
                        <div class="space-y-6">
                            <div @click="showSubExams = !showSubExams" class="flex items-center justify-between cursor-pointer group">
                                <div class="flex items-center gap-3">
                                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-[0.2em]">Adicionar Categoria e Filhos</h4>
                                    <div class="h-px w-20 bg-slate-100 group-hover:w-40 transition-all"></div>
                                </div>
                                <component :is="showSubExams ? ChevronUp : ChevronDown" class="w-5 h-5 text-slate-300" />
                            </div>

                            <Transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-4"
                                enter-to-class="opacity-100 translate-y-0"
                            >
                                <div v-if="showSubExams" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Referências Gerais</label>
                                            <textarea v-model="form.Referencia" rows="3" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl px-6 py-4 text-sm font-medium transition-all" placeholder="Texto de referência padrão..."></textarea>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Sugestões / Observações</label>
                                            <textarea v-model="form.Sugestao" rows="3" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 rounded-2xl px-6 py-4 text-sm font-medium transition-all" placeholder="Sugestões para o laudo..."></textarea>
                                        </div>
                                    </div>

                                    <!-- Lista de Sub-itens (Filhos) -->
                                    <div class="bg-slate-50/50 p-8 rounded-[2.5rem] border border-slate-100 space-y-6">
                                        <div class="flex items-center justify-between">
                                            <h5 class="text-[10px] font-black text-indigo-600 uppercase tracking-widest flex items-center gap-2">
                                                <Beaker class="w-4 h-4" /> COMPONENTES DO EXAME (FILHOS)
                                            </h5>
                                            <button type="button" @click="addFilho" class="text-[9px] font-black text-white bg-indigo-600 px-4 py-2 rounded-xl hover:bg-indigo-700 transition-all uppercase flex items-center gap-2 shadow-lg shadow-indigo-100">
                                                <Plus class="w-3.5 h-3.5" /> Adicionar Componente
                                            </button>
                                        </div>

                                        <div v-if="form.Filhos.length > 0" class="space-y-4">
                                            <div v-for="(filho, index) in form.Filhos" :key="index" class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-end gap-4 animate-fadeIn">
                                                <div class="flex-grow space-y-2">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase">Filhos (Nome Categoria)</label>
                                                    <input v-model="filho.nome" placeholder="Ex: Hemoglobina" class="w-full bg-slate-50 border-transparent rounded-xl px-4 py-2.5 text-xs font-bold" />
                                                </div>
                                                <div class="w-full md:w-36 space-y-2">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase text-center block whitespace-nowrap">Intervalo de Ref, de:</label>
                                                    <input v-model="filho.de" placeholder="0" class="w-full bg-slate-50 border-transparent rounded-xl px-4 py-2.5 text-xs font-bold text-center" />
                                                </div>
                                                <div class="w-full md:w-32 space-y-2">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase text-center block">Até</label>
                                                    <input v-model="filho.ate" placeholder="100" class="w-full bg-slate-50 border-transparent rounded-xl px-4 py-2.5 text-xs font-bold text-center" />
                                                </div>
                                                <button type="button" @click="removeFilho(index)" class="p-2.5 bg-slate-50 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all shadow-sm">
                                                    <Trash2 class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                        <div v-else class="py-10 text-center opacity-20 italic text-sm font-bold">
                                            Nenhum filho/componente adicionado.
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-8 bg-slate-50 border-t border-slate-100 flex gap-4">
                        <button type="button" @click="isModalOpen = false" class="flex-grow py-4 bg-white border border-slate-200 text-slate-500 rounded-2xl font-black text-[11px] tracking-widest hover:bg-slate-100 transition-all uppercase">
                            Cancelar
                        </button>
                        <button type="button" @click="submitForm" :disabled="form.processing" class="flex-[3] py-4 bg-indigo-600 text-white rounded-2xl font-black text-[11px] tracking-[0.2em] hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-100 disabled:opacity-50 uppercase flex items-center justify-center gap-3">
                            <Save class="w-5 h-5" /> {{ isEditing ? 'Guardar Alterações' : 'Confirmar e Gravar Exame' }}
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
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
