<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { Search, CheckCircle2, AlertCircle, Printer, PlusCircle, ClipboardCheck, Plus, LayoutGrid, Users, Stethoscope, Syringe } from 'lucide-vue-next';

const props = defineProps({
    internados: { type: Array, default: () => [] },
    historico: { type: Array, default: () => [] }
});

const searchTerm = ref('');
const histSearchTerm = ref('');
const prescricoesSearchTerm = ref('');
const selectedPaciente = ref(null);
const showPrescricaoModal = ref(false);
const showAtoModal = ref(false);
const showSinaisModal = ref(false);
const showAltaModal = ref(false);

const atoType = ref('medico'); // 'medico' or 'enfermagem'

const prescricaoForm = ref({
    Descricao: '',
    Observacao: '',
    Tipo: 'Internamento'
});

const atoForm = ref({
    descricao: ''
});

const sinaisForm = ref({
    Peso: '0',
    Temperatura: '0',
    PressaoArterial: '0',
    PressaoArterialBE: '0',
    FrequenciaCardioca: '0',
    PulsoBE: '0',
    FrequenciaRespiratoria: '0',
    SituacaoOxigenio: '0',
    Obs: '',
    Enfermeiro: ''
});

const altaForm = ref({
    Operado: '',
    Complicacoes: '',
    Repouso: '',
    Obs: ''
});

const details = ref({ prescricoes: [], atosMedicos: [], atosEnfermagem: [], sinaisVitais: [] });
const isLoading = ref(false);

const filteredInternados = computed(() => {
    if (!searchTerm.value) return props.internados;
    const term = searchTerm.value.toLowerCase();
    return props.internados.filter(p => 
        p.PacienteNome?.toLowerCase().includes(term) ||
        p.Codigo?.toLowerCase().includes(term)
    );
});

const filteredHistorico = computed(() => {
    if (!histSearchTerm.value) return props.historico;
    const term = histSearchTerm.value.toLowerCase();
    return props.historico.filter(h => 
        h.PacienteNome?.toLowerCase().includes(term) ||
        h.Codigo?.toLowerCase().includes(term)
    );
});

const filteredPrescricoes = computed(() => {
    if (!prescricoesSearchTerm.value) return details.value.prescricoes;
    const term = prescricoesSearchTerm.value.toLowerCase();
    return details.value.prescricoes.filter(p => 
        p.Descricao?.toLowerCase().includes(term) ||
        p.Medico?.toLowerCase().includes(term)
    );
});

const selecionarPaciente = async (paciente) => {
    selectedPaciente.value = paciente;
    isLoading.value = true;
    try {
        const response = await axios.get(route('hospitalar.internamento.details', paciente.Codigo));
        details.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
    } finally {
        isLoading.value = false;
    }
};

const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmTitle = ref('');
const confirmText = ref('');

const toast = ref({ show: false, message: '', type: 'success' });

const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 4000);
};

const openConfirm = (title, text, action) => {
    confirmTitle.value = title;
    confirmText.value = text;
    confirmAction.value = action;
    showConfirmModal.value = true;
};

const executeConfirm = () => {
    if (confirmAction.value) confirmAction.value();
    showConfirmModal.value = false;
};

const submitPrescricao = async () => {
    if (!selectedPaciente.value) return;
    try {
        await axios.post(route('hospitalar.internamento.prescricao.store'), {
            ...prescricaoForm.value,
            IdAgenda: selectedPaciente.value.Codigo
        });
        showPrescricaoModal.value = false;
        prescricaoForm.value = { Descricao: '', Observacao: '', Tipo: 'Internamento' };
        showToast('Prescrição gravada com sucesso');
        selecionarPaciente(selectedPaciente.value);
    } catch (error) {
        showToast('Erro ao salvar prescrição', 'error');
    }
};

const togglePrescricaoStatus = async (prescricao, campo) => {
    try {
        const novoValor = prescricao[campo] !== 'True';
        await axios.post(route('hospitalar.internamento.prescricao.toggle', prescricao.Id), {
            campo: campo,
            valor: novoValor
        });
        prescricao[campo] = novoValor ? 'True' : 'False';
        showToast('Status atualizado');
        const response = await axios.get(route('hospitalar.internamento.details', selectedPaciente.value.Codigo));
        details.value = response.data;
    } catch (error) {
        showToast('Erro ao atualizar status', 'error');
    }
};

const submitAto = async () => {
    if (!selectedPaciente.value) return;
    try {
        await axios.post(route('hospitalar.internamento.ato'), {
            ...atoForm.value,
            tipo: atoType.value,
            IdAgenda: selectedPaciente.value.Codigo
        });
        showAtoModal.value = false;
        atoForm.value = { descricao: '' };
        showToast('Ato registrado com sucesso');
        selecionarPaciente(selectedPaciente.value);
    } catch (error) {
        showToast('Erro ao salvar ato', 'error');
    }
};

