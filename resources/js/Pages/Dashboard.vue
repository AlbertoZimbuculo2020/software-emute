<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Pie, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  CategoryScale,
  LinearScale,
  BarElement
} from 'chart.js';
import { 
    Users, 
    CalendarCheck, 
    Microscope, 
    Filter, 
    ActivitySquare, 
    UserCheck, 
    Clock, 
    CheckCircle2,
    Stethoscope,
    FileText,
    TrendingUp
} from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement);

const page = usePage();
const props = defineProps({
    topConsultas: { type: Array, default: () => [] },
    topExames: { type: Array, default: () => [] },
    statusStats: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ totalConsultas: 0, totalExames: 0, totalPacientes: 0 }) },
    activityLabels: { type: Array, default: () => [] },
    activityData: { type: Array, default: () => [] },
    emAndamentoLista: { type: Array, default: () => [] },
    emAndamentoCount: { type: Number, default: 0 },
    realizadasPorMedico: { type: Array, default: () => [] },
    realizadasLista: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({ start_date: '', end_date: '' }) }
});

const can = (permission) => {
    const permissions = page.props.auth.permissions;
    return permissions.includes('*') || permissions.includes(permission);
};

const startDate = ref(props.filtros.start_date);
const endDate = ref(props.filtros.end_date);
const activeTab = ref(can('dashConsultasAndamento') ? 'em_andamento' : 'realizadas'); // Tabs: 'em_andamento', 'realizadas'

const applyFilters = () => {
    router.get(route('dashboard'), {
        start_date: startDate.value,
        end_date: endDate.value
    }, { preserveState: true, preserveScroll: true });
};

// Formatting helpers
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-PT');
};

const formatTime = (dateTimeString) => {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    return date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
};

// Calculate Max Count for Progress Bar in Consultas
const maxConsultaCount = computed(() => {
    return props.topConsultas.length > 0 ? Math.max(...props.topConsultas.map(c => c.count)) : 100;
});

// Chart Data: Status Pie Chart
const pieChartData = computed(() => {
    if (props.statusStats.length === 0) {
        return {
            labels: ['Sem Dados'],
            datasets: [{ data: [1], backgroundColor: ['#E5E7EB'], borderWidth: 0 }]
        };
    }
    return {
        labels: props.statusStats.map(s => s.label),
        datasets: [{
            data: props.statusStats.map(s => s.count),
            backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#14B8A6', '#F43F5E'],
            hoverOffset: 4
        }]
    };
});

const pieChartOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
};

// Bar Chart (Atividade da Semana)
const barChartData = computed(() => {
    return {
        labels: props.activityLabels.length > 0 ? props.activityLabels : ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        datasets: [{
            label: 'Acessos/Atividade',
            data: props.activityData.length > 0 ? props.activityData : [0, 0, 0, 0, 0, 0, 0],
            backgroundColor: '#60A5FA',
            borderRadius: 4
        }]
    };
});

const barChartOptions = {
    responsive: true, maintainAspectRatio: false,
    scales: { y: { display: false }, x: { grid: { display: false } } },
    plugins: { legend: { display: false } }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Agendada': return 'bg-blue-100 text-blue-700';
        case 'Triagem': return 'bg-purple-100 text-purple-700';
        case 'Consultorio': return 'bg-orange-100 text-orange-700';
        case 'Laboratorio': return 'bg-indigo-100 text-indigo-700';
        case 'Enfermaria': return 'bg-emerald-100 text-emerald-700';
        default: return 'bg-gray-100 text-gray-700';
    }
};
</script>

