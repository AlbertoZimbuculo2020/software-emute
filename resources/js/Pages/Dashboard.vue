<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
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
import { Users, CalendarCheck, Microscope, Wallet } from 'lucide-vue-next';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, BarElement);

const props = defineProps({
    topConsultas: { type: Array, default: () => [] },
    topExames: { type: Array, default: () => [] },
    statusStats: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ totalConsultas: 0, totalExames: 0, totalPacientes: 0 }) }
});

// Calculate Max Count for Progress Bar in Consultas
const maxConsultaCount = computed(() => {
    return props.topConsultas.length > 0 ? Math.max(...props.topConsultas.map(c => c.count)) : 100;
});

// Chart Data: Status Pie Chart
const pieChartData = computed(() => {
    if (props.statusStats.length === 0) {
        return {
            labels: ['Agendada', 'Finalizado', 'Triagem'],
            datasets: [{
                data: [1, 1, 1], // Placeholder
                backgroundColor: ['#E5E7EB', '#F3F4F6', '#F9FAFB'],
                borderWidth: 0
            }]
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
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
    }
};

// Placeholder Bar Chart (e.g. Activity)
const barChartData = {
    labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
    datasets: [{
        label: 'Acessos/Atividade',
        data: [12, 19, 3, 5, 2, 3, 9], // Mock since no historical daily data is sent
        backgroundColor: '#60A5FA',
        borderRadius: 4
    }]
};

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: { display: false },
        x: { grid: { display: false } }
    },
    plugins: {
        legend: { display: false }
    }
};
</script>

<template>
    <Head title="Controle Operacional" />

    <DashboardLayout>
        
        <!-- ROW 1: Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow cursor-pointer">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Consultas Mês</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">{{ props.summary.totalConsultas }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center">
                    <CalendarCheck class="w-6 h-6" />
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow cursor-pointer">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Exames Mês</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">{{ props.summary.totalExames }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center">
                    <Microscope class="w-6 h-6" />
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow cursor-pointer">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pacientes Registados</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">{{ props.summary.totalPacientes }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center">
                    <Users class="w-6 h-6" />
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow cursor-pointer">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Média Diária (Sim.)</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">24</p>
                </div>
                <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center">
                    <Wallet class="w-6 h-6" />
                </div>
            </div>
        </div>

        <!-- ROW 2: Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Pie Chart -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 lg:col-span-1 h-[320px] flex flex-col">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800 mb-4">Situação das Consultas</h3>
                <div class="flex-grow relative">
                    <Pie :data="pieChartData" :options="pieChartOptions" />
                    <!-- Se não houver dados, mostrar overlay -->
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

        <!-- ROW 3: Top 10 Lists -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 pb-6">
            
            <!-- TOP 10 Consultas Mais Marcadas -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px]">
                <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800">TOP 10 Consultas Mais Marcadas</h3>
                    <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-1 rounded font-bold">MÊS ATUAL</span>
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
                        <div class="w-full space-y-5 opacity-40 grayscale">
                            <div v-for="i in 5" :key="i" class="space-y-1.5">
                                <div class="flex justify-between items-end">
                                    <span class="text-[11px] font-bold text-gray-700">Consulta Geral #{{ i }}</span>
                                    <span class="text-[10px] font-black text-gray-500">{{ 100 - (i * 10) }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                                    <div :style="{width: (100 - (i * 10)) + '%'}" class="bg-blue-500 h-full rounded-full"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 italic font-medium mt-6">Ainda não existem consultas registadas.</p>
                    </div>
                </div>
            </div>

            <!-- TOP 10 Exames Mais Solicitados -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px]">
                <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800">TOP 10 Exames Mais Solicitados</h3>
                    <div class="flex space-x-1">
                        <div v-for="d in 3" :key="d" class="w-1 h-1 rounded-full bg-blue-300"></div>
                    </div>
                </div>
                <div class="flex-grow p-6 overflow-y-auto custom-scrollbar">
                     <div v-if="props.topExames.length > 0" class="grid grid-cols-2 gap-4 w-full">
                         <div v-for="(exame, index) in props.topExames" :key="index" class="p-4 bg-gray-50 rounded-xl border border-gray-100 flex items-center space-x-3 transition-all hover:shadow-md hover:border-blue-200 cursor-pointer group">
                              <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-600 font-black text-sm group-hover:bg-blue-600 group-hover:text-white transition-colors">#{{ index + 1 }}</div>
                              <div class="flex-grow overflow-hidden">
                                  <p class="text-[10px] font-black text-gray-600 uppercase tracking-tighter truncate" :title="exame.label">{{ exame.label }}</p>
                                  <p class="text-[9px] text-gray-400 font-medium">{{ exame.count }} Solicitações</p>
                              </div>
                         </div>
                     </div>
                     <div v-else class="w-full h-full flex flex-col items-center justify-center">
                        <div class="grid grid-cols-2 gap-4 w-full opacity-40 grayscale">
                             <div v-for="i in 6" :key="i" class="p-4 bg-gray-50 rounded-xl border border-gray-100 flex items-center space-x-3">
                                  <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-600 font-black text-sm">#{{ i }}</div>
                                  <div class="flex-grow overflow-hidden">
                                      <p class="text-[10px] font-black text-gray-600 uppercase tracking-tighter truncate">EXAME {{ i }}</p>
                                      <p class="text-[9px] text-gray-400 font-medium">{{ 50 - i }} Solicitações</p>
                                  </div>
                             </div>
                         </div>
                        <p class="text-xs text-gray-400 italic font-medium mt-6">Ainda não existem exames registados.</p>
                     </div>
                </div>
            </div>

        </div>
    </DashboardLayout>
</template>