const submitSinais = async () => {
    if (!selectedPaciente.value) return;
    try {
        await axios.post(route('hospitalar.internamento.sinais.store'), {
            ...sinaisForm.value,
            IdAgenda: selectedPaciente.value.Codigo
        });
        showSinaisModal.value = false;
        sinaisForm.value = { Peso: '', Temperatura: '', PressaoArterial: '', FrequenciaCardioca: '', FrequenciaRespiratoria: '', SituacaoOxigenio: '', Obs: '' };
        showToast('Sinais vitais registrados');
        selecionarPaciente(selectedPaciente.value);
    } catch (error) {
        showToast('Erro ao salvar sinais vitais', 'error');
    }
};

const submitAlta = async () => {
    if (!selectedPaciente.value) return;
    try {
        await axios.post(route('hospitalar.internamento.alta', selectedPaciente.value.Codigo), altaForm.value);
        showAltaModal.value = false;
        selectedPaciente.value = null;
        showToast('Alta processada com sucesso');
        recarregar();
    } catch (error) {
        showToast('Erro ao processar alta', 'error');
    }
};

const handleAltaConfirm = () => {
    openConfirm(
        'Confirmar Alta Médica', 
        'Tem certeza que deseja dar alta a este paciente? Esta ação atualizará o status do agendamento.',
        submitAlta
    );
};

const recarregar = async () => {
    router.reload({ 
        only: ['internados', 'historico'],
        onSuccess: () => {
            if (selectedPaciente.value) {
                selecionarPaciente(selectedPaciente.value);
            }
        }
    });
};

const openAtoModal = (type) => {
    atoType.value = type;
    showAtoModal.value = true;
};

const openSinaisModal = () => {
    sinaisForm.value = {
        Peso: '0',
        Temperatura: '0',
        PressaoArterial: '0',
        PressaoArterialBE: '0',
        FrequenciaCardioca: '0',
        PulsoBE: '0',
        FrequenciaRespiratoria: '0',
        SituacaoOxigenio: '0',
        Obs: '',
        Enfermeiro: props.auth?.user?.name || 'Enfermeiro'
    };
    showSinaisModal.value = true;
};

const imprimirProcesso = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.internamento.imprimir.processo', selectedPaciente.value.Codigo), '_blank');
};

const imprimirAtosEnfermagem = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.internamento.imprimir.atos-enfermagem', selectedPaciente.value.Codigo), '_blank');
};

const imprimirRelatorioVitais = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.internamento.imprimir.vitais', selectedPaciente.value.Codigo), '_blank');
};
</script>

