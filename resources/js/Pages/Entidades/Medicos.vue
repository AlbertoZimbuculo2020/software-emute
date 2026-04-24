<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    Stethoscope, Plus, Search, User, CreditCard, 
    Phone, MapPin, Trash2, Edit, Save, X, 
    ChevronRight, ClipboardList, Activity, Check
} from 'lucide-vue-next';

const props = defineProps({
    medicos: Array,
    consultas: Array,
    associacoes: Array
});

const searchTerm = ref('');
const isEditing = ref(false);
const selectedMedico = ref(null);

const form = useForm({
    nome: '',
    nif: '',
    carteira_medica: '',
    telefone: '',
    cidade: '',
    rua: '',
});

const assocForm = useForm({
    IdTipoEntidade: '',
    IdConsulta: ''
});

const filteredMedicos = computed(() => {
    if (!searchTerm.value) return props.medicos;
    return props.medicos.filter(m => 
        m.Nome.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        m.Codigo.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

const selectMedico = (medico) => {
    selectedMedico.value = medico;
    isEditing.value = true;
    form.nome = medico.Nome;
    form.nif = medico.NIF;
    form.carteira_medica = medico.CarteiraMedica;
    form.telefone = medico.Telefone;
    form.cidade = medico.Cidade;
    form.rua = medico.Rua;
};

const resetForm = () => {
    isEditing.value = false;
    selectedMedico.value = null;
    form.reset();
};

const salvarMedico = () => {
    if (isEditing.value) {
        form.put(route('medicos.update', selectedMedico.value.Codigo), {
            onSuccess: () => resetForm()
        });
    } else {
        form.post(route('medicos.store'), {
            onSuccess: () => resetForm()
        });
    }
};

const excluirMedico = (codigo) => {
    if (confirm('Tem certeza que deseja remover este médico?')) {
        router.delete(route('medicos.destroy', codigo));
    }
};

const associarConsulta = () => {
    if (!selectedMedico.value) return;
    assocForm.IdTipoEntidade = selectedMedico.value.Codigo;
    assocForm.post(route('medicos.consultas.store'), {
        onSuccess: () => {
            assocForm.IdConsulta = '';
        }
    });
};

const removerAssociacao = (id) => {
    if (confirm('Remover esta consulta associada?')) {
        router.delete(route('medicos.consultas.destroy', id));
    }
};

const getMedicoConsultas = (codigo) => {
    return props.associacoes.filter(a => a.IdTipoEntidade === codigo && a.Estado === 'Ativo');
};

const availableConsultas = computed(() => {
    if (!selectedMedico.value) return [];
    const currentIds = getMedicoConsultas(selectedMedico.value.Codigo).map(a => a.IdConsulta);
    return props.consultas.filter(c => !currentIds.includes(c.Id.toString()));
});
</script>

<template>
    <Head title="Cadastro de Médicos" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8">
            <div class="max-w-[1700px] mx-auto space-y-8">
                
                <!-- Header Moderno -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl shadow-sm border border-slate-200/60">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                            <div class="p-2 bg-blue-600 rounded-xl text-white shadow-lg shadow-blue-200">
                                <Stethoscope class="w-6 h-6" />
                            </div>
                            CADASTRO DE MÉDICOS
                        </h1>
                        <p class="text-slate-500 text-sm font-medium mt-1 ml-11">Gestão de Corpo Clínico e Especialidades</p>
                    </div>
                    
                    <div class="flex items-center gap-3 bg-slate-50 p-1.5 rounded-2xl border border-slate-100">
                        <div class="flex items-center px-4 py-2 bg-white rounded-xl shadow-sm border border-slate-200">
                            <Search class="w-4 h-4 text-slate-400 mr-3" />
                            <input v-model="searchTerm" placeholder="Buscar médico..." class="bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-700 placeholder:text-slate-300 w-48 lg:w-64" />
                        </div>
                        <button @click="resetForm" class="flex items-center px-6 py-2.5 bg-slate-900 text-white rounded-xl font-black text-[11px] tracking-widest hover:bg-slate-800 transition-all shadow-lg shadow-slate-200">
                            <Plus class="w-4 h-4 mr-2" /> NOVO MÉDICO
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Lista de Médicos (Esquerda) -->
                    <div class="lg:col-span-4 space-y-4">
                        <div v-for="medico in filteredMedicos" :key="medico.Id" 
                             @click="selectMedico(medico)"
                             :class="selectedMedico?.Codigo === medico.Codigo ? 'ring-2 ring-blue-500 bg-blue-50/50' : 'bg-white hover:bg-slate-50'"
                             class="p-5 rounded-[2rem] shadow-xl shadow-slate-200/40 border border-slate-100 cursor-pointer transition-all group relative overflow-hidden">
                            <div class="flex items-center gap-4 relative z-10">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-400 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-blue-100 group-hover:scale-105 transition-transform">
                                    {{ medico.Nome.substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="flex-grow">
                                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight group-hover:text-blue-600 transition-colors">{{ medico.Nome }}</h3>
                                    <div class="flex items-center gap-3 mt-1.5">
                                        <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full uppercase tracking-tighter">{{ medico.Codigo }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                                            <ClipboardList class="w-3 h-3" /> {{ getMedicoConsultas(medico.Codigo).length }} Consultas
                                        </span>
                                    </div>
                                </div>
                                <ChevronRight class="w-5 h-5 text-slate-300 group-hover:text-blue-500 group-hover:translate-x-1 transition-all" />
                            </div>
                        </div>
                    </div>

                    <!-- Formulário e Associações (Direita) -->
                    <div class="lg:col-span-8 space-y-8">
                        <!-- Formulário Principal -->
                        <div class="bg-white p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100">
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 bg-blue-50 rounded-2xl text-blue-600">
                                        <Edit class="w-5 h-5" />
                                    </div>
                                    <h2 class="text-xl font-black text-slate-800 tracking-tight">
                                        {{ isEditing ? 'EDITAR MÉDICO' : 'NOVO MÉDICO' }}
                                    </h2>
                                </div>
                                <div v-if="selectedMedico" class="flex gap-2">
                                    <button @click="excluirMedico(selectedMedico.Codigo)" class="p-2.5 bg-red-50 text-red-500 rounded-xl hover:bg-red-100 transition-colors">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Nome Completo</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-blue-500 text-slate-300 transition-colors">
                                            <User class="w-4 h-4" />
                                        </div>
                                        <input v-model="form.nome" placeholder="NOME DO MÉDICO" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl pl-11 pr-4 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">NIF / Contribuinte</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-blue-500 text-slate-300 transition-colors">
                                            <CreditCard class="w-4 h-4" />
                                        </div>
                                        <input v-model="form.nif" placeholder="999.999.999" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl pl-11 pr-4 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Carteira Médica / Ordem</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-blue-500 text-slate-300 transition-colors">
                                            <ClipboardList class="w-4 h-4" />
                                        </div>
                                        <input v-model="form.carteira_medica" placeholder="REF-00000" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl pl-11 pr-4 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Contacto Telefónico</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-blue-500 text-slate-300 transition-colors">
                                            <Phone class="w-4 h-4" />
                                        </div>
                                        <input v-model="form.telefone" placeholder="+244 ..." class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl pl-11 pr-4 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Cidade</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-blue-500 text-slate-300 transition-colors">
                                            <MapPin class="w-4 h-4" />
                                        </div>
                                        <input v-model="form.cidade" placeholder="LUANDA" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl pl-11 pr-4 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Rua / Endereço</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-blue-500 text-slate-300 transition-colors">
                                            <MapPin class="w-4 h-4" />
                                        </div>
                                        <input v-model="form.rua" placeholder="BAIRRO ..." class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl pl-11 pr-4 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-end gap-3">
                                <button @click="resetForm" class="px-8 py-3.5 bg-slate-100 text-slate-500 rounded-2xl font-black text-[11px] tracking-widest hover:bg-slate-200 transition-all uppercase">
                                    Cancelar
                                </button>
                                <button @click="salvarMedico" :disabled="form.processing" class="flex items-center px-10 py-3.5 bg-blue-600 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 disabled:opacity-50 uppercase">
                                    <Save class="w-4 h-4 mr-2" /> {{ isEditing ? 'Atualizar' : 'Salvar' }}
                                </button>
                            </div>
                        </div>

                        <!-- Consultas Associadas (Apenas se estiver editando) -->
                        <div v-if="selectedMedico" class="bg-white p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100">
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 bg-purple-50 rounded-2xl text-purple-600">
                                        <Activity class="w-5 h-5" />
                                    </div>
                                    <h2 class="text-xl font-black text-slate-800 tracking-tight">CONSULTAS ASSOCIADAS</h2>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Seletor de Nova Associação -->
                                <div class="space-y-4 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Adicionar Nova Especialidade</h4>
                                    <div class="space-y-4">
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-blue-500 text-slate-300">
                                                <ClipboardList class="w-4 h-4" />
                                            </div>
                                            <select v-model="assocForm.IdConsulta" class="w-full bg-white border-slate-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl pl-11 pr-4 py-3.5 text-sm font-bold text-slate-700 transition-all">
                                                <option value="">Selecione uma consulta...</option>
                                                <option v-for="c in availableConsultas" :key="c.Id" :value="c.Id">{{ c.Descricao }}</option>
                                            </select>
                                        </div>
                                        <button @click="associarConsulta" :disabled="!assocForm.IdConsulta" class="w-full flex items-center justify-center px-6 py-4 bg-slate-900 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-slate-800 transition-all shadow-lg disabled:opacity-50 uppercase">
                                            <Plus class="w-4 h-4 mr-2" /> Associar à Clinica
                                        </button>
                                    </div>
                                </div>

                                <!-- Lista de Associações Atuais -->
                                <div class="space-y-3">
                                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Consultas Ativas</h4>
                                    <div v-if="getMedicoConsultas(selectedMedico.Codigo).length === 0" class="p-8 text-center bg-slate-50 rounded-3xl border border-dashed border-slate-200">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nenhuma consulta associada</p>
                                    </div>
                                    <div v-for="assoc in getMedicoConsultas(selectedMedico.Codigo)" :key="assoc.Id" class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-100 shadow-sm group">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                                                <Check class="w-4 h-4" />
                                            </div>
                                            <span class="text-xs font-black text-slate-700 uppercase">{{ assoc.Descricao }}</span>
                                        </div>
                                        <button @click="removerAssociacao(assoc.Id)" class="p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
