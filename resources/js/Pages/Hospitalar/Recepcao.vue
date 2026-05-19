<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { 
    Search, UserPlus, Plus, ClipboardList, Stethoscope, 
    Calendar, MousePointer2, RotateCcw, FileText, Activity, 
    CreditCard, Users, User, ChevronDown, Check, X, Printer, Download
} from 'lucide-vue-next';
import { watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    medicos: Array,
    consultas: Array,
    seguradoras: Array,
    agendamentos: Array,
    examesPendentes: Array,
    internamentosPendentes: Array,
    filters: Object
});

const form = useForm({
    IdPaciente: '',
    nome: '',
    filiacao_pai: '',
    filiacao_mae: '',
    data_nascimento: new Date().toISOString().split('T')[0],
    idade: 0,
    telefone: '',
    sexo: 'MASCULINO',
    endereco: '',
    cidade: 'SEM',
    tipo_paciente: 'Particular',
    IdSeguradora: '',
    IdConsulta: '',
    IdMedico: '',
    DataAgendamento: new Date().toISOString().split('T')[0],
    situacao: 'Agendada',
});

const searchLoading = ref(false);
const searchTerm = ref('');
const searchResults = ref([]);
const showResults = ref(false);
const patientNameInput = ref(null);
const notification = ref({ show: false, message: '', type: 'success' });
const isUpdating = ref(false);

// Filtros de Data
const startDate = ref(props.filters?.startDate || new Date().toISOString().split('T')[0]);
const endDate = ref(props.filters?.endDate || new Date().toISOString().split('T')[0]);

const filtrarPorData = () => {
    router.get(route('hospitalar.recepcao'), {
        startDate: startDate.value,
        endDate: endDate.value
    }, {
        preserveState: true,
        replace: true
    });
};

const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => {
        notification.value.show = false;
    }, 4000);
};

// Sync Idade <-> Data Nascimento
watch(() => form.data_nascimento, (newVal) => {
    if (isUpdating.value) return;
    if (newVal) {
        isUpdating.value = true;
        const birthDate = new Date(newVal);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        form.idade = age;
        setTimeout(() => isUpdating.value = false, 50);
    }
});

watch(() => form.idade, (newVal) => {
    if (isUpdating.value) return;
    if (newVal !== undefined) {
        isUpdating.value = true;
        const today = new Date();
        const birthYear = today.getFullYear() - newVal;
        const birthDate = new Date(birthYear, today.getMonth(), today.getDate());
        form.data_nascimento = birthDate.toISOString().split('T')[0];
        setTimeout(() => isUpdating.value = false, 50);
    }
});

const buscarPaciente = debounce(async () => {
    if (!searchTerm.value || searchTerm.value.length < 1) {
        searchResults.value = [];
        showResults.value = false;
        return;
    }

    // Prevents searching and reopening the dropdown when a patient is selected
    if (searchTerm.value === form.nome) {
        showResults.value = false;
        return;
    }
    
    searchLoading.value = true;
    try {
        const response = await axios.post(route('hospitalar.recepcao.search'), { term: searchTerm.value });
        searchResults.value = response.data;
        showResults.value = response.data.length > 0;
    } catch (error) {
        console.error(error);
    } finally {
        searchLoading.value = false;
    }
}, 300);

watch(searchTerm, () => {
    buscarPaciente();
});

const selecionarPaciente = (p) => {
    form.IdPaciente = p.Codigo;
    form.nome = p.Nome;
    form.telefone = p.Telefone || '';
    form.endereco = p.Endereco || '';
    form.cidade = p.Cidade || 'SEM';
    form.filiacao_pai = p.Pai || '';
    form.filiacao_mae = p.Mae || '';
    form.sexo = p.Genero || 'MASCULINO';
    form.IdSeguradora = p.IdSegura || '';
    form.tipo_paciente = p.IdSegura ? 'Assegurado' : 'Particular';
    
    if (p.DataNascimento) {
        isUpdating.value = true;
        form.data_nascimento = p.DataNascimento;
        // Age will be calculated by watcher
    }
    
    showResults.value = false;
    searchTerm.value = p.Nome;
};

