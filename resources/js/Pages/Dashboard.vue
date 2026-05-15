<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Pie, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  CategoryScale,
  LinearScale,
  BarElement,
  PointElement,
  LineElement,
  Filler
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
    TrendingUp,
    ChevronRight,
    Search,
    AlertCircle,
    ArrowUpRight
} from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement, PointElement, LineElement, Filler);

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

const clinicData = computed(() => page.props.clinicData);
const user = computed(() => page.props.auth.user);

const can = (permission) => {
    const permissions = page.props.auth.permissions;
    return permissions.includes('*') || permissions.includes(permission);
};

const startDate = ref(props.filtros.start_date);
const endDate = ref(props.filtros.end_date);
const activeTab = ref(can('dashConsultasAndamento') ? 'em_andamento' : 'realizadas');

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
            backgroundColor: [
                '#3B82F6', // Blue
                '#10B981', // Green
                '#F59E0B', // Orange
                '#6366F1', // Indigo
                '#EC4899', // Pink
                '#8B5CF6', // Violet
                '#F43F5E'  // Rose
            ],
            hoverOffset: 15,
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    };
});

const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: { 
        legend: { 
            position: 'bottom', 
            labels: { 
                boxWidth: 8, 
                usePointStyle: true,
                padding: 20,
                font: { size: 10, weight: 'bold' },
                color: '#64748b'
            } 
        },
        tooltip: {
            backgroundColor: '#1e293b',
            titleFont: { size: 12, weight: 'bold' },
            bodyFont: { size: 12 },
            padding: 12,
            cornerRadius: 10,
            displayColors: true
        }
    }
};

// Bar Chart (Atividade da Semana)
const barChartData = computed(() => {
    return {
        labels: props.activityLabels.length > 0 ? props.activityLabels : ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        datasets: [{
            label: 'Atividade',
            data: props.activityData.length > 0 ? props.activityData : [0, 0, 0, 0, 0, 0, 0],
            backgroundColor: (context) => {
                const chart = context.chart;
                const {ctx, chartArea} = chart;
                if (!chartArea) return null;
                const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                gradient.addColorStop(0, '#3b82f620');
                gradient.addColorStop(1, '#3b82f6');
                return gradient;
            },
            borderRadius: 8,
            hoverBackgroundColor: '#2563eb'
        }]
    };
});

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: { 
        y: { 
            display: true,
            grid: { display: false },
            ticks: { color: '#94a3b8', font: { size: 10 } }
        }, 
        x: { 
            grid: { display: false },
            ticks: { color: '#94a3b8', font: { size: 10, weight: 'bold' } }
        } 
    },
    plugins: { 
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1e293b',
            padding: 12,
            cornerRadius: 10
        }
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'Agendada': return 'bg-blue-50 text-blue-600 border-blue-100';
        case 'Triagem': return 'bg-purple-50 text-purple-600 border-purple-100';
        case 'Consultorio': return 'bg-orange-50 text-orange-600 border-orange-100';
        case 'Laboratorio': return 'bg-indigo-50 text-indigo-600 border-indigo-100';
        case 'Enfermaria': return 'bg-emerald-50 text-emerald-600 border-emerald-100';
        default: return 'bg-gray-50 text-gray-600 border-gray-100';
    }
};
</script>