<template>
    <Head title="Controle Operacional" />

    <DashboardLayout>
        
        <!-- Filtros de Data -->
        <div v-if="can('dashConsultasAndamento') || can('dashProdutividadeMedica') || can('dashVerResumo') || can('dashVerGraficos') || can('dashVerTopListas')" class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex items-center space-x-2 text-gray-500">
                <Filter class="w-5 h-5 text-blue-500" />
                <span class="text-xs font-black uppercase tracking-widest">Filtros Dinâmicos</span>
            </div>
            <div class="flex items-center space-x-3">
                <input type="date" v-model="startDate" class="px-4 py-2 border border-gray-200 rounded-xl text-xs font-bold text-gray-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" />
                <span class="text-gray-300 font-black">-</span>
                <input type="date" v-model="endDate" class="px-4 py-2 border border-gray-200 rounded-xl text-xs font-bold text-gray-600 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none" />
                <button @click="applyFilters" class="px-6 py-2 bg-blue-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-all hover:scale-105 active:scale-95">Filtrar</button>
            </div>
        </div>

        <!-- ROW 1: Summary Cards -->
        <div v-if="can('dashVerResumo') || can('dashConsultasAndamento')" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div v-if="can('dashVerResumo')" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow cursor-pointer group">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Consultas Período</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">{{ props.summary.totalConsultas }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                    <CalendarCheck class="w-6 h-6" />
                </div>
            </div>
            <div v-if="can('dashVerResumo')" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow cursor-pointer group">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Exames Período</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">{{ props.summary.totalExames }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                    <Microscope class="w-6 h-6" />
                </div>
            </div>
            <div v-if="can('dashVerResumo')" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow cursor-pointer group">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pacientes Registados</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">{{ props.summary.totalPacientes }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                    <Users class="w-6 h-6" />
                </div>
            </div>
            <div v-if="can('dashConsultasAndamento')" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow cursor-pointer group">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Consultas Em Andamento</p>
                    <p class="text-2xl font-black text-orange-500 mt-1">{{ props.emAndamentoCount }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                    <ActivitySquare class="w-6 h-6" />
                </div>
            </div>
        </div>

        <!-- ROW 2: Charts -->
        <div v-if="can('dashVerGraficos')" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Pie Chart -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 lg:col-span-1 h-[320px] flex flex-col">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800 mb-4">Situação das Consultas</h3>
                <div class="flex-grow relative">
                    <Pie :data="pieChartData" :options="pieChartOptions" />
                    <div v-if="props.statusStats.length === 0" class="absolute inset-0 flex items-center justify-center backdrop-blur-[2px]">
                        <span class="text-xs font-bold text-gray-500 bg-white/80 px-3 py-1 rounded-full shadow-sm">Sem Dados</span>
                    </div>
                </div>
            </div>

            <!-- Bar Chart -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 lg:col-span-2 h-[320px] flex flex-col">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800 mb-4">Atividade da Semana</h3>
                <div class="flex-grow">
                    <Bar :data="barChartData" :options="barChartOptions" />
                </div>
            </div>
        </div>

        <!-- ROW 3: Top 10 Lists & Combined Dynamic Card -->
        <div v-if="can('dashVerTopListas') || can('dashConsultasAndamento') || can('dashProdutividadeMedica')" class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-6">
            
            <!-- TOP 10 Consultas -->
            <div v-if="can('dashVerTopListas')" class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px]">
                <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800">TOP 10 Consultas Mais Marcadas</h3>
                </div>
                <div class="flex-grow p-6 overflow-y-auto custom-scrollbar">
                    <div v-if="props.topConsultas.length > 0" class="w-full space-y-5">
                        <div v-for="(consulta, index) in props.topConsultas" :key="index" class="space-y-1.5">
                            <div class="flex justify-between items-end">
                                <span class="text-[11px] font-bold text-gray-700">{{ consulta.label }}</span>
                                <span class="text-[10px] font-black text-gray-500">{{ Math.round((consulta.count / maxConsultaCount) * 100) }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                                <div :style="{width: ((consulta.count / maxConsultaCount) * 100) + '%'}" class="bg-blue-500 h-full rounded-full transition-all duration-1000"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="w-full h-full flex flex-col items-center justify-center">
                        <p class="text-xs text-gray-400 italic font-medium">Nenhuma consulta registada no período.</p>
                    </div>
                </div>
            </div>

            <!-- TOP 10 Exames -->
            <div v-if="can('dashVerTopListas')" class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px]">
                <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800">TOP 10 Exames Mais Solicitados</h3>
                </div>
                <div class="flex-grow p-6 overflow-y-auto custom-scrollbar">
                     <div v-if="props.topExames.length > 0" class="space-y-3">
                         <div v-for="(exame, index) in props.topExames" :key="index" class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center space-x-3 transition-all hover:border-blue-200">
                              <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-600 font-black text-xs">#{{ index + 1 }}</div>
                              <div class="flex-grow overflow-hidden">
                                  <p class="text-[10px] font-black text-gray-600 uppercase tracking-tighter truncate" :title="exame.label">{{ exame.label }}</p>
                                  <p class="text-[9px] text-gray-400 font-medium">{{ exame.count }} Solicitações</p>
                              </div>
                         </div>
                     </div>
                     <div v-else class="w-full h-full flex flex-col items-center justify-center">
                        <p class="text-xs text-gray-400 italic font-medium">Nenhum exame registado no período.</p>
                     </div>
                </div>
            </div>

            <!-- NEW COMBINED DYNAMIC CARD: Em Andamento & Realizadas -->
            <div v-if="can('dashConsultasAndamento') || can('dashProdutividadeMedica')" class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px] overflow-hidden relative">
                
                <!-- TABS HEADER -->
                <div class="flex border-b border-gray-100 relative z-10 bg-gray-50/50">
                    <button 
                        v-if="can('dashConsultasAndamento')"
                        @click="activeTab = 'em_andamento'"
                        :class="['flex-1 py-4 text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all', activeTab === 'em_andamento' ? 'bg-white text-orange-600 border-b-2 border-orange-500 shadow-[0_4px_10px_-4px_rgba(0,0,0,0.1)]' : 'text-gray-400 hover:bg-white hover:text-orange-500']">
                        <Clock class="w-4 h-4" />
                        Em Andamento
                        <span class="bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full text-[9px]">{{ props.emAndamentoCount }}</span>
                    </button>
                    <button 
                        v-if="can('dashProdutividadeMedica')"
                        @click="activeTab = 'realizadas'"
                        :class="['flex-1 py-4 text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all', activeTab === 'realizadas' ? 'bg-white text-emerald-600 border-b-2 border-emerald-500 shadow-[0_4px_10px_-4px_rgba(0,0,0,0.1)]' : 'text-gray-400 hover:bg-white hover:text-emerald-500']">
                        <CheckCircle2 class="w-4 h-4" />
                        Realizadas
                        <span class="bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full text-[9px]">{{ props.realizadasLista.length }}</span>
                    </button>
                </div>

                <!-- TABS CONTENT -->
                <div class="flex-grow p-5 overflow-y-auto custom-scrollbar relative z-10 bg-white">
                     
                     <!-- TAB: EM ANDAMENTO -->
                     <div v-if="can('dashConsultasAndamento')" v-show="activeTab === 'em_andamento'">
                         <div v-if="props.emAndamentoLista.length > 0" class="space-y-3">
                             <div v-for="(consulta, index) in props.emAndamentoLista" :key="index" class="p-3 border border-orange-100/60 rounded-xl hover:shadow-md hover:border-orange-200 transition-all bg-orange-50/20">
                                  <div class="flex justify-between items-start mb-2">
                                      <p class="text-[11px] font-black text-slate-700 uppercase tracking-tight">{{ consulta.Paciente }}</p>
                                      <span :class="['text-[9px] font-bold px-2 py-0.5 rounded uppercase tracking-wider', getStatusColor(consulta.Situacao)]">{{ consulta.Situacao }}</span>
                                  </div>
                                  <p class="text-[10px] text-slate-500 font-medium flex items-center gap-1 mt-2">
                                      <UserCheck class="w-3 h-3 text-slate-400" />
                                      {{ consulta.Medico || 'Sem médico atribuído' }}
                                  </p>
                             </div>
                         </div>
                         <div v-else class="w-full py-16 flex flex-col items-center justify-center text-orange-700/40">
                            <Clock class="w-12 h-12 mb-3 opacity-50" />
                            <p class="text-xs font-black uppercase tracking-widest">Tudo Tranquilo</p>
                            <p class="text-[10px] mt-1 font-medium">Não há consultas em andamento.</p>
                         </div>
                     </div>

                     <!-- TAB: REALIZADAS (Lista Detalhada) -->
                     <div v-if="can('dashProdutividadeMedica')" v-show="activeTab === 'realizadas'">
                         <div v-if="props.realizadasLista.length > 0" class="space-y-4">
                             <div v-for="(consulta, index) in props.realizadasLista" :key="index" class="p-4 border border-emerald-100 shadow-sm rounded-2xl hover:shadow-md hover:border-emerald-300 transition-all bg-emerald-50/10">
                                  <div class="flex justify-between items-start mb-3">
                                      <div>
                                          <p class="text-[11px] font-black text-slate-800 uppercase tracking-tight">{{ consulta.Paciente }}</p>
                                          <p class="text-[9px] text-slate-400 font-bold uppercase flex items-center gap-1">
                                              <CalendarCheck class="w-3 h-3" /> {{ formatDate(consulta.DataAgendamento) }} 
                                              <Clock class="w-3 h-3 ml-1" /> {{ formatTime(consulta.Hora) }}
                                          </p>
                                      </div>
                                      <div class="text-right">
                                          <span class="text-[9px] font-black bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded uppercase tracking-widest">Finalizado</span>
                                      </div>
                                  </div>

                                  <div class="grid grid-cols-2 gap-4 mt-3 pt-3 border-t border-emerald-100/50">
                                      <div>
                                          <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-1">Médico Assistente</p>
                                          <p class="text-[10px] font-black text-slate-700 uppercase flex items-center gap-1">
                                              <UserCheck class="w-3 h-3 text-emerald-500" /> {{ consulta.Medico || 'N/A' }}
                                          </p>
                                      </div>
                                      <div>
                                          <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-1">Resultado / Diagnóstico</p>
                                          <p class="text-[10px] font-medium text-slate-600 line-clamp-2 italic" :title="consulta.Resultado">
                                              {{ consulta.Resultado || 'Sem observações' }}
                                          </p>
                                      </div>
                                  </div>
                             </div>
                         </div>
                         <div v-else class="w-full py-16 flex flex-col items-center justify-center text-emerald-700/40">
                            <CheckCircle2 class="w-12 h-12 mb-3 opacity-50" />
                            <p class="text-xs font-black uppercase tracking-widest">Sem Atendimentos</p>
                            <p class="text-[10px] mt-1 font-medium">Nenhum médico finalizou consultas.</p>
                         </div>
                     </div>

                </div>
            </div>

        </div>
    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>