const admitirPaciente = (situacao = 'Agendada') => {
    if (!form.nome) {
        showNotification('Por favor, preencha pelo menos o nome do paciente.', 'error');
        return;
    }

    form.situacao = situacao;

    form.post(route('hospitalar.recepcao.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showNotification(`Paciente ${situacao === 'Triagem' ? 'enviado para triagem' : 'admitido'} com sucesso!`);
            limparForm();
        },
        onError: (err) => {
            console.error(err);
            showNotification(err.error || 'Erro ao realizar admissão.', 'error');
        }
    });
};

const enviarParaTriagem = (agendamento) => {
    if (!agendamento) {
        showNotification('Selecione um agendamento.', 'error');
        return;
    }
    
    router.post(route('hospitalar.recepcao.enviar-triagem'), { codigo: agendamento.Codigo }, {
        onSuccess: () => showNotification('Paciente enviado para a triagem com sucesso!'),
        onError: () => showNotification('Erro ao enviar para triagem.', 'error')
    });
};

const limparForm = () => {
    form.reset();
    searchTerm.value = '';
    searchResults.value = [];
    showResults.value = false;
    form.DataAgendamento = new Date().toISOString().split('T')[0];
    form.data_nascimento = new Date().toISOString().split('T')[0];
    
    setTimeout(() => {
        if (patientNameInput.value) {
            patientNameInput.value.focus();
        }
    }, 100);
};

// Polling para atualização das listas
const pollInterval = ref(null);
const startPolling = () => {
    pollInterval.value = setInterval(() => {
        router.reload({ only: ['agendamentos', 'examesPendentes', 'internamentosPendentes'] });
    }, 30000); // 30 segundos
};

watch(() => props.agendamentos.length, (newVal, oldVal) => {
    if (newVal > oldVal) {
        showNotification('Novo agendamento recebido!', 'success');
    }
});

watch(() => props.examesPendentes.length, (newVal, oldVal) => {
    if (newVal > oldVal) {
        showNotification('Novo exame pendente para pagamento!', 'info');
    }
});

import { onMounted, onUnmounted } from 'vue';
onMounted(() => {
    startPolling();
});

onUnmounted(() => {
    if (pollInterval.value) clearInterval(pollInterval.value);
});
</script>

