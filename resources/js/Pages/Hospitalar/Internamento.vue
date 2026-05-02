<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import axios from 'axios';
import { Search } from 'lucide-vue-next';

const props = defineProps({
    internados: { type: Array, default: () => [] },
    historico: { type: Array, default: () => [] }
});

const searchTerm = ref('');
const histSearchTerm = ref('');
const prescricoesSearchTerm = ref('');
const selectedPaciente = ref(null);
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

const recarregar = () => {
    router.reload({ only: ['internados', 'historico'] });
};
</script>

<template>
    <Head title="Internamento" />

    <DashboardLayout>
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
                        <button class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px]">
                            Prescrições<br>Médicas
                        </button>
                        <button class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] leading-tight">
                            Registo de Actos Médicos e Solicitação de<br>Exames
                        </button>
                        <button class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px]">
                            Título de Alta
                        </button>
                    </div>
                </div>

                <div class="flex flex-col border border-[#2196F3]">
                    <div class="text-center font-bold text-[10px] uppercase text-slate-800 bg-white py-0.5">ÁREA DOS ENFERMEIROS</div>
                    <div class="flex gap-0.5 bg-white p-0.5">
                        <button class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] leading-tight">
                            Cumprimento<br>(Enfermagem)
                        </button>
                        <button class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] leading-tight">
                            Controlo de sinais<br>Vitais
                        </button>
                        <button class="bg-[#2196F3] text-white px-3 py-1 font-bold hover:bg-[#1976D2] transition-colors text-[10px] leading-tight">
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
                                        <th class="p-1 w-6 border-r border-slate-300 text-center"><input type="checkbox" /></th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Medico</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Descricao</th>
                                        <th class="p-1.5 border-r border-slate-300 font-normal text-slate-700">Enfermeiro</th>
                                        <th class="p-1.5 font-normal text-slate-700">Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in filteredPrescricoes" :key="p.Id" class="border-b border-slate-200 hover:bg-slate-50 text-[10px]">
                                        <td class="p-1 border-r border-slate-200/50 text-center"><input type="checkbox" /></td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ p.Medico || '---' }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50 max-w-[200px] truncate" :title="p.Descricao">{{ p.Descricao }}</td>
                                        <td class="p-1.5 border-r border-slate-200/50">{{ p.Enfermeiro || '---' }}</td>
                                        <td class="p-1.5">{{ p.CREATED_AT }}</td>
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
    </DashboardLayout>
</template>

<style scoped>
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
