<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { 
    Search, Save, Printer, CheckCircle, 
    Trash2, Plus, AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    aguardando: Array,
    depositos: Array,
    materiais: Array
});

const page = usePage();
const flash = computed(() => page.props.flash);

const searchTerm = ref('');
const histSearchTerm = ref('');
const selectedPaciente = ref(null);
const details = ref({ exames: [], historico: [], paciente: {}, materiaisUsados: [] });
const isLoading = ref(false);
const selectedDeposito = ref('');

// Custom Confirm Modal State
const confirmModal = ref({
    isOpen: false,
    title: '',
    message: '',
    onConfirm: null
});

const openConfirm = (title, message, onConfirm) => {
    confirmModal.value = { isOpen: true, title, message, onConfirm };
};

const closeConfirm = () => {
    confirmModal.value.isOpen = false;
};

let pollingInterval = null;

onMounted(() => {
    // Passo 1: Simular SignalR / Timer do WinForms para Fila em tempo real
    pollingInterval = setInterval(() => {
        router.reload({ only: ['aguardando'], preserveScroll: true, preserveState: true });
    }, 15000); // Polling a cada 15 segundos
    
    if (props.depositos && props.depositos.length > 0) {
        selectedDeposito.value = props.depositos[0].Codigo;
    }
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

const unidadesLaboratorio = [
    "mg/dl", "mmHg", "U/L", "%", "g/dl", "IU/L", "umol/L",
    "ng/ml", "mEq/L", "ml/min", "mMol/L", "cells/mm3"
];

const filteredAguardando = computed(() => {
    if (!searchTerm.value) return props.aguardando;
    const term = searchTerm.value.toLowerCase();
    return props.aguardando.filter(a => 
        a.PacienteNome?.toLowerCase().includes(term) ||
        a.Codigo?.toLowerCase().includes(term)
    );
});

const filteredHistorico = computed(() => {
    if (!histSearchTerm.value) return details.value.historico;
    const term = histSearchTerm.value.toLowerCase();
    return details.value.historico.filter(h => 
        h.Descricao?.toLowerCase().includes(term)
    );
});

// Dynamic Exam Parsing
const parsedExames = ref([]);

watch(() => details.value.exames, (newExames) => {
    parsedExames.value = newExames.map(e => {
        let campos = e.Filhos ? e.Filhos.split('|').filter(x => x.trim()) : [];
        let refs = e.Referencia ? e.Referencia.split('|').filter(x => x.trim()) : [];
        let resultados = e.Resultado ? e.Resultado.split('|').filter(x => x.trim()) : [];

        if(campos.length === 0) {
            return {
                ...e,
                isSingle: true,
                singleResultado: e.Resultado || '',
                singleObs: e.Obs || '',
                fillMode: 'manual'
            };
        }

        let rows = campos.map((campo, index) => {
            let fullResult = resultados[index] || '';
            let val = fullResult;
            let unit = '';
            
            for (let u of unidadesLaboratorio) {
                if (fullResult.endsWith(u)) {
                    unit = u;
                    val = fullResult.replace(u, '').trim();
                    break;
                }
            }

            return {
                dado: campo,
                resultado: val,
                unidade: unit,
                referencia: refs[index] || ''
            };
        });

        return {
            ...e,
            isSingle: false,
            rows: rows,
            fillMode: 'manual'
        };
    });
}, { deep: true });

const selecionarPaciente = async (paciente) => {
    selectedPaciente.value = paciente;
    isLoading.value = true;

    try {
        const response = await axios.get(route('hospitalar.laboratorio.details', paciente.Codigo));
        details.value = response.data;
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
    } finally {
        isLoading.value = false;
    }
};

const gravarResultadoExame = (exam, soSugestoes = false) => {
    let finalResult = '';
    
    if (exam.isSingle) {
        finalResult = exam.singleResultado || '';
    } else {
        finalResult = exam.rows.map(r => `${r.resultado || ''} ${r.unidade || ''}`.trim()).join('|');
    }

    router.post(route('hospitalar.laboratorio.resultado'), {
        idExame: exam.Id,
        resultado: finalResult,
        nrAmostra: exam.nrAmostra || exam.Referencia || '',
        obs: exam.singleObs || ''
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selecionarPaciente(selectedPaciente.value);
        }
    });
};

const gravarTodosDados = () => {
    if (!parsedExames.value || parsedExames.value.length === 0) return;
    openConfirm(
        'Gravar Todos os Dados', 
        'Deseja gravar simultaneamente os resultados de todos os exames preenchidos deste paciente?',
        () => {
            parsedExames.value.forEach(exam => {
                gravarResultadoExame(exam);
            });
            closeConfirm();
        }
    );
};

const imprimirResultados = () => {
    if (!selectedPaciente.value) return;
    window.open(route('hospitalar.laboratorio.imprimir', selectedPaciente.value.Codigo), '_blank');
};

const finalizarLaboratorio = () => {
    if (!selectedPaciente.value) return;
    openConfirm(
        'Finalizar Atendimento',
        'Deseja finalizar o atendimento laboratorial para este paciente? O material gasto será abatido do armazém selecionado.',
        () => {
            router.post(route('hospitalar.laboratorio.finalizar', selectedPaciente.value.Codigo), {
                deposito: selectedDeposito.value
            }, {
                onSuccess: () => {
                    selectedPaciente.value = null;
                    details.value = { exames: [], historico: [], paciente: {}, materiaisUsados: [] };
                    closeConfirm();
                }
            });
        }
    );
};

const calcularIdade = (nascimento) => {
    if (!nascimento) return 'N/D';
    const birthDate = new Date(nascimento);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    return age;
};

// Materiais Usados Logic
const showMaterialModal = ref(false);
const materialForm = useForm({
    produto: '',
    descricao: '',
    quantidade: 1,
    preco: 0
});

const openMaterialModal = () => showMaterialModal.value = true;
const closeMaterialModal = () => { showMaterialModal.value = false; materialForm.reset(); };

const selectMaterial = (event) => {
    const selected = props.materiais.find(m => m.CODIGO === event.target.value);
    if (selected) {
        materialForm.descricao = selected.DESCRICAO;
        materialForm.preco = selected.PRECO_VENDA || 0;
    }
};

const salvarMaterial = () => {
    router.post(route('hospitalar.laboratorio.material.store'), {
        idAgenda: selectedPaciente.value.Codigo,
        produto: materialForm.produto,
        descricao: materialForm.descricao,
        quantidade: materialForm.quantidade,
        preco: materialForm.preco
    }, {
        onSuccess: () => {
            closeMaterialModal();
            selecionarPaciente(selectedPaciente.value);
        }
    });
};

const removerMaterial = (id) => {
    if (confirm('Remover este material?')) {
        router.delete(route('hospitalar.laboratorio.material.destroy', id), {
            onSuccess: () => { selecionarPaciente(selectedPaciente.value); }
        });
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(value);
};
</script>

<template>
    <Head title="Laboratório Clínico" />

    <DashboardLayout>
        <!-- Main Application Wrapper: Exact layout matching WinForms with clean styles -->
        <div class="lg:h-[calc(100vh-64px)] h-auto flex flex-col lg:flex-row bg-[#f0f0f0] font-sans text-xs text-slate-800 overflow-visible lg:overflow-hidden">
            
            <!-- LEFT SIDEBAR -->
            <div class="w-full lg:w-[350px] flex flex-col border-b lg:border-b-0 lg:border-r border-slate-300 bg-white shrink-0 h-[500px] lg:h-full">
                
                <!-- Lista de Espera Header -->
                <div class="bg-[#000080] text-white text-center py-1.5 font-bold text-sm tracking-wide shadow-sm z-10">
                    Lista de Espera
                </div>
                
                <div class="bg-slate-100 text-slate-500 text-[10px] p-1.5 border-b border-slate-300">
                    Drag a column header here to group by that column
                </div>

                <!-- Lista de Espera Grid -->
                <div class="flex-1 overflow-y-auto border-b border-slate-300 bg-white">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-slate-100 border-b border-slate-300 z-10">
                            <tr>
                                <th class="p-1.5 border-r border-slate-300 font-normal text-slate-600">Nome</th>
                                <th class="p-1.5 border-r border-slate-300 font-normal text-slate-600">Data Exame</th>
                                <th class="p-1.5 font-normal text-slate-600">Medico</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pac in filteredAguardando" :key="pac.Id" 
                                @click="selecionarPaciente(pac)"
                                :class="[
                                    'border-b border-slate-200 cursor-pointer transition-colors',
                                    selectedPaciente?.Codigo === pac.Codigo 
                                        ? 'bg-[#1976d2] text-white' 
                                        : 'hover:bg-blue-50 text-slate-800'
                                ]">
                                <td class="p-1.5 border-r border-slate-200/50 truncate max-w-[120px]">
                                    <div class="flex items-center gap-1">
                                        <span v-if="selectedPaciente?.Codigo === pac.Codigo" class="text-white text-[10px]">→</span>
                                        {{pac.PacienteNome}}
                                    </div>
                                </td>
                                <td class="p-1.5 border-r border-slate-200/50">{{pac.DataAgendamento}}</td>
                                <td class="p-1.5 truncate max-w-[90px]">{{pac.MedicoNome}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Histórico do Paciente Header -->
                <div class="bg-[#e0e0e0] text-slate-800 text-center py-1.5 font-bold text-sm border-b border-slate-300 shadow-sm z-10">
                    Histórico do Paciente
                </div>
                
                <!-- Search Histórico -->
                <div class="p-1.5 flex gap-1 border-b border-slate-300 bg-[#f8f8f8]">
                    <input type="text" v-model="histSearchTerm" class="flex-1 border border-slate-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none" />
                    <button class="bg-[#2196F3] hover:bg-[#1976d2] text-white px-3 py-1 text-xs font-bold flex items-center gap-1 transition-colors shadow-sm">
                        <Search class="w-3 h-3" /> BUSCAR PACIENTE
                    </button>
                </div>

                <div class="bg-slate-100 text-slate-500 text-[10px] p-1.5 border-b border-slate-300">
                    Drag a column header here to group by that column
                </div>

                <!-- Histórico Grid -->
                <div class="h-[200px] overflow-y-auto bg-white">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-slate-100 border-b border-slate-300 z-10">
                            <tr>
                                <th class="p-1.5 border-r border-slate-300 font-normal text-slate-600">Data</th>
                                <th class="p-1.5 border-r border-slate-300 font-normal text-slate-600">Consulta</th>
                                <th class="p-1.5 border-r border-slate-300 font-normal text-slate-600">Paciente</th>
                                <th class="p-1.5 border-r border-slate-300 font-normal text-slate-600">Medico</th>
                                <th class="p-1.5 border-r border-slate-300 font-normal text-slate-600">Situacao</th>
                                <th class="p-1.5 font-normal text-slate-600">Visualizar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="hist in filteredHistorico" :key="hist.Id" class="border-b border-slate-200 hover:bg-slate-50 text-slate-800">
                                <td class="p-1.5 border-r border-slate-200/50">{{ hist.DataAgendamento }}</td>
                                <td class="p-1.5 border-r border-slate-200/50 truncate max-w-[60px]">{{ hist.DescricaoConsulta || 'N/D' }}</td>
                                <td class="p-1.5 border-r border-slate-200/50 truncate max-w-[60px]">{{ hist.PacienteNome }}</td>
                                <td class="p-1.5 border-r border-slate-200/50 truncate max-w-[60px]">{{ hist.MedicoNome }}</td>
                                <td class="p-1.5 border-r border-slate-200/50">{{ hist.Situacao || 'Finalizado' }}</td>
                                <td class="p-1.5 text-center">
                                    <button class="bg-slate-200 hover:bg-slate-300 border border-slate-400 px-2 py-0.5 text-[10px] text-slate-700">Selecionar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RIGHT MAIN AREA -->
            <div class="flex-1 flex flex-col min-w-0 bg-[#f0f0f0]">
                
                <!-- Main Header -->
                <div class="bg-[#000080] text-white text-center py-2 font-bold text-sm tracking-widest shadow-sm z-10 w-full">
                    LABORATÓRIO DE EXAMES NORMAIS
                </div>

                <div v-if="selectedPaciente" class="flex-1 flex flex-col overflow-hidden print-report-container">
                    
                    <!-- Print Only Header -->
                    <div class="hidden print:block text-center mb-8 border-b-2 border-black pb-4">
                        <h1 class="text-2xl font-bold uppercase tracking-widest">Relatório de Análises Clínicas</h1>
                        <p class="text-sm">Emute ERP Hospitalar - Departamento de Laboratório</p>
                        <p class="text-xs mt-1">Data de Emissão: {{ new Date().toLocaleDateString('pt-PT') }}</p>
                    </div>

                    <!-- Patient Name Banner -->
                    <div class="bg-[#000080] text-white text-center py-1.5 font-bold text-sm tracking-widest mx-2 mt-2 shadow-sm print:hidden">
                        {{ details.paciente.PacienteNome }}
                    </div>

                    <!-- Patient Details Grid -->
                    <div class="bg-[#e8e8e8] mx-2 p-2 border border-slate-300 shadow-sm print:bg-white print:border-black print:mb-8 print:mx-0">
                        <div class="grid grid-cols-1 sm:grid-cols-[auto_1fr_auto_1fr] gap-x-4 gap-y-1 items-center">
                            
                            <label class="text-right pr-2 text-slate-700">Código</label>
                            <input type="text" :value="details.paciente.Codigo" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />
                            
                            <div class="col-span-2 flex items-center gap-6 pl-4 text-slate-700">
                                <label class="flex items-center gap-1"><input type="radio" :checked="!details.paciente.Asseguradora" disabled class="accent-blue-600"/> Particular</label>
                                <label class="flex items-center gap-1"><input type="radio" :checked="!!details.paciente.Asseguradora" disabled class="accent-blue-600"/> Assegurado</label>
                            </div>

                            <label class="text-right pr-2 text-slate-700">Nome</label>
                            <input type="text" :value="details.paciente.PacienteNome" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />

                            <label class="text-right pr-2 text-slate-700">Asseguradora</label>
                            <input type="text" :value="details.paciente.Asseguradora || 'N/A'" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />

                            <label class="text-right pr-2 text-slate-700">Data de Nascimento</label>
                            <input type="text" :value="details.paciente.DataNascimento" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />

                            <label class="text-right pr-2 text-slate-700">Idade</label>
                            <input type="text" :value="calcularIdade(details.paciente.DataNascimento)" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />

                            <label class="text-right pr-2 text-slate-700">Telefone</label>
                            <input type="text" :value="details.paciente.Telefone" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />

                            <label class="text-right pr-2 text-slate-700">Sexo</label>
                            <input type="text" :value="details.paciente.Genero" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />

                            <label class="text-right pr-2 text-slate-700">Morada</label>
                            <input type="text" :value="details.paciente.Morada" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />

                            <label class="text-right pr-2 text-slate-700">Consulta</label>
                            <input type="text" :value="details.paciente.DescricaoConsulta || 'Clinica Geral'" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />

                            <label class="text-right pr-2 text-slate-700">Médico</label>
                            <input type="text" :value="details.paciente.MedicoNome" disabled class="w-full border border-slate-300 px-2 py-1 bg-white text-slate-800 focus:outline-none" />
                        </div>
                    </div>

                    <!-- Exames Solicitados Header -->
                    <div class="bg-[#000080] text-white py-1.5 mx-2 mt-2 shadow-sm flex items-center justify-center relative">
                        <h2 class="font-bold text-sm tracking-widest uppercase">Exames Solicitados</h2>
                        <!-- Elegant minimal button for materials on the right side -->
                        <button @click="openMaterialModal" class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 text-white px-3 py-0.5 border border-white/40 text-[10px] font-bold flex items-center gap-1 transition-colors">
                            <Plus class="w-3 h-3" /> Materiais ({{ details.materiaisUsados?.length || 0 }})
                        </button>
                    </div>

                    <!-- Exames Grid Area -->
                    <div class="flex-1 overflow-y-auto p-2 mx-2 border-x border-b border-slate-300 bg-[#f8f8f8] mb-2 shadow-inner">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            
                            <!-- Exame Cards -->
                            <div v-for="(exam, idx) in parsedExames" :key="exam.Id" class="bg-[#f0f0f0] border border-slate-300 p-2 shadow-sm flex flex-col">
                                
                                <!-- Card Header -->
                                <div class="flex justify-between items-center border-b border-slate-300 pb-1 mb-2">
                                    <h3 class="font-bold text-slate-800 text-sm">{{ idx + 1 }}) {{ exam.Descricao }}</h3>
                                    <span :class="exam.Estado === 'N_PAGO' ? 'bg-red-600' : 'bg-green-600'" class="text-white text-[10px] font-bold px-1.5 py-0.5 uppercase">
                                        {{ exam.Estado || 'PAGO' }}
                                    </span>
                                </div>
                                
                                <!-- Fill Options -->
                                <div class="flex items-center gap-4 mb-2 text-slate-700">
                                    <label class="flex items-center gap-1 cursor-pointer">
                                        <input type="radio" value="manual" v-model="exam.fillMode" class="accent-blue-600" />
                                        Preencher Manualmente
                                    </label>
                                    <label class="flex items-center gap-1 cursor-pointer">
                                        <input type="radio" value="anexo" disabled class="accent-blue-600" />
                                        Anexar (PDF, Imagem)
                                    </label>
                                </div>

                                <!-- Rows Table -->
                                <div v-if="exam.fillMode === 'manual'" class="flex-1">
                                    <div v-if="!exam.isSingle" class="border border-slate-300 bg-white">
                                        <table class="w-full text-left">
                                            <thead class="border-b border-slate-300 bg-slate-100">
                                                <tr>
                                                    <th class="p-1 border-r border-slate-300 font-bold text-slate-700 w-2/5">Dado</th>
                                                    <th class="p-1 border-r border-slate-300 font-bold text-slate-700">Resultado</th>
                                                    <th class="p-1 font-bold text-slate-700">Unidade</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(row, rIdx) in exam.rows" :key="rIdx" class="border-b border-slate-200 last:border-0 hover:bg-slate-50">
                                                    <td class="p-1 border-r border-slate-300 text-slate-800 truncate">{{ row.dado }}</td>
                                                    <td class="p-1 border-r border-slate-300">
                                                        <input type="text" v-model="row.resultado" class="w-full border border-slate-300 px-1 py-0.5 text-xs focus:border-blue-500 focus:outline-none" />
                                                    </td>
                                                    <td class="p-1">
                                                        <select v-model="row.unidade" class="w-full border border-slate-300 px-1 py-0.5 text-xs focus:border-blue-500 focus:outline-none bg-white">
                                                            <option value=""></option>
                                                            <option v-for="u in unidadesLaboratorio" :key="u" :value="u">{{ u }}</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-else class="space-y-2 bg-white p-2 border border-slate-300">
                                        <div>
                                            <label class="font-bold text-slate-700">Resultado</label>
                                            <input type="text" v-model="exam.singleResultado" class="w-full border border-slate-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none" />
                                        </div>
                                        <div>
                                            <label class="font-bold text-slate-700">Observação</label>
                                            <textarea v-model="exam.singleObs" rows="2" class="w-full border border-slate-300 px-2 py-1 text-xs focus:border-blue-500 focus:outline-none resize-none"></textarea>
                                        </div>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="flex gap-2 mt-2">
                                        <button @click="gravarResultadoExame(exam)" class="flex-1 bg-white border border-slate-300 hover:bg-slate-100 py-1.5 text-slate-800 font-bold shadow-sm transition-colors">
                                            Gravar Resultado
                                        </button>
                                        <button @click="gravarResultadoExame(exam, true)" class="flex-1 bg-white border border-slate-300 hover:bg-slate-100 py-1.5 text-slate-800 font-bold shadow-sm transition-colors">
                                            Gravar Sugestões
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div class="p-4 flex flex-wrap justify-center gap-2 lg:gap-4 bg-[#f8f8f8] border-t border-slate-300 shadow-[0_-4px_6px_-6px_rgba(0,0,0,0.1)]">
                        <button @click="gravarTodosDados" class="flex-1 lg:flex-none bg-[#2196F3] hover:bg-[#1976d2] text-white px-4 lg:px-6 py-2 lg:py-2.5 font-bold shadow-md flex items-center justify-center gap-2 transition-colors">
                            <Save class="w-4 h-4" /> GRAVAR DADOS
                        </button>
                        <button @click="imprimirResultados" class="flex-1 lg:flex-none bg-[#FF9800] hover:bg-[#F57C00] text-white px-4 lg:px-6 py-2 lg:py-2.5 font-bold shadow-md flex items-center justify-center gap-2 transition-colors">
                            <Printer class="w-4 h-4" /> IMPRIMIR
                        </button>
                        <button @click="finalizarLaboratorio" class="w-full lg:w-auto bg-[#4CAF50] hover:bg-[#388E3C] text-white px-8 py-2 lg:py-2.5 font-bold shadow-md flex items-center justify-center gap-2 transition-colors">
                            <CheckCircle class="w-4 h-4" /> FINALIZAR
                        </button>
                    </div>
                </div>

                <!-- Empty State Full Right -->
                <div v-else class="flex-1 flex flex-col items-center justify-center bg-[#f8f8f8]">
                </div>

            </div>
        </div>

        <!-- Material Selection Modal -->
        <div v-if="showMaterialModal" class="fixed inset-0 z-[100] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeMaterialModal"></div>
            <div class="relative bg-white shadow-2xl w-full max-w-2xl border border-slate-400">
                <div class="bg-[#000080] p-3 text-white flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-widest">Registro de Materiais Usados</h3>
                    <button @click="closeMaterialModal" class="text-white hover:text-slate-200 transition-colors">
                        <Plus class="w-5 h-5 rotate-45" />
                    </button>
                </div>
                
                <div class="p-4 space-y-4">
                    <!-- Armazém Selector -->
                    <div class="bg-blue-50 border border-blue-200 p-3 flex items-center gap-4">
                        <label class="font-bold text-blue-900 text-xs uppercase tracking-widest shrink-0">Armazém (Stock):</label>
                        <select v-model="selectedDeposito" class="w-full bg-white border border-blue-300 focus:border-blue-500 py-1.5 px-2 text-xs font-bold text-slate-700">
                            <option v-for="dep in props.depositos" :key="dep.Codigo" :value="dep.Codigo">{{ dep.Descricao }}</option>
                        </select>
                    </div>

                    <div class="bg-slate-50 border border-slate-300 p-4 space-y-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Produto Consumido</label>
                            <select v-model="materialForm.produto" @change="selectMaterial" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:outline-none py-1.5 px-2">
                                <option value="">-- Selecionar Material --</option>
                                <option v-for="mat in props.materiais" :key="mat.CODIGO" :value="mat.CODIGO">{{ mat.DESCRICAO }} ({{ formatCurrency(mat.PRECO_VENDA) }})</option>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Quantidade Usada</label>
                                <input type="number" min="1" v-model="materialForm.quantidade" class="w-full bg-white border border-slate-300 focus:border-blue-500 focus:outline-none py-1.5 px-2" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Custo Unitário</label>
                                <input type="text" :value="formatCurrency(materialForm.preco)" disabled class="w-full bg-slate-100 border border-slate-300 py-1.5 px-2 text-slate-500" />
                            </div>
                        </div>
                        
                        <div class="flex justify-end pt-2">
                            <button @click="salvarMaterial" :disabled="!materialForm.produto" class="bg-[#2196F3] text-white px-4 py-1.5 font-bold hover:bg-[#1976d2] transition-colors disabled:opacity-50">
                                Adicionar
                            </button>
                        </div>
                    </div>

                    <!-- List of added materials -->
                    <div v-if="details.materiaisUsados?.length > 0" class="border border-slate-300">
                        <table class="w-full text-left">
                            <thead class="bg-slate-100 border-b border-slate-300">
                                <tr>
                                    <th class="p-2 font-bold text-slate-700 border-r border-slate-300">Material</th>
                                    <th class="p-2 font-bold text-slate-700 border-r border-slate-300 text-center">Qtd</th>
                                    <th class="p-2 font-bold text-slate-700 border-r border-slate-300 text-right">Total</th>
                                    <th class="p-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="mat in details.materiaisUsados" :key="mat.Id" class="border-b border-slate-200 last:border-0 hover:bg-slate-50">
                                    <td class="p-2 border-r border-slate-300 text-slate-800">{{ mat.Descricao }}</td>
                                    <td class="p-2 border-r border-slate-300 text-center">{{ mat.Quantidade }}</td>
                                    <td class="p-2 border-r border-slate-300 text-right">{{ formatCurrency(mat.Total) }}</td>
                                    <td class="p-2 text-center">
                                        <button @click="removerMaterial(mat.Id)" class="text-red-600 hover:text-red-800 transition-colors">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Elegant Confirm Modal -->
        <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[150] flex items-center justify-center">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeConfirm"></div>
            <div class="relative bg-white shadow-2xl w-full max-w-sm rounded-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="p-5">
                    <div class="flex items-center gap-3 mb-3 text-[#000080]">
                        <AlertCircle class="w-6 h-6" />
                        <h3 class="font-bold text-lg">{{ confirmModal.title }}</h3>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">{{ confirmModal.message }}</p>
                    <div class="flex justify-end gap-3">
                        <button @click="closeConfirm" class="px-4 py-2 border border-slate-300 text-slate-700 font-bold rounded hover:bg-slate-50 transition-colors text-sm">
                            Cancelar
                        </button>
                        <button @click="confirmModal.onConfirm" class="px-5 py-2 bg-[#000080] hover:bg-blue-900 text-white font-bold rounded shadow transition-colors text-sm">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>

<style scoped>
/* Scoped styles for scrollbar */
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
</style>