<template>
    <Head title="Recepção Hospitalar - Admissão" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f1f5f9] p-4 lg:p-6">
            <div class="max-w-full mx-auto space-y-6">
                
                <!-- Modern Compact Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-600 rounded-2xl text-white shadow-lg shadow-blue-200">
                            <Activity class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-xl font-black text-slate-900 tracking-tight leading-none">RECEPÇÃO HOSPITALAR</h1>
                            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">Workstation de Admissão e Triagem</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="hidden md:block text-right">
                            <p class="text-[9px] font-black uppercase text-slate-400 tracking-widest leading-none">Status do Sistema</p>
                            <div class="flex items-center gap-2 mt-1 justify-end">
                                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                <span class="text-xs font-black text-slate-700 uppercase tracking-tighter">Conectado / Em Tempo Real</span>
                            </div>
                        </div>
                        <div class="h-10 w-px bg-slate-200"></div>
                        <div class="flex items-center gap-3 bg-slate-50 px-4 py-2 rounded-xl border border-slate-100">
                            <Calendar class="w-4 h-4 text-blue-600" />
                            <span class="text-xs font-black text-slate-700">{{ new Date().toLocaleDateString('pt-PT', { day: '2-digit', month: 'long', year: 'numeric' }) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Main Grid: Admission (Left) & Dashboard (Right) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- LEFT COLUMN: Patient Registration Form -->
                    <div class="lg:col-span-4 space-y-6">
                        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200 overflow-hidden sticky top-6">
                            <div class="bg-slate-50/50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    <UserPlus class="w-4 h-4 text-blue-600" /> Ficha de Admissão
                                </h2>
                                <button @click="limparForm" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Limpar Formulário">
                                    <RotateCcw class="w-4 h-4" />
                                </button>
                            </div>

                            <div class="p-6 space-y-5">
                                <!-- Search & Quick Actions -->
                                <div class="space-y-3">
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <Search class="h-4 w-4 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                                        </div>
                                        <input 
                                            v-model="searchTerm" 
                                            @focus="showResults = searchResults.length > 0"
                                            placeholder="Buscar por Nome ou Código..." 
                                            class="w-full pl-10 bg-slate-50 border-slate-100 focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-4 py-3 text-sm font-semibold transition-all"
                                        />
                                        
                                        <!-- Result Dropdown -->
                                        <div v-if="showResults" class="absolute top-full left-0 right-0 mt-3 bg-white border border-slate-200 rounded-2xl shadow-2xl z-[100] max-h-60 overflow-auto p-2 space-y-1">
                                            <div v-for="p in searchResults" :key="p.Codigo" @click="selecionarPaciente(p)" class="flex items-center justify-between p-3 hover:bg-blue-50 rounded-xl cursor-pointer transition-colors group">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-black text-[10px]">
                                                        {{ p.Nome.substring(0, 2) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-black text-slate-800">{{ p.Nome }}</p>
                                                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">{{ p.Codigo }} | {{ p.Telefone || 'Sem Telefone' }}</p>
                                                    </div>
                                                </div>
                                                <Check class="w-4 h-4 text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="h-px bg-slate-100 w-full"></div>

                                <!-- Personal Data Fields -->
                                <div class="space-y-4">
                                    <!-- Nome -->
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nome do Paciente</label>
                                        <input ref="patientNameInput" v-model="form.nome" class="w-full bg-slate-50 border-slate-100 focus:bg-white focus:ring-2 focus:ring-blue-500/20 rounded-xl px-4 py-2.5 text-sm font-bold uppercase shadow-sm transition-all" />
                                    </div>

                                    <!-- Filiação -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Pai</label>
                                            <input v-model="form.filiacao_pai" class="w-full bg-slate-50 border-slate-100 focus:ring-2 focus:ring-blue-500/20 rounded-xl px-4 py-2.5 text-xs font-semibold shadow-sm transition-all" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Mãe</label>
                                            <input v-model="form.filiacao_mae" class="w-full bg-slate-50 border-slate-100 focus:ring-2 focus:ring-blue-500/20 rounded-xl px-4 py-2.5 text-xs font-semibold shadow-sm transition-all" />
                                        </div>
                                    </div>

                                    <!-- Nasc / Idade / Sexo -->
                                    <div class="grid grid-cols-3 gap-4">
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nascimento</label>
                                            <input type="date" v-model="form.data_nascimento" class="w-full bg-slate-50 border-slate-100 rounded-xl px-3 py-2.5 text-[11px] font-bold shadow-sm" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Idade</label>
                                            <input type="number" v-model="form.idade" class="w-full bg-slate-50 border-slate-100 rounded-xl px-3 py-2.5 text-center text-sm font-black shadow-sm" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Sexo</label>
                                            <select v-model="form.sexo" class="w-full bg-slate-50 border-slate-100 rounded-xl px-3 py-2.5 text-[11px] font-black shadow-sm">
                                                <option value="MASCULINO">MASC</option>
                                                <option value="FEMININO">FEMI</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Contato / Endereço -->
                                    <div class="grid grid-cols-1 gap-4">
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Telefone</label>
                                            <input v-model="form.telefone" placeholder="Ex: 9xx xxx xxx" class="w-full bg-slate-50 border-slate-100 rounded-xl px-4 py-2.5 text-sm font-bold shadow-sm" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Endereço (Rua / Bairro)</label>
                                            <div class="flex gap-2">
                                                <input v-model="form.endereco" class="flex-grow bg-slate-50 border-slate-100 rounded-xl px-4 py-2.5 text-xs font-semibold shadow-sm" />
                                                <button @click="form.endereco = 'SEM'" class="px-3 bg-slate-100 text-slate-500 rounded-xl text-[10px] font-black uppercase hover:bg-slate-200 transition-colors">SEM</button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Billing Info -->
                                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-4">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Financeiro</span>
                                            <div class="flex bg-white p-1 rounded-xl shadow-sm border border-slate-100">
                                                <button @click="form.tipo_paciente = 'Particular'; form.IdSeguradora = '';" :class="form.tipo_paciente === 'Particular' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50'" class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase transition-all">Particular</button>
                                                <button @click="form.tipo_paciente = 'Assegurado'" :class="form.tipo_paciente === 'Assegurado' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:bg-slate-50'" class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase transition-all">Segurado</button>
                                            </div>
                                        </div>
                                        <div v-if="form.tipo_paciente === 'Assegurado'" class="space-y-1.5">
                                            <select v-model="form.IdSeguradora" class="w-full bg-white border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold shadow-sm">
                                                <option value="">Selecione a Seguradora...</option>
                                                <option v-for="s in props.seguradoras" :key="s.Id" :value="s.Id">{{ s.Nome }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Service Selection -->
                                    <div class="space-y-4 bg-blue-50/30 p-4 rounded-2xl border border-blue-100/50">
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest ml-1">Especialidade / Médico</label>
                                            <select v-model="form.IdConsulta" class="w-full bg-white border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold shadow-sm">
                                                <option value="">Tipo de Consulta...</option>
                                                <option v-for="c in props.consultas" :key="c.Id" :value="c.Id">{{ c.Descricao }} ({{ c.Valor }} KZ)</option>
                                            </select>
                                        </div>
                                        <select v-model="form.IdMedico" class="w-full bg-white border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold shadow-sm">
                                            <option value="">Médico Responsável...</option>
                                            <option v-for="m in props.medicos" :key="m.Id" :value="m.Id">{{ m.Nome }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Action Bar -->
                            <div class="p-6 bg-slate-50 border-t border-slate-100 grid grid-cols-2 gap-3">
                                <button 
                                    @click="admitirPaciente('Agendada')" 
                                    class="flex flex-col items-center justify-center gap-1.5 bg-white hover:bg-blue-50 text-blue-600 p-4 rounded-2xl border border-slate-200 hover:border-blue-300 transition-all active:scale-95 shadow-sm group"
                                >
                                    <Calendar class="w-5 h-5 group-hover:scale-110 transition-transform" />
                                    <span class="text-[9px] font-black uppercase tracking-tighter">Agendar</span>
                                </button>
                                <button 
                                    @click="admitirPaciente('Triagem')" 
                                    class="flex flex-col items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-2xl shadow-lg shadow-blue-200 transition-all active:scale-95 group"
                                >
                                    <Activity class="w-5 h-5 group-hover:scale-110 transition-transform" />
                                    <span class="text-[9px] font-black uppercase tracking-tighter">ENVIAR TRIAGEM</span>
                                </button>
                                <button 
                                    @click="admitirPaciente('Laboratorio')" 
                                    class="col-span-2 flex items-center justify-center gap-3 bg-slate-900 hover:bg-black text-white p-4 rounded-2xl transition-all active:scale-95 group"
                                >
                                    <ClipboardList class="w-5 h-5 text-amber-400" />
                                    <span class="text-[10px] font-black uppercase tracking-widest">Solicitar Exames Externos</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Multi-Dashboard -->
                    <div class="lg:col-span-8 space-y-6">
                        
                        <!-- Dashboard 1: Agendamentos do Dia -->
                        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200 overflow-hidden flex flex-col min-h-[400px]">
                            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                                <div>
                                    <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                        <Calendar class="w-4 h-4 text-blue-600" /> Consultas Marcadas
                                    </h2>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Fluxo de atendimentos do período</p>
                                </div>
                                <div class="flex items-center gap-2 bg-white p-1.5 rounded-xl border border-slate-200">
                                    <input type="date" v-model="startDate" class="border-none focus:ring-0 text-[10px] font-bold text-slate-600 p-1" />
                                    <span class="text-slate-300">-</span>
                                    <input type="date" v-model="endDate" class="border-none focus:ring-0 text-[10px] font-bold text-slate-600 p-1" />
                                    <button @click="filtrarPorData" class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all active:scale-90">
                                        <RotateCcw class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex-grow overflow-auto p-4 custom-scrollbar">
                                <table class="w-full border-separate border-spacing-y-2">
                                    <thead>
                                        <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                            <th class="px-4 py-2 text-left">Paciente</th>
                                            <th class="px-4 py-2 text-left">Consulta</th>
                                            <th class="px-4 py-2 text-left">Médico</th>
                                            <th class="px-4 py-2 text-center">Estado</th>
                                            <th class="px-4 py-2 text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="agenda in props.agendamentos" :key="agenda.Id" class="group bg-slate-50/50 hover:bg-blue-50/60 transition-all border border-transparent hover:border-blue-200 rounded-2xl">
                                            <td class="px-4 py-3 first:rounded-l-2xl border-y border-l border-transparent">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-[10px] font-black text-slate-500 shadow-sm border border-slate-100">
                                                        {{ agenda.PacienteNome?.substring(0, 2) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-bold text-slate-800 leading-none">{{ agenda.PacienteNome }}</p>
                                                        <div class="flex items-center gap-2 mt-1.5">
                                                            <span class="text-[8px] font-black text-blue-600 bg-blue-50 px-1 rounded border border-blue-100" title="Cód. Agendamento">{{ agenda.Codigo }}</span>
                                                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">PC: {{ agenda.IdPaciente }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 border-y border-transparent">
                                                <span class="text-[10px] font-bold text-slate-600 bg-white px-2 py-1 rounded-lg border border-slate-100">{{ agenda.Consulta }}</span>
                                            </td>
                                            <td class="px-4 py-3 border-y border-transparent">
                                                <div class="flex items-center gap-2">
                                                    <Stethoscope class="w-3 h-3 text-blue-500" />
                                                    <span class="text-[10px] font-semibold text-slate-500">{{ agenda.MedicoNome || 'Pendente' }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 border-y border-transparent text-center">
                                                <span :class="{
                                                    'bg-amber-100 text-amber-700': agenda.Situacao === 'Agendada',
                                                    'bg-blue-100 text-blue-700': agenda.Situacao === 'Triagem',
                                                    'bg-emerald-100 text-emerald-700': agenda.Situacao === 'Finalizado',
                                                    'bg-slate-200 text-slate-700': agenda.Situacao === 'Laboratorio'
                                                }" class="px-2.5 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest border border-black/5">
                                                    {{ agenda.Situacao }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 last:rounded-r-2xl border-y border-r border-transparent text-right">
                                                <div class="flex justify-end gap-1.5 transition-opacity">
                                                    <button v-if="agenda.Situacao === 'Agendada'" @click="enviarParaTriagem(agenda)" class="p-1.5 bg-emerald-100 text-emerald-700 hover:bg-emerald-600 hover:text-white rounded-lg transition-all shadow-sm" title="Enviar para Triagem">
                                                        <Activity class="w-3.5 h-3.5" />
                                                    </button>
                                                    <button @click="selecionarPaciente({
                                                        Codigo: agenda.IdPaciente,
                                                        Nome: agenda.PacienteNome,
                                                        IdSegura: agenda.IdSeguradora
                                                    })" class="p-1.5 bg-white text-slate-600 hover:bg-blue-600 hover:text-white rounded-lg transition-all shadow-sm border border-slate-200" title="Editar">
                                                        <FileText class="w-3.5 h-3.5" />
                                                    </button>
                                                    <a :href="route('hospitalar.consultorio.imprimir.ficha', agenda.Codigo)" 
                                                       target="_blank"
                                                       class="p-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-all shadow-sm border border-slate-200" 
                                                       title="Imprimir Ficha Médica">
                                                        <Printer class="w-3.5 h-3.5" />
                                                    </a>
                                                    <a :href="route('hospitalar.consultorio.imprimir.ficha', { id: agenda.Codigo, download: 1 })" 
                                                       target="_blank"
                                                       class="p-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-lg transition-all shadow-sm border border-slate-200" 
                                                       title="Baixar Ficha (PDF)">
                                                        <Download class="w-3.5 h-3.5" />
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Secondary Split Dashboard -->
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                            
                            <!-- Dashboard 2: Exames por Pagar -->
                            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200 overflow-hidden flex flex-col h-[350px]">
                                <div class="px-5 py-3 bg-amber-50/50 border-b border-amber-100 flex items-center justify-between">
                                    <h3 class="text-[10px] font-black text-amber-800 uppercase tracking-widest flex items-center gap-2">
                                        <CreditCard class="w-4 h-4" /> Exames por Pagar (Caixa)
                                    </h3>
                                    <span class="bg-amber-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full shadow-sm">{{ props.examesPendentes?.length || 0 }}</span>
                                </div>
                                <div class="flex-grow overflow-auto p-4 custom-scrollbar">
                                    <table class="w-full">
                                        <thead class="sticky top-0 bg-white z-10">
                                            <tr class="text-[8px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100">
                                                <th class="py-2 text-left">Paciente</th>
                                                <th class="py-2 text-left">Exame</th>
                                                <th class="py-2 text-right">Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="exame in props.examesPendentes" :key="exame.Id" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                                <td class="py-3">
                                                    <p class="text-[10px] font-bold text-slate-700 leading-tight">{{ exame.PACIENTE }}</p>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span class="text-[8px] font-black text-blue-600 bg-blue-50 px-1 rounded border border-blue-100">{{ exame.AGENDA }}</span>
                                                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">PC: {{ exame.PROCESSO }}</span>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="text-[9px] font-semibold text-slate-500 truncate block max-w-[120px]">{{ exame.EXAME }}</span>
                                                </td>
                                                <td class="py-3 text-right">
                                                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-[9px] font-black uppercase shadow-sm transition-all active:scale-95">PAGAR</button>
                                                </td>
                                            </tr>
                                            <tr v-if="!props.examesPendentes?.length">
                                                <td colspan="3" class="py-20 text-center opacity-30">
                                                    <CreditCard class="w-10 h-10 mx-auto mb-2" />
                                                    <p class="text-[9px] font-black uppercase">Nenhum exame pendente</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Dashboard 3: Área de Internamento -->
                            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200 overflow-hidden flex flex-col h-[350px]">
                                <div class="px-5 py-3 bg-emerald-50/50 border-b border-emerald-100 flex items-center justify-between">
                                    <h3 class="text-[10px] font-black text-emerald-800 uppercase tracking-widest flex items-center gap-2">
                                        <Activity class="w-4 h-4" /> Área de Internamento
                                    </h3>
                                    <span class="bg-emerald-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full shadow-sm">{{ props.internamentosPendentes?.length || 0 }}</span>
                                </div>
                                <div class="flex-grow overflow-auto p-4 custom-scrollbar">
                                    <table class="w-full">
                                        <thead class="sticky top-0 bg-white z-10">
                                            <tr class="text-[8px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100">
                                                <th class="py-2 text-left">Paciente</th>
                                                <th class="py-2 text-left">Data</th>
                                                <th class="py-2 text-right">Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="interna in props.internamentosPendentes" :key="interna.Codigo" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                                <td class="py-3">
                                                    <p class="text-[10px] font-bold text-slate-700 leading-tight">{{ interna.Paciente }}</p>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span class="text-[8px] font-black text-blue-600 bg-blue-50 px-1 rounded border border-blue-100">{{ interna.Codigo }}</span>
                                                        <span class="text-[8px] text-slate-400 font-medium tracking-tighter">{{ interna.Consulta }}</span>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-[9px] font-medium text-slate-500">
                                                    {{ new Date(interna.DataInternamento).toLocaleDateString('pt-PT') }}
                                                </td>
                                                <td class="py-3 text-right">
                                                    <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-[9px] font-black uppercase shadow-sm transition-all active:scale-95">INTERNAR</button>
                                                </td>
                                            </tr>
                                            <tr v-if="!props.internamentosPendentes?.length">
                                                <td colspan="3" class="py-20 text-center opacity-30">
                                                    <Users class="w-10 h-10 mx-auto mb-2" />
                                                    <p class="text-[9px] font-black uppercase">Nenhum internamento pendente</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification System -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="notification.show" class="fixed bottom-6 right-6 z-[1000] flex items-center gap-4 px-6 py-4 bg-slate-900 text-white rounded-2xl shadow-2xl border border-white/10 backdrop-blur-xl">
                <div :class="{
                    'bg-emerald-500': notification.type === 'success',
                    'bg-blue-500': notification.type === 'info',
                    'bg-red-500': notification.type === 'error'
                }" class="p-1.5 rounded-lg">
                    <Check v-if="notification.type === 'success'" class="w-4 h-4 text-white" />
                    <Activity v-else-if="notification.type === 'info'" class="w-4 h-4 text-white" />
                    <X v-else class="w-4 h-4 text-white" />
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest leading-none">{{ notification.message }}</p>
                    <p class="text-[8px] text-slate-400 mt-1 uppercase font-bold">Notificação do Sistema</p>
                </div>
            </div>
        </Transition>
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
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.2s;
}

select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}
</style>