<template>
    <Head title="Dashboard Executivo" />

    <DashboardLayout>
        <div class="space-y-6 pb-10">
            
            <!-- HEADER / WELCOME BANNER -->
            <div class="relative overflow-hidden bg-white rounded-[2.5rem] border border-slate-200 p-8 shadow-sm">
                <!-- Decorative elements -->
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500/5 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-indigo-500/5 rounded-full blur-3xl"></div>

                <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-blue-600 rounded-3xl flex items-center justify-center text-white shadow-xl shadow-blue-200 shrink-0">
                            <TrendingUp class="w-8 h-8" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Olá, {{ user.NOME_UTILIZADOR || user.name }}</h2>
                            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mt-2">
                                Bem-vindo ao cockpit operacional da <span class="text-blue-600">{{ clinicData?.nome || 'EMUTE' }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="bg-slate-50 border border-slate-200 p-2 rounded-2xl flex items-center gap-4">
                            <div class="flex flex-col pl-3">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Período Selecionado</span>
                                <span class="text-xs font-black text-slate-700 mt-1">
                                    {{ formatDate(startDate) || 'Hoje' }} - {{ formatDate(endDate) || 'Hoje' }}
                                </span>
                            </div>
                            <div class="h-8 w-px bg-slate-200"></div>
                            <button @click="router.get(route('dashboard'))" class="p-2 bg-white text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all shadow-sm border border-slate-100">
                                <Filter class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 1: Summary Cards -->
            <div v-if="can('dashVerResumo') || can('dashConsultasAndamento')" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Consultas -->
                <div v-if="can('dashVerResumo')" class="group relative bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-xl hover:shadow-blue-500/10 transition-all cursor-pointer overflow-hidden">
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <CalendarCheck class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-full uppercase tracking-widest">Consultas</span>
                        </div>
                        <p class="text-3xl font-black text-slate-900 leading-none">{{ props.summary.totalConsultas }}</p>
                        <p class="text-xs font-bold text-slate-400 mt-2">Volume total no período</p>
                        <div class="mt-4 flex items-center text-[10px] font-black text-emerald-500 uppercase tracking-tighter">
                            <ArrowUpRight class="w-3 h-3 mr-1" /> Crescimento Estável
                        </div>
                    </div>
                </div>

                <!-- Total Exames -->
                <div v-if="can('dashVerResumo')" class="group relative bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 transition-all cursor-pointer overflow-hidden">
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <Microscope class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full uppercase tracking-widest">Exames</span>
                        </div>
                        <p class="text-3xl font-black text-slate-900 leading-none">{{ props.summary.totalExames }}</p>
                        <p class="text-xs font-bold text-slate-400 mt-2">Análises processadas</p>
                        <div class="mt-4 flex items-center text-[10px] font-black text-blue-500 uppercase tracking-tighter">
                            <ActivitySquare class="w-3 h-3 mr-1" /> Alta Produtividade
                        </div>
                    </div>
                </div>

                <!-- Pacientes -->
                <div v-if="can('dashVerResumo')" class="group relative bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-xl hover:shadow-emerald-500/10 transition-all cursor-pointer overflow-hidden">
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <Users class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-black text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full uppercase tracking-widest">Pacientes</span>
                        </div>
                        <p class="text-3xl font-black text-slate-900 leading-none">{{ props.summary.totalPacientes }}</p>
                        <p class="text-xs font-bold text-slate-400 mt-2">Base de dados ativa</p>
                        <div class="mt-4 flex items-center text-[10px] font-black text-emerald-500 uppercase tracking-tighter">
                            <CheckCircle2 class="w-3 h-3 mr-1" /> Retenção Positiva
                        </div>
                    </div>
                </div>

                <!-- Em Andamento -->
                <div v-if="can('dashConsultasAndamento')" class="group relative bg-slate-900 p-6 rounded-[2rem] shadow-xl shadow-slate-900/20 transition-all cursor-pointer overflow-hidden">
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-orange-500/20 rounded-full blur-2xl group-hover:scale-125 transition-transform"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-orange-500/10 text-orange-500 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform border border-orange-500/20">
                                <ActivitySquare class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-black text-orange-400 bg-orange-500/10 px-2 py-1 rounded-full uppercase tracking-widest border border-orange-500/20">Em Tempo Real</span>
                        </div>
                        <p class="text-4xl font-black text-white leading-none">{{ props.emAndamentoCount }}</p>
                        <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-tighter">Atendimentos Agora</p>
                        <div class="mt-4 flex items-center gap-2">
                             <div class="flex -space-x-2">
                                 <div v-for="i in Math.min(3, props.emAndamentoCount)" :key="i" class="w-6 h-6 rounded-full border-2 border-slate-900 bg-orange-500 flex items-center justify-center text-[8px] font-black text-white">
                                     {{ i }}
                                 </div>
                             </div>
                             <span class="text-[9px] font-black text-orange-400 uppercase tracking-widest">Fila Ativa</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTERS DRAWER (Static for now, but styled) -->
            <div v-if="can('dashVerResumo')" class="bg-slate-50/50 p-2 rounded-3xl border border-slate-100 flex items-center gap-2 max-w-fit mx-auto">
                <input type="date" v-model="startDate" class="bg-white px-4 py-2 border-none rounded-2xl text-[10px] font-black text-slate-600 shadow-sm focus:ring-2 focus:ring-blue-500/20 outline-none" />
                <div class="w-4 h-px bg-slate-300"></div>
                <input type="date" v-model="endDate" class="bg-white px-4 py-2 border-none rounded-2xl text-[10px] font-black text-slate-600 shadow-sm focus:ring-2 focus:ring-blue-500/20 outline-none" />
                <button @click="applyFilters" class="ml-2 px-6 py-2 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/20">Atualizar</button>
            </div>

            <!-- ROW 2: Charts & Top Stats -->
            <div v-if="can('dashVerGraficos')" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Pie Chart Card -->
                <div class="lg:col-span-4 bg-white rounded-[2.5rem] p-8 border border-slate-200 shadow-sm flex flex-col h-[450px]">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-tight">Status Operacional</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Situação das Consultas</p>
                        </div>
                        <div class="p-2 bg-slate-50 rounded-xl">
                            <PieChart class="w-4 h-4 text-slate-400" />
                        </div>
                    </div>
                    <div class="flex-grow relative">
                        <Pie :data="pieChartData" :options="pieChartOptions" />
                        <!-- Donut Center text -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none pb-12">
                            <span class="text-3xl font-black text-slate-900">{{ props.summary.totalConsultas }}</span>
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total</span>
                        </div>
                    </div>
                </div>

                <!-- Bar Chart Card -->
                <div class="lg:col-span-8 bg-white rounded-[2.5rem] p-8 border border-slate-200 shadow-sm flex flex-col h-[450px]">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-tight">Atividade Semanal</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Fluxo de atendimentos por dia</p>
                        </div>
                        <div class="flex items-center gap-2">
                             <div class="flex items-center gap-2 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100">
                                 <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></div>
                                 <span class="text-[9px] font-black text-emerald-600 uppercase tracking-widest">Tendência de Alta</span>
                             </div>
                        </div>
                    </div>
                    <div class="flex-grow">
                        <Bar :data="barChartData" :options="barChartOptions" />
                    </div>
                </div>
            </div>

            <!-- ROW 3: Detailed Lists -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- TOP Consultas -->
                <div v-if="can('dashVerTopListas')" class="lg:col-span-4 bg-white rounded-[2.5rem] p-8 border border-slate-200 shadow-sm flex flex-col h-[480px]">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest">Top Especialidades</h3>
                        <span class="text-[9px] font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-lg">Ranking #10</span>
                    </div>
                    <div class="flex-grow overflow-y-auto custom-scrollbar pr-2 space-y-5">
                         <div v-for="(consulta, index) in props.topConsultas" :key="index" class="group">
                             <div class="flex justify-between items-center mb-1.5">
                                 <span class="text-[11px] font-black text-slate-700 uppercase tracking-tight truncate max-w-[180px]">{{ consulta.label }}</span>
                                 <span class="text-[10px] font-black text-slate-400 group-hover:text-blue-600 transition-colors">{{ consulta.count }}</span>
                             </div>
                             <div class="relative w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                 <div :style="{width: ((consulta.count / maxConsultaCount) * 100) + '%'}" class="absolute h-full bg-blue-600 rounded-full transition-all duration-1000 group-hover:shadow-[0_0_8px_rgba(37,99,235,0.4)]"></div>
                             </div>
                         </div>
                         <div v-if="!props.topConsultas.length" class="h-full flex flex-col items-center justify-center text-slate-300">
                             <AlertCircle class="w-10 h-10 mb-2 opacity-20" />
                             <p class="text-[10px] font-black uppercase tracking-widest">Sem Registos</p>
                         </div>
                    </div>
                </div>

                <!-- Combined Activity Tab -->
                <div v-if="can('dashConsultasAndamento') || can('dashProdutividadeMedica')" class="lg:col-span-8 bg-white rounded-[2.5rem] border border-slate-200 shadow-sm flex flex-col h-[480px] overflow-hidden">
                    <!-- Custom Tabs -->
                    <div class="flex p-2 bg-slate-50 border-b border-slate-100">
                        <button 
                            @click="activeTab = 'em_andamento'"
                            :class="activeTab === 'em_andamento' ? 'bg-white text-orange-600 shadow-md shadow-orange-100' : 'text-slate-400 hover:text-orange-500'"
                            class="flex-1 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-3 transition-all"
                        >
                            <Clock class="w-4 h-4" /> Em Andamento
                            <span class="bg-orange-100 text-orange-600 w-5 h-5 flex items-center justify-center rounded-lg text-[9px]">{{ props.emAndamentoCount }}</span>
                        </button>
                        <button 
                            @click="activeTab = 'realizadas'"
                            :class="activeTab === 'realizadas' ? 'bg-white text-emerald-600 shadow-md shadow-emerald-100' : 'text-slate-400 hover:text-emerald-500'"
                            class="flex-1 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-3 transition-all"
                        >
                            <CheckCircle2 class="w-4 h-4" /> Finalizadas
                            <span class="bg-emerald-100 text-emerald-600 w-5 h-5 flex items-center justify-center rounded-lg text-[9px]">{{ props.realizadasLista.length }}</span>
                        </button>
                    </div>

                    <div class="flex-grow overflow-y-auto p-8 custom-scrollbar bg-white">
                        <!-- Content: Em Andamento -->
                        <div v-show="activeTab === 'em_andamento'" class="space-y-4 animate-fadeIn">
                            <div v-for="(consulta, index) in props.emAndamentoLista" :key="index" class="p-4 bg-white rounded-3xl border border-slate-100 hover:border-orange-200 hover:shadow-lg hover:shadow-orange-100/50 transition-all group flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center shadow-inner">
                                        <UserCheck class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-800 uppercase tracking-tight">{{ consulta.Paciente }}</p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5 mt-1">
                                            <Stethoscope class="w-3 h-3" /> Dr. {{ consulta.Medico || 'Plantão' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span :class="getStatusColor(consulta.Situacao)" class="px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest border">
                                        {{ consulta.Situacao }}
                                    </span>
                                    <ChevronRight class="w-4 h-4 text-slate-200 group-hover:text-orange-400 group-hover:translate-x-1 transition-all" />
                                </div>
                            </div>
                            <div v-if="!props.emAndamentoLista.length" class="h-64 flex flex-col items-center justify-center text-slate-300">
                                <ActivitySquare class="w-16 h-16 mb-4 opacity-10" />
                                <p class="text-[10px] font-black uppercase tracking-[0.3em]">Nenhum Atendimento Ativo</p>
                            </div>
                        </div>

                        <!-- Content: Realizadas -->
                        <div v-show="activeTab === 'realizadas'" class="space-y-4 animate-fadeIn">
                             <div v-for="(consulta, index) in props.realizadasLista" :key="index" class="p-5 bg-white rounded-3xl border border-slate-100 hover:border-emerald-200 hover:shadow-lg transition-all group">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-inner">
                                            <CheckCircle2 class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-black text-slate-800 uppercase tracking-tight">{{ consulta.Paciente }}</p>
                                            <p class="text-[9px] font-bold text-slate-400 uppercase flex items-center gap-2 mt-1">
                                                <CalendarCheck class="w-3 h-3" /> {{ formatDate(consulta.DataAgendamento) }} 
                                                <div class="w-1 h-1 bg-slate-300 rounded-full"></div>
                                                <Clock class="w-3 h-3" /> {{ formatTime(consulta.Hora) }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg uppercase border border-emerald-100">Finalizado</span>
                                </div>
                                <div class="p-3 bg-slate-50/50 rounded-2xl border border-slate-100/50 flex items-center gap-4">
                                    <div class="flex-grow">
                                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Diagnóstico / Resumo</p>
                                        <p class="text-[10px] text-slate-600 font-medium italic line-clamp-1">{{ consulta.Resultado || 'Atendimento concluído sem intercorrências' }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Médico</p>
                                        <p class="text-[10px] font-black text-slate-700 uppercase">Dr. {{ (consulta.Medico || 'N/A').split(' ')[0] }}</p>
                                    </div>
                                </div>
                             </div>
                             <div v-if="!props.realizadasLista.length" class="h-64 flex flex-col items-center justify-center text-slate-300">
                                <FileText class="w-16 h-16 mb-4 opacity-10" />
                                <p class="text-[10px] font-black uppercase tracking-[0.3em]">Sem Histórico Recente</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.4s ease-out forwards;
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

/* Donut Chart Animation scale */
canvas {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
canvas:hover {
    transform: scale(1.02);
}
</style>
