<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { Search, CheckCircle2, AlertCircle } from 'lucide-vue-next';

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
    Peso: '',
    Temperatura: '',
    PressaoArterial: '',
    FrequenciaCardioca: '',
    FrequenciaRespiratoria: '',
    SituacaoOxigenio: '',
    Obs: ''
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

const recarregar = () => {
    router.reload({ only: ['internados', 'historico'] });
};

const openAtoModal = (type) => {
    atoType.value = type;
    showAtoModal.value = true;
};
</script>

<template>
    <Head title="Internamento" />

    <DashboardLayout>
        <!-- Elegant Local Toast -->
        <Transition name="toast">
            <div v-if="toast.show" 
                class="fixed bottom-10 right-10 z-[200] p-4 rounded-xl shadow-2xl border-l-4 flex items-center gap-4 min-w-[300px]"
                :class="toast.type === 'success' ? 'bg-white border-green-500' : 'bg-white border-red-500'">
                <div :class="toast.type === 'success' ? 'text-green-500' : 'text-red-500'">
                    <CheckCircle2 v-if="toast.type === 'success'" class="w-6 h-6" />
                    <AlertCircle v-else class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-50">{{ toast.type === 'success' ? 'Sucesso' : 'Erro' }}</p>
                    <p class="text-xs font-bold text-slate-800">{{ toast.message }}</p>
                </div>
            </div>
        </Transition>

        <div class="h-[calc(100vh-64px)] flex flex-col bg-[#f0f0f0] font-sans text-[11px] text-slate-800 overflow-hidden">

            <!-- Top Action Bar -->
            <div class="flex flex-wrap lg:flex-nowrap gap-1 bg-white p-1 shrink-0 border-b border-slate-300">
                <button @click="recarregar" class="bg-[#2196F3] text-white px-4 py-2 font-bold hover:bg-[#1976D2] transition-colors">
                    Atualizar Registos
                </button>
                <button class="bg-[#2196F3] text-white px-4 py-2 font-bold hover:bg-[#1976D2] transition-colors">
                    Imprimir Processo Clínico
                </button>
                
                <div class="flex flex-col border border-[#2196F3]">
                    <div class="text-center font-bold text-[10px] uppercase text-slate-800 bg-white py-0.5">ÁREA DOS MÉDICOS</div>
                    <div class="flex gap-0.5 bg-white p-0.5">
                        <button 
                            @click="showPrescricaoModal = true"
                            :disabled="!selectedPaciente"
                            class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] disabled:opacity-50">
                            Prescrições<br>Médicas
                        </button>
                        <button 
                            @click="openAtoModal('medico')"
                            :disabled="!selectedPaciente"
                            class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] leading-tight disabled:opacity-50">
                            Registo de Actos Médicos e Solicitação de<br>Exames
                        </button>
                        <button 
                            @click="showAltaModal = true"
                            :disabled="!selectedPaciente"
                            class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] disabled:opacity-50">
                            Título de Alta
                        </button>
                    </div>
                </div>

                <div class="flex flex-col border border-[#2196F3]">
                    <div class="text-center font-bold text-[10px] uppercase text-slate-800 bg-white py-0.5">ÁREA DOS ENFERMEIROS</div>
                    <div class="flex gap-0.5 bg-white p-0.5">
                        <button 
                            @click="prescricoesSearchTerm = ''"
                            :disabled="!selectedPaciente"
                            class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] leading-tight disabled:opacity-50">
                            Cumprimento<br>(Enfermagem)
                        </button>
                        <button 
                            @click="showSinaisModal = true"
                            :disabled="!selectedPaciente"
                            class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] leading-tight disabled:opacity-50">
                            Controlo de sinais<br>Vitais
                        </button>
                        <button 
                            @click="openAtoModal('enfermagem')"
                            :disabled="!selectedPaciente"
                            class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] leading-tight disabled:opacity-50">
                            Registo de Visitas e Atos da<br>Enfermaria
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="bg-[#000080] text-white text-center py-0.5 font-bold text-[10px] tracking-widest shrink-0">...</div>

            <!-- Main Content Area -->
            <div class="flex flex-col lg:flex-row flex-1 gap-1 p-1 overflow-hidden">
                
                <!-- Left Column -->
                <div class="flex-[1.5] flex flex-col gap-1 overflow-hidden h-[500px] lg:h-full">
                    
                    <!-- Pacientes Internados -->
                    <div class="flex-[2] bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-slate-100 text-slate-500 text-[10px] p-1.5 border-b border-slate-300 flex justify-between items-center">
                            <span>Drag a column header here to group by that column</span>
                            <Search class="w-3.5 h-3.5 text-slate-400" />
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-[#f4f4f4] border-b border-slate-300 z-10 text-[10px]">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Codigo</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Paciente</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Consulta</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Data Internamento</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Tipo</th>
                                        <th class="p-1.5 font-normal text-slate-700">Medico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredInternados" :key="p.Codigo" 
                                        @click="selecionarPaciente(p)"
                                        :class="selectedPaciente?.Codigo === p.Codigo ? 'bg-[#1976D2] text-white' : 'hover:bg-slate-50'"
                                        class="border-b border-slate-200 cursor-pointer text-[10px]">
                                        <td class="p-1.5 border-r border-slate-200/50">{{ p.Codigo }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50 font-bold truncate max-w-[150px]">{{ p.PacienteNome }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ p.DescricaoConsulta || 'N/D' }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ p.DataInternamento }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ p.Tipo || 'Internamento' }}</td>
                                        <td class="p-1.5 truncate max-w-[100px]">{{ p.MedicoNome }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Histórico -->
                    <div class="flex-1 bg-white border border-slate-300 flex flex-col overflow-hidden min-h-[150px]">
                        <div class="bg-[#e0e0e0] text-slate-800 py-1 px-2 font-bold text-[11px] border-b border-slate-300 shadow-sm">
                            Histórico de Pacientes Internados
                        </div>
                        <div class="p-1.5 bg-[#f8f8f8] border-b border-slate-300 flex">
                            <input v-model="histSearchTerm" type="text" placeholder="Enter text to search..." class="border border-slate-300 px-2 py-1 text-xs w-64 focus:outline-none focus:border-blue-400" />
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-[#f4f4f4] border-b border-slate-300 z-10 text-[10px]">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Codigo</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Tipo</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Paciente</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Data Entrada</th>
                                        <th class="p-1.5 font-normal text-slate-700">Relatorio Processo C...</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="h in filteredHistorico" :key="h.Id" class="border-b border-slate-200 hover:bg-slate-50 text-[10px]">
                                        <td class="p-1.5 border-r border-slate-200/50">{{ h.Codigo }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">Histórico</td>
                                        <td class="p-1.5 border-r border-slate-200/50 font-bold truncate max-w-[150px]">{{ h.PacienteNome }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ h.DataEntrada }}</td>
                                        <td class="p-1.5 text-blue-600 underline cursor-pointer">Visualizar</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="flex-1 flex flex-col gap-1 overflow-hidden h-[600px] lg:h-full">
                    
                    <!-- Prescrições Médicas -->
                    <div class="flex-[2] bg-white border border-slate-300 flex flex-col overflow-hidden">
                        <div class="bg-[#e0e0e0] text-slate-800 py-1 px-2 font-bold text-[11px] border-b border-slate-300 shadow-sm">
                            Prescrições Médicas
                        </div>
                        <div class="p-1.5 bg-[#f8f8f8] border-b border-slate-300 flex gap-1 items-center">
                            <input v-model="prescricoesSearchTerm" type="text" placeholder="Enter text to search..." class="flex-1 border border-slate-300 px-2 py-1 text-xs focus:outline-none focus:border-blue-400" />
                            <button class="bg-white border border-slate-300 px-4 py-1 hover:bg-slate-100 text-xs">Find</button>
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-[#f4f4f4] border-b border-slate-300 z-10 text-[10px]">
                                    <tr>
                                        <th class="p-1 w-6 border-r border-slate-300 text-center">F</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Medico</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Descricao</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Enfermeiro</th>
                                        <th class="p-1 w-6 border-r border-slate-300 text-center" title="Manhã">M</th>
                                        <th class="p-1 w-6 border-r border-slate-300 text-center" title="Tarde">T</th>
                                        <th class="p-1 w-6 border-r border-slate-300 text-center" title="Noite">N</th>
                                        <th class="p-1.5 font-normal text-slate-700">Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredPrescricoes" :key="p.Id" class="border-b border-slate-200 hover:bg-slate-50 text-[10px]">
                                        <td class="p-1 border-r border-slate-200/50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento')" />
                                        </td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ p.Medico || '---' }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50 max-w-[200px] truncate" :title="p.Descricao">{{ p.Descricao }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ p.Infermeiro || '---' }}</td>
                                        <td class="p-1 border-r border-slate-200/50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento1 === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento1')" />
                                        </td>
                                        <td class="p-1 border-r border-slate-200/50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento2 === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento2')" />
                                        </td>
                                        <td class="p-1 border-r border-slate-200/50 text-center">
                                            <input type="checkbox" :checked="p.Cumprimento3 === 'True'" @change="togglePrescricaoStatus(p, 'Cumprimento3')" />
                                        </td>
                                        <td class="p-1.5">{{ p.DataInternamento || p.CREATED_AT }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Registo de Visitas -->
                    <div class="flex-1 bg-white border border-slate-300 flex flex-col overflow-hidden min-h-[150px]">
                        <div class="bg-[#e0e0e0] text-slate-800 py-1 px-2 font-bold text-[11px] border-b border-slate-300 shadow-sm">
                            Registo de Visitas e Actos Médicos
                        </div>
                        <div class="bg-slate-100 text-slate-500 text-[10px] p-1.5 border-b border-slate-300 flex justify-between items-center">
                            <span>Drag a column header here to group by that column</span>
                            <Search class="w-3.5 h-3.5 text-slate-400" />
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-[#f4f4f4] border-b border-slate-300 z-10 text-[10px]">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Data</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Medico</th>
                                        <th class="p-1.5 font-normal text-slate-700">Descrição</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="a in details.atosMedicos" :key="a.Id" class="border-b border-slate-200 hover:bg-slate-50 text-[10px]">
                                        <td class="p-1.5 border-r border-slate-200/50">{{ a.DataAto || a.CREATED_AT }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ a.Medico }}</td>
                                        <td class="p-1.5 truncate max-w-[200px]" :title="a.Descricao">{{ a.Descricao }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Controlo de Sinais Vitais -->
                    <div class="flex-1 bg-white border border-slate-300 flex flex-col overflow-hidden min-h-[150px]">
                        <div class="bg-[#e0e0e0] text-slate-800 py-1 px-2 font-bold text-[11px] border-b border-slate-300 shadow-sm">
                            Controlo de Sinais Vitais
                        </div>
                        <div class="bg-slate-100 text-slate-500 text-[10px] p-1.5 border-b border-slate-300 flex justify-between items-center">
                            <span>Drag a column header here to group by that column</span>
                            <Search class="w-3.5 h-3.5 text-slate-400" />
                        </div>
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead class="sticky top-0 bg-[#f4f4f4] border-b border-slate-300 z-10 text-[10px]">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Cod. Paciente</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Data</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Paciente</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Temp.</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Peso</th>
                                        <th class="p-1.5 font-normal text-slate-700">Pressão</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="v in details.sinaisVitais" :key="v.Id" class="border-b border-slate-200 hover:bg-slate-50 text-[10px]">
                                        <td class="p-1.5 border-r border-slate-200/50">{{ selectedPaciente?.Codigo }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ v.DataAgendamento || v.CREATED_AT }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ selectedPaciente?.PacienteNome }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ v.Temperatura }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ v.Peso }}</td>
                                        <td class="p-1.5">{{ v.PressaoArterial }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modals -->
        <div v-if="showPrescricaoModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[100] p-4">
            <div class="bg-white w-full max-w-md shadow-2xl border border-slate-300">
                <div class="bg-[#000080] text-white p-2 font-bold flex justify-between items-center">
                    <span>Nova Prescrição</span>
                    <button @click="showPrescricaoModal = false" class="hover:bg-red-600 px-2">&times;</button>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Tipo</label>
                        <select v-model="prescricaoForm.Tipo" class="w-full border border-slate-300 p-2 text-xs focus:ring-1 focus:ring-blue-500 outline-none">
                            <option value="Internamento">Internamento</option>
                            <option value="Observacao">Observação</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Prescrição</label>
                        <textarea v-model="prescricaoForm.Descricao" rows="4" class="w-full border border-slate-300 p-2 text-xs focus:ring-1 focus:ring-blue-500 outline-none resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Observação</label>
                        <input v-model="prescricaoForm.Observacao" type="text" class="w-full border border-slate-300 p-2 text-xs focus:ring-1 focus:ring-blue-500 outline-none" />
                    </div>
                </div>
                <div class="p-2 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                    <button @click="showPrescricaoModal = false" class="px-4 py-1.5 border border-slate-300 hover:bg-slate-100 text-xs font-bold">Cancelar</button>
                    <button @click="submitPrescricao" class="px-4 py-1.5 bg-[#2196F3] text-white hover:bg-[#1976D2] text-xs font-bold">Gravar</button>
                </div>
            </div>
        </div>

        <div v-if="showAtoModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[100] p-4">
            <div class="bg-white w-full max-w-md shadow-2xl border border-slate-300">
                <div class="bg-[#000080] text-white p-2 font-bold flex justify-between items-center">
                    <span>Registo de Atos ({{ atoType === 'medico' ? 'Médico' : 'Enfermagem' }})</span>
                    <button @click="showAtoModal = false" class="hover:bg-red-600 px-2">&times;</button>
                </div>
                <div class="p-4">
                    <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Descrição do Ato</label>
                    <textarea v-model="atoForm.descricao" rows="6" class="w-full border border-slate-300 p-2 text-xs focus:ring-1 focus:ring-blue-500 outline-none resize-none"></textarea>
                </div>
                <div class="p-2 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                    <button @click="showAtoModal = false" class="px-4 py-1.5 border border-slate-300 hover:bg-slate-100 text-xs font-bold">Cancelar</button>
                    <button @click="submitAto" class="px-4 py-1.5 bg-[#2196F3] text-white hover:bg-[#1976D2] text-xs font-bold">Gravar</button>
                </div>
            </div>
        </div>

        <div v-if="showSinaisModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[100] p-4">
            <div class="bg-white w-full max-w-lg shadow-2xl border border-slate-300">
                <div class="bg-[#000080] text-white p-2 font-bold flex justify-between items-center">
                    <span>Registo de Sinais Vitais</span>
                    <button @click="showSinaisModal = false" class="hover:bg-red-600 px-2">&times;</button>
                </div>
                <div class="p-4 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Peso (kg)</label>
                        <input v-model="sinaisForm.Peso" type="text" class="w-full border border-slate-300 p-2 text-xs outline-none" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Temperatura (°C)</label>
                        <input v-model="sinaisForm.Temperatura" type="text" class="w-full border border-slate-300 p-2 text-xs outline-none" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Pressão Arterial</label>
                        <input v-model="sinaisForm.PressaoArterial" type="text" class="w-full border border-slate-300 p-2 text-xs outline-none" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Freq. Cardíaca</label>
                        <input v-model="sinaisForm.FrequenciaCardioca" type="text" class="w-full border border-slate-300 p-2 text-xs outline-none" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Freq. Respiratória</label>
                        <input v-model="sinaisForm.FrequenciaRespiratoria" type="text" class="w-full border border-slate-300 p-2 text-xs outline-none" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Saturação O2</label>
                        <input v-model="sinaisForm.SituacaoOxigenio" type="text" class="w-full border border-slate-300 p-2 text-xs outline-none" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Observações</label>
                        <textarea v-model="sinaisForm.Obs" rows="2" class="w-full border border-slate-300 p-2 text-xs outline-none resize-none"></textarea>
                    </div>
                </div>
                <div class="p-2 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                    <button @click="showSinaisModal = false" class="px-4 py-1.5 border border-slate-300 hover:bg-slate-100 text-xs font-bold">Cancelar</button>
                    <button @click="submitSinais" class="px-4 py-1.5 bg-[#2196F3] text-white hover:bg-[#1976D2] text-xs font-bold">Gravar</button>
                </div>
            </div>
        </div>

        <div v-if="showAltaModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[100] p-4">
            <div class="bg-white w-full max-w-md shadow-2xl border border-slate-300">
                <div class="bg-[#000080] text-white p-2 font-bold flex justify-between items-center">
                    <span>Título de Alta</span>
                    <button @click="showAltaModal = false" class="hover:bg-red-600 px-2">&times;</button>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Operado</label>
                        <input v-model="altaForm.Operado" type="text" class="w-full border border-slate-300 p-2 text-xs outline-none" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Complicações</label>
                        <textarea v-model="altaForm.Complicacoes" rows="2" class="w-full border border-slate-300 p-2 text-xs outline-none resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Recomendações de Repouso</label>
                        <input v-model="altaForm.Repouso" type="text" class="w-full border border-slate-300 p-2 text-xs outline-none" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Observações Finais</label>
                        <textarea v-model="altaForm.Obs" rows="3" class="w-full border border-slate-300 p-2 text-xs outline-none resize-none"></textarea>
                    </div>
                </div>
                <div class="p-2 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                    <button @click="showAltaModal = false" class="px-4 py-1.5 border border-slate-300 hover:bg-slate-100 text-xs font-bold">Cancelar</button>
                    <button @click="handleAltaConfirm" class="px-4 py-1.5 bg-green-600 text-white hover:bg-green-700 text-xs font-bold uppercase">Confirmar Alta</button>
                </div>
            </div>
        </div>

        <!-- Elegant Confirmation Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] flex items-center justify-center z-[300] p-4 animate-in fade-in duration-200">
            <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-slate-200 scale-in-center">
                <div class="p-6 text-center">
                    <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <AlertCircle class="w-8 h-8" />
                    </div>
                    <h3 class="text-lg font-black text-slate-800 mb-2">{{ confirmTitle }}</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">{{ confirmText }}</p>
                </div>
                <div class="flex border-t border-slate-100">
                    <button @click="showConfirmModal = false" class="flex-1 py-4 text-xs font-bold text-slate-400 hover:bg-slate-50 transition-colors uppercase tracking-widest border-r border-slate-100">Não, cancelar</button>
                    <button @click="executeConfirm" class="flex-1 py-4 text-xs font-black text-blue-600 hover:bg-blue-50 transition-colors uppercase tracking-widest">Sim, continuar</button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* Toast Animations */
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