<template>
    <Head title="Internamento" />

    <DashboardLayout>
        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="toast.show" 
                class="fixed bottom-10 right-10 z-[600] p-4 rounded bg-white shadow-2xl border-l-4"
                :class="toast.type === 'success' ? 'border-green-500' : 'border-red-500'">
                <div class="flex items-center gap-3">
                    <CheckCircle2 v-if="toast.type === 'success'" class="w-5 h-5 text-green-500" />
                    <AlertCircle v-else class="w-5 h-5 text-red-500" />
                    <span class="text-xs font-bold">{{ toast.message }}</span>
                </div>
            </div>
        </Transition>

        <div class="h-[calc(100vh-64px)] flex flex-col bg-[#f4f4f4] text-[11px] text-slate-800 overflow-hidden font-sans">

            <!-- Segmented Top Action Bar (Matches Screenshot) -->
            <div class="flex items-center gap-1 p-1 bg-white border-b border-slate-300 shrink-0 h-14">
                <!-- Group 1: General -->
                <div class="flex gap-1 pr-2 border-r border-slate-200">
                    <button @click="recarregar" class="bg-[#2196F3] text-white px-3 py-2 font-bold hover:bg-[#1976D2] text-[10px] min-w-[120px]">
                        Atualizar Registos
                    </button>
                    <button @click="imprimirProcesso" :disabled="!selectedPaciente" class="bg-[#2196F3] text-white px-3 py-2 font-bold hover:bg-[#1976D2] text-[10px] min-w-[120px] disabled:opacity-50">
                        Imprimir Processo Clínico
                    </button>
                </div>

                <!-- Group 2: Área dos Médicos -->
                <div class="flex flex-col flex-1 mx-1 border border-blue-200 rounded-sm">
                    <div class="bg-[#f0f7ff] text-[#2196F3] text-center font-black text-[9px] uppercase py-0.5 border-b border-blue-100">Área dos Médicos</div>
                    <div class="flex gap-0.5 p-0.5">
                        <button @click="showPrescricaoModal = true" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Prescrições Médicas</button>
                        <button @click="openAtoModal('medico')" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Registo de Actos Médicos</button>
                        <button @click="showAltaModal = true" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Título de Alta</button>
                    </div>
                </div>

                <!-- Group 3: Área dos Enfermeiros -->
                <div class="flex flex-col flex-1 mx-1 border border-blue-200 rounded-sm">
                    <div class="bg-[#f0f7ff] text-[#2196F3] text-center font-black text-[9px] uppercase py-0.5 border-b border-blue-100">Área dos Enfermeiros</div>
                    <div class="flex gap-0.5 p-0.5">
                        <button class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Cumprimento (Enfermagem)</button>
                        <button @click="openSinaisModal" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Controlo de Sinais Vitais</button>
                        <button @click="openAtoModal('enfermagem')" class="flex-1 bg-[#2196F3] text-white py-1.5 font-bold hover:bg-[#1976D2] text-[9px]">Registo de Actos da Enfermaria</button>
                    </div>
                </div>
            </div>

            <!-- Dashboard Sub-Header (Blue Bar) -->
            <div class="bg-[#000080] h-6 flex items-center px-4 shrink-0 shadow-md">
                <div class="text-white font-bold text-[10px] tracking-widest flex items-center gap-2">
                    <Users class="w-3.5 h-3.5" />
                    GESTÃO DE INTERNAMENTO HOSPITALAR
                    <span v-if="selectedPaciente" class="ml-4 bg-white/20 px-2 py-0.5 rounded">PACIENTE ATUAL: {{ selectedPaciente.PacienteNome }}</span>
                </div>
            </div>

            <!-- Main Layout (Matches Screenshot) -->
            <div class="flex-1 flex overflow-hidden p-1 gap-1">
                
                <!-- Left Column: Patient Lists (Large Area) -->
                <div class="w-[65%] flex flex-col gap-1 overflow-hidden">
                    
                    <!-- Top: Pacientes Internados -->
                    <div class="flex-[3] bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-[#f8f9fa] border-b border-slate-200 p-1 flex items-center">
                             <div class="relative flex-1 max-w-sm">
                                <Search class="w-3 h-3 absolute left-2 top-1.5 text-slate-400" />
                                <input v-model="searchTerm" type="text" placeholder="Drag a column header here to group by that column" class="w-full pl-7 pr-2 py-1 text-[10px] bg-white border border-slate-200 outline-none focus:border-blue-400" />
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse border-slate-200">
                                <thead class="sticky top-0 bg-[#eef2f7] border-b border-slate-300 z-10 text-[10px] text-slate-600">
                                    <tr>
                                        <th class="p-2 border-r border-slate-200 font-bold">Codigo</th>
                                        <th class="p-2 border-r border-slate-200 font-bold">Paciente</th>
                                        <th class="p-2 border-r border-slate-200 font-bold">Consulta</th>
                                        <th class="p-2 border-r border-slate-200 font-bold">Data Internamento</th>
                                        <th class="p-2 border-r border-slate-200 font-bold">Tipo</th>
                                        <th class="p-2 font-bold">Medico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredInternados" :key="p.Codigo" 
                                        @click="selecionarPaciente(p)"
                                        class="border-b border-slate-100 hover:bg-blue-50 cursor-pointer text-[10px] transition-colors"
                                        :class="{'bg-[#e3f2fd] border-l-4 border-l-[#2196F3]': selectedPaciente?.Codigo === p.Codigo}">
                                        <td class="p-2 border-r border-slate-100/50">{{ p.Codigo }}</td>
                                        <td class="p-2 border-r border-slate-100/50 font-bold">{{ p.PacienteNome }}</td>
                                        <td class="p-2 border-r border-slate-100/50">{{ p.DescricaoConsulta || 'N/D' }}</td>
                                        <td class="p-2 border-r border-slate-100/50">{{ p.DataInternamento }}</td>
                                        <td class="p-2 border-r border-slate-100/50">{{ p.Tipo || 'Internamento' }}</td>
                                        <td class="p-2">{{ p.MedicoNome }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bottom: Histórico -->
                    <div class="flex-1 bg-white border border-slate-300 flex flex-col overflow-hidden min-h-[150px]">
                        <div class="bg-[#f0f0f0] px-2 py-1 font-bold text-slate-600 border-b border-slate-300 text-[10px]">Histórico de Pacientes Internados</div>
                        <div class="p-1 bg-white border-b border-slate-200">
                            <input v-model="histSearchTerm" type="text" placeholder="Enter text to search..." class="w-64 border border-slate-200 px-2 py-1 text-[10px]" />
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-[9px]">
                                <thead class="sticky top-0 bg-[#f8f8f8] border-b border-slate-200 z-10 text-slate-500">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-200">Codigo</th>
                                        <th class="p-1.5 border-r border-slate-200">Tipo</th>
                                        <th class="p-1.5 border-r border-slate-200">Paciente</th>
                                        <th class="p-1.5 border-r border-slate-200">Data Entrada</th>
                                        <th class="p-1.5">Relatorio Processo...</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="h in filteredHistorico" :key="h.Codigo" class="border-b border-slate-50 hover:bg-slate-50">
                                        <td class="p-1.5 border-r border-slate-50">{{ h.Codigo }}</td>
                                        <td class="p-1.5 border-r border-slate-50">Histórico</td>
                                        <td class="p-1.5 border-r border-slate-50">{{ h.PacienteNome }}</td>
                                        <td class="p-1.5 border-r border-slate-50">{{ h.DataEntrada }}</td>
                                        <td class="p-1.5 text-blue-600 underline cursor-pointer">Visualizar</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Vertical Clinical Records (Matches Screenshot Stack) -->
                <div class="w-[35%] flex flex-col gap-1 overflow-hidden">
                    
                    <!-- 1. Prescrições Médicas -->
                    <div class="flex-[1.5] bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-[#f0f0f0] px-2 py-1 font-bold text-slate-700 border-b border-slate-300 flex justify-between items-center text-[10px]">
                            <span>Prescrições Médicas</span>
                        </div>
                        <div class="p-1.5 bg-white border-b border-slate-200 flex gap-1">
                            <input v-model="prescricoesSearchTerm" type="text" placeholder="Enter text to search..." class="flex-1 border border-slate-200 px-2 py-1 text-[10px]" />
                            <button class="bg-[#f8f8f8] border border-slate-300 px-3 py-1 hover:bg-slate-100 text-[10px]">Find</button>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-[10px]">
                                <thead class="sticky top-0 bg-[#f4f4f4] border-b border-slate-200 z-10 text-slate-500">
                                    <tr>
                                        <th class="p-1 border-r border-slate-200 text-center w-6">F</th>
                                        <th class="p-1.5 border-r border-slate-200">Medico</th>
                                        <th class="p-1.5 border-r border-slate-200">Descricao</th>
                                        <th class="p-1.5 border-r border-slate-200">Enfermeiro</th>
                                        <th class="p-1 border-r border-slate-200 text-center w-6">M</th>
                                        <th class="p-1 border-r border-slate-200 text-center w-6">T</th>
                                        <th class="p-1 border-r border-slate-200 text-center w-6">N</th>
                                        <th class="p-1.5">Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredPrescricoes" :key="p.Id" class="border-b border-slate-50">
                                        <td class="p-1 border-r border-slate-50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento')" />
                                        </td>
                                        <td class="p-1.5 border-r border-slate-50 truncate max-w-[80px]" :title="p.Medico">{{ p.Medico }}</td>
                                        <td class="p-1.5 border-r border-slate-50 truncate max-w-[120px]" :title="p.Descricao">{{ p.Descricao }}</td>
                                        <td class="p-1.5 border-r border-slate-50 italic text-slate-400">{{ p.Infermeiro || '---' }}</td>
                                        <td class="p-1 border-r border-slate-50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento1 === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento1')" />
                                        </td>
                                        <td class="p-1 border-r border-slate-50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento2 === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento2')" />
                                        </td>
                                        <td class="p-1 border-r border-slate-50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento3 === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento3')" />
                                        </td>
                                        <td class="p-1.5 whitespace-nowrap">{{ p.DataInternamento || p.CREATED_AT }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. Registo de Visitas e Actos Médicos -->
                    <div class="flex-1 bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-[#f0f0f0] px-2 py-1 font-bold text-slate-700 border-b border-slate-300 text-[10px]">Registo de Visitas e Actos Médicos</div>
                        <div class="p-1 bg-white border-b border-slate-200">
                             <div class="relative">
                                <Search class="w-3 h-3 absolute left-2 top-1.5 text-slate-400" />
                                <input type="text" placeholder="Drag a column header here to group by that column" class="w-full pl-7 pr-2 py-1 text-[9px] bg-white border border-slate-200 outline-none" />
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-[9px]">
                                <thead class="sticky top-0 bg-[#f8f8f8] border-b border-slate-200 z-10 text-slate-500">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-200">Data</th>
                                        <th class="p-1.5 border-r border-slate-200">Medico</th>
                                        <th class="p-1.5">Descrição</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="a in details.atosMedicos" :key="a.Id" class="border-b border-slate-50">
                                        <td class="p-1.5 border-r border-slate-50 whitespace-nowrap">{{ a.DataAto || a.CREATED_AT }}</td>
                                        <td class="p-1.5 border-r border-slate-50">{{ a.Medico }}</td>
                                        <td class="p-1.5 italic text-slate-500">{{ a.Descricao }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. Controlo de Sinais Vitais -->
                    <div class="flex-1 bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-[#f0f0f0] px-2 py-1 font-bold text-slate-700 border-b border-slate-300 text-[10px]">Controlo de Sinais Vitais</div>
                        <div class="p-1 bg-white border-b border-slate-200">
                             <div class="relative">
                                <Search class="w-3 h-3 absolute left-2 top-1.5 text-slate-400" />
                                <input type="text" placeholder="Drag a column header here to group by that column" class="w-full pl-7 pr-2 py-1 text-[9px] bg-white border border-slate-200 outline-none" />
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-[9px]">
                                <thead class="sticky top-0 bg-[#f8f8f8] border-b border-slate-200 z-10 text-slate-500">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-200">Cod. Paciente</th>
                                        <th class="p-1.5 border-r border-slate-200">Data</th>
                                        <th class="p-1.5 border-r border-slate-200">Medico</th>
                                        <th class="p-1.5 border-r border-slate-200 text-center w-8">Temp.</th>
                                        <th class="p-1.5 border-r border-slate-200 text-center w-8">Peso</th>
                                        <th class="p-1.5 text-center w-12">Pressao</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="v in details.sinaisVitais" :key="v.Id" class="border-b border-slate-50">
                                        <td class="p-1.5 border-r border-slate-50">{{ selectedPaciente?.Codigo }}</td>
                                        <td class="p-1.5 border-r border-slate-50 whitespace-nowrap">{{ v.DataAgendamento || v.CREATED_AT }}</td>
                                        <td class="p-1.5 border-r border-slate-50">{{ selectedPaciente?.PacienteNome }}</td>
                                        <td class="p-1.5 border-r border-slate-50 text-center font-bold text-red-600">{{ v.Temperatura }}°</td>
                                        <td class="p-1.5 border-r border-slate-50 text-center">{{ v.Peso }}kg</td>
                                        <td class="p-1.5 text-center">{{ v.PressaoArterial }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        
        <!-- Nursing Modal (Refined to be unified but opened via button) -->
        <div v-if="showAtoModal && atoType === 'enfermagem'" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[500] p-4">
            <div class="bg-white w-full max-w-5xl shadow-2xl border-4 border-[#000080] flex flex-col h-[700px] rounded-sm overflow-hidden animate-in zoom-in duration-200">
                <div class="bg-[#000080] text-white p-2.5 font-bold text-xs text-center uppercase tracking-widest relative">
                    REGISTO DE VISITAS E ATOS DA ENFERMARIA
                    <button @click="showAtoModal = false" class="absolute right-3 top-2 hover:bg-red-600 rounded px-2 transition-colors">&times;</button>
                </div>
                
                <div class="flex-1 flex flex-col bg-[#e9e9e9] p-6 overflow-hidden">
                    <div class="text-center text-slate-800 font-bold text-2xl mb-6">Registo de Visitas e Atos da Enfermagem</div>
                    
                    <div class="flex gap-4 mb-6 shrink-0">
                        <button @click="submitAto" class="bg-white border-2 border-slate-400 p-3 flex flex-col items-center min-w-[130px] hover:bg-green-50 hover:border-green-500 transition-all rounded shadow-md group">
                            <Plus class="w-12 h-12 text-green-600 mb-1" />
                            <span class="text-[11px] font-black uppercase text-slate-700">Adicionar</span>
                        </button>
                        <button @click="imprimirAtosEnfermagem" class="bg-white border-2 border-slate-400 p-3 flex flex-col items-center min-w-[130px] hover:bg-blue-50 hover:border-blue-500 transition-all rounded shadow-md group">
                            <Printer class="w-12 h-12 text-slate-600 mb-1" />
                            <span class="text-[11px] font-black uppercase text-slate-700">Imprimir</span>
                        </button>
                    </div>

                    <div class="flex gap-8 mb-6 shrink-0 bg-white/50 p-4 border border-slate-300 rounded">
                        <div class="w-1/3 space-y-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-1">Data e Hora</label>
                                <div class="w-full border-2 border-slate-300 p-2 text-xs bg-white font-bold">{{ new Date().toLocaleString() }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-1">Paciente</label>
                                <div class="w-full border-2 border-slate-300 p-2 text-xs bg-[#f0f0f0] font-black">{{ selectedPaciente?.PacienteNome }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-1">Enfermeiro</label>
                                <div class="w-full border-2 border-slate-300 p-2 text-xs bg-white font-bold">{{ $page.props.auth.user.name }}</div>
                            </div>
                        </div>
                        <div class="flex-1 flex flex-col">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-1">Atos Realizados</label>
                            <textarea v-model="atoForm.descricao" class="flex-1 border-2 border-slate-300 p-3 text-sm focus:border-blue-500 outline-none resize-none bg-white rounded shadow-inner" placeholder="Descreva as intervenções..."></textarea>
                        </div>
                    </div>

                    <div class="flex-1 bg-white border-2 border-slate-400 flex flex-col overflow-hidden rounded shadow-xl">
                        <div class="bg-white text-slate-400 text-[10px] p-2 border-b border-slate-200 flex justify-between items-center italic font-bold">
                            <span>Drag a column header here to group by that column</span>
                            <Search class="w-4 h-4" />
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="sticky top-0 bg-[#f4f4f4] border-b-2 border-slate-300 z-10 font-black uppercase text-slate-600">
                                    <tr>
                                        <th class="p-3 border-r border-slate-300 w-24">AGENDA</th>
                                        <th class="p-3 border-r border-slate-300 w-44">DATA_HORA</th>
                                        <th class="p-3 border-r border-slate-300">DESCRICAO</th>
                                        <th class="p-3 border-r border-slate-300 w-56">PACIENTE</th>
                                        <th class="p-3 w-56">ENFERMEIRO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="a in details.atosEnfermagem" :key="a.Id" class="border-b border-slate-100 hover:bg-blue-50 transition-colors">
                                        <td class="p-3 border-r border-slate-100 text-slate-400 font-mono">{{ selectedPaciente?.Codigo }}</td>
                                        <td class="p-3 border-r border-slate-100">{{ a.DataAto || a.CREATED_AT }}</td>
                                        <td class="p-3 border-r border-slate-100 text-blue-800 italic">{{ a.Descricao }}</td>
                                        <td class="p-3 border-r border-slate-100">{{ selectedPaciente?.PacienteNome }}</td>
                                        <td class="p-3 font-bold text-slate-500 uppercase text-[10px]">{{ a.Enfermeiro }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Ato Modal -->
        <div v-if="showAtoModal && atoType === 'medico'" class="fixed inset-0 bg-black/70 flex items-center justify-center z-[500] p-4">
            <div class="bg-white w-full max-w-lg shadow-2xl rounded-sm overflow-hidden border-2 border-blue-900 animate-in zoom-in duration-200">
                <div class="bg-[#000080] text-white p-4 font-bold text-center uppercase tracking-widest relative">
                    Registo de Visita Médica
                    <button @click="showAtoModal = false" class="absolute right-4 top-3 hover:bg-red-600 rounded px-2 transition-colors">&times;</button>
                </div>
                <div class="p-6">
                    <label class="block text-[11px] font-black uppercase text-slate-500 mb-2">Evolução Clínica / Ato Médico</label>
                    <textarea v-model="atoForm.descricao" rows="10" class="w-full border-2 border-slate-300 p-4 text-sm font-bold focus:border-blue-600 outline-none resize-none rounded bg-slate-50 shadow-inner" placeholder="Registe a evolução do paciente..."></textarea>
                </div>
                <div class="p-4 bg-slate-100 border-t border-slate-200 flex justify-end gap-2">
                    <button @click="showAtoModal = false" class="px-6 py-2 border-2 border-slate-300 hover:bg-slate-200 text-xs font-black uppercase rounded transition-all">Cancelar</button>
                    <button @click="submitAto" class="px-6 py-2 bg-blue-600 text-white hover:bg-blue-800 text-xs font-black uppercase rounded shadow-lg transition-all">Gravar Registo</button>
                </div>
            </div>
        </div>

        <!-- Sinais Vitais Modal (Legacy Style) -->
        <div v-if="showSinaisModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[500] p-4">
            <div class="bg-[#f0f0f0] w-full max-w-6xl shadow-2xl rounded-sm border-2 border-slate-400 overflow-hidden flex flex-col h-[650px]">
                <div class="bg-[#000080] text-white p-2 font-bold flex justify-between items-center uppercase text-xs tracking-widest">
                    <span>Controlo de sinais vitais</span>
                    <button @click="showSinaisModal = false" class="hover:bg-red-600 px-2 rounded transition-colors">&times;</button>
                </div>
                
                <div class="bg-[#000080] text-white text-center py-1 text-sm font-black border-t border-white/20">
                    {{ selectedPaciente?.Codigo }}
                </div>

                <div class="flex-1 flex overflow-hidden p-4 gap-4">
                    <!-- Left Column: Info and Form -->
                    <div class="w-1/2 flex flex-col gap-4 overflow-y-auto custom-scrollbar pr-2">
                        <div class="space-y-1 mb-4">
                            <div class="text-xl font-black text-slate-800">{{ selectedPaciente?.Codigo }}</div>
                            <div class="text-xl font-black text-slate-800">{{ selectedPaciente?.PacienteNome }}</div>
                            <div class="text-slate-500 font-bold">...</div>
                        </div>

                        <div class="grid grid-cols-3 items-center gap-2">
                            <div class="font-black text-[11px] uppercase text-slate-600">ENFERMEIRO</div>
                            <div class="col-span-2 border border-slate-300 bg-white p-1.5 text-xs font-bold uppercase shadow-sm">{{ $page.props.auth.user.name }}</div>

                            <div class="font-bold text-xs text-slate-700">Peso:</div>
                            <div class="col-span-2"><input v-model="sinaisForm.Peso" type="text" class="w-full border border-slate-300 p-1.5 text-right text-xs font-bold outline-none focus:border-blue-500" /></div>

                            <div class="font-bold text-xs text-slate-700">Temperatura corporal:</div>
                            <div class="col-span-2"><input v-model="sinaisForm.Temperatura" type="text" class="w-full border border-slate-300 p-1.5 text-right text-xs font-bold outline-none focus:border-blue-500" /></div>

                            <div class="font-bold text-xs text-slate-700">Tensão Arterial BD:</div>
                            <div class="col-span-2"><input v-model="sinaisForm.PressaoArterial" type="text" class="w-full border border-slate-300 p-1.5 text-right text-xs font-bold outline-none focus:border-blue-500" /></div>

                            <div class="font-bold text-xs text-slate-700">Tensão Arterial BE:</div>
                            <div class="col-span-2"><input v-model="sinaisForm.PressaoArterialBE" type="text" class="w-full border border-slate-300 p-1.5 text-right text-xs font-bold outline-none focus:border-blue-500" /></div>

                            <div class="font-bold text-xs text-slate-700">Pulsação BD:</div>
                            <div class="col-span-2"><input v-model="sinaisForm.FrequenciaCardioca" type="text" class="w-full border border-slate-300 p-1.5 text-right text-xs font-bold outline-none focus:border-blue-500" /></div>

                            <div class="font-bold text-xs text-slate-700">Pulsação BE:</div>
                            <div class="col-span-2"><input v-model="sinaisForm.PulsoBE" type="text" class="w-full border border-slate-300 p-1.5 text-right text-xs font-bold outline-none focus:border-blue-500" /></div>

                            <div class="font-bold text-xs text-slate-700">Frequência respiratória:</div>
                            <div class="col-span-2"><input v-model="sinaisForm.FrequenciaRespiratoria" type="text" class="w-full border border-slate-300 p-1.5 text-right text-xs font-bold outline-none focus:border-blue-500" /></div>

                            <div class="font-bold text-xs text-slate-700">Situação de oxigênio (oximetria):</div>
                            <div class="col-span-2"><input v-model="sinaisForm.SituacaoOxigenio" type="text" class="w-full border border-slate-300 p-1.5 text-right text-xs font-bold outline-none focus:border-blue-500" /></div>

                            <div class="font-bold text-xs text-slate-700 self-start mt-1">Observação:</div>
                            <div class="col-span-2"><textarea v-model="sinaisForm.Obs" class="w-full border border-slate-300 p-2 text-xs font-bold outline-none focus:border-blue-500 h-16 resize-none"></textarea></div>
                        </div>

                        <button @click="submitSinais" class="w-full py-2.5 bg-gradient-to-b from-blue-400 to-blue-600 text-white font-black uppercase text-sm rounded shadow-lg hover:from-blue-500 hover:to-blue-700 transition-all border border-blue-700">
                            GRAVAR DADOS DA TRIAGEM
                        </button>
                    </div>

                    <!-- Right Column: History -->
                    <div class="w-1/2 border border-slate-300 bg-white flex flex-col overflow-hidden">
                        <div class="bg-slate-100 p-2 text-center font-bold text-xs border-b border-slate-300">Histórico de Triagem do Paciente</div>
                        
                        <div class="p-3 bg-slate-50 border-b border-slate-200">
                            <div class="text-[10px] font-bold text-slate-500 uppercase mb-2">Pesquisar Triagem por Data</div>
                            <div class="flex gap-2">
                                <div class="flex-1 flex items-center gap-2">
                                    <span class="text-[10px] font-bold">Data Inicio</span>
                                    <input type="date" class="flex-1 border border-slate-300 p-1 text-xs outline-none" />
                                </div>
                                <div class="flex-1 flex items-center gap-2">
                                    <span class="text-[10px] font-bold">Data Final</span>
                                    <input type="date" class="flex-1 border border-slate-300 p-1 text-xs outline-none" />
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 overflow-auto custom-scrollbar">
                            <table class="w-full text-[10px] border-collapse">
                                <thead class="bg-slate-100 sticky top-0 font-bold border-b border-slate-300">
                                    <tr>
                                        <th class="p-2 border-r border-slate-200">Cod. Paciente</th>
                                        <th class="p-2 border-r border-slate-200">Data</th>
                                        <th class="p-2 border-r border-slate-200">Paciente</th>
                                        <th class="p-2">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="sv in details.sinaisVitais" :key="sv.Id" class="border-b border-slate-100 hover:bg-slate-50">
                                        <td class="p-2 border-r border-slate-100">{{ selectedPaciente?.Codigo }}</td>
                                        <td class="p-2 border-r border-slate-100">{{ new Date(sv.CREATED_AT).toLocaleDateString() }}</td>
                                        <td class="p-2 border-r border-slate-100 truncate max-w-[100px]">{{ selectedPaciente?.PacienteNome }}</td>
                                        <td class="p-2 text-center">
                                            <button @click="sinaisForm = {...sv}" class="text-blue-600 hover:underline font-bold">Selecionar</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-4 bg-slate-100 border-t border-slate-300">
                            <button @click="imprimirRelatorioVitais" class="w-full py-2 bg-gradient-to-b from-blue-400 to-blue-600 text-white font-black uppercase text-xs rounded shadow hover:from-blue-500 hover:to-blue-700 transition-all border border-blue-700">
                                RELATORIO DE CONTRO VITAIS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alta Modal -->
        <div v-if="showAltaModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-[500] p-4">
            <div class="bg-white w-full max-w-md shadow-2xl rounded-sm overflow-hidden border border-slate-200 scale-in-center">
                <div class="bg-green-700 text-white p-5 font-black text-center uppercase tracking-widest relative">
                    Título de Alta Hospitalar
                    <button @click="showAltaModal = false" class="absolute right-4 top-4 hover:bg-green-900 rounded-full w-8 h-8 flex items-center justify-center">&times;</button>
                </div>
                <div class="p-8 space-y-6 bg-white">
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Procedimento Operado</label>
                        <input v-model="altaForm.Operado" type="text" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-green-600 bg-slate-50" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Complicações</label>
                        <textarea v-model="altaForm.Complicacoes" rows="2" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-green-600 bg-slate-50 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Repouso e Recomendações</label>
                        <input v-model="altaForm.Repouso" type="text" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-green-600 bg-slate-50" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Notas de Alta Finais</label>
                        <textarea v-model="altaForm.Obs" rows="3" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-green-600 bg-slate-50 resize-none"></textarea>
                    </div>
                </div>
                <div class="p-4 bg-slate-50 flex gap-3 border-t border-slate-100">
                    <button @click="showAltaModal = false" class="flex-1 py-4 border-2 border-slate-200 text-xs font-black uppercase rounded-lg hover:bg-white transition-all">Cancelar</button>
                    <button @click="handleAltaConfirm" class="flex-[2] py-4 bg-green-600 text-white text-xs font-black uppercase rounded-lg hover:bg-green-700 shadow-xl transition-all">Confirmar Alta</button>
                </div>
            </div>
        </div>

        <!-- Prescrição Modal -->
        <div v-if="showPrescricaoModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[500] p-4">
            <div class="bg-white w-full max-w-md shadow-2xl rounded-sm overflow-hidden border border-slate-200 scale-in-center">
                <div class="bg-[#000080] text-white p-5 font-black text-center uppercase tracking-widest relative">
                    Nova Prescrição Médica
                    <button @click="showPrescricaoModal = false" class="absolute right-5 top-4 hover:bg-blue-900 rounded-full w-8 h-8 flex items-center justify-center">&times;</button>
                </div>
                <div class="p-8 space-y-6">
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Tipo</label>
                        <select v-model="prescricaoForm.Tipo" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-blue-600 font-black bg-slate-50">
                            <option value="Internamento">Internamento</option>
                            <option value="Observacao">Observação</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Prescrição / Medicamento</label>
                        <textarea v-model="prescricaoForm.Descricao" rows="6" class="w-full border-2 border-slate-200 p-3 text-sm font-bold rounded-lg outline-none focus:border-blue-600 resize-none bg-slate-50 shadow-inner" placeholder="Ex: Ciprofloxacina 400mg..."></textarea>
                    </div>
                    <div>
                        <label class="block text-[11px] font-black uppercase text-slate-500 mb-1.5">Observações para Enfermagem</label>
                        <input v-model="prescricaoForm.Observacao" type="text" class="w-full border-2 border-slate-200 p-3 text-sm rounded-lg outline-none focus:border-blue-600 bg-slate-50" />
                    </div>
                </div>
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                    <button @click="showPrescricaoModal = false" class="flex-1 py-4 border-2 border-slate-200 text-xs font-black uppercase rounded-lg hover:bg-white transition-all text-slate-400">Descartar</button>
                    <button @click="submitPrescricao" class="flex-[2] py-4 bg-[#2196F3] text-white text-xs font-black uppercase rounded-lg hover:bg-blue-700 shadow-xl transition-all">Gravar Plano</button>
                </div>
            </div>
        </div>

        <!-- Elegant Confirmation Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-[4px] flex items-center justify-center z-[1000] p-4 animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-sm rounded-xl shadow-2xl overflow-hidden border border-slate-100 scale-in-center">
                <div class="p-10 text-center">
                    <div class="w-24 h-24 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-8">
                        <AlertCircle class="w-12 h-12" />
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-4 tracking-tighter">{{ confirmTitle }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed font-bold px-4">{{ confirmText }}</p>
                </div>
                <div class="flex border-t border-slate-50 p-4 gap-3 bg-slate-50/50">
                    <button @click="showConfirmModal = false" class="flex-1 py-4 text-xs font-black text-slate-400 hover:bg-white transition-all uppercase tracking-widest rounded-lg border border-slate-100">Cancelar</button>
                    <button @click="executeConfirm" class="flex-1 py-4 text-xs font-black text-blue-600 hover:bg-blue-600 hover:text-white transition-all uppercase tracking-widest rounded-lg border border-blue-100 shadow-lg shadow-blue-50">Confirmar</button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.toast-enter-from {
    transform: translateY(100px) scale(0.9);
    opacity: 0;
}
.toast-leave-to {
    transform: translateX(100px);
    opacity: 0;
}

@keyframes scale-in {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.scale-in-center {
    animation: scale-in 0.2s ease-out forwards;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
button:active {
    transform: scale(0.98);
}
</style>
