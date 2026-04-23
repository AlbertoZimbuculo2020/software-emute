<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const activeFilter = ref('finalizadas');
</script>

<template>
    <Head title="Controle Operacional" />

    <DashboardLayout>
        <!-- Layout em Quadrantes -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 pb-6">
            
            <!-- Quadrante 1: TOP 10 Consultas -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px]">
                <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800">TOP 10 Consultas Mais Marcadas</h3>
                    <span class="text-[10px] bg-blue-50 text-blue-600 px-2 py-1 rounded font-bold">MÊS ATUAL</span>
                </div>
                <div class="flex-grow flex items-center justify-center p-8">
                    <!-- Placeholder de Gráfico/Lista -->
                    <div class="w-full space-y-4">
                        <div v-for="i in 5" :key="i" class="space-y-1">
                            <div class="flex justify-between text-[10px] font-bold text-gray-600">
                                <span>Consulta Geral #{{ i }}</span>
                                <span>{{ 100 - (i * 10) }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                <div :style="{width: (100 - (i * 10)) + '%'}" class="bg-blue-500 h-full rounded-full"></div>
                            </div>
                        </div>
                        <div class="pt-4 text-center">
                            <p class="text-xs text-gray-400 italic">Visualização otimizada disponível no relatório completo</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quadrante 2: CONSULTAS POR MÉDICOS -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px]">
                <div class="p-5 border-b border-gray-50 bg-gray-50/50 rounded-t-2xl">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800">CONSULTAS POR MÉDICOS</h3>
                </div>
                <!-- Filtros Modernos -->
                <div class="p-4 bg-gray-50/30 border-b border-gray-100 grid grid-cols-2 gap-4">
                    <div class="flex flex-col space-y-1">
                        <label class="text-[9px] font-bold text-gray-400 uppercase">Período</label>
                        <div class="flex items-center space-x-2">
                             <input type="date" class="text-[10px] border-gray-200 rounded-lg p-1.5 focus:ring-blue-500 focus:border-blue-500" value="2026-04-23" />
                             <span class="text-gray-300">→</span>
                             <input type="date" class="text-[10px] border-gray-200 rounded-lg p-1.5 focus:ring-blue-500 focus:border-blue-500" value="2026-04-23" />
                        </div>
                    </div>
                    <div class="flex flex-col space-y-1">
                        <label class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Médico</label>
                        <select class="text-[10px] border-gray-200 rounded-lg p-1.5">
                            <option>EMUTE - Todos os Médicos</option>
                        </select>
                    </div>
                </div>
                
                <!-- Opções de Status -->
                <div class="px-5 py-3 flex items-center justify-between bg-white">
                    <div class="flex space-x-4">
                        <label class="flex items-center space-x-2 cursor-pointer group">
                            <input type="radio" v-model="activeFilter" value="finalizadas" class="text-blue-600 focus:ring-blue-500" />
                            <span class="text-[10px] font-bold text-gray-600 group-hover:text-blue-600">Finalizadas</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer group">
                            <input type="radio" v-model="activeFilter" value="andamento" class="text-blue-600 focus:ring-blue-500" />
                            <span class="text-[10px] font-bold text-gray-600 group-hover:text-blue-600">Em Andamento</span>
                        </label>
                    </div>
                    <div class="flex space-x-2">
                        <button class="bg-blue-600 text-white text-[9px] px-4 py-1.5 rounded-lg font-black uppercase tracking-widest shadow-md shadow-blue-100 hover:scale-105 transition-transform">BUSCAR</button>
                        <button class="border border-blue-600 text-blue-600 text-[9px] px-4 py-1.5 rounded-lg font-black uppercase tracking-widest hover:bg-blue-50 transition-colors">IMPRIMIR</button>
                    </div>
                </div>

                <!-- Tabela Placeholder -->
                <div class="flex-grow overflow-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr class="text-[9px] font-black text-gray-400 uppercase border-b border-gray-100">
                                <th class="px-4 py-2">Código</th>
                                <th class="px-4 py-2">Dados</th>
                                <th class="px-4 py-2">Médico</th>
                                <th class="px-4 py-2">Paciente</th>
                                <th class="px-4 py-2">Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-50 hover:bg-blue-50/30 transition-colors">
                                <td colspan="5" class="py-12 text-center">
                                     <span class="text-xs text-gray-300 italic font-medium">Nenhum registro encontrado para o período</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quadrante 3: Empty State / Infográficos -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px] group overflow-hidden">
                <div class="p-5 border-b border-gray-50">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800">DISPONIBILIDADE HOSPITALAR</h3>
                </div>
                <div class="flex-grow flex flex-col items-center justify-center p-8 bg-gradient-to-br from-white to-gray-50">
                    <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mb-4 transition-transform group-hover:scale-110 duration-500">
                        <span class="text-3xl">💹</span>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-blue-400">Ambiente Monitorado</p>
                    <p class="text-xs text-gray-400 mt-2 text-center max-w-[200px]">As estatísticas de ocupação e fluxo serão carregadas automaticamente.</p>
                </div>
            </div>

            <!-- Quadrante 4: TOP 10 Exames -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-[400px]">
                <div class="p-5 border-b border-gray-50 flex items-center justify-between">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-800">TOP 10 Exames Mais Solicitados</h3>
                    <div class="flex space-x-1">
                        <div v-for="d in 3" :key="d" class="w-1 h-1 rounded-full bg-blue-300"></div>
                    </div>
                </div>
                <div class="flex-grow flex items-center justify-center p-8">
                     <div class="grid grid-cols-2 gap-4 w-full">
                         <div v-for="i in 6" :key="i" class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center space-x-3 transition-all hover:shadow-md hover:border-blue-200 cursor-pointer">
                              <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-600 font-black text-xs">#{{ i }}</div>
                              <div>
                                  <p class="text-[10px] font-black text-gray-600 uppercase tracking-tighter">EXAME {{ i }}</p>
                                  <p class="text-[9px] text-gray-400">{{ 50 - i }} Solicitações</p>
                              </div>
                         </div>
                     </div>
                </div>
            </div>

        </div>
    </DashboardLayout>
</template>
